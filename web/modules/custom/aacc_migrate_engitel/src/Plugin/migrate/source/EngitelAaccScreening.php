<?php

namespace Drupal\aacc_migrate_engitel\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Definiton source plugin.
 *
 * @MigrateSource(
 *   id = "engitel_aacc_screening",
 *   database_state_key = "migrate"
 * )
 */
class EngitelAaccScreening extends EngitelAaccBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = 'SELECT * FROM dbo.Content WHERE cntcId = 4';
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function countQuery() {
    $count_query = 'SELECT count(*) FROM dbo.Content WHERE cntcId = 4';
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
    $content = $row->getSourceProperty('cntBody');
    $exploded_content = explode('*BRPAGE*', $content);
    $screening_content = array_shift($exploded_content);

    $matches = [];
    preg_match_all("/<h2>(.+?)<\/h2>/is", $screening_content, $matches);
    $paragraph = $this->createExpandableParagraph([
      'label' => html_entity_decode($matches[1][0]),
      'value' => preg_replace("/<h2>(.+?)<\/h2>/is", "", $screening_content),
      'format' => 'full_html',
    ]);

    $paragraphs[] = [
      'target_id'          => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];

    $items = [];
    foreach ($exploded_content as $sub_screening) {
      $matches = [];
      preg_match_all("/<h2>(.+?)<\/h2>/is", $sub_screening, $matches);
      if (!empty($matches[1])) {
        $items[] = [
          'Title' => html_entity_decode(trim($matches[1][0])),
          'Content' => preg_replace("/<h2>(.+?)<\/h2>/is", "", $sub_screening),
        ];
      }
    }
    $paragraph = $this->createScreeningParagraph('Tests', $items);

    $paragraphs[] = [
      'target_id'          => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];

    $row->setSourceProperty('subcontent', $paragraphs);
    return parent::prepareRow($row);
  }

}
