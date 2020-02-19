<?php

namespace Drupal\server_visa_application\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\server_visa_application\VisaApplicationManagerInterface;
use Drupal\server_visa_application\VisaApplicationViewerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class VisaApplicationForm.
 */
class VisaApplicationForm extends ControllerBase {

  /**
   * The current user account.
   *
   * @var Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The language manager.
   *
   * @var Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Visa application manager.
   *
   * @var Drupal\server_visa_application\VisaApplicationManagerInterface
   */
  protected $visaApplicationManager;

  /**
   * Visa application viewer.
   *
   * @var Drupal\server_visa_application\VisaApplicationViewerInterface
   */
  protected $visaApplicationViewer;

  /**
   * Constructs a new VisaApplicationForm object.
   */
  public function __construct(
    AccountProxyInterface $account,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    FormBuilderInterface $form_builder,
    VisaApplicationManagerInterface $visa_application_manager,
    VisaApplicationViewerInterface $visa_application_viewer
  ) {
    $this->account = $account;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->visaApplicationManager = $visa_application_manager;
    $this->visaApplicationViewer = $visa_application_viewer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('server_visa_application.manager'),
      $container->get('server_visa_application.viewer')
    );
  }

  /**
   * Main entry point.
   *
   * @param int $section_number
   *   The section number.
   *
   * @return array
   *   Renderable array.
   */
  public function main(int $section_number) {
    $node = $this->visaApplicationManager->getApplicationNodeByUser($this->account);

    $form = $this->entityTypeManager
      ->getFormObject('node', 'section_' . $section_number)
      ->setEntity($node);

    $build = $this->formBuilder->getForm($form);

    return $build;
  }

  /**
   * Application overview point.
   *
   * @return array
   *   Renderable array.
   */
  public function overview() {
    if ($this->account->isAnonymous()) {
      return $this->visaApplicationViewer->overviewAnon();
    }

    $visa_application_node = $this->visaApplicationManager->getApplicationNodeByUser($this->account);
    $sections_status = $this->visaApplicationManager->getSectionsStatus($visa_application_node);

    return $this->visaApplicationViewer->overview(
      $visa_application_node,
      $sections_status
    );
  }

  /**
   * Checks access to Visa application form.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function applicationFormAccess(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::allowed();
    }

    try {
      $this->visaApplicationManager->getApplicationNodeByUser($account);
      return AccessResult::allowed();
    }
    catch (\Exception $e) {
      return AccessResult::forbidden();
    }
  }

  /**
   * Checks access for CRUD node operations.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\node\NodeInterface|null $node
   *   The node object.
   * @param string|null $node_type
   *   The node type name, in case of `node.add`.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function nodeAccess(AccountInterface $account, NodeInterface $node = NULL, string $node_type = NULL) {
    $admin_access = $account->hasPermission('administer nodes') ? AccessResult::allowed() : AccessResult::forbidden();
    if (empty($node) && !empty($node_type)) {
      // Only site admins or content editors should have access.
      return $admin_access;
    }

    return $node->access('view', $this->account, TRUE);
  }

}
