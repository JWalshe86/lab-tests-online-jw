<?php

namespace Drupal\aacc_ads;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Ad Key Value entities.
 *
 * @ingroup aacc_ads
 */
class AdsListBuilder extends EntityListBuilder {

  // Use LinkGeneratorTrait;
  // Use Link;.

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Ad Key Value ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\aacc_ads\Entity\Ads $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::fromTextAndUrl($entity->label(), new Url(
      'entity.ads.edit_form', [
        'ads' => $entity->id(),
      ]
    ));
    return $row + parent::buildRow($entity);
  }

}
