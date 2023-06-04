<?php

namespace Drupal\soapbox_filters\Plugin\better_exposed_filters\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a common trait for working with active filters.
 */
trait BefActiveFilters {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'show_active_filters' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['show_active_filters'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show in active filters'),
      '#default_value' => !empty($this->configuration['show_active_filters']),
      '#description' => $this->t('Whether active filters should be shown for this field when selections are made'),
    ];

    return $form;
  }

  /**
   * Generate active filter item and add active filter section.
   *
   * @param string $field_id
   *   The field id.
   * @param array $items
   *   Array of actiove filters.
   * @param array $form
   *   View exposed form.
   * @param bool $multiple
   *   It will be used for generating for selector.
   */
  public function addActiveFilter($field_id, array $items, array &$form, $multiple = FALSE) {
    $active_filters = [];
    foreach ($items as $item) {
      $active_filters['active-filter-' . $field_id . '-' . $item['value']] = [
        '#type' => 'label',
        '#for' => $multiple
        ? str_replace('_', '-', "edit-{$field_id}-{$item['value']}")
        : str_replace('_', '-', "edit-{$field_id}"),
        '#target_field' => $field_id,
        '#target_value' => $item['value'],
        '#title' => $item['title'],
        '#attributes' => [
          'class' => [
            'c-active-filter',
            'js-active-filter',
          ],
        ],
      ];
    }

    if (empty($form['active_filters'])) {
      $form['active_filters'] = [
        '#type'   => 'fieldgroup',
        '#title'  => $this->t('Active filters'),
        '#weight' => 25,
      ];

      // Attach active filter JS to the form if auto submit setting
      // is disabled.
      $form['#attached']['library'][] = 'soapbox_filters/active-filters';
      $form['#attached']['drupalSettings']['activeFilters']['autoSubmit'] = FALSE;
    }

    $form['active_filters'] = array_merge_recursive($form['active_filters'], $active_filters);
  }

}
