<?php

/**
 * @file
 * Provide views data for comment.module.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function soapbox_views_form_views_exposed_form_alter(
  &$form,
  FormStateInterface $form_state,
  $form_id
) {

  // After build.
  if (!isset($form['#after_build'])) {
    $form['#after_build'] = [];
  }
  $form['#after_build'][] = '_soapbox_views_form_views_exposed_form_after_build';

  // Add the url to the cache contexts so change of url refreshes.
  $form['#cache'] = ['contexts' => ['url.path']];
}

/**
 * Handle views exposed form after build.
 *
 * After build function for views exposed form.
 * Passes 3 arrays to 'views-exposed-form.html.twig' to allow easy separation
 * of sorters, filters, and buttons in the twig template.
 *
 * @param array $form
 *   The form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Current form state.
 *
 * @return array
 *   The update form.
 */
function _soapbox_views_form_views_exposed_form_after_build(
  array $form,
  FormStateInterface $form_state
):array {

  $form['#sorters'] = [];
  $form['#filters'] = [];
  $form['#submit_buttons'] = [];

  if (\Drupal::service('router.admin_context')->isAdminRoute()) {
    return $form;
  }

  // Cannot see a better way to do this at the moment, need to get view object.
  $ajax_enabled = FALSE;
  $view_theme = reset($form['#theme']);
  $view_theme = str_replace('views_exposed_form__', '', $view_theme);
  $view_parts = explode('__', $view_theme);
  $view_machine_name = reset($view_parts);
  $view_display = end($view_parts);

  // Load the view and this display, determine if it is meant to be ajax.
  if ($view = Views::getView($view_machine_name)) {
    $view->setDisplay($view_display);
    $display = $view->getDisplay();
    $ajax_enabled = $display->getOption('use_ajax');
    $autosubmit = FALSE;
    $exposed_form_options = $display->getOption('exposed_form');
    if (isset($exposed_form_options['options']['bef']['general']['autosubmit'])) {
      $autosubmit = $exposed_form_options['options']['bef']['general']['autosubmit'];
    }
  }

  foreach ($form as $field => &$settings) {

    // Skip form meta data.
    if (strpos($field, '#') === 0 || strpos($field, 'form') === 0) {
      continue;
    }

    if ($field == 'actions') {
      $form['#submit_buttons'][] = 'actions';
      foreach ($settings as $subfield => &$subsettings) {
        // Skip form meta data.
        if (strpos($subfield, '#') === 0 || strpos($subfield, 'form') === 0) {
          continue;
        }

        $classes = [];
        $form['#submit_buttons'] = ($form['#submit_buttons'] ?? []);
        $form['#submit_buttons'][] = $subfield;

        // Submit button classes.
        $base_class = 'c-filter';
        $classes[] = $base_class . '__button';
        if ($subfield == 'reset') {
          $classes[] = $base_class . '__button--reset';
        }
        elseif ($subfield == 'submit') {
          $classes[] = $base_class . '__button--submit';
          if ($ajax_enabled && $autosubmit) {
            $classes[] = 'js-hide';
          }
        }

        // Allow other modules to alter the button classes.
        if (in_array($subfield, ['reset', 'submit'])) {
          $button_type = $subfield;
          \Drupal::moduleHandler()
            ->alter('soapbox_views_filter_button_classes', $classes, $button_type);
        }

        if (isset($settings[$subfield])) {
          $settings[$subfield]['#attributes'] = array_merge_recursive($settings[$subfield]['#attributes'],
            [
              'class' => $classes,
            ]);
        }
      }
    }
    elseif (strpos($field, 'sort') === 0) {
      $form['#sorters'] = ($form['#sorters'] ?? []);
      $form['#sorters'][] = $field;

      foreach ($form[$field] as $subkey => $subfield) {
        if (strpos($subkey, '#') === FALSE) {
          $form['#sorters'][] = $subkey;
        }
      }

      $base_class = 'c-sorter';
      _soapbox_views_field_add_classes($base_class, $field, $settings, $form_state);
    }
    else {
      $form['#filters'] = ($form['#filters'] ?? []);
      $form['#filters'][] = $field;
      $base_class = 'c-filter';
      _soapbox_views_field_add_classes($base_class, $field, $settings, $form_state);

      // Setup advanced filter class.
      if (!empty($form['#advanced_filters']) && in_array($field,
          $form['#advanced_filters'])) {
        foreach (array_keys($settings['#options']) as $option) {
          $settings[$option]['#attributes']['class'][] = $base_class . '__item--advanced';
        }
      }

      if (!empty($form['#nondropdown']) && in_array($field,
          $form['#nondropdown'])) {
        $settings['#attributes']['class'][] = $base_class . ' c-filter--no-legend';

      }
    }
  }

  return $form;
}

/**
 * Add classes within form.
 *
 * @param string $base_class
 *   For instance 'c-filter' or 'c-sorters'.
 * @param string $field
 *   Field machine name.
 * @param array $settings
 *   Render array field settings.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 */
function _soapbox_views_field_add_classes(string $base_class, string $field, array &$settings, FormStateInterface $form_state): void {
  $selected = [];
  if ($form_state->hasValue($field)) {
    $selected_values = $form_state->getValue($field);
    if (is_array($selected_values)) {
      $selected = array_values($selected_values);
      $selected = array_filter($selected);
    }
  }
  if (
    isset($settings['#type'])
    && in_array($settings['#type'], ['checkboxes', 'radios'])
    && isset($settings['#options'])
    && is_array($settings['#options'])
  ) {
    foreach (array_keys($settings['#options']) as $option) {

      $type = ($settings['#type'] == 'checkboxes' ? 'checkbox' : 'radio');

      // Add classes to nested options.
      $settings[$option]['#attributes'] = array_merge_recursive($settings[$option]['#attributes'],
        [
          'class' => [
            $base_class . '__item',
            $base_class . '__item--field-' . str_replace('_', '-', $field),
            $base_class . '__item--type-' . $type,
          ],
        ]);

      // Add label classes to nested options.
      $settings[$option]['#label_attributes'] = ($settings[$option]['#label_attributes'] ?? []);
      $settings[$option]['#label_attributes'] = array_merge_recursive($settings[$option]['#label_attributes'],
        [
          'class' => [
            $base_class . '__item-label',
            $base_class . '__item-label--field-' . str_replace('_', '-',
              $field),
            $base_class . '__item-label--type-' . $type,
          ],
        ]);

      // Add wrapper classes to nested options.
      $settings[$option]['#wrapper_attributes'] = ($settings[$option]['#wrapper_attributes'] ?? []);
      $settings[$option]['#wrapper_attributes'] = array_merge_recursive($settings[$option]['#wrapper_attributes'],
        [
          'class' => [
            $base_class . '__wrapper',
            $base_class . '__wrapper--field-' . str_replace('_', '-', $field),
            $base_class . '__wrapper--type-' . $type,
          ],
        ]);

      \Drupal::moduleHandler()
        ->alter('soapbox_views_filter_field_attributes', $settings, $option);
    }

    // Maybe hide some elements.
    if (isset($settings['#hidden_options']) && $settings['#hidden_options']) {
      foreach ($settings['#hidden_options'] as $hidden_option) {
        if (isset($settings[$hidden_option])) {
          $settings[$hidden_option]['#wrapper_attributes'] = array_merge_recursive($settings[$hidden_option]['#wrapper_attributes'],
            [
              'class' => [
                'u-accessible-hide',
              ],
            ]);
        }
      }
    }
  }

  // Add classes to parent element.
  $classes = [
    $base_class . '--field-' . str_replace('_', '-', $field),
  ];
  $label_classes = [
    $base_class . '__label',
    $base_class . '__label--field-' . str_replace('_', '-', $field),
  ];
  $wrapper_classes = [
    $base_class . '__wrapper',
    $base_class . '__wrapper--field-' . str_replace('_', '-', $field),
  ];

  // Add that this has selections if it does.
  if ($selected) {
    $classes[] = 'has-selections';
  }
  else {
    $classes[] = 'no-selections';
  }

  // Add field type if we know it.
  if (isset($settings['#type'])) {
    $type = str_replace('_', '-', $settings['#type']);
    $classes[] = $base_class . '--type-' . $type;
    $label_classes[] = $base_class . '-label--type-' . $type;
    $wrapper_classes[] = $base_class . '__wrapper--type-' . $type;
  }

  // Merge into existing classes.
  $settings['#attributes'] = array_merge_recursive($settings['#attributes'], [
    'class' => $classes,
  ]);
  $settings['#label_attributes'] = ($settings['#label_attributes'] ?? []);
  $settings['#label_attributes'] = array_merge_recursive($settings['#label_attributes'],
    [
      'class' => $label_classes,
    ]);
  $settings['#wrapper_attributes'] = ($settings['#wrapper_attributes'] ?? []);
  $settings['#wrapper_attributes'] = array_merge_recursive($settings['#wrapper_attributes'],
    [
      'class' => $wrapper_classes,
    ]);
}

/**
 * Implements hook_views_data_alter().
 */
function soapbox_views_views_data_alter(array &$data) {
  // Fields to override by default.
  $alterable_date_fields = [
    'node_field_data.unified_date' => 'soapbox_date',
    'node_field_data.published_at' => 'soapbox_date',
  ];
  // Let modules alter this array.
  \Drupal::moduleHandler()->alter('soapbox_views_date_filter_fields', $alterable_date_fields, $data);
  if (!empty($alterable_date_fields)) {
    foreach ($alterable_date_fields as $field => $plugin) {
      // Replace filter plugin for given fields.
      $path = explode('.', $field);
      if (NestedArray::keyExists($data, $path)) {
        NestedArray::setValue($data, array_merge($path, ['filter', 'id']), $plugin);
      }
    }
  }
}
