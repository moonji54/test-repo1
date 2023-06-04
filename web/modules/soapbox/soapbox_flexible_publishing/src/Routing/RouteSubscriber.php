<?php

namespace Drupal\soapbox_flexible_publishing\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('node.add_page')) {
      $route->setDefault('_controller', '\Drupal\soapbox_flexible_publishing\Controller\FlexiblePublishingNodeController::addPage');
    }
  }

}
