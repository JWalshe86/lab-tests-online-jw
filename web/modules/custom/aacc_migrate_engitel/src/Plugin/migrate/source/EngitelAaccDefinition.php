<?php

namespace Drupal\aacc_migrate_engitel\Plugin\migrate\source;

/**
 * Definition source plugin.
 *
 * @MigrateSource(
 *   id = "engitel_aacc_definition",
 *   database_state_key = "migrate"
 * )
 */
class EngitelAaccDefinition extends EngitelAaccBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = 'SELECT * FROM dbo.Content WHERE cntcId = 7';
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function countQuery() {
    $count_query = 'SELECT count(*) FROM dbo.Content WHERE cntcId = 7';
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

}
