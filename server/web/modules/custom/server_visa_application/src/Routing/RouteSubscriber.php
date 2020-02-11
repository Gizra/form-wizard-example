<?php

namespace Drupal\server_visa_application\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   *
   * Make sure non-admins cannot view, edit or delete Visa applications nodes.
   * They have to go through the wizard to do so.
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route_names = [
      'node.add',
      'node.edit',
      'node.delete',
    ];
    foreach ($route_names as $route_name) {
      if ($route = $collection->get($route_name)) {
        $route->setRequirement('_custom_access', '\Drupal\server_visa_application\Controller\VisaApplicationForm::nodeAccess');
      }
    }

  }

}
