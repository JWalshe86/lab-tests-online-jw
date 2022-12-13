<?php

namespace Drupal\aacc_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SearchHeader' block.
 *
 * @Block(
 *  id = "search_header",
 *  admin_label = @Translation("Search Header"),
 * )
 */
class SearchHeader extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['search_header']['#theme'] = 'search_header';

    return $build;
  }

}
