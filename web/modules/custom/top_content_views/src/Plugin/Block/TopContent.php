<?php

namespace Drupal\top_content_views\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Top Content' Block.
 *
 * @Block(
 *   id = "top_content",
 *   admin_label = @Translation("Top Content"),
 * )
 */
class TopContent extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'top_content';
    $build['#TopTests'] = views_embed_view('tests_conditions_screenings', 'top_tests');
    $build['#TopConditions'] = views_embed_view('tests_conditions_screenings', 'top_conditions');
    $build['#TopScreenings'] = views_embed_view('tests_conditions_screenings', 'top_screenings');

    return $build;
  }

}
