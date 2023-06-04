<?php

/**
 * @file
 * Soapbox Related Content API documentation.
 */

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Perform alterations on the main automated related content query.
 *
 * @param \Drupal\Core\Entity\Query\QueryInterface $query
 *   The entity query just before execution.
 */
function hook_soapbox_featured_cards_automated_selections_alter(QueryInterface $query, ParagraphInterface $paragraph) {
  $query->sort('field_condition', 'value');
}
