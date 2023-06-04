<?php

namespace Drupal\soapbox_filters\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\Single;
use Drupal\Core\Form\FormStateInterface;

/**
 * Single with active filters widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "bef_soapbox_single_active_filters",
 *   label = @Translation("Single On/Off Checkbox with Active Filters"),
 * )
 */
class SingleWithActiveFilters extends Single {

  use BefActiveFilters;

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    parent::exposedFormAlter($form, $form_state);

    $filter = $this->handler;
    // Form element is designated by the element ID which is user-
    // configurable.
    $field_id = $filter->options['is_grouped'] ? $filter->options['group_info']['identifier'] : $filter->options['expose']['identifier'];
    $input = $form_state->getUserInput();

    // Don't generate active filters if.
    if (empty($form[$field_id])
      || !$this->configuration['show_active_filters']
      || empty($input[$field_id])
      || $input[$field_id] == 'All'
    ) {
      return;
    }

    $items = [
      [
        'title' => $form[$field_id]['#title'] ?? $field_id,
        'value' => $input[$field_id],
      ],
    ];
    $this->addActiveFilter($field_id, $items, $form);
  }

}
