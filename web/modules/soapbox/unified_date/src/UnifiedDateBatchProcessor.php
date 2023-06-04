<?php

namespace Drupal\unified_date;

use Drupal\node\Entity\Node;

/**
 * Unified date batch processor.
 */
class UnifiedDateBatchProcessor {

  /**
   * Process callback for the batch set BulkUpdateForm.
   *
   * @param array $settings
   *   The settings from the export form.
   * @param array $context
   *   The batch context.
   */
  public function processBatch(array $settings, array &$context) {
    if (empty($context['sandbox'])) {

      // Store data in results for batch finish.
      $context['results']['data']     = [];
      $context['results']['settings'] = $settings;

      // Set initial batch progress.
      $context['sandbox']['settings']   = $settings;
      $context['sandbox']['progress']   = 0;
      $context['sandbox']['current_id'] = 0;
      $context['sandbox']['max']        = $this->getMax($settings);

    }
    else {
      $settings = $context['sandbox']['settings'];
    }
    if ($context['sandbox']['max'] == 0) {

      // If we have no rows to export, immediately finish.
      $context['finished'] = 1;

    }
    else {

      // Get the next batch worth of data.
      $this->setNodeDates($settings, $context);

      // Check if we are now finished.
      if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      }

    }

  }

  /**
   * Get the submissions for the given contact form.
   *
   * @param array $settings
   *   The settings from the export form.
   * @param array $context
   *   The batch context.
   */
  private function setNodeDates(array $settings, array &$context) {
    $limit = 5;
    $query = \Drupal::entityQuery('node');
    $query->condition('nid', $context['sandbox']['current_id'], '>');
    $query->condition('type', $settings['node_types'], 'IN');
    $query->range(0, $limit);
    $query->sort('nid', 'ASC');
    $nids = $query->execute();
    if ($nids) {
      $unified_date_manager = \Drupal::service('unified_date.manager');
      if ($nodes = Node::loadMultiple($nids)) {
        foreach ($nodes as $node) {
          $unified_date_manager->setNodeUnifiedDate($node);
          $context['sandbox']['current_id'] = $node->id();
          $context['sandbox']['progress']++;
          $context['results']['updates']++;
        }
      }
    }
  }

  /**
   * Get max amount of nodes to update.
   *
   * @param array $settings
   *   The settings from the export form.
   *
   * @return int
   *   The maximum number of messages to export.
   */
  private function getMax(array $settings) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', $settings['node_types'], 'IN');
    $query->count();
    $result = $query->execute();
    return ($result ? $result : 0);
  }

}
