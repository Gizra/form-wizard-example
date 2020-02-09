<?php

namespace Drupal\server_visa_application;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * StoryManager VisaApplicationManagerInterface.
 */
interface VisaApplicationManagerInterface {

  /**
   * Section is not filled at all.
   */
  const SECTION_NOT_FILLED = 0;

  /**
   * Section is partially filled.
   */
  const SECTION_PARTIAL = 1;

  /**
   * Section is complete.
   */
  const SECTION_COMPLETE = 2;

  /**
   * Section is locked.
   */
  const SECTION_LOCKED = 3;

  /**
   * The section number of the waiver.
   */
  const WAIVER_SECTION_NUMBER = 3;

  /**
   * Application is new or partially filled, but waiver was not submitted yet.
   */
  const APPLICATION_NEW = 'new';

  /**
   * Waiver was submitted, so applicant can no longer edit the application.
   */
  const APPLICATION_LOCKED = 'locked';

  /**
   * Application was rejected by a site admin.
   */
  const APPLICATION_REJECTED = 'rejected';

  /**
   * Application was accepted by a site admin.
   */
  const APPLICATION_ACCEPTED = 'accepted';

  /**
   * Get an existing application or a new unsaved one.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The User to query.
   *
   * @return \Drupal\node\NodeInterface
   *   The Visa application node.
   */
  public function getApplicationNodeByUser(AccountInterface $user);

  /**
   * Get status of an application's section.
   *
   * @param \Drupal\node\NodeInterface $application_node
   *   The Application node.
   * @param int $section_number
   *   The section number.
   *
   * @return int
   *   The status.
   */
  public function getSectionStatus(NodeInterface $application_node, int $section_number);

  /**
   * Get status of all section in an application.
   *
   * @param \Drupal\node\NodeInterface $application_node
   *   The Application node.
   *
   * @return array
   *   Array of status for all sections.
   */
  public function getSectionsStatus(NodeInterface $application_node);

  /**
   * Indicate if waiver section can be signed.
   *
   * We need to confirm all previous sections are marked as `SECTION_COMPLETE`.
   *
   * @param \Drupal\node\NodeInterface $application_node
   *   The Application node.
   *
   * @return bool
   *   TRUE if waiver can be signed.
   */
  public function applicationCanBeSigned(NodeInterface $application_node);

  /**
   * Get Application node out of a given Teacher node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node referenced from the Student application.
   *
   * @return \Drupal\node\NodeInterface
   *   The Application node referencing the node.
   */
  public function getApplicationFromNode(NodeInterface $node);

}
