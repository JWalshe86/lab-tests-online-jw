<?php

namespace Drupal\sponsor_views\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Sponsors' Block to display all the different types of sponsors.
 *
 * @Block(
 *   id = "sponsor_views_collection",
 *   admin_label = @Translation("All Sponsors"),
 * )
 */
class Sponsors extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'sponsors';
    $build['#attached']['library'][] = 'sponsor_views/sponsors-mobile-accordion';
    $build['#ChampionPartners'] = views_embed_view('stakeholders', 'champion_partners_full');
    $build['#CollaboratingPartners'] = views_embed_view('stakeholders', 'collaborating_partners_full');
    $build['#InternationalPartners'] = views_embed_view('stakeholders', 'international_partners_full');
    $build['#InternationalSponsors'] = views_embed_view('stakeholders', 'international_sponsors_full');
    $build['#Sponsors'] = views_embed_view('stakeholders', 'sponsors_full');
    return $build;
  }

}
