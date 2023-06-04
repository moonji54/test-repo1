<?php

namespace Drupal\soapbox_filters\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\DefaultWidget;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Autocomplete with active filters widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "bef_soapbox_autocomplete_active_filters",
 *   label = @Translation("Autocomplete with Active Filters"),
 * )
 */
class AutocompleteWithActiveFilters extends DefaultWidget {

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
      || empty($input[$field_id])) {
      return;
    }

    if (is_array($input[$field_id]) && isset($input[$field_id][0]['target_id'])) {

      // When using pagination and filters are already applied, input values are
      // already ['target_id' => 123]. Normalise the values.
      $targets = [];
      foreach ($input[$field_id] as $target) {
        $targets[] = $target['target_id'];
      }
      $nodes = Node::loadMultiple($targets);
      foreach ($nodes as $node) {
        $items[] = [
          'title' => $node->label(),
          'value' => $node->label() . '(' . $node->id() . ')',
        ];
      }
    }
    else {

      // Input values is in tag style.
      $input_values = Tags::explode($input[$field_id]);

      foreach ($input_values as $input_value) {
        // Remove the final node id from the active filter label.
        $label_parts = explode('(', $input_value);
        if (count($label_parts) > 1) {
          array_pop($label_parts);
        }
        $label = implode('(', $label_parts);

        $items[] = [
          'title' => $label,
          'value' => $input_value,
        ];
      }
    }

    // If we have items, add to the list of active filters.
    if (!empty($items)) {
      $this->addActiveFilter($field_id, $items, $form);
    }
  }

}
