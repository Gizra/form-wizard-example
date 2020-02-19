<?php

namespace Drupal\server_visa_application;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\node\NodeInterface;

/**
 * Class VisaApplicationViewer.
 */
class VisaApplicationViewer implements VisaApplicationViewerInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new HighSchoolManager object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritDoc}
   */
  public function overview(NodeInterface $application_node, array $sections_status, array $teacher_evaluations_status = [], $due_date_past = FALSE) {

    if (!$application_node->field_user_picture->isEmpty()) {
      $file_uri = $application_node->get('field_user_picture')->entity->getFileUri();
      $user_picture = $this->entityTypeManager
        ->getStorage('image_style')
        ->load('medium')
        ->buildUrl($file_uri);
    }
    else {
      // A default user_picture.
      $module_path = $this->moduleHandler->getModule('server_visa_application')->getPath();
      $user_picture = file_create_url($module_path . '/images/default-avatar.png');
    }

    $build = [
      '#theme' => 'server_visa_application_overview',
      '#user' => $application_node->getOwner(),
      '#user_picture' => $user_picture,
      '#sections_status' => $sections_status,
      '#current_year' => date('Y'),
    ];

    $build['#cache']['contexts'] = [
      'user',
      'url',
    ];

    $build['#cache']['tags'] = [
      // We have several nodes that participates in the application form.
      'node_list',
    ];

    return $build;
  }

}
