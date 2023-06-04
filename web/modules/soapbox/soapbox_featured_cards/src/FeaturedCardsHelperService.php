<?php

namespace Drupal\soapbox_featured_cards;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * A class to help set up automated and manual featured cards.
 */
class FeaturedCardsHelperService {

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Render\RendererInterface definition.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * Drupal\paragraphs\Entity\Paragraph definition.
   *
   * @var Drupal\paragraphs\Entity\Paragraph
   */
  protected Paragraph $paragraph;

  /**
   * The image toggle field.
   *
   * Contains the toggle field to say whether
   * to the featured box needs to show/hide images.
   *
   * @var string
   */
  protected string $imageToggleField;

  /**
   * The manual selection type.
   *
   * This indicates whether the manual selection is
   * added through sub paragraphs or just directly as node references.
   *
   * @var string
   */
  protected string $manualSelectionType;

  /**
   * The field name used for manual selections.
   *
   * @var string
   */
  protected string $fieldManual;

  /**
   * Field name used for node selections through manual paragraphs.
   *
   * @var string
   */
  protected string $paragraphNodeSelectionField;

  /**
   * Field name used for page builder components.
   *
   * @var string
   */
  protected string $pageBuilderField;

  /**
   * View modes available for rendering featured cards.
   *
   * @var array
   */
  protected array $viewModes;

  /**
   * The field name to indicated allowed node types in automated contents.
   *
   * @var string
   */
  protected string $typesField;

  /**
   * The fields to indicated allowed taxonomy filters in automated contents.
   *
   * @var array
   */
  protected array $taxonomyFields;

  /**
   * The field name to indicated the limit in automated contents.
   *
   * @var string
   */
  protected string $quantityField;

  /**
   * The field names to indicated the sorting in automated contents.
   *
   * @var array
   */
  protected array $sortFields;

  /**
   * The field name to indicated the sorting order in automated contents.
   *
   * @var string
   */
  protected $sortOrder;

  /**
   * Date filter.
   *
   * Set if date filter should be applied when populating automated contents.
   * i.e upcoming/past/both.
   *
   * @var string
   */
  protected $dateFilter;

  /**
   * The date fields to check the content's recency by.
   *
   * @var array
   */
  protected $dateFields;

  /**
   * Field controlling whether to use AND or OR.
   *
   * The field name to indicated if taxonomy condition AND or OR to be used in
   * automated contents.
   *
   * @var string
   */
  protected $fieldAndTaxonomyCondition;

  /**
   * Allowed types for automated nodes.
   *
   * @var array
   */
  protected $types;

  /**
   * Constructs a new FeaturedCardsHelperService object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer
  ) {

    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * The image toggle field.
   *
   * @param string $field
   *   The image toggle field machine name.
   */
  public function setImageToggleField(string $field = 'field_with_image'): void {
    $this->imageToggleField = $field;
  }

  /**
   * The page builder field.
   *
   * @param string $field
   *   The paragraph reference revisions page builder field.
   */
  public function setPageBuilderField(string $field = 'field_page_builder'): void {
    $this->pageBuilderField = $field;
  }

  /**
   * Set the view modes.
   *
   * @param string[] $view_modes
   *   The view modes.
   */
  public function setViewModes(array $view_modes = [
    'card' => 'card',
    'card_with_image' => 'card_with_image',
  ]): void {
    $this->viewModes = $view_modes;
  }

  /**
   * Set the paragraph being worked on.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   */
  public function setParagraph(ParagraphInterface $paragraph): void {
    $this->paragraph = $paragraph;
  }

  /**
   * Set the allowed types.
   *
   * @param array $types
   *   The allowed types.
   */
  public function setAllowedTypes(array $types = []): void {
    if ($types) {
      $this->types = $types;
    }
    elseif ($this->typesField && $this->paragraph instanceof ParagraphInterface && $this->paragraph->hasField($this->typesField)) {
      foreach ($this->paragraph->get($this->typesField) as $type) {
        $this->types[] = $type->value;
      }
    }
  }

  /**
   * Set the manual selection fields.
   *
   * @param string $type
   *   The type.
   * @param string $manual_field
   *   The field machine name.
   * @param string $paragraph_node_selection_field
   *   The entity reference field machine name.
   */
  public function setManualSelectionFields($type = 'node', $manual_field = 'field_manual_selection', $paragraph_node_selection_field = ''): void {
    $this->manualSelectionType = $type;
    $this->fieldManual = $manual_field;
    if ($type !== 'node') {
      $this->paragraphNodeSelectionField = $paragraph_node_selection_field;
    }
  }

  /**
   * Set automated settings.
   *
   * @param string $types_field
   *   The field controlling the types.
   * @param string[] $taxonomy_fields
   *   Taxonomy reference fields.
   * @param string $quantity_field
   *   Quantity field.
   * @param array $sort_fields
   *   Sorting field.
   * @param string $date_filter
   *   Date filter field.
   * @param array $date_fields
   *   Date filter array of fields.
   * @param string $field_and_taxonomy_condition
   *   Field onditions.
   */
  public function setAutomatedSettings(
    string $types_field = 'field_content_types',
    array $taxonomy_fields = [
      'field_type_of_tax',
      'field_key_themes',
    ],
    string $quantity_field = 'field_quantity',
    array $sort_fields = ['unified_date'],
    string $date_filter = '',
    array $date_fields = [],
    string $field_and_taxonomy_condition = 'field_and_taxonomy_condition'
  ): void {
    $this->typesField = $types_field;
    $this->taxonomyFields = $taxonomy_fields;
    $this->quantityField = $quantity_field;
    $this->sortFields = $sort_fields;
    $this->dateFilter = $date_filter;
    $this->dateFields = $date_fields;
    $this->fieldAndTaxonomyCondition = $field_and_taxonomy_condition;
  }

  /**
   * Handle preprocess Paragraph for featured cards.
   *
   * @param array $variables
   *   Variables from hook_preprocess_paragraph().
   */
  public function preprocessFeaturedCards(array &$variables): void {
    $this->paragraph = $variables['paragraph'];

    // Prepare automated featured cards.
    $storage = $this->entityTypeManager->getStorage('node');
    $node_ids_excluded = $this->getExclusions($variables['paragraph']);
    $node_ids_automated = $this->getAutomatedSelections($variables['paragraph'],
      $node_ids_excluded);

    if ($this->imageToggleField) {
      // Check if layout is with image.
      $with_image = $variables['paragraph']->get($this->imageToggleField)->value;
    }
    else {
      $with_image = FALSE;
    }

    if ($this->manualSelectionType === 'node') {
      $node_ids_selected = $this->getSelections($variables['paragraph']);

      if ($node_ids_automated) {
        $node_ids_automated = $node_ids_automated['node_ids'];
        $node_ids = array_merge($node_ids_selected, $node_ids_automated);
      }
      else {
        $node_ids = $node_ids_selected;
      }
      $nodes = $storage->loadMultiple($node_ids);
      $variables['featured_cards'] = $this->renderFeaturedCards(['all_nodes' => $nodes], $with_image);
    }
    else {
      if ($node_ids_automated) {
        $nodes = $storage->loadMultiple($node_ids_automated['node_ids']);
        $variables['featured_cards'] = $this->renderFeaturedCards([
          'automated_nodes' => $nodes,
          'add_manual' => TRUE,
        ], $with_image);
      }
      else {
        $variables['featured_cards'] = $this->renderFeaturedCards([
          'add_manual' => TRUE,
        ], $with_image);
      }

    }
  }

  /**
   * Get combined selected and node nids already selected by the site editor.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph configuration.
   *
   * @return array
   *   An array of NIDs.
   */
  protected function getSelections(ParagraphInterface $paragraph): array {
    $selected = [];
    if ($manual_items = $paragraph->get($this->fieldManual)) {

      foreach ($manual_items as $featured_card) {
        $target_id = '';
        if ($this->manualSelectionType === 'node') {
          $target_id = $featured_card->get('entity')->getTargetIdentifier();
        }
        else {
          if ($featured_card->entity->hasField($this->paragraphNodeSelectionField)) {
            $target_id = $featured_card->entity->get($this->paragraphNodeSelectionField)->target_id;

          }
        }
        if ($target_id) {
          $selected[] = $target_id;
        }
      }
    }

    return $selected;
  }

  /**
   * Get node nids already selected by the site editor.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph configuration.
   *
   * @return array
   *   An array of NIDs.
   */
  public function getExclusions(ParagraphInterface $paragraph): array {

    $exclusions = $components = [];
    $node = $paragraph->getParentEntity();
    if (!$node) {
      return $exclusions;
    }

    if ($node->hasField($this->pageBuilderField)) {
      $components = $node->get($this->pageBuilderField);
    }
    if ($components) {
      $found_current = FALSE;
      foreach ($components as $component) {

        // Only consider items before self as pre-selections.
        if ($component->entity->id() == $paragraph->id()) {
          $found_current = TRUE;
        }

        // If the bundle is featured cards, get the selections.
        if (!$found_current && $component->entity->bundle() == $this->paragraph->bundle()) {
          $exclusions = $this->getPreSelectionsFromFeaturedCardsParagraph($exclusions,
            $component->entity);
        }
      }
    }

    // Add self.
    $exclusions = $this->getPreSelectionsFromFeaturedCardsParagraph($exclusions,
      $paragraph);

    return $exclusions;
  }

  /**
   * Get the pre selections prior to automated items.
   *
   * @param array $exclusions
   *   The exclusion node IDs.
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   *
   * @return array
   *   The exclusion node IDs.
   */
  protected function getPreSelectionsFromFeaturedCardsParagraph(
    array $exclusions,
    ParagraphInterface $paragraph
  ): array {

    $selections = $this->getSelections($paragraph);
    if ($selections) {
      $exclusions = array_merge($exclusions, $selections);
    }
    return (array) $exclusions;
  }

  /**
   * Get automatic selections based on paragraph settings.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph configuration.
   * @param array $exclusions
   *   The NIDs to be excluded.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface[]
   *   An array of automated selections or false.
   */
  public function getAutomatedSelections(ParagraphInterface $paragraph, array $exclusions) {
    // Use drupal_static to prevent duplicated cards.
    $nodes = &drupal_static(__FUNCTION__);
    $previous_exclusions = [];
    if ($nodes) {
      $previous_exclusions = array_merge($nodes['node_ids'], $nodes['previous_exclusions']);
      $exclusions = array_merge($exclusions, $previous_exclusions);
    }
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery();

    // Add current node id to exclusions.
    $parent_entity = $paragraph->getParentEntity();
    if ($parent_entity) {
      $exclusions[] = $parent_entity->id();
    }

    // Maybe exclude node ids we already have rendered on the page.
    if ($exclusions) {
      $query->condition('nid', $exclusions, 'NOT IN');
    }

    // Filter by node types.
    if ($this->types) {
      $query->condition('type', $this->types, 'IN');
    }

    // Date filter.
    // Upcoming/past contents only.
    $this->sortOrder = 'DESC';
    if ($this->dateFilter === 'upcoming') {
      $this->sortOrder = 'ASC';
      $date_condition = '>';
    }
    elseif ($this->dateFilter === 'past') {
      $date_condition = '<';
    }
    if (isset($date_condition)) {
      $now = new DrupalDateTime('now');
      $date_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
      $date_to_compare = $now->format($date_format);
      if (empty($this->dateFields)) {
        $query->condition('unified_date', $date_to_compare, $date_condition);
      }
      elseif (is_string($this->dateFields)) {
        $query->condition($this->dateFields, $date_to_compare, $date_condition);
      }
      else {
        // Array date fields.
        $types = [];
        foreach ($this->dateFields as $type => $field) {
          if ($this->types && in_array($type, $this->types)) {
            $or_group = $query->orConditionGroup()
              ->condition($field, $date_to_compare, $date_condition);
            $types[] = $type;
          }
        }

        if ($types) {
          $other_type_or_group = $query->orConditionGroup()
            ->condition('type', $types, 'NOT IN')
            ->condition($or_group);

          $query->condition($other_type_or_group);
        }
      }
    }

    // Taxonomy filters.
    $use_and = $paragraph->hasField($this->fieldAndTaxonomyCondition) ? $paragraph->get($this->fieldAndTaxonomyCondition) : FALSE;
    foreach ($this->taxonomyFields as $taxonomy_field) {
      $term_ids = [];

      // Filter by tags.
      if ($paragraph->hasField($taxonomy_field)) {
        foreach ($paragraph->get($taxonomy_field) as $term) {
          if ($term->entity) {
            $term_ids[] = $term->entity->id();
          }
        }
      }

      if ($term_ids) {
        // If 'and' use and relation.
        if ($use_and && $use_and->value) {
          foreach ($term_ids as $term_id) {
            $group = $query->andConditionGroup();
            $group->condition($taxonomy_field, [$term_id], 'IN');
            $query->condition($group);
          }
        }
        else {
          // Else use an 'OR" relation which is default.
          $query->condition($taxonomy_field, $term_ids, 'IN');
        }
      }
    }

    // Only published nodes.
    $query->condition('status', 1);

    // Sort by sort fields.
    foreach ($this->sortFields as $sort_field) {
      // If it's a date field sort byt the order as set above.
      if (stripos($sort_field, 'date') !== FALSE || stripos($sort_field, 'time') !== FALSE) {
        $query->sort($sort_field, $this->sortOrder);
      }
      // If it's a name/title field sort alphabetically in ASC order.
      elseif (stripos($sort_field, 'title') !== FALSE || stripos($sort_field, 'name') !== FALSE) {
        $query->sort($sort_field, 'ASC');
      }
      else {
        // Otherwise sort by desc for most recency.
        $query->sort($sort_field, 'DESC');
      }
    }

    // Allow other modules to alter the query.
    \Drupal::moduleHandler()
      ->alter('soapbox_featured_cards_automated_selections', $query, $paragraph);

    // Limit.
    $query->range(0, $paragraph->get($this->quantityField)->value);
    $node_ids = $query->execute();

    // If we have results, load the objects in a single query.
    if ($node_ids) {
      // Return node ids and previous exclusions.
      $nodes = [
        'node_ids' => $node_ids,
        'previous_exclusions' => $previous_exclusions,
      ];
      return $nodes;
    }

    return FALSE;
  }

  /**
   * Run the drupal renderer service on the nodes with featured card view mode.
   *
   * @param array $nodes
   *   An array of nodes.
   * @param bool $with_image
   *   Boolean value.
   *
   * @return array
   *   An array of rendered output.
   *
   * @throws \Exception
   */
  protected function renderFeaturedCards(array $nodes, bool $with_image): array {

    $outputs = [];
    if (!$nodes) {
      return $outputs;
    }
    // Get view_mode value.
    $view_mode = $with_image ? $this->viewModes['card_with_image'] : $this->viewModes['card'];

    if (!empty($nodes['all_nodes'])) {
      $outputs = $this->renderNodes($nodes['all_nodes'], $with_image);
    }

    if (!empty($nodes['add_manual'])) {
      // Render manual selections.
      if ($this->paragraph->hasField($this->fieldManual) && $contents = $this->paragraph->get($this->fieldManual)) {
        foreach ($contents as $referenced_entity) {
          // Manually selected node.
          if ($referenced_entity->entity->hasField($this->paragraphNodeSelectionField)) {
            if ($node = $referenced_entity->entity->get($this->paragraphNodeSelectionField)->entity) {
              $view_builder = $this->entityTypeManager->getViewBuilder('node');

              // Build the view object and render the node.
              $view = $view_builder->view($node, $view_mode);
            }
          }
          else {
            // Manual paragraph.
            $view_builder = $this->entityTypeManager->getViewBuilder('paragraph');
            // Build the view object and render the paragraph.
            $view = $view_builder->view($referenced_entity->entity);
          }

          $outputs[] = $this->renderer->render($view);

        }
      }
    }

    if (!empty($nodes['automated_nodes'])) {
      $auto_outputs = $this->renderNodes($nodes['automated_nodes'], $with_image);
      $outputs = array_merge($outputs, $auto_outputs);
    }

    return $outputs;
  }

  /**
   * Render the nodes.
   *
   * @param array $nodes
   *   An array of nodes.
   * @param bool $with_image
   *   Boolean value.
   *
   * @return array
   *   An array of rendered output.
   */
  protected function renderNodes(array $nodes, bool $with_image):array {
    $outputs = [];
    if (!$nodes) {
      return $outputs;
    }
    $view_mode = $with_image ? $this->viewModes['card_with_image'] : $this->viewModes['card'];
    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    foreach ($nodes as $node) {
      // Build the view object and render the node.
      $view = $view_builder->view($node, $view_mode);
      $outputs[] = $this->renderer->render($view);
    }

    return $outputs;

  }

}
