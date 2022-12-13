<?php

namespace Drupal\aacc_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Definiton source plugin.
 *
 * @MigrateSource(
 *   id = "aacc_definition",
 *   database_state_key = "migrate"
 * )
 */
class AaccDefinition extends AaccSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('SiteTree_Live', 'tree')
      ->fields('tree');
    $query->leftJoin('LightboxGlossaryPage_Live', 'glossary', 'glossary.ID = tree.ID');
    $query->leftJoin('Page_Live', 'page', 'page.ID = tree.ID');
    $query->fields('page', ['IncDate']);
    $query->condition('tree.className', 'LightboxGlossaryPage');
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
    $expoded = explode(',', $row->getSourceProperty('Aka'));
    $synonyms = array_map('trim', $expoded);
    $row->setSourceProperty('aka', $synonyms);
    $row->setSourceProperty('alias', '/glossary/' . $row->getSourceProperty('URLSegment'));
    return parent::prepareRow($row);
  }

}
