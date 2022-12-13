<?php

namespace Drupal\aacc_migrate_engitel\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Definiton source plugin.
 *
 * @MigrateSource(
 *   id = "engitel_aacc_condition",
 *   database_state_key = "migrate"
 * )
 */
class EngitelAaccCondition extends EngitelAaccBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = 'SELECT * FROM dbo.Content WHERE cntcId = 3';
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function countQuery() {
    $count_query = 'SELECT count(*) FROM dbo.Content WHERE cntcId = 3';
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
    $reviewed_date = date('Y-m-d', strtotime($row->getSourceProperty('cntLastPubOp')));
    $row->setSourceProperty('cntLastPubOp', $reviewed_date);
    $replaced = preg_replace('/\*BRPAGE\*/', '', $row->getSourceProperty('cntBody'));

    $paragraphs = [];

    $paragraph = $this->createExpandableParagraph([
      'label' => '',
      'value' => $replaced,
      'format' => 'full_html',
      'hide_teaser' => FALSE,
    ]);

    $paragraphs[] = [
      'target_id'          => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];

    $row->setSourceProperty('subcontent', $paragraphs);

    $row->setSourceProperty('cntBody', $replaced);
    return parent::prepareRow($row);
  }

}
