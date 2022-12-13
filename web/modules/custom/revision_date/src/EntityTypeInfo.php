<?php

namespace Drupal\revision_date;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 *
 * @internal
 */
class EntityTypeInfo implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\revision_date\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Bundle information service.
   *
   * @var \Drupal\revision_date\EntityTypeBundleInfoInterface
   */
  private $bundleInfo;

  /**
   * EntityTypeInfo constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   Bundle information service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $bundleInfo) {
    $this->entityTypeManager = $entityTypeManager;
    $this->bundleInfo = $bundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Returns an iterable list of entity names and bundle names under moderation.
   *
   * That is, this method returns a list of bundles that have Content
   * Moderation enabled on them.
   *
   * @return \Generator
   *   A generator, yielding a 2 element associative array:
   *   - entity: The machine name of an entity type, such as "node" or
   *     "block_content".
   *   - bundle: The machine name of a bundle, such as "page" or "article".
   */
  protected function getRevisionableBundles() {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entityTypes */
    $entityTypes = array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $entityType) {
      return $entityType->isRevisionable();
    });

    foreach ($entityTypes as $entityTypeName => $entityType) {
      /** @var TYPE_NAME $bundle */
      foreach ($this->bundleInfo->getBundleInfo($entityTypeName) as $bundleId => $bundle) {
        yield ['entity' => $entityTypeName, 'bundle' => $bundleId];
      }
    }
  }

  /**
   * Gets the "extra fields" for a bundle.
   *
   * @return array
   *   A nested array of 'pseudo-field' elements. Each list is nested within the
   *   following keys: entity type, bundle name, context (either 'form' or
   *   'display'). The keys are the name of the elements as appearing in the
   *   renderable array (either the entity form or the displayed entity). The
   *   value is an associative array:
   *   - label: The human readable name of the element. Make sure you sanitize
   *     this appropriately.
   *   - description: A short description of the element contents.
   *   - weight: The default weight of the element.
   *   - visible: (optional) The default visibility of the element. Defaults to
   *     TRUE.
   *   - edit: (optional) String containing markup (normally a link) used as the
   *     element's 'edit' operation in the administration interface. Only for
   *     'form' context.
   *   - delete: (optional) String containing markup (normally a link) used as
   *     the element's 'delete' operation in the administration interface. Only
   *     for 'form' context.
   *
   * @see hook_entity_extra_field_info()
   */
  public function entityExtraFieldInfo() {
    $return = [];
    foreach ($this->getRevisionableBundles() as $bundle) {
      $return[$bundle['entity']][$bundle['bundle']]['display']['revision_date'] = [
        'label' => $this->t('Revision Date'),
        'description' => $this->t('The date of the displayed revision.'),
        'weight' => -20,
        'visible' => FALSE,
      ];
    }

    return $return;
  }

}
