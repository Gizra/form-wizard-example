<?php

namespace Drupal\server_visa_application\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Set active theme on Visa application pages.
 */
class ThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route_names = [
      'server_visa_application.server_visa_application_overview',
      'server_visa_application.server_visa_application_form',
    ];

    return in_array($route_match->getRouteName(), $route_names);
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return 'theme_server';
  }

}
