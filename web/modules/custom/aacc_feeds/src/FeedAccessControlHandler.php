<?php

namespace Drupal\aacc_feeds;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the feed entity.
 *
 * @ingroup aacc_feeds
 * @category AACC_Feeds
 * @package AACC
 * @author jelmore@unleashed-technologies.com <jelmore@unleashed-technologies.com>
 * @license https://unleashed-technologies.com Unleashed-Technologies.com
 * @link https://labtestsonline.com
 */
class FeedAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity Object.
   * @param string $operation
   *   Operation String.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account Object.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultInterface
   *   AccessResult object.
   */
  protected function checkAccess(
        EntityInterface $entity,
        $operation,
  AccountInterface $account
    ) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions(
        $account,
        ['view feed entity']
      );

      case 'edit':
        return AccessResult::allowedIfHasPermissions(
        $account,
        ['edit feed entity']
      );

      case 'delete':
        return AccessResult::allowedIfHasPermissions(
        $account,
        ['delete feed entity']
      );
    }
    return AccessResult::allowed();
  }

  /**
   * Performs create access checks.
   *
   * This method is supposed to be overwritten by extending classes that
   * do their own custom access checking.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   * @param array $context
   *   An array of key-value pairs to pass additional context when needed.
   * @param string|null $entity_bundle
   *   (optional) The bundle of the entity. Required if the entity supports
   *   bundles, defaults to NULL otherwise.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkCreateAccess(
        AccountInterface $account,
        array $context,
        $entity_bundle = NULL
    ) {
    return AccessResult::allowedIfHasPermissions(
        $account,
        ['add feed entity']
    );
  }

}
