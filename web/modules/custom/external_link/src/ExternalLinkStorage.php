<?php

namespace Drupal\external_link;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class ExternalLinkStorage extends SqlContentEntityStorage implements ExternalLinkStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ExternalLinkInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {external_link_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {external_link_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ExternalLinkInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {external_link_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('external_link_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
