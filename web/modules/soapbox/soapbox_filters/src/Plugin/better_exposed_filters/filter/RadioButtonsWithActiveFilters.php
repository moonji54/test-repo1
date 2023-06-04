<?php

namespace Drupal\soapbox_filters\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\RadioButtons;
use Drupal\Core\Form\FormStateInterface;

/**
 * Radio buttons and checkboxes with active filters widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "bef_soapbox_active_filters",
 *   label = @Translation("Checkboxes/Radio Buttons with Active Filters"),
 * )
 */
class RadioButtonsWithActiveFilters extends RadioButtons {

  use BefActiveFilters;

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    parent::exposedFormAlter($form, $form_state);

    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $filter = $this->handler;
    // Form element is designated by the element ID which is user-
    // configurable.
    $field_id = $filter->options['is_grouped'] ? $filter->options['group_info']['identifier'] : $filter->options['expose']['identifier'];
    $input = $form_state->getUserInput();

    // Don't generate active filters if.
    if (empty($form[$field_id])
      || !$this->configuration['show_active_filters']
      || empty($input[$field_id])) {
      return;
    }

    $input_values = $input[$field_id];
    if (!is_array($input_values)) {
      $input_values = [$input_values => $input_values];
    }

    $items = [];
    foreach ($input_values as $input_value) {
      if (!empty($form[$field_id]['#options'][$input_value])) {
        $items[] = [
          'title' => $form[$field_id]['#options'][$input_value] ?? $input_value,
          'value' => $input_value,
        ];
      }
    }

    if (!empty($items)) {
      $this->addActiveFilter($field_id, $items, $form, $filter->options['expose']['multiple']);
    }
  }

}
