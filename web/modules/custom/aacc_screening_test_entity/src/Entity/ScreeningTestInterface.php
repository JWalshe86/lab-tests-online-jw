<?php

namespace Drupal\aacc_screening_test_entity\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Screening Test entities.
 *
 * @ingroup aacc_screening_test_entity
 */
interface ScreeningTestInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Screening Test name.
   *
   * @return string
   *   Name of the Screening Test.
   */
  public function getName();

  /**
   * Sets the Screening Test name.
   *
   * @param string $name
   *   The Screening Test name.
   *
   * @return \Drupal\aacc_screening_test_entity\Entity\ScreeningTestInterface
   *   The called Screening Test entity.
   */
  public function setName($name);

  /**
   * Gets the Screening Test creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Screening Test.
   */
  public function getCreatedTime();

  /**
   * Sets the Screening Test creation timestamp.
   *
   * @param int $timestamp
   *   The Screening Test creation timestamp.
   *
   * @return \Drupal\aacc_screening_test_entity\Entity\ScreeningTestInterface
   *   The called Screening Test entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Screening Test published status indicator.
   *
   * Unpublished Screening Test are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Screening Test is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Screening Test.
   *
   * @param bool $published
   *   TRUE to set this Screening Test to published, FALSE to un-publish.
   *
   * @return \Drupal\aacc_screening_test_entity\Entity\ScreeningTestInterface
   *   The called Screening Test entity.
   */
  public function setPublished($published);

}
