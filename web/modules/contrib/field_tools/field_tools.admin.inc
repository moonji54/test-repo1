<?php
/**
 * @file
 * NOTICE: THIS FILE IS OBSOLETE. IT IS BEING KEPT UNTIL ALL FUNCTIONALITY IS
 * PORTED TO DRUPAL 8.
 */

/**
 * Page callback for the field overview list.
 */
function field_tools_field_overview() {
  $output = '';

  $entity_info = entity_get_info();
  $field_types = field_info_field_types();
  $fields = field_info_fields();

  // Sort the fields, defaulting to field name.
  $query = drupal_get_query_parameters();
  $sort = isset($query['sort']) ? check_plain($query['sort']) : 'field_name';
  uasort($fields, function($a, $b) use ($sort, $field_types) {
    switch ($sort) {
      case 'type':
        // Sort by the field type label.
        return strcmp($field_types[$a[$sort]]['label'], $field_types[$b[$sort]]['label']);
      default:
        return strcmp($a[$sort], $b[$sort]);
    }
  });

  $rows = array();
  foreach ($fields as $field_name => $field) {
    $row = array();
    // Column: field name.
    $row[] = l($field_name, 'admin/reports/fields/field/' . $field_name);
    // Column: field type.
    $row[] = $field_types[$field['type']]['label'];
    // Column: field instances.
    $row[] = field_tools_field_instances_list($field);

    // Column : operations.
    $items_ops = array();
    $items_ops[] = l('edit instances', "admin/reports/fields/field/$field_name/edit");
    $items_ops[] = l('delete instances', "admin/reports/fields/field/$field_name/delete");
    $row[] = theme('item_list', array(
      'items' => $items_ops,
    ));

    $rows[] = $row;
  }

  $header = array(
    l(t('Field name'), current_path(), array(
      'query' => array(
        'sort' => 'field_name',
      ),
    )),
    l(t('Type'), current_path(), array(
      'query' => array(
        'sort' => 'type',
      ),
    )),
    t('Instances'),
    t('Operations'),
  );

  $output .= theme('table', array(
    'rows' => $rows,
    'header' => $header,
  ));

  drupal_add_css(\Drupal::service('extension.list.module')->getPath('field_tools') . '/field_tools.css');

  return $output;
}

/**
 * Page callback for the field references overview list.
 *
 * @todo Make this all singing and dancing with some sort of visual
 * representation of the relationships.
 */
function field_tools_field_references_overview() {
  $output = '';

  $entity_info = entity_get_info();
  $field_types = field_info_field_types();
  $fields = field_info_fields();

  $rows = array();
  foreach ($fields as $field_name => $field) {
    // TODO: also deal with taxonomy term ref, product ref.
    if (!in_array($field['type'], array('entityreference', 'taxonomy_term_reference'))) {
      continue;
    }

    $row = array();
    // Column: field name.
    $row[] = l($field_name, 'admin/reports/fields/field/' . $field_name);
    // Column: field type.
    $row[] = $field_types[$field['type']]['label'];
    // Column: field instances.
    $row[] = field_tools_field_instances_list($field);
    // Column: target entity type.
    switch ($field['type']) {
      case 'taxonomy_term_reference':
        $target_type = 'taxonomy_term';
        break;
      case 'entityreference':
        $target_type = $field['settings']['target_type'];
    } // switch
    $row[] = $entity_info[$target_type]['label'];
    // Column: target bundles.
    switch ($field['type']) {
      case 'taxonomy_term_reference':
        $vocabulary = $field['settings']['allowed_values'][0]['vocabulary'];
        $row[] = l($entity_info['taxonomy_term']['bundles'][$vocabulary]['label'],
          _field_ui_bundle_admin_path('taxonomy_term', $vocabulary));
        break;
      case 'entityreference':
        switch ($field['settings']['handler']) {
          case 'base':
            // Base handler: select bundles.
            if (empty($field['settings']['handler_settings']['target_bundles'])) {
              // Nothing means all bundles.
              $row[] = t("All bundles");
            }
            else {
              $target_bundles = $field['settings']['handler_settings']['target_bundles'];
              $items = array();
              foreach ($target_bundles as $target_bundle) {
                $bundle_label = $entity_info[$target_type]['bundles'][$target_bundle]['label'];
                $items[$bundle_label] = l($bundle_label,
                  _field_ui_bundle_admin_path($target_type, $target_bundle));
              }
              ksort($items);

              $row[] = theme('item_list', array(
                'items' => $items,
              ));
            }
            break;
          default:
            $row[] = "**other handler**";
            // TODO: view selection

        } // switch entref handler
        break;
    } // switch field type

    $rows[] = $row;
  }

  $header = array(
    t('Field name'),
    t('Type'),
    t('Instances'),
    t('Target entity type'),
    t('Target bundles'),
  );

  $output .= theme('table', array(
    'rows' => $rows,
    'header' => $header,
  ));

  return $output;
}

/**
 * Output a graph of references between entity types.
 *
 * Requires GraphAPI module.
 *
 * @param $graph_engine
 *  (optional) The name of a GraphAPI engine to use. Defaults to the first
 *  engine returned by graphapi_get_engines().
 */
function field_tools_field_references_graph($graph_engine = NULL) {
  $graph = graphapi_new_graph();

  $entity_info = entity_get_info();
  $field_types = field_info_field_types();
  $fields = field_info_fields();
  $field_map = field_info_field_map();
  $instances = field_info_instances();

  // Step 1: Create graph nodes for reference fields.
  foreach ($fields as $field_name => $field) {
    $target_type = NULL;
    switch ($field['type']) {
      case 'entityreference':
        $target_type = $field['settings']['target_type'];
        $link_color = 'red';
        break;
      case 'taxonomy_term_reference':
        $target_type = 'taxonomy_term';
        $link_color = 'green';
        break;
      // TODO: deal with product reference fields.
    }

    // If no target type was set, then we don't know about this sort of field.
    if (empty($target_type)) {
      continue;
    }

    // First pass: only entity types in graph, no bundles.
    foreach ($field_map[$field_name]['bundles'] as $instance_entity_type => $instance_bundles) {
      //dsm("FROM $instance_entity_type TO $target_type");
      $link_data = array(
        'title' => $field_name,
        'type'  => 'field-' . $field['type'],
        'color' => $link_color,
      );
      graphapi_set_link_data($graph, $instance_entity_type, $target_type, $link_data);
    }
  }

  // Step 2: Create graph nodes for schema properties that point to other entity tables.
  // First we need a lookup of all the entity tables.
  $entity_tables = array();
  foreach ($entity_info as $entity_type => $entity_type_info) {
    $entity_tables[$entity_type_info['base table']] = $entity_type;
  }
  // Now work through each entity's table schema
  foreach ($entity_info as $entity_type => $entity_type_info) {
    $entity_table_schema = drupal_get_schema($entity_type_info['base table']);
    if (!isset($entity_table_schema['foreign keys'])) {
      continue;
    }
    foreach ($entity_table_schema['foreign keys'] as $relation_name => $relation_info) {
      if (isset($entity_tables[$relation_info['table']])) {
        // The relation goes to another entity type's table, so add a link to
        // our graph.
        $link_data = array(
          'title' => $relation_name,
          'type'  => 'schema',
          'color' => 'blue',
        );
        graphapi_set_link_data($graph, $entity_type, $entity_tables[$relation_info['table']], $link_data);
      }
    }
  }

  //dsm($graph);

  $config = array(
    'width' => 800,
    'height' => 400,
  );

  $graph_api_engines = graphapi_get_engines();
  if (empty($graph_engine)) {
    // Take the first engine if none was given in the URL.
    $graph_api_engine_names = array_keys($graph_api_engines);
    $graph_engine = array_shift($graph_api_engine_names);
  }

  $config['engine'] = $graph_engine;

  $build = array();

  $vars = array(
    'graph' => $graph,
    'config' => $config,
  );

  $build['graph'] = array(
    '#markup' => theme('graphapi_dispatch', $vars)
  );

  $engine_links_items = array();
  foreach ($graph_api_engines as $name => $label) {
    if ($graph_engine == $name) {
      $label .= ' ' . t("(current)");
    }
    $engine_links_items[] = l($label, "admin/reports/fields/graph/$name");
  }

  $build['engine_links'] = array(
    '#theme' => 'item_list',
    '#items' => $engine_links_items,
    '#title' => t("Select graph rendering engine"),
  );

  return $build;
}

/**
 * Form to edit all instances of a field.
 *
 * @param $field
 *  A field definition array.
 *
 * @see field_tools_field_edit_form_validate()
 * @see field_tools_field_edit_form_submit()
 */
function field_tools_field_edit_form($form, &$form_state, $field) {
  // Take the first instance in the list as the one to populate the form with.
  $bundles = array_keys($field['bundles']);
  $entity_type = array_shift($bundles);
  $bundle = $field['bundles'][$entity_type][0];

  $form['warning'] = array(
    '#markup' => t('WARNING: Editing these values will change ALL INSTANCES of this field:')
      . '<br />'
      . field_tools_field_instances_list($field),
  );

  $instance = field_info_instance($entity_type, $field['field_name'], $bundle);

  form_load_include($form_state, 'inc', 'field_ui', 'field_ui.admin');

  // Remainder cribbed from field_ui_field_edit_form().
  $form['#field'] = $field;
  $form['#instance'] = $instance;

  if (!empty($field['locked'])) {
    $form['locked'] = array(
      '#markup' => t('The field %field is locked and cannot be edited.', array('%field' => $instance['label'])),
    );
    return $form;
  }

  $field_type = field_info_field_types($field['type']);
  $widget_type = field_info_widget_types($instance['widget']['type']);
  $bundles = field_info_bundles();

  // Create a form structure for the instance values.
  $form['instance'] = array(
    '#tree' => TRUE,
    '#type' => 'fieldset',
    '#title' => t('%type settings', array('%type' => $bundles[$entity_type][$bundle]['label'])),
    '#description' => t('These settings will be applied the ALL INSTANCES OF THE %field field.', array(
      '%field' => $instance['label'],
      '%type' => $bundles[$entity_type][$bundle]['label'],
    )),
    // Ensure field_ui_field_edit_instance_pre_render() gets called in addition
    // to, not instead of, the #pre_render function(s) needed by all fieldsets.
    '#pre_render' => array_merge(array('field_ui_field_edit_instance_pre_render'), \Drupal::service('element_info')->getInfoProperty('fieldset', '#pre_render', array())),
  );

  // Build the non-configurable instance values.
  $form['instance']['field_name'] = array(
    '#type' => 'value',
    '#value' => $instance['field_name'],
  );
  $form['instance']['entity_type'] = array(
    '#type' => 'value',
    '#value' => $entity_type,
  );
  $form['instance']['bundle'] = array(
    '#type' => 'value',
    '#value' => $bundle,
  );
  $form['instance']['widget']['weight'] = array(
    '#type' => 'value',
    '#value' => !empty($instance['widget']['weight']) ? $instance['widget']['weight'] : 0,
  );

  // Build the configurable instance values.
  $form['instance']['label'] = array(
    '#type' => 'textfield',
    '#title' => t('Label'),
    '#default_value' => !empty($instance['label']) ? $instance['label'] : $field['field_name'],
    '#required' => TRUE,
    '#weight' => -20,
  );
  $form['instance']['required'] = array(
    '#type' => 'checkbox',
    '#title' => t('Required field'),
    '#default_value' => !empty($instance['required']),
    '#weight' => -10,
  );

  $form['instance']['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Help text'),
    '#default_value' => !empty($instance['description']) ? $instance['description'] : '',
    '#rows' => 5,
    '#description' => t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', array('@tags' => _field_filter_xss_display_allowed_tags())),
    '#weight' => -5,
  );

  // Build the widget component of the instance.
  $form['instance']['widget']['type'] = array(
    '#type' => 'value',
    '#value' => $instance['widget']['type'],
  );
  $form['instance']['widget']['module'] = array(
    '#type' => 'value',
    '#value' => $widget_type['module'],
  );
  $form['instance']['widget']['active'] = array(
    '#type' => 'value',
    '#value' => !empty($field['instance']['widget']['active']) ? 1 : 0,
  );

  // Add additional field instance settings from the field module.
  $additions = module_invoke($field['module'], 'field_instance_settings_form', $field, $instance);
  if (is_array($additions)) {
    $form['instance']['settings'] = $additions;
  }

  // Add additional widget settings from the widget module.
  $additions = module_invoke($widget_type['module'], 'field_widget_settings_form', $field, $instance);
  if (is_array($additions)) {
    $form['instance']['widget']['settings'] = $additions;
    $form['instance']['widget']['active']['#value'] = 1;
  }

  // Add handling for default value if not provided by any other module.
  if (field_behaviors_widget('default value', $instance) == FIELD_BEHAVIOR_DEFAULT && empty($instance['default_value_function'])) {
    $form['instance']['default_value_widget'] = field_ui_default_value_widget($field, $instance, $form, $form_state);
  }

  // End crib.

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save settings'));

  return $form;
}

/**
 * Validate handler for the form for all instances of a field.
 *
 * @see field_tools_field_edit_form()
 * @see field_tools_field_edit_form_submit()
 * @see field_ui_field_edit_form_validate()
 */
function field_tools_field_edit_form_validate($form, &$form_state) {
  field_ui_field_edit_form_validate($form, $form_state);
}

/**
 * Submit handler for the form for all instances of a field.
 *
 * @see field_tools_field_edit_form()
 * @see field_tools_field_edit_form_validate()
 * @see field_ui_field_edit_form_submit()
 */
function field_tools_field_edit_form_submit($form, &$form_state) {
  $instance = $form_state['values']['instance'];
  $field = $form['#field'];

  // Handle the default value.
  if (isset($form['instance']['default_value_widget'])) {
    $element = $form['instance']['default_value_widget'];

    // Extract field values.
    $items = array();
    field_default_extract_form_values(NULL, NULL, $field, $instance, LANGUAGE_NONE, $items, $element, $form_state);
    field_default_submit(NULL, NULL, $field, $instance, LANGUAGE_NONE, $items, $element, $form_state);

    $instance['default_value'] = $items ? $items : NULL;
  }

  foreach ($field['bundles'] as $entity_type => $bundles) {
    foreach ($bundles as $bundle) {
      // Fake the entity type and bundle into the instance data to save.
      $instance['entity_type'] = $entity_type;
      $instance['bundle'] = $bundle;

      $instance_source = field_read_instance($entity_type, $field['field_name'], $bundle);
      $instance = array_merge($instance_source, $instance);
      field_update_instance($instance);
      \Drupal::messenger()->addMessage(t('Saved %label instance on entity %entity, bundle %bundle', array(
        '%label' => $instance['label'],
        // TODO: labels.
        '%entity' => $entity_type,
        '%bundle' => $bundle,
      )));
    }
  }

  // Redirect the user to the overview page.
  $form_state['redirect'] = 'admin/reports/fields/tools';
}

/**
 * Form for deleting multiple instances of a field.
 */
function field_tools_field_delete_form($form, &$form_state, $field) {
  drupal_set_title(t('Delete instances from field %fieldname', array(
    '%fieldname' => $field['field_name'],
  )), PASS_THROUGH);

  $form['info'] = array(
    '#markup' => $field['field_name'],
  );

  $entity_info = entity_get_info();

  $options = array();
  foreach ($field['bundles'] as $entity_type => $bundles) {
    foreach ($bundles as $bundle) {
      $options["$entity_type:$bundle"] = $entity_info[$entity_type]['label'] . ': ' . $entity_info[$entity_type]['bundles'][$bundle]['label'];
    }
  }

  $form['instances'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Instances to delete'),
    '#description' => t("WARNING: deleting an instance will delete ALL THE DATA in that instance."),
    '#options' => $options,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Delete field instances'),
  );

  // @todo: use confirm_form()!
  return $form;
}

/**
 * Submit handler for deleting multiple instances.
 */
function field_tools_field_delete_form_submit($form, &$form_state) {
  $field = $form_state['build_info']['args'][0];
  foreach (array_filter($form_state['values']['instances']) as $instance_key) {
    list($entity_type, $bundle_name) = explode(':', $instance_key);
    $instance = field_info_instance($entity_type, $field['field_name'], $bundle_name);
    field_delete_instance($instance);
    \Drupal::messenger()->addMessage(t('Deleted instance of %fieldname from %entity bundle %bundle', array(
      '%fieldname' => $field['field_name'],
      '%entity' => $entity_type,
      '%bundle' => $bundle_name,
    )));
  }

  // Redirect the user to the overview page.
  $form_state['redirect'] = 'admin/reports/fields/tools';
}

/**
 * Form builder for the cloning multiple fields from a bundle.
 *
 * @param $entity_type
 *  The machine name of the entity.
 * @param $bundle_name
 *  The machine name of the bundle, or a bundle object if the particular
 *  entity type has a menu loader for bundles.
 */
function field_tools_bundle_fields_clone_from_form($form, &$form_state, $entity_type, $bundle_name) {
  // Get the bundle name if the bundle name is really a bundle object.
  $bundle_name = field_extract_bundle($entity_type, $bundle_name);

  $field_instances = field_info_instances($entity_type, $bundle_name);
  //dsm($field_instances);

  // Order the instances by weight.
  uasort($field_instances, function($a, $b) {
    if ($a['widget']['weight'] == $b['widget']['weight']) {
        return 0;
    }
    return ($a['widget']['weight'] < $b['widget']['weight']) ? -1 : 1;
  });

  $options_fields = array();
  foreach ($field_instances as $field_name => $field) {
    $options_fields[$field_name] = t("@field-label (machine name: @field-name)", array(
      '@field-label' => $field['label'],
      '@field-name' => $field_name,
    ));
  }
  asort($options_fields);

  $form['fields'] = array(
    '#title' => t('Fields to clone'),
    '#type' => 'checkboxes',
    '#options' => $options_fields,
    '#description' => t("Select fields to clone onto one or more bundles."),
  );

  $form['bundles'] = array(
    '#title' => t('Bundle(s) to clone onto'),
    '#type' => 'checkboxes',
    '#options' => field_tools_options_entity_bundle($entity_type, $bundle_name),
    //'#default_value' => $default_bundles,
    '#description' => t("Select bundles on which to apply the selected fields."),
  );
  // Disable the checkbox for the current bundle.
  $form['bundles']["$entity_type:$bundle_name"] = array(
    '#disabled' => TRUE,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add field instances'),
  );

  return $form;
}

/**
 * Submit handler for the mass clone form.
 */
function field_tools_bundle_fields_clone_from_form_submit($form, &$form_state) {
  // Get details from the original form builder parameters.
  list($source_entity_type, $source_bundle_name) = $form_state['build_info']['args'];
  // Get the bundle name if the bundle name is really a bundle object.
  $source_bundle_name = field_extract_bundle($source_entity_type, $source_bundle_name);

  // Get names of fields to clone.
  $field_names = array_filter($form_state['values']['fields']);

  foreach ($field_names as $field_name) {
    $field = field_info_field($field_name);
    $instance = field_info_instance($source_entity_type, $field_name, $source_bundle_name);

    $new_instances = array();
    foreach (array_filter($form_state['values']['bundles']) as $option_key) {
      list($entity_type, $bundle_type) = explode(':', $option_key);
      $new_instances[$entity_type][] = $bundle_type;
    }
    if (!empty($new_instances)) {
      _field_tools_add_instance_to_bundles($instance, $new_instances);
    }
  }
}

/**
 * Form builder for cloning a single field..
 *
 * @param $instance
 *  A FieldAPI field instance definition array.
 */
function field_tools_field_clone_form($form, &$form_state, $instance) {
  //dsm($instance);
  $form['#instance'] = $instance;

  $field_name = $instance['field_name'];

  // TODO: is there a way to turn most of what follows into a form element?
  $field = field_info_field($field_name);
  $field_exists = isset($field);
  $field_type = field_info_field_types('taxonomy_term_reference');

  // Field settings fieldset.
  // @todo restore this when we add a field apply-type UI.
  /*
  $form['settings'] = array(
    '#type' => 'fieldset',
  );
  $form['settings']['multiple'] = array('#type' => 'checkbox',
    '#title' => t('Multiple select'),
    '#description' => t('Allows reference fields to hold more than one term from this vocabulary.'),
  );
  // Lock this if the field exists.
  if ($field_exists) {
    $form['settings']['multiple'] += array(
      '#disabled' => TRUE,
      '#default_value' => ($field['cardinality'] == 1 ? FALSE : TRUE),
    );
    $form['settings']['multiple']['#description'] .= ' ' . t('This setting may not be changed here because this field already has instances.');
  }

  $form['settings']['required'] = array('#type' => 'checkbox',
    '#title' => t('Required'),
    '#description' => t('At least one term in this vocabulary must be selected when submitting data with this field.'),
  );

  form_load_include($form_state, 'inc', 'field_ui', 'field_ui.admin');
  $widget_options = field_ui_widget_type_options($field['type']);
  $form['settings']['widget_type'] = array(
    '#type' => 'select',
    '#title' => t('Widget type'),
    '#required' => TRUE,
    '#options' => $widget_options,
    '#default_value' => $field_type['default_widget'],
    '#description' => t('The type of form element you would like to present to the user when creating this field in the types below.'),
  );
  */

  $options = field_tools_options_entity_bundle($instance['entity_type'], $instance['bundle'], FALSE);

  //dsm($options);
  $default_bundles = array();
  if ($field_exists) {
    foreach ($field['bundles'] as $entity_type => $bundles) {
      foreach ($bundles as $bundle_type) {
        $default_bundles[] = $entity_type . ':' . $bundle_type;
      }
    }
  }
  $form['bundles'] = array(
    '#type' => 'checkboxes',
    '#options' => $options,
    '#default_value' => $default_bundles,
    '#description' => t("Select bundles on which to apply this field."),
  );
  // Very neat but undocumented trick: see http://drupal.org/node/1349432
  foreach ($default_bundles as $option_key) {
    $form['bundles'][$option_key] = array(
      '#disabled' => TRUE,
    );
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add field instances'),
  );

  return $form;
}

/**
 * Submit handler for the field clone form.
 */
function field_tools_field_clone_form_submit($form, &$form_state) {
  $instance = $form['#instance'];
  $field_name = $instance['field_name'];
  $field = field_info_field($field_name);

  $new_instances = array();
  foreach (array_filter($form_state['values']['bundles']) as $option_key) {
    list($entity_type, $bundle_type) = explode(':', $option_key);
    $new_instances[$entity_type][] = $bundle_type;
  }
  if (!empty($new_instances)) {
    _field_tools_add_instance_to_bundles($instance, $new_instances);
  }
}

/**
 * Helper to get FormAPI options for entity bundles.
 *
 * @param string $current_entity_type
 *   The current entity type that these options will be used with.
 * @param string $current_bundle
 *   The current bundle name that these options will be used with.
 * @param boolean $filter
 *  Whether to filter out the current bundle.
 *
 * @return
 *  An array for FormAPI '#options' properties, with:
 *    - keys of the form ENTITY:BUNDLE, using machine names.
 *    - values of the form ENTITY: BUNDLE, using labels.
 */
function field_tools_options_entity_bundle($current_entity_type, $current_bundle, $filter = TRUE) {
  $options = array();
  foreach (entity_get_info() as $entity_type => $entity_info) {
    // Skip entities that don't take fields.
    if (empty($entity_info['fieldable'])) {
      continue;
    }

    foreach ($entity_info['bundles'] as $bundle_type => $bundle_info) {
      // Don't show the current bundle in the options, unless not filtering.
      if (!$filter || !($current_entity_type == $entity_type && $bundle_type == $current_bundle)) {
        $options[$entity_type . ':' . $bundle_type] = $entity_info['label'] . ': ' . $bundle_info['label'];
      }
    }
  }
  return $options;
}

/**
 * Form builder for the cloning multiple fields to a bundle.
 *
 * @param $current_entity_type
 *  The machine name of the entity.
 * @param $current_bundle_name
 *  The machine name of the bundle, or a bundle object if the particular
 *  entity type has a menu loader for bundles.
 */
function field_tools_bundle_fields_clone_to_form($form, &$form_state, $current_entity_type, $current_bundle_name) {
  $current_bundle_name = field_extract_bundle($current_entity_type, $current_bundle_name);
  $all_instances = field_info_instances();

  $entity_types = entity_get_info();
  $form['entity_type'] = array(
    '#value' => $current_entity_type,
    '#type'  => 'value',
  );
  $form['bundle'] = array(
    '#value' => $current_bundle_name,
    '#type'  => 'value',
  );
  foreach ($entity_types as $entity_key => $entity_type) {
    if ($entity_type['fieldable'] == TRUE) {
      if (!empty($entity_type['bundles'])) {
        // This entity_type has bundles.
        $form['fields'][$entity_key] = array(
          '#type' => 'fieldset',
          '#title' => $entity_type['label'],
          '#description' => '',
          '#tree' => TRUE,
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        );
        foreach ($entity_type['bundles'] as $bundle_name => $bundle) {
          if ($current_entity_type == $entity_key && $bundle_name == $current_bundle_name) {
            continue;
          }
          if (empty($all_instances[$entity_key][$bundle_name])) {
            // No fields on this bundle.
            continue;
          }
          $form['fields'][$entity_key][$bundle_name . '_set'] = array(
            '#type' => 'fieldset',
            '#title' => $bundle['label'],
            '#description' => '',
            '#collapsible' => TRUE,
            '#collapsed' => TRUE,
          );
          $form['fields'][$entity_key][$bundle_name . '_set'][$bundle_name] = array(
            '#type' => 'checkboxes',
            '#title' => $bundle['label'],
            '#description' => '',
            '#options' => array(),
          );

          foreach ($all_instances[$entity_key][$bundle_name] as $field_name => $field_info) {
            // Make sure this field doesn't already exist on the current bundle.
            $on_current_bundle = is_array($all_instances[$current_entity_type][$current_bundle_name]) && array_key_exists($field_name, $all_instances[$current_entity_type][$current_bundle_name]);
            if (!$on_current_bundle && _field_tools_entity_can_attach_field($current_entity_type, field_info_field($field_name))) {
              $form['fields'][$entity_key][$bundle_name . '_set'][$bundle_name]['#options'][$field_name] = $field_info['label'] . " ({$field_name})";
            }
          }
          if (empty($form['fields'][$entity_key][$bundle_name . '_set'][$bundle_name]['#options'])) {
            // @todo should we not show a bundle if no fields can be attached??
            $form['fields'][$entity_key][$bundle_name . '_set'][$bundle_name]['#description'] = t('Contains no fields that can be attached to this bundle.');
          }
        }
        // Set #parents to skip sets in form values.
        foreach (element_children($form['fields'][$entity_key]) as $key) {
          if (strrpos($key, '_set') === drupal_strlen($key) - drupal_strlen('_set')) {
            foreach (element_children($form['fields'][$entity_key][$key]) as $sub_key ) {
              $form['fields'][$entity_key][$key][$sub_key]['#parents'] = array('fields', $entity_key, $sub_key);
            }
          }
        }
        $bundle_names = element_children($form['fields'][$entity_key]);
        if (empty($bundle_names)) {
          // Don't show entities that have no bundle with fields.
          unset($form['fields'][$entity_key]);
        }
      }
    }
  }
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Add field instances',
  );
  return $form;
}

/**
 * Validation for cloning multiple fields into one bundle.
 *
 * Make sure no field was selected more than once.
 */
function field_tools_bundle_fields_clone_to_form_validate($form, &$form_state) {
  $selected_fields = array();
  $fields = $form_state['values']['fields'];
  foreach ($fields as $entity_type => $bundles) {
    foreach ($bundles as $bundle_name => $bundle_fields) {
      $bundle_fields = array_filter($bundle_fields);
      $matching_fields = array_intersect_key($selected_fields, $bundle_fields);
      if (!empty($matching_fields)) {
        $match_key = array_shift($matching_fields);
        form_error($form['fields'][$entity_type][$bundle_name . '_set'], t("You have selected the field %field more that once.", array(
          '%field' => $match_key,
        )));
        return;
      }
      $selected_fields += $bundle_fields;
    }
  }
}

/**
 * Submit handler for the mass clone to bundle form.
 */
function field_tools_bundle_fields_clone_to_form_submit($form, &$form_state) {
  $fields = $form_state['values']['fields'];
  $current_entity_type = $form_state['values']['entity_type'];
  $current_bundle_name = $form_state['values']['bundle'];

  foreach ($fields as $entity_type => $bundles) {
    foreach ($bundles as $bundle_name => $bundle_fields) {
      $bundle_fields = array_filter($bundle_fields);
      foreach ($bundle_fields as $field_name) {
        $field_info = field_info_instance($entity_type, $field_name, $bundle_name);
        _field_tools_add_instance_to_bundles($field_info, array($current_entity_type => array($current_bundle_name)));
      }
    }
  }
}

/**
 * Helper function to clone a single field instance into multiple bundles.
 *
 * @param array $instance
 *   The field instance to be added to the bundles.
 * @param array $new_instances
 *   An array describing entity bundles on which to create field instances.
 *   Each key is an entity type machine name, each value is an array of bundle
 *   machine names of that entity.
 */
function _field_tools_add_instance_to_bundles($instance, $new_instances) {
  $original_display = $instance['display'];
  $field_info = field_info_field($instance['field_name']);
  $entity_types = entity_get_info();
  foreach ($new_instances as $entity_type => $bundles) {
    $bundles = array_filter($bundles);
    if (!empty($bundles)) {
      if (!_field_tools_entity_can_attach_field($entity_type, $field_info)) {
        \Drupal::messenger()->addMessage(t('Field %field_label cannot be attached to entity type %entity_type', array(
          '%field_label' => $instance['label'],
          '%entity_type' => $entity_types[$entity_type]['label'],
        )));
        continue;
      }

      // Strip out keys that are specific to the instance being copied.
      $instance = array_diff_key($instance, array_flip(array('id', 'field_id', 'bundle', 'entity_type', 'deleted')));

      // Only bring back displays that have matching "view mode" in this entity
      // type.
      $view_modes = $entity_types[$entity_type]['view modes'];
      // Add a key for the default display settings, so the array intersection
      // keeps them, as we always want those.
      $view_modes['default'] = TRUE;

      $instance['display'] = array_intersect_key($original_display, $view_modes);
      if (empty($instance['display'])) {
        //@todo should there be logic to handle to no matching 'view modes'
      }
      $instance['entity_type'] = $entity_type;
      foreach ($bundles as $bundle) {
        if (_field_tools_field_already_attached($entity_type, $bundle, $field_info)) {
          \Drupal::messenger()->addMessage(t('Field %field_label is already attached to %entity_type - %bundle', array(
            '%field_label' => $instance['label'],
            '%entity_type' => $entity_types[$entity_type]['label'],
            '%bundle' => $entity_types[$entity_type]['bundles'][$bundle]['label'],
          )));
          continue;
        }
        $instance['bundle'] = $bundle;
        field_create_instance($instance);
        \Drupal::messenger()->addMessage(t('Attached field %field_label to %entity_type - %bundle', array(
          '%field_label' => $instance['label'],
          '%entity_type' => $entity_types[$entity_type]['label'],
          '%bundle' => $entity_types[$entity_type]['bundles'][$bundle]['label'],
        )));
      }
    }
  }
}

/**
 * Page callback to list all instances of a field with links to edit them.
 */
function field_tools_field_page($field_name) {
  $field = field_info_field($field_name);
  if (!$field) {
    return t('No field found.');
  }
  //dsm($field);
  $field_instance_list = field_tools_info_instances($field_name);
  $bundles = field_info_bundles();

  $items = array();
  foreach ($field_instance_list as $entity_type => $bundle_list) {
    foreach ($bundle_list as $bundle) {
      $admin_path = _field_ui_bundle_admin_path($entity_type, $bundle);

      $items[] = l($bundles[$entity_type][$bundle]['label'], $admin_path . '/fields/' . $field_name);
    }
  }


  return theme('item_list', array('items' => $items));
}

/**
 * Helper to format a nested list of field instances, grouped by entity type.
 *
 * @todo: make this a theme function?
 *
 * @param $field
 *  A field definition array.
 *
 * @return
 *  A nested list of entities and bundles that this field has instances on.
 */
function field_tools_field_instances_list($field) {
  $entity_info = entity_get_info();
  $items_entities = array();
  foreach ($field['bundles'] as $entity_type => $field_bundle_names) {
    // Fields may exist for entities that no longer do.
    if (!isset($entity_info[$entity_type])) {
      continue;
    }

    $items_bundles = array();
    foreach ($field_bundle_names as $bundle) {
      if (!isset($entity_info[$entity_type]['bundles'][$bundle])) {
        continue;
      }

      // @todo: sort these.
      $admin_path = _field_ui_bundle_admin_path($entity_type, $bundle);
      $bundle_label = $entity_info[$entity_type]['bundles'][$bundle]['label'];
      $items_bundles[$bundle_label] = $admin_path ? l($bundle_label, $admin_path . '/fields/' . $field['field_name']) : $bundle_label;
    }
    ksort($items_bundles);

    $entity_label = $entity_info[$entity_type]['label'];
    $items_entities[$entity_label] = $entity_label . theme('item_list', array(
      'items' => $items_bundles,
    ));
  }
  ksort($items_entities);

  return theme('item_list', array(
    'items' => $items_entities,
  ));
}
