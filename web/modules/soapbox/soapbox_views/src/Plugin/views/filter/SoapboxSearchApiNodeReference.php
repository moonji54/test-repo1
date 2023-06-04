<?php

namespace Drupal\soapbox_views\Plugin\views\filter;

use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter by term id.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("soapbox_search_api_node")
 */
class SoapboxSearchApiNodeReference extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {

    // Get general base search api db table.
    $base_tables = $this->view->getBaseTables();
    $base_tables = array_keys($base_tables);
    $base_table = reset($base_tables);
    $base_table = str_replace('_index_', '_db_', $base_table);

    // Get field table for this field.
    $field_table = $this->getField();
    $field_table = str_replace('.', '_', $field_table);

    // Get distinct values from table.
    $query = \Drupal::database()->select($base_table . $field_table, 'f1');
    $query->addField('f1', 'value');
    $query->distinct();
    $values = $query->execute()->fetchCol();

    // Build values.
    $this->valueOptions = [];
    if ($values && $nodes = Node::loadMultiple($values)) {
      foreach ($nodes as $node) {
        $this->valueOptions[$node->id()] = $node->label();
      }
    }
    return $this->valueOptions;
  }

}
