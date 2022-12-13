<?php

namespace Drupal\aacc_ads\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'TapNativeWidgetBlock' block.
 *
 * @Block(
 *  id = "tap_native_widget_block",
 *  admin_label = @Translation("Tap native widget block"),
 * )
 */
class TapNativeWidgetBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'tapnative_widget',
    ];
  }

}
