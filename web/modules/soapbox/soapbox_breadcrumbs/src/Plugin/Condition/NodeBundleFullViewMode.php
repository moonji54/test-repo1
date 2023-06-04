<?php

namespace Drupal\soapbox_breadcrumbs\Plugin\Condition;

use Drupal\node\NodeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Plugin\Condition\NodeType;

/**
 * Provides a 'Node Bundle View Mode Full' condition.
 *
 * @Condition(
 *   id = "node_bundle_view_mode_full",
 *   label = @Translation("Node Bundle View Mode Full"),
 *   context = {
 *     "node_bundle_view_mode_full" = @ContextDefinition("node_bundle_view_mode_full", label = @Translation("Node Bundle View Mode Full"))
 *   }
 * )
 */
class NodeBundleFullViewMode extends NodeType implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof NodeInterface) {
      return !empty($this->configuration['bundles'][$node->getType()]);
    }
    else {
      return FALSE;
    }
  }

}
