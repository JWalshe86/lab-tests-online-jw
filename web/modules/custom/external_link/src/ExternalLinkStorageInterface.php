<?php

namespace Drupal\external_link;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\external_link\Entity\ExternalLinkInterface;

/**
 * Defines the storage handler class for External Link entities.
 *
 * This extends the base storage class, adding required special handling for
 * External Link entities.
 *
 * @ingroup external_link
 */
interface ExternalLinkStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of External Link revision IDs for a specific External Link.
   *
   * @param \Drupal\external_link\Entity\ExternalLinkInterface $entity
   *   The External Link entity.
   *
   * @return int[]
   *   External Link revision IDs (in ascending order).
   */
  public function revisionIds(ExternalLinkInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as External Link author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   External Link revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\external_link\Entity\ExternalLinkInterface $entity
   *   The External Link entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ExternalLinkInterface $entity);

  /**
   * Unsets the language for all External Link with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
