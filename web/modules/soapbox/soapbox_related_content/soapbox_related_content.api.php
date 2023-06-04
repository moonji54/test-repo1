<?php

/**
 * @file
 * Soapbox Featured Cards API documentation.
 */

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Perform alterations on the main automated content query.
 *
 * @param \Drupal\Core\Entity\Query\QueryInterface $query
 *   The entity query just before execution.
 */
function hook_soapbox_related_content_auto_query_references_alter(QueryInterface $query) {
  $query->sort('unified_date', 'DESC');
}

/**
 * Perform alterations on the fallback to same type only query.
 *
 * @param \Drupal\Core\Entity\Query\QueryInterface $query
 *   The entity query just before execution.
 */
function hook_soapbox_related_content_auto_query_same_type_alter(QueryInterface $query) {
  $query->sort('unified_date', 'DESC');
}

/**
 * Perform alterations on the fallback to content types only query.
 *
 * @param \Drupal\Core\Entity\Query\QueryInterface $query
 *   The entity query just before execution.
 */
function hook_soapbox_related_content_auto_query_types_alter(QueryInterface $query) {
  $query->sort('unified_date', 'DESC');
}

/**
 * Perform query alterations when checking for content by specific IDs by field.
 *
 * @param \Drupal\Core\Entity\Query\QueryInterface $query
 *   The entity query just before execution.
 */
function hook_soapbox_related_content_specific_ids_by_field_alter(QueryInterface $query) {
  $query->sort('unified_date', 'DESC');
}
