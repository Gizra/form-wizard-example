<?php

namespace Drupal\server_visa_application;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\server_visa_application\Form\VisaApplicationNodeInlineForm;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class VisaApplicationManager.
 */
class VisaApplicationManager implements VisaApplicationManagerInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * ContainerAwareInterface definition.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerAwareInterface
   */
  protected $entityQuery;

  /**
   * Constructs a new HighSchoolManager object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ContainerAwareInterface $entity_query) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritDoc}
   */
  public function getApplicationNodeByUser(AccountInterface $user) {
    if (!$user->id()) {
      throw new VisaApplicationException('Visa application belong only to authenticated users.');
    }

    /** @var \Drupal\node\NodeInterface $node */
    $nid = $this->getExistingApplicationNodeByUser($user);

    if (!empty($nid)) {
      return $this->entityTypeManager->getStorage('node')->load($nid);
    }

    $values = ['type' => 'visa_application'];
    /** @var \Drupal\node\NodeInterface $new_node */
    $new_node = $this->entityTypeManager
      ->getStorage('node')
      ->create($values);

    // Set the author.
    $full_user = $this->entityTypeManager->getStorage('user')->load($user->id());
    $new_node->setOwner($full_user);
    $new_node->save();

    return $new_node;
  }

  /**
   * Get an existing application.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The User to query.
   *
   * @return mixed
   *   The existing application node, or FALSE if none found.
   */
  protected function getExistingApplicationNodeByUser(AccountInterface $user) {
    $result = $this->entityQuery->get('node')
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('type', 'visa_application')
      ->condition('uid', $user->id())
      ->range(0, 1)
      ->execute();

    return !empty($result) ? reset($result) : FALSE;
  }

  /**
   * Retrieves the list to validate for a specific step.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity to validate.
   * @param string $form_mode
   *   The form display mode.
   *
   * @return array
   *   List of field names.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFauxRequiredFieldsToValidate(NodeInterface $node, $form_mode) {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->entityTypeManager->getStorage('entity_form_display')
      ->load("node." . $node->bundle() . ".$form_mode");
    $fields_name_in_form_mode = array_keys($form_display->getComponents());

    $field_definitions = [];

    foreach ($this->entityFieldManager->getFieldDefinitions('node', $node->bundle()) as $field_name => $field_definition) {
      if ($field_definition instanceof ConfigEntityInterface
        // @todo: Is there a better way to get only "real" configurable fields?
        && $field_name != 'promote'
      ) {
        $field_definitions[$field_name] = $field_definition;
      }
    }

    $field_names_to_validate = array_intersect(array_keys($field_definitions), $fields_name_in_form_mode);

    $field_names_to_validate_that_are_faux_required = [];
    foreach ($field_names_to_validate as $field_name) {
      $field_definition = $field_definitions[$field_name];
      if ($field_definition->getThirdPartySetting('server_visa_application', 'faux_required')) {
        $field_names_to_validate_that_are_faux_required[$field_name] = $field_name;
      }
    }

     return $field_names_to_validate_that_are_faux_required;
  }

  /**
   * Computes the section status based on the field statuses.
   *
   * @param array $fields_status
   *   The statuses of the fields.
   *
   * @return int
   *   The section status constant.
   */
  protected function fieldsStatusToSectionStatus(array $fields_status) {
    $is_filled = FALSE;
    $is_complete = TRUE;

    foreach ($fields_status as $status) {
      if (empty($status)) {
        // At least one field is not filled.
        $is_complete = FALSE;
      }
      else {
        // At least one field is filled.
        $is_filled = TRUE;
      }
    }

    if (!$is_filled) {
      return self::SECTION_NOT_FILLED;
    }

    if ($is_complete) {
      return self::SECTION_COMPLETE;
    }

    return self::SECTION_PARTIAL;
  }

  /**
   * {@inheritDoc}
   */
  public function getSectionStatus(NodeInterface $application_node, int $section_number) {
    if ($application_node->getType() != 'visa_application') {
      throw new VisaApplicationException('Not a Visa application node');
    }

    // If application is not `New`, then everything should be locked.
    if ($application_node->field_application_status->value != self::APPLICATION_NEW) {
      return self::SECTION_LOCKED;
    }

    $fields_status = [];
    $nested_form_status = [];
    foreach ($this->getFauxRequiredFieldsToValidate($application_node, 'section_' . $section_number) as $field_name) {

      $is_empty = $application_node->{$field_name}->isEmpty();

      if ($application_node->get($field_name)->getFieldDefinition()->getType() == 'entity_reference') {
        $nested_form_status[] = $this->getReferenceStatus($application_node, $field_name);
      }

      if ($field_name == 'field_address'
        && isset($application_node->field_address)
        && empty($application_node->field_address->address_line1)
        && empty($application_node->field_address->address_line2)
        && empty($application_node->field_address->postal_code)) {
        // Country code might be populated by code, but then, it's still empty.
        $is_empty = TRUE;
      }

      $fields_status[$field_name] = !$is_empty;
    }

    // Allow specific sections logic to be altered. This is used for example
    // when we have an "Other" option, which shows a text area to fill the
    // information. That text area should be required only if "Other" was
    // selected.
    $methodName = 'fieldStatusSection' . $section_number . 'Alter';
    if (method_exists($this, $methodName)) {
      $fields_status_to_pass = $this->{$methodName}($fields_status, $application_node);
    }
    else {
      $fields_status_to_pass = $fields_status;
    }

    $main_status = $this->fieldsStatusToSectionStatus($fields_status_to_pass, $application_node);

    // Fuse the status of the main fields and the fields in sub-nodes.
    if ($main_status == self::SECTION_NOT_FILLED) {
      // If main status is not filled, we can still have things in the sub-nodes
      // so indicate that with partial.
      if (in_array(self::SECTION_PARTIAL, $nested_form_status) ||
        in_array(self::SECTION_COMPLETE, $nested_form_status)) {

        return self::SECTION_PARTIAL;
      }
    }
    if ($main_status == self::SECTION_COMPLETE) {
      // Upon complete main node, others should be complete as well, otherwise
      // it's only partial.
      foreach ($nested_form_status as $form_status) {
        if ($form_status != self::SECTION_COMPLETE) {
          return self::SECTION_PARTIAL;
        }
      }
    }

    return $main_status;
  }

  /**
   * Fields status for section 2 alter; Unset "Other" fields.
   *
   * @param array $fields_status
   *   The fields status array.
   * @param \Drupal\node\NodeInterface $application_node
   *   The application node.
   *
   * @return array
   *   The altered fields status array.
   */
  private function fieldStatusSection2Alter(array $fields_status, NodeInterface $application_node) {
    if ($application_node->field_travel_purpose->value != 'other') {
      unset($fields_status['field_travel_purpose_other']);
    }

    return $fields_status;
  }

  /**
   * {@inheritDoc}
   */
  public function getSectionsStatus(NodeInterface $application_node) {
    $return = [];
    foreach (range(1, self::CONFIRMATION_SECTION_NUMBER) as $section_number) {
      $return[$section_number] = $this->getSectionStatus($application_node, $section_number);
    }

    return $return;
  }

  /**
   * {@inheritDoc}
   */
  public function applicationCanBeSigned(NodeInterface $application_node) {
    foreach (range(1, self::CONFIRMATION_SECTION_NUMBER - 1) as $section_number) {
      if ($this->getSectionStatus($application_node, $section_number) != self::SECTION_COMPLETE) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Get the status of the reference sub-section / node.
   *
   * @param \Drupal\node\NodeInterface $application_node
   *   The visa application node.
   * @param string $field_name
   *   The name of the reference field.
   *
   * @return int
   *   Status indicator constant like
   *   \Drupal\server_visa_application\VisaApplicationManagerInterface::SECTION_LOCKED.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getReferenceStatus(NodeInterface $application_node, $field_name) {
    if ($application_node->get($field_name)->isEmpty()) {
      return self::SECTION_NOT_FILLED;
    }

    $fields_status = [];
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($application_node->get($field_name)->referencedEntities() as $entity) {
      foreach ($this->getFauxRequiredFieldsToValidate($entity, 'default') as $field_name) {
        $fields_status[$field_name] = !$entity->{$field_name}->isEmpty();
      }
    }

    if (empty($fields_status)) {
      // There were no faux required fields, so it means everything is filled.
      return self::SECTION_COMPLETE;
    }

    return $this->fieldsStatusToSectionStatus($fields_status);
  }

  /**
   * {@inheritDoc}
   */
  public function getApplicationFromNode(NodeInterface $node) {
    switch ($node->bundle()) {
      case 'experience':
        $field_name = 'field_experiences';
        break;

      case 'honor_award':
        $field_name = 'field_honors_awards';
        break;

      case 'household_member':
        $field_name = 'field_household_members';
        break;

      case 'parent':
        $field_name = 'field_parent';
        break;

      case 'teacher':
        $field_name = 'field_teacher';
        break;

      default:
        // Un related node.
        return FALSE;
    }

    $result = $this->entityQuery->get('node')
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('type', 'visa_application')
      ->condition($field_name, $node->id())
      ->range(0, 1)
      ->execute();

    if (empty($result)) {
      return FALSE;
    }

    $nid = reset($result);
    return $this->entityTypeManager->getStorage('node')->load($nid);
  }

}
