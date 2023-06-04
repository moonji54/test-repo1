<?php

namespace Drupal\soapbox_content_labels\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;

/**
 * Filter by content label term id within limited categories.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("content_label_taxonomy_index_tid")
 */
class ContentLabelTaxonomyFilter extends TaxonomyIndexTid {

  /**
   * {@inheritDoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['content_categories'] = ['default' => []];
    return $options;
  }

  /**
   * {@inheritDoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    // In the configuration of the field, let the site builder select
    // which categories to restrict to.
    if (!$form_state->get('exposed')) {
      $categories = $this->termStorage->loadTree('content_category');
      $options = [];
      foreach ($categories as $category) {
        $options[$category->tid] = $category->name;
      }
      $form['content_categories'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Content categories to show content labels from'),
        '#options' => $options,
        '#default_value' => $this->options['content_categories'],
        '#description' => $this->t('Applies only when using the content category as a filter'),
      ];
    }

    // In the exposed form, restrict the options to the content labels that
    // are within the selected categories. If no categories are selected,
    // allow the default values to remain.
    $categories = array_filter($this->options['content_categories']);
    if ($categories && $form_state->get('exposed')) {
      $allowed_content_labels = $this->getContentLabelsInCategories($categories);
      if ($allowed_content_labels) {
        $form['value']['#options'] = array_intersect_key($form['value']['#options'], $allowed_content_labels);
      }
    }
  }

  /**
   * Get the content labels that are in the selected categories only.
   *
   * @param array $categories
   *   The selected categories.
   *
   * @return array
   *   The allowed content labels.
   */
  protected function getContentLabelsInCategories(array $categories) {
    $query = $this->termStorage->getQuery();
    $query->condition('field_category', $categories, 'IN');
    return $query->execute() ?: [];
  }

}
