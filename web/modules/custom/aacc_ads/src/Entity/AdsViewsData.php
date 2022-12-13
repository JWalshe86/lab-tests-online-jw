<?php

namespace Drupal\aacc_ads\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Ad Key Value entities.
 */
class AdsViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
