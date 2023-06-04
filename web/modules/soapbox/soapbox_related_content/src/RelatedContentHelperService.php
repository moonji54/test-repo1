<?php

namespace Drupal\soapbox_related_content;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Related contents helper service.
 */
class RelatedContentHelperService {

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Render\RendererInterface definition.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Allowed node types.
   *
   * @var array
   */
  protected $allowedTypes = [];

  /**
   * Manual selections.
   *
   * @var array
   */
  protected $manualSelections = [];

  /**
   * Determine if it is page content type.
   *
   * @var array
   */
  protected $pageContentType = FALSE;

  /**
   * Fields to check.
   *
   * This can be entity reference or taxonomy reference fields.
   *
   * @var array
   */
  protected $fieldsToCheck = [];

  /**
   * Other fields to check.
   *
   * This can be entity reference or taxonomy reference fields.
   *
   * @var array
   */
  protected $crossreferenceFieldsToCheck = [];

  /**
   * The field names to check for matches based on existing node references.
   *
   * @var array
   */
  protected $entityIdsToMatchByField = [];

  /**
   * Ids to exclude from the entities searched.
   *
   * @var array
   */
  protected $excludedIds = [];

  /**
   * View mode to render into.
   *
   * @var array
   */
  protected $viewMode = '';

  /**
   * Return limit.
   *
   * @var int
   */
  protected $limit = 4;

  /**
   * Store sort orders with default as unified date descending.
   *
   * @var \string[][]
   */
  protected $sortOrders = [
    [
      'field' => 'unified_date',
      'direction' => 'DESC',
    ],
  ];

  /**
   * Constructs a new RelatedContentHelperService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer
  ) {

    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * Add Ids of entities to get excluded.
   *
   * @param array $ids
   *   The additional IDs to exclude.
   */
  public function addExcludedIds(array $ids) {
    $this->excludedIds = array_merge($this->excludedIds, $ids);
    $this->excludedIds = array_unique($this->excludedIds);
  }

  /**
   * Set the allowed node types.
   *
   * @param array $allowed_types
   *   An array of machine names of node types.
   */
  public function setAllowedTargetNodeTypes(array $allowed_types) {

    $this->allowedTypes = $allowed_types;
  }

  /**
   * Set the field names to check for matches based on existing node references.
   *
   * This checks these fields on the current node and finds their references,
   * then uses those references to find related content.
   *
   * @param array $fields_to_check
   *   An array of machine names of fields.
   */
  public function setFieldsToCheck(array $fields_to_check) {

    $this->fieldsToCheck = $fields_to_check;
  }

  /**
   * Set the field names to check for matches based on existing node references.
   *
   * This checks these fields on the current node and finds their references,
   * then uses those references to find related content. This overwrites
   * if you have already added the field.
   *
   * @param string $field_name
   *   The field name on the potential related content that would reference
   *   these entity ids. Eg, 'field_authors' on a publication, when providing
   *   an array of author entity ids.
   * @param array $entity_ids
   *   An array of entity IDs.
   */
  public function setSpecificEntityIdsToMatchByField($field_name, array $entity_ids) {

    $this->entityIdsToMatchByField[$field_name] = $entity_ids;
  }

  /**
   * Set cross-reference fields to check.
   *
   * These are fields on other contents that may reference this content. For
   * instance, people that have a parent 'project', adding 'field_project' - a
   * field on the person entity - would find people where field_project matches
   * the current node ID.
   *
   * @param array $fields_to_check
   *   An array of machine names of fields.
   */
  public function setCrossReferenceFieldsToCheck(array $fields_to_check) {

    $this->crossreferenceFieldsToCheck = $fields_to_check;
  }

  /**
   * Set manual selections.
   *
   * @param array $nids
   *   An array of node nids.
   */
  public function setManualSelections(array $nids) {

    $this->manualSelections = $nids;
  }

  /**
   * Determine page content type.
   *
   * @param bool $page_content_type
   *   TRUE if content type is page.
   */
  public function setPageContentType($page_content_type) {

    $this->pageContentType = $page_content_type;
  }

  /**
   * Set the view mode to render.
   *
   * @param string $view_mode
   *   Machine name of the view mode to render.
   */
  public function setViewMode($view_mode) {

    $this->viewMode = $view_mode;
  }

  /**
   * Set the limit of results.
   *
   * @param int $limit
   *   The number of results to return.
   */
  public function setReturnLimit($limit) {

    $this->limit = $limit;
  }

  /**
   * Set the sort orders.
   *
   * @param array $sort_orders
   *   An array of sort orders with keys, field and direction.
   *   Eg.
   *
   * @code
   *   Array
   *   (
   *     Array
   *     (
   *       [field] => unified_date
   *       [direction] => DESC
   *     )
   *   )
   * @endcode
   */
  public function setSortOrders(array $sort_orders) {

    $this->sortOrders = $sort_orders;
  }

  /**
   * Handle preprocess Node for related content.
   *
   * @param array $variables
   *   Variables from hook_preprocess_node().
   *
   * @throws \Exception
   */
  public function preprocessNode(array &$variables) {

    // Only preprocess full view, otherwise it will be endless loop as we render
    // the nested related nodes.
    if ($variables['view_mode'] == 'full') {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $variables['node'];

      // Pass the variable back to the template. You can use this as follows:
      // {% for related_node in related_nodes %}
      // {{ related_node }}
      // {% endforeach %}.
      if ($related_nodes = $this->getRelatedNodes($node)) {
        $variables['related_nodes'] = $this->renderSelections($related_nodes);
      }
    }
  }

  /**
   * Get all related node entities.
   *
   * This includes nodes matching the same terms and other specified entity
   * references, as well as cross referenced nodes.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The current node being viewed.
   *
   * @return \Drupal\node\NodeInterface[]
   *   An array of related node entities.
   */
  public function getRelatedNodes(NodeInterface $node) {
    $related_nodes = [];

    // Get manually selected contents.
    if (!empty($this->manualSelections)) {
      $related_nodes = Node::loadMultiple($this->manualSelections);
    }

    // Determine related contents matching specific entity ids.
    if (!empty($this->entityIdsToMatchByField)) {
      $related_nodes = $this->getRelatedNodesMatchingSpecificIdsByField($node, $related_nodes);
    }

    // Determine related contents based on tags or other entity reference
    // targets of the current node.
    if ($this->limit && !empty($this->fieldsToCheck)) {
      $node_references = $this->getNodeReferences($node);
      $related_nodes = $this->getRelatedNodesMatchingNodeReferences($node, $node_references, $related_nodes);
    }

    // Get contents by allowed content types only.
    if ($this->limit && empty($this->fieldsToCheck) && !empty($this->allowedTypes)) {
      $related_nodes = $this->getRelatedNodesMatchingAllowedTypes($node, $related_nodes);
    }

    // Get contents that reference this content.
    if ($this->limit && !empty($this->crossreferenceFieldsToCheck)) {
      $related_nodes = $this->getCrossReferencedNodesForCurrentNode($node, $related_nodes);
    }

    // Limit (don't slice if -1, ie, unlimited).
    if ($related_nodes && count($related_nodes) > $this->limit && $this->limit > 0) {
      $related_nodes = array_slice($related_nodes, 0, $this->limit);
    }

    return $related_nodes;
  }

  /**
   * Get related nodes matching specific entity ids in specific fields.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The current node being viewed.
   * @param array $related_nodes
   *   An array of related nodes to add to.
   */
  protected function getRelatedNodesMatchingSpecificIdsByField(NodeInterface $node, array $related_nodes) {
    $query = $this->buildQueryForRelatedNodesMatchingSpecificIdsByField($node, $related_nodes);

    // Allow other modules to alter the query.
    \Drupal::moduleHandler()->alter('soapbox_related_content_specific_ids_by_field', $query);

    // Get results.
    $limit = $this->getLimit($related_nodes);
    if ($limit && $related_node_ids = $query->execute()) {
      $query->range(0, $limit);
      $storage = $this->entityTypeManager->getStorage('node');
      $found_nodes = $storage->loadMultiple($related_node_ids);
      $found_nodes = $this->orderRelatedNodesByMatches(
        $found_nodes,
        $this->entityIdsToMatchByField
      );
      if (!empty($related_nodes)) {
        return array_merge($related_nodes, $found_nodes);
      }
      return $found_nodes;
    }
    return [];
  }

  /**
   * Query for related nodes matching specific entity ids in specific fields.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The current node being viewed.
   * @param array $related_nodes
   *   An array of related nodes to add to.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query.
   */
  protected function buildQueryForRelatedNodesMatchingSpecificIdsByField(NodeInterface $node, array $related_nodes) {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery();
    $query->condition('status', Node::PUBLISHED);

    // Must be one of allowed content types.
    $query->condition('type', $this->allowedTypes, 'IN');

    // Must not be the current node or one we already have.
    $skip_nids = [
      $node->id(),
    ];
    if (!empty($related_nodes)) {
      foreach ($related_nodes as $related_node) {
        $skip_nids[] = $related_node->id();
      }
    }
    $this->addExcludedIds($skip_nids);

    $query->condition('nid', $this->excludedIds, 'NOT IN');

    // Filter by the specific entity ids for each field.
    foreach ($this->entityIdsToMatchByField as $field_name => $entity_ids) {
      $query->condition($field_name, $entity_ids, 'IN');
    }

    foreach ($this->sortOrders as $sort_order) {
      $query->sort($sort_order['field'], $sort_order['direction']);
    }
    return $query;
  }

  /**
   * Get the referenced ids.
   *
   * @param object $node
   *   The node entity.
   *
   * @return array
   *   The term ids in an associative array by field machine name.
   */
  protected function getNodeReferences($node) {

    // Build storage array for references.
    $node_references = [];
    foreach ($this->fieldsToCheck as $field_to_check) {
      $node_references[$field_to_check] = [];
    }

    // Loop through references.
    foreach (array_keys($node_references) as $field_machine_name) {
      if (!$node->hasField($field_machine_name)) {
        continue;
      }

      // Load the field values.
      $field = $node->get($field_machine_name);
      if ($field->count() && $references = $field->getValue()) {

        // Get the target ids from the value.
        foreach ($references as $reference) {
          if (!is_array($reference) || !isset($reference['target_id'])) {
            continue;
          }

          $node_references[$field_machine_name][] = $reference['target_id'];
        }
      }
    }

    return $node_references;
  }

  /**
   * Get related contents matching same fields.
   *
   * @param object $node
   *   The node entity.
   * @param array $node_references
   *   The array of reference entity ids from the original node.
   * @param array|null $related_nodes
   *   Any related nodes already found.
   *
   * @return array
   *   The node objects.
   *
   * @throws \Exception
   */
  protected function getRelatedNodesMatchingNodeReferences(
    $node,
    array $node_references,
    $related_nodes
  ) {

    $storage = $this->entityTypeManager->getStorage('node');

    // Get related node ids.
    $related_node_ids = [];
    foreach ($node_references as $field => $target_ids) {
      if (!$target_ids) {
        continue;
      }
      $query = $this->buildQueryForRelatedNodesMatchingNodeReferences('multiple_types', $node, $related_nodes, $field, $target_ids);

      // Get results.
      if ($limit = $this->getLimit($related_nodes)) {
        $query->range(0, $limit);
        $results = $query->execute();
        if ($results) {
          $related_node_ids = array_merge($related_node_ids, $results);
        }
      }
    }

    // If related node ids count is less than the limit, get more with
    // the same content type as this node.
    if (!$this->pageContentType && count($related_node_ids) < $this->limit || $this->limit == -1) {
      $exclusions = $related_node_ids;
      $exclusions[] = $node->id();
      $query = $this->buildQueryForRelatedNodesMatchingNodeReferences('same_type_only', $node, $related_nodes, $field, $target_ids);

      // Get results.
      if ($limit = $this->getLimit($related_node_ids)) {
        $query->range(0, $limit);
        $results = $query->execute();
        if ($results) {
          $related_node_ids = array_merge($related_node_ids, $results);
        }
      }
    }

    // If related node ids count is less than the limit, get more with
    // just content type restrictions.
    if (count($related_node_ids) < $this->limit || $this->limit == -1) {
      $exclusions = $related_node_ids;
      $exclusions[] = $node->id();
      $query = $this->buildQueryForRelatedNodesMatchingNodeReferences('ignore_fields', $node, $related_nodes);

      // Get results.
      if ($limit = $this->getLimit($related_nodes)) {
        $query->range(0, $limit);
        $results = $query->execute();
        if ($results) {
          $related_node_ids = array_merge($related_node_ids, $results);
        }
      }
    }

    if ($related_node_ids) {

      $found_nodes = $storage->loadMultiple($related_node_ids);
      $found_nodes = $this->orderRelatedNodesByMatches(
        $found_nodes,
        $node_references
      );
      if (!empty($related_nodes)) {
        return array_merge($related_nodes, $found_nodes);
      }
      return $found_nodes;
    }

    return $related_nodes;
  }

  /**
   * Query for related nodes matching specific entity ids in specific fields.
   *
   * @param string $type
   *   One of the following:
   *   - multiple_types: Most specific query for specific types where field
   *     match is the same.
   *   - same_type_only: Same as above, but where the node type is the same
   *     as the one being viewed.
   *   - ignore_fields: If we still haven't found anything, find content in the
   *     types from multiple_types, but where the field itself is ignored.
   *   - multiple_types_ignore_fields: Same as multiple_types but without the
   *     specific fields.
   * @param \Drupal\node\NodeInterface $node
   *   The current node being viewed.
   * @param array $related_nodes
   *   An array of related nodes to add to.
   * @param string|null $field
   *   The field name.
   * @param array|null $target_ids
   *   The target IDs.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query.
   */
  protected function buildQueryForRelatedNodesMatchingNodeReferences($type, NodeInterface $node, array $related_nodes, $field = NULL, array $target_ids = NULL) {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery();
    $query->condition('status', Node::PUBLISHED);

    // Must have at least 1 match.
    if (!in_array($type, ['ignore_fields', 'multiple_types_ignore_fields']) && $field && $target_ids) {
      $query->condition($field, $target_ids, 'IN');
    }

    // Must be one of allowed content types.
    if ($type == 'same_type_only') {
      $query->condition('type', $node->getType(), '=');
    }
    else {
      $query->condition('type', $this->allowedTypes, 'IN');
    }

    // Must not be the current node or one we already have.
    $skip_nids = [
      $node->id(),
    ];
    if (!empty($related_nodes)) {
      foreach ($related_nodes as $related_node) {
        $skip_nids[] = $related_node->id();
      }
    }

    $this->addExcludedIds($skip_nids);
    if (!empty($this->excludedIds)) {
      $query->condition('nid', $this->excludedIds, 'NOT IN');
    }

    foreach ($this->sortOrders as $sort_order) {
      $query->sort($sort_order['field'], $sort_order['direction']);
    }

    // Allow other modules to alter the query.
    switch ($type) {
      case 'multiple_types':
        \Drupal::moduleHandler()->alter('soapbox_related_content_auto_query_references', $query);
        break;

      case 'multiple_types_ignore_fields':
        \Drupal::moduleHandler()->alter('soapbox_related_content_auto_query_multiple_types_ignore_fields', $query);
        break;

      case 'same_type_only':
        \Drupal::moduleHandler()->alter('soapbox_related_content_auto_query_same_type', $query);
        break;

      case 'ignore_fields':
        \Drupal::moduleHandler()->alter('soapbox_related_content_auto_query_types', $query);
        break;
    }

    return $query;
  }

  /**
   * Get related contents matching allowed content types only.
   *
   * @param object $node
   *   The node.
   * @param array $related_nodes
   *   An array of related nodes.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRelatedNodesMatchingAllowedTypes($node, array $related_nodes) {
    $query = $this->buildQueryForRelatedNodesMatchingNodeReferences('multiple_types_ignore_fields', $node, $related_nodes);
    $storage = $this->entityTypeManager->getStorage('node');

    // Get results.
    if ($limit = $this->getLimit($related_nodes)) {
      $query->range(0, $limit);
      $results = $query->execute();
      if ($results) {
        $related_nodes = array_merge($related_nodes, $storage->loadMultiple($results));
      }
    }

    return $related_nodes;
  }

  /**
   * Get related contents tagged by the current node - Ex: person, partner.
   *
   * @param object $node
   *   The node entity.
   * @param array $related_nodes
   *   Any related nodes already found.
   *
   * @return array
   *   The node objects.
   *
   * @throws \Exception
   */
  protected function getCrossReferencedNodesForCurrentNode(
    $node,
    array $related_nodes
  ) {

    if (!empty($this->crossreferenceFieldsToCheck)) {
      $storage = $this->entityTypeManager->getStorage('node');

      // Get related node ids.
      $related_node_ids = [];

      $query = $storage->getQuery();
      $query->condition('status', Node::PUBLISHED);

      // The current node must be in the fields.
      foreach ($this->crossreferenceFieldsToCheck as $field) {
        $query->condition($field, [$node->id()], 'IN');
      }

      // Must be one of allowed content types.
      $query->condition('type', $this->allowedTypes, 'IN');

      // Must not be the current node or one we already have.
      $skip_nids = [
        $node->id(),
      ];
      if (!empty($related_nodes)) {
        foreach ($related_nodes as $related_node) {
          $skip_nids[] = $related_node->id();
        }
      }
      $this->addExcludedIds($skip_nids);

      $query->condition('nid', $this->excludedIds, 'NOT IN');

      foreach ($this->sortOrders as $sort_order) {
        $query->sort($sort_order['field'], $sort_order['direction']);
      }

      // Get results.
      if ($limit = $this->getLimit($related_nodes)) {
        $query->range(0, $limit);
        $results = $query->execute();
        if ($results) {
          $related_node_ids = array_merge($related_node_ids, $results);
        }

        if ($related_node_ids) {
          return array_merge($related_nodes,
            $storage->loadMultiple($related_node_ids));
        }
      }
    }

    return $related_nodes;
  }

  /**
   * Get the default query limit.
   *
   * @param array $related_nodes
   *   Existing related nodes.
   *
   * @return int
   *   The limit.
   */
  protected function getLimit(array $related_nodes): int {
    // Set an arbitrary limit to prevent related content comparisons from
    // loading too many nodes into memory for count comparison.
    // If the requested number of related contents is higher than the
    // arbitrary limit, set the limit to that.
    if ($this->limit == -1) {
      return 25;
    }
    elseif ($related_nodes) {
      return max(0, $this->limit - count($related_nodes));
    }
    else {
      return $this->limit;
    }
  }

  /**
   * Order related nodes by number of matches.
   *
   * @param array $related_nodes
   *   The array of related nodes.
   * @param array $node_references
   *   The array of node references.
   *
   * @return array
   *   The ordered matches.
   */
  protected function orderRelatedNodesByMatches(
    array $related_nodes,
    array $node_references
  ) {

    $node_results = [];
    $match_counts = [];
    if ($related_nodes) {
      foreach ($related_nodes as $related_node) {
        $match_count = $this->getMatchCount($related_node, $node_references);

        // Build nested array within match counts.
        if (!isset($match_counts[$match_count])) {
          $match_counts[$match_count] = [];
        }
        $match_counts[$match_count][] = $related_node;
      }
    }

    // Sort by match counts with highest count first.
    krsort($match_counts);

    // Flatten array in the new order.
    foreach ($match_counts as $match_count => $nodes) {
      foreach ($nodes as $node) {
        $node_results[] = $node;
      }
    }

    return $node_results;
  }

  /**
   * Get number of matches.
   *
   * @param object $related_node
   *   The node entity being compared to the original.
   * @param array $node_references
   *   The array of reference entity ids from the original node.
   *
   * @return int
   *   The number of matches.
   *
   * @throws \Exception
   */
  protected function getMatchCount($related_node, array $node_references) {

    $count = 0;
    foreach ($node_references as $field_machine_name => $target_ids) {
      if (!$related_node->hasField($field_machine_name)) {
        continue;
      }

      // Load the field values.
      $field = $related_node->get($field_machine_name);
      if ($field->count() && $references = $field->getValue()) {

        // Get the target ids from the value.
        foreach ($references as $reference) {
          if (!is_array($reference) || !isset($reference['target_id'])) {
            continue;
          }

          if (in_array($reference['target_id'], $target_ids)) {
            $count++;
          }
        }
      }
    }

    return $count;
  }

  /**
   * Run the drupal renderer service on the nodes with featured box view mode.
   *
   * @param array $nodes
   *   An array of nodes.
   *
   * @return array
   *   An array of rendered output.
   *
   * @throws \Exception
   */
  protected function renderSelections(array $nodes) {

    $outputs = [];
    if (!$nodes) {
      return $outputs;
    }

    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    foreach ($nodes as $node) {

      // Build the view object and render the node.
      $view = $view_builder->view($node, $this->viewMode);
      $outputs[] = $this->renderer->render($view);
    }

    return $outputs;
  }

}
