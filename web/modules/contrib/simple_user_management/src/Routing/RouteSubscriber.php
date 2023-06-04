<?php

namespace Drupal\simple_user_management\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.admin_create')) {
      $requirements = $route->getRequirements();

      // Remove only core '_entity_create_access' which requires the
      // 'administer users' permission.
      if (isset($requirements['_entity_create_access'])) {
        unset($requirements['_entity_create_access']);
      }

      // Replace it with our 'create user accounts' permission.
      $route->setRequirements([
        '_permission' => 'create user accounts',
      ]);
    }
  }

}
