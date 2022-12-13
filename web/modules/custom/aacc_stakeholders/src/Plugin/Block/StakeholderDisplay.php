<?php

namespace Drupal\aacc_stakeholders\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'StakeholderDisplay' block.
 *
 * @Block(
 *  id = "stakeholder_display",
 *  admin_label = @Translation("Stakeholder display"),
 * )
 */
class StakeholderDisplay extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    $build = [];
    $build['stakeholder_display']['#markup'] = '<div class="stakeholder-load"></div>';
    $build['#attached']['library'][] = 'aacc_stakeholders/stakeholder-ajax';
    if ($node) {
      $build['#attached']['drupalSettings']['aacc_stakeholders']['stakeholder']['nid'] = $node->id();
    }
    $build['#cache'] = [
      'contexts' => ['url.path'],
    ];

    return $build;
  }

}
