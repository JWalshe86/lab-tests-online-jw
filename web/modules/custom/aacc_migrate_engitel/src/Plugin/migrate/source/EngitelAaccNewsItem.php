<?php

namespace Drupal\aacc_migrate_engitel\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;

/**
 * Definiton source plugin.
 *
 * @MigrateSource(
 *   id = "engitel_aacc_news_item",
 *   database_state_key = "migrate"
 * )
 */
class EngitelAaccNewsItem extends EngitelAaccBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = 'SELECT * FROM dbo.Content WHERE cntcId = 1';
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function countQuery() {
    $count_query = 'SELECT count(*) FROM dbo.Content WHERE cntcId = 1';
    return $count_query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'cntId' => $this->t('Post ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'cntId' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $term_name = t('In the News');
    $terms = taxonomy_term_load_multiple_by_name($term_name, 'news_type');
    if (!empty($terms)) {
      $term = reset($terms);
    }
    else {
      $term = Term::create(
        [
          'name' => $term_name,
          'vid' => 'news_type',
        ]
      );
      $term->save();
    }

    $row->setSourceProperty('news_type', $term->id());

    return parent::prepareRow($row);
  }

}
