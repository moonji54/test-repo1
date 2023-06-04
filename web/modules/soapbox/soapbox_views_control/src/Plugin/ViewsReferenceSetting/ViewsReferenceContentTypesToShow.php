<?php

namespace Drupal\soapbox_views_control\Plugin\ViewsReferenceSetting;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\viewsreference\Plugin\ViewsReferenceSettingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The views reference setting "content types to limit results to" plugin.
 *
 * @ViewsReferenceSetting(
 *   id = "content_type_limiting",
 *   label = @Translation("Content types to limit results to"),
 *   default_value = "",
 * )
 */
class ViewsReferenceContentTypesToShow extends PluginBase implements ViewsReferenceSettingInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The node type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeTypeStorage;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->nodeTypeStorage = $container->get('entity_type.manager')
      ->getStorage('node_type');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function alterFormField(array &$form_field) {
    $form_field['#title'] = $this->t('Content types to limit results to');
    $form_field['#type'] = 'checkboxes';
    $form_field['#default_value'] = $form_field['#default_value'] ?: [];
    $form_field['#weight'] = 50;
    $form_field['#options'] = [];

    // Get the view from configuration if we don't have a View object.
    if ($this->configuration['view_name'] && $view = Views::getView($this->configuration['view_name'])) {
      if ($this->configuration['display_id'] && $view->setDisplay($this->configuration['display_id'])) {
        if ($handlers = $view->getDisplay()->getHandlers('filter')) {
          foreach ($handlers as $handler) {
            // Skip exposed fields, applies only to hidden content type
            // filters.
            if (!isset($handler->options['exposed']) || !$handler->options['exposed']) {
              $node_type_labels = $this->getNodeTypeLabels();
              if (!empty($handler->options['value']) && is_array($handler->options['value'])) {
                foreach ($handler->options['value'] as $type) {
                  // Check the type still exists.
                  if (isset($node_type_labels[$type])) {
                    $form_field['#options'][$type] = $node_type_labels[$type];
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterView(ViewExecutable $view, $content_types_to_limit_to) {
    $content_types_to_limit_to = array_values($content_types_to_limit_to);
    $content_types_to_limit_to = array_filter($content_types_to_limit_to);
    $content_types_to_limit_to = array_combine($content_types_to_limit_to, $content_types_to_limit_to);
    if (isset($view->element['#viewsreference']) && !empty($content_types_to_limit_to)) {
      // This requires that the hidden exposed filter is the first filter for
      // content types. I cannot find a way around it for now, but it seems
      // an edge case, so will live with it for the time being.
      // Essentially ideally the 'type'/'bundle' below would be dynamic.
      $field = 'type';
      // In case we have deal with the search api view,
      // let's use default bundle field name.
      if (strpos($view->storage->get('base_table'), 'search_api_index_') !== FALSE) {
        $field = 'bundle';
      }
      // If handler exists.
      if ($handler = $view->getDisplay()->getHandler('filter', $field)) {
        $handler->value = $content_types_to_limit_to;
      }
    }
  }

  /**
   * Get the node type labels.
   *
   * @return array
   *   Node type labels keyed by machine name.
   */
  protected function getNodeTypeLabels() {
    // Get list of node types in order to show labels instead of
    // machine names to the site editor.
    $node_types = $this->nodeTypeStorage->loadMultiple();
    $node_type_labels = [];
    foreach ($node_types as $node_type) {
      $node_type_labels[$node_type->id()] = $node_type->label();
    }
    return $node_type_labels;
  }

}
