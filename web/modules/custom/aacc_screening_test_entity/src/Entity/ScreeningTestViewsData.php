<?php

namespace Drupal\aacc_screening_test_entity\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Screening Test entities.
 */
class ScreeningTestViewsData extends EntityViewsData {

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
