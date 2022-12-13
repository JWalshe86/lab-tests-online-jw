<?php

namespace Drupal\aacc_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Definiton source plugin.
 *
 * @MigrateSource(
 *   id = "aacc_screening",
 *   database_state_key = "migrate"
 * )
 */
class AaccScreening extends AaccSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $parent_id = $this->select('IndexScreeningPage', 'isp')
      ->fields('isp', ['HolderPageID'])
      ->range(0, 1)
      ->condition('isp.HolderPageID', 0, '>')
      ->execute()->fetchField();

    $query = $this->select('SiteTree_Live', 'tree')
      ->fields('tree');
    $query->leftJoin('WellnessPage_Live', 'wellness', 'wellness.ID = tree.ID');
    $query->leftJoin('Page_Live', 'page', 'page.ID = tree.ID');
    $query->fields('page', ['IncDate']);
    $query->condition('tree.className', 'WellnessPage');
    $query->condition('tree.ParentID', $parent_id);
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

    $query = $this->select('SiteTree_Live', 'tree')->fields('tree');
    $query->condition('tree.ParentID', $row->getSourceProperty('ID'));
    $result = $query->execute();
    $paragraphs = [];
    $items = [];

    foreach ($result as $sub_screening) {
      $items[] = $sub_screening;
    }

    if ($items) {
      $paragraph = $this->createScreeningParagraph('Tests', $items);

      $paragraphs[] = [
        'target_id'          => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];
    }

    $row->setSourceProperty('subcontent', $paragraphs);
    $row->setSourceProperty('alias', '/screenings/' . $row->getSourceProperty('URLSegment'));
    return parent::prepareRow($row);
  }

}
