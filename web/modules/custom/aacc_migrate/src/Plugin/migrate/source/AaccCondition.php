<?php

namespace Drupal\aacc_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Definiton source plugin.
 *
 * @MigrateSource(
 *   id = "aacc_condition",
 *   database_state_key = "migrate"
 * )
 */
class AaccCondition extends AaccSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('SiteTree_Live', 'tree')
      ->fields('tree');
    $query->leftJoin('ConditionPage_Live', 'cond', 'cond.ID = tree.ID');
    $query->fields('cond', ['Common']);
    $query->leftJoin('Page_Live', 'page', 'page.ID = tree.ID');
    $query->fields('page', ['IncDate', 'IsTargetHCP']);
    $query->condition('tree.className', 'ConditionPage');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'ID' => $this->t('Post ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'ID' => [
        'type' => 'integer',
        'alias' => 'tree',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Set tags property.
    $row->setSourceProperty('old_path', $this->getOldUrl($row->getSourceProperty('ParentID'), $row->getSourceProperty('URLSegment')));

    $paragraphs = [];

    $content = $row->getSourceProperty('Content');

    $paragraph = $this->createExpandableParagraph([
      'label' => '',
      'value' => $this->prepareLinks($content),
      'format' => 'full_html',
      'hide_teaser' => 0,
    ]);

    $paragraphs[] = [
      'target_id'          => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];

    $row->setSourceProperty('subcontent', $paragraphs);
    $row->setSourceProperty('Common', $this->prepareSynonyms($row->getSourceProperty('Common')));
    $row->setSourceProperty('alias', '/conditions/' . $row->getSourceProperty('URLSegment'));
    return parent::prepareRow($row);
  }

}
