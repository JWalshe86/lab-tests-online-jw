<?php

namespace Drupal\external_link\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining External Link entities.
 *
 * @ingroup external_link
 */
interface ExternalLinkInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the External Link name.
   *
   * @return string
   *   Name of the External Link.
   */
  public function getName();

  /**
   * Sets the External Link name.
   *
   * @param string $name
   *   The External Link name.
   *
   * @return \Drupal\external_link\Entity\ExternalLinkInterface
   *   The called External Link entity.
   */
  public function setName($name);

  /**
   * Gets the External Link creation timestamp.
   *
   * @return int
   *   Creation timestamp of the External Link.
   */
  public function getCreatedTime();

  /**
   * Sets the External Link creation timestamp.
   *
   * @param int $timestamp
   *   The External Link creation timestamp.
   *
   * @return \Drupal\external_link\Entity\ExternalLinkInterface
   *   The called External Link entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the External Link published status indicator.
   *
   * Unpublished External Link are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the External Link is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a External Link.
   *
   * @param bool $published
   *   TRUE to set this External Link to published, FALSE to unpublish.
   *
   * @return \Drupal\external_link\Entity\ExternalLinkInterface
   *   The called External Link entity.
   */
  public function setPublished($published);

  /**
   * Gets the External Link revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the External Link revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\external_link\Entity\ExternalLinkInterface
   *   The called External Link entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the External Link revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the External Link revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\external_link\Entity\ExternalLinkInterface
   *   The called External Link entity.
   */
  public function setRevisionUserId($uid);

}
