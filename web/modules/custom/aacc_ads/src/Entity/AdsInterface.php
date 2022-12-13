<?php

namespace Drupal\aacc_ads\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ad Key Value entities.
 *
 * @ingroup aacc_ads
 */
interface AdsInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Ad Key Value name.
   *
   * @return string
   *   Name of the Ad Key Value.
   */
  public function getName();

  /**
   * Sets the Ad Key Value name.
   *
   * @param string $name
   *   The Ad Key Value name.
   *
   * @return \Drupal\aacc_ads\Entity\AdsInterface
   *   The called Ad Key Value entity.
   */
  public function setName($name);

  /**
   * Gets the Ad Key Value creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ad Key Value.
   */
  public function getCreatedTime();

  /**
   * Sets the Ad Key Value creation timestamp.
   *
   * @param int $timestamp
   *   The Ad Key Value creation timestamp.
   *
   * @return \Drupal\aacc_ads\Entity\AdsInterface
   *   The called Ad Key Value entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Ad Key Value published status indicator.
   *
   * Unpublished Ad Key Value are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Ad Key Value is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Ad Key Value.
   *
   * @param bool $published
   *   TRUE to set this Ad Key Value to published, FALSE to un-publish.
   *
   * @return \Drupal\aacc_ads\Entity\AdsInterface
   *   The called Ad Key Value entity.
   */
  public function setPublished($published);

}
