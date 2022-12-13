<?php

namespace Drupal\aacc_migrate_engitel\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Definiton source plugin.
 *
 * @MigrateSource(
 *   id = "engitel_aacc_test",
 *   database_state_key = "migrate"
 * )
 */
class EngitelAaccTest extends EngitelAaccBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = 'SELECT dbo.Content.cntId as ID, * FROM dbo.Content
      LEFT OUTER JOIN dbo.C_TBE_LT_TESTS test ON test.cntId = dbo.Content.cntId
      WHERE dbo.Content.cntcId = 2';
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function countQuery() {
    $count_query = 'SELECT count(*) FROM dbo.Content WHERE cntcId = 2';
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
      'ID' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $paragraphs = [];

    $paragraph = $this->createGridParagraph('At a Glance', [
      [
        'label' => 'Glance get test?',
        'value' => $row->getSourceProperty('testglanceGetTest'),
        'format' => 'full_html',
      ],
    ]);

    $paragraphs[] = [
      'target_id'          => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];

    $paragraph = $this->createExpandableParagraph([
      'label' => 'What is being tested',
      'value' => $row->getSourceProperty('testSampleWhat'),
      'format' => 'full_html',
      'hide_teaser' => FALSE,
    ]);

    $paragraphs[] = [
      'target_id'          => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];

    $row->setSourceProperty('subcontent', $paragraphs);
    $reviewed = $row->getSourceProperty('cntLastPubOp');
    $row->setSourceProperty('cntLastPubOp', date('Y-m-d', strtotime($reviewed)));
    $row->setSourceProperty('testAliasName', array_map('trim', explode(',', $row->getSourceProperty('testAliasName'))));
    return parent::prepareRow($row);
  }

}
