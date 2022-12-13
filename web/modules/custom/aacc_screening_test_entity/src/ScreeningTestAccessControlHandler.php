<?php

namespace Drupal\aacc_screening_test_entity;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Screening Test entity.
 *
 * @see \Drupal\aacc_screening_test_entity\Entity\ScreeningTest.
 */
class ScreeningTestAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\aacc_screening_test_entity\Entity\ScreeningTestInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished screening test entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published screening test entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit screening test entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete screening test entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add screening test entities');
  }

}
