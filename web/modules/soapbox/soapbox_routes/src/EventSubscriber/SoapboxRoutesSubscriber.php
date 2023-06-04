<?php

namespace Drupal\soapbox_routes\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Soapbox Routes event subscriber.
 */
class SoapboxRoutesSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $convert_to_admin_routes = [
      'user.login',
      'user.pass',
      'user.pass.http',
      'user.page',
      'user.logout',
      'user.reset',
      'user.register',
      'user.reset.login',
      'user.reset.form',
      'user.well-known.change_password',
      'entity.user.canonical',
      'tfa.entry',
      'tfa.login',
    ];
    foreach ($convert_to_admin_routes as $route_name) {
      if ($route = $collection->get($route_name)) {

        // Change the path to use the admin theme instead. This requires that
        // the user has permission to view the administration theme.
        $route->setOption('_admin_route', TRUE);
      }
    }
  }

}
