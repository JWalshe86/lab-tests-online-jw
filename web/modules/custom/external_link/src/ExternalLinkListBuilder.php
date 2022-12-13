<?php

namespace Drupal\external_link;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of External Link entities.
 *
 * @ingroup external_link
 */
class ExternalLinkListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('External Link ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\external_link\Entity\ExternalLink $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::fromTextAndUrl($entity->label(), new Url(
     'entity.external_link.edit_form', [
       'external_link' => $entity->id(),
     ]
    ));
    return $row + parent::buildRow($entity);
  }

}
