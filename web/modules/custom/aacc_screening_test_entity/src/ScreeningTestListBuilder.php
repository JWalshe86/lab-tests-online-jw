<?php

namespace Drupal\aacc_screening_test_entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Screening Test entities.
 *
 * @ingroup aacc_screening_test_entity
 */
class ScreeningTestListBuilder extends EntityListBuilder {

  // Use LinkGeneratorTrait;.

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Screening Test ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\aacc_screening_test_entity\Entity\ScreeningTest $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::fromTextAndUrl(
      $entity->label(),
      new Url(
        'entity.screening_test.edit_form', [
          'screening_test' => $entity->id(),
        ]
      )
    );
    return $row + parent::buildRow($entity);
  }

}
