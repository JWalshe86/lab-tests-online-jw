<?php

namespace Drupal\aacc_add_this\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'AddThis' block.
 *
 * @Block(
 *  id = "add_this",
 *  admin_label = @Translation("Add This"),
 * )
 */
class AddThis extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['add_this']['#theme'] = 'add_this';
    $build['add_this']['#attached']['library'][] = 'aacc_add_this/add-this';

    return $build;
  }

}
