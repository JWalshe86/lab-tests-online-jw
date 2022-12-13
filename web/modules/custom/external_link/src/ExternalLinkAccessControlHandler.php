<?php

namespace Drupal\external_link;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the External Link entity.
 *
 * @see \Drupal\external_link\Entity\ExternalLink.
 */
class ExternalLinkAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\external_link\Entity\ExternalLinkInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished external link entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published external link entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit external link entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete external link entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add external link entities');
  }

}
