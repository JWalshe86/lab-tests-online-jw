<?php

namespace Drupal\aacc_ads;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Ad Key Value entity.
 *
 * @see \Drupal\aacc_ads\Entity\Ads.
 */
class AdsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\aacc_ads\Entity\AdsInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ad key value entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published ad key value entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit ad key value entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete ad key value entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ad key value entities');
  }

}
