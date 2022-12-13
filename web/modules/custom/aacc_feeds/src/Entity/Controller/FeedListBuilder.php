<?php

namespace Drupal\aacc_feeds\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for aacc_feeds_feed entity.
 *
 * @ingroup aacc_feeds
 * @category AACC_Feeds
 * @package AACC
 * @author jelmore@unleashed-technologies.com <jelmore@unleashed-technologies.com>
 * @license https://unleashed-technologies.com Unleashed-Technologies.com
 * @link https://labtestsonline.com
 */
class FeedListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * Override ::render so that can add our own content above the table.
   *
   * @return mixed
   *   Build Array.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t(
        'AACC Feeds implements a Feeds model.  These feeds
      are fieldable entities.  You can manage the fields on the <a href="@adminlink">
      Feeds admin page</a>.', [
        '@adminlink' => \Drupal::urlGenerator()
          ->generateFromRoute('aacc_feeds.feed_settings'),
      ]
      ),
    ];

    $build += parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the feed list.
   *
   * @return array
   *   Header array.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Feed ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity Object.
   *
   * @return array
   *   Row array.
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    // $row['name'] = $entity->link();
    $row['name'] = $entity->toLink()->toString();
    return $row + parent::buildRow($entity);
  }

}
