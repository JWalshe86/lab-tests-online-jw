<?php

namespace Drupal\aacc_jumplist_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Jumplist Menu' Block.
 *
 * @Block(
 *   id = "jumplist_menu",
 *   admin_label = @Translation("Jumplist Menu"),
 * )
 */
class JumplistMenu extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'jumplist_menu';
    if (!empty(views_get_view_result('jumplists', 'jumplist_tests'))) {
      $build['#Tests'] = views_embed_view('jumplists', 'jumplist_tests');
    }
    if (!empty(views_get_view_result('jumplists', 'jumplist_conditions'))) {
      $build['#Conditions'] = views_embed_view('jumplists', 'jumplist_conditions');
    }
    if (!empty(views_get_view_result('jumplists', 'jumplist_screenings'))) {
      $build['#Screenings'] = views_embed_view('jumplists', 'jumplist_screenings');
    }
    return $build;
  }

}
