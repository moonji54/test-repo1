<?php

namespace Drupal\unified_date;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\FieldConfigInterface;
use Drupal\node\NodeInterface;

/**
 * Unified date manager.
 */
class UnifiedDateManager {

  /**
   * Raw unified date settings config.
   *
   * @var array
   */
  protected $configRawData = [];

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * UnifiedDateManager constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityFieldManagerInterface $entity_field_manager,
    ModuleHandlerInterface $module_handler
  ) {
    $this->configRawData = $config_factory->get('unified_date.settings')->getRawData();
    $this->entityFieldManager = $entity_field_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Get node unified date.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getUnifiedDate(NodeInterface $node) {

    $node_type = $node->getType();

    // Field where date is stored.
    $field = $this->configRawData[$node_type] ?? 'base-field:created';

    // Strip base-field prefix.
    $field = str_replace('base-field:', '', $field);

    // Determine date.
    $date = NULL;
    if ($node->hasField($field)) {
      $date = $node->get($field)->value;
      if (!is_numeric($date) && !is_null($date)) {
        $date = strtotime($date);
      }
    }

    // Set Default to created time if unified date is empty.
    if (!$date) {
      $date = $node->getCreatedTime();
    }

    // Let other modules to alter date value.
    $this->moduleHandler->alter('unified_date', $date, $node);

    return $date;
  }

  /**
   * Set node unified date.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setNodeUnifiedDate(NodeInterface $node) {
    $node->unified_date = $this->getUnifiedDate($node);
    $node->save();
  }

  /**
   * Get node fields with date type.
   *
   * @param string $node_type
   *   The node type.
   *
   * @return mixed
   *   The array of date field keys.
   */
  public function getNodeDateFields($node_type) {
    // Get all field attached to a particular node type.
    $fields = array_map(
      function ($field_definition) {
        // For config based field, which not a base entity field.
        if ($field_definition instanceof FieldConfigInterface
          && in_array($field_definition->getType(), ['datetime', 'timestamp'])) {
          return $field_definition->getName();
        }

        // For entity base fields.
        if ($field_definition instanceof BaseFieldDefinition) {
          if (in_array($field_definition->getName(), ['created', 'changed', 'published_at'])) {
            return 'base-field:' . $field_definition->getName();
          }
        }

        return FALSE;
      },
      $this->entityFieldManager->getFieldDefinitions('node', $node_type)
    );
    $fields = array_values(array_filter($fields));
    return array_combine($fields, $fields);
  }

}
