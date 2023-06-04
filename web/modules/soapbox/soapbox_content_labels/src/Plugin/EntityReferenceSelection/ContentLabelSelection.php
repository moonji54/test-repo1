<?php

namespace Drupal\soapbox_content_labels\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\node\NodeInterface;

/**
 * Provides specific access control for the taxonomy_term content labels.
 *
 * @EntityReferenceSelection(
 *   id = "content_labels:taxonomy_term",
 *   base_plugin_label = @Translation("Content Label Taxonomy Term selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "content_labels",
 *   weight = 1
 * )
 */
class ContentLabelSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {

    // Match the general behaviour of DefaultSelection but
    // always with a query (DefaultSelection exits early in some cases).
    $target_type = $this->getConfiguration()['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $options = [];
    if ($results = $query->execute()) {
      $entities = $this->entityTypeManager->getStorage($target_type)->loadMultiple($results);
      foreach ($entities as $entity_id => $entity) {
        $bundle = $entity->bundle();
        $options[$bundle][$entity_id] = Html::escape($this->entityRepository->getTranslationFromContext($entity)->label());
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Filter by content labels allowed for this node type.
    if ($node_type = $this->getNodeType()) {
      $query->condition('field_category.entity.field_allowed_content_types.target_id', $node_type);
    }

    // Only published terms.
    $query->condition('status', 1);
    return $query;
  }

  /**
   * Load current node type. How depends on which route we are on.
   *
   * @return string|null
   *   The node type.
   */
  protected function getNodeType() {

    // Load current node type. How depends on which route we are on.
    $route_match = \Drupal::routeMatch();
    $node_type = $route_match->getParameter('node_type');
    if (empty($node_type)) {
      $node_type = $route_match->getParameter('bundle');
    }
    if (empty($node_type)) {
      $node = $route_match->getParameter('node');
      if ($node && $node instanceof NodeInterface) {
        $node_type = $node->bundle();
      }
    }
    if (is_object($node_type)) {
      $node_type = $node_type->id();
    }
    return $node_type;
  }

}
