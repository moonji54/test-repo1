<?php

/**
 * @file
 * Soapbox Featured Cards API documentation.
 */

/**
 * Add classes to the filter buttons.
 *
 * @param array|object $classes
 *   The button classes.
 * @param string $button_type
 *   Button type - reset|submit.
 */
function hook_soapbox_views_filter_button_classes_alter(&$classes, &$button_type) {
  if ($button_type === 'reset') {
    $classes[] = 'some-class';
  }
  elseif ($button_type === 'submit') {
    $classes[] = 'some-submit-class';
  }
}

/**
 * Implements soapbox_views_filter_field_attributes_alter().
 */
function hook_soapbox_views_filter_field_attributes_alter(&$settings, &$option) {
  // Add a special class to a specific field's wrapper.
  if ($settings[$option]['#title'] === 'Some title') {
    $settings[$option]['#wrapper_attributes']['class'][] = 'hello';
  }
}

/**
 * Implements hook_soapbox_views_date_filter_fields_alter().
 */
function hook_soapbox_views_date_filter_fields_alter(&$fields, $views_data) {
  // Change filter plugins for specific fields.
  $fields += [
    'node_field_date.created' => 'soapbox_date',
    'node__field_event_date.field_event_date_value' => 'soapbox_datetime',
  ];
}
