<?php

namespace Drupal\server_visa_application;

use Drupal\node\NodeInterface;

/**
 * Interface VisaApplicationViewerInterface.
 */
interface VisaApplicationViewerInterface {

  /**
   * Renders the Overview page.
   *
   * @param \Drupal\node\NodeInterface $application_node
   *   The application node.
   * @param array $sections_status
   *   The section status.
   *
   * @return array
   *   A renderable array.
   */
  public function overview(NodeInterface $application_node, array $sections_status);

  /**
   * Renders a info page for anonymous users.
   */
  public function overviewAnon();

}
