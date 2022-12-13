<?php

namespace Drupal\aacc_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Definiton source plugin.
 *
 * @MigrateSource(
 *   id = "aacc_test",
 *   database_state_key = "migrate"
 * )
 */
class AaccTest extends AaccSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('SiteTree_Live', 'tree')
      ->fields('tree');
    $query->leftJoin('AnalytePage_Live', 'test', 'test.ID = tree.ID');
    $query->fields('test');
    $query->leftJoin('Page_Live', 'page', 'page.ID = tree.ID');
    $query->fields('page', ['IncDate', 'IsTargetHCP']);
    $query->condition('tree.className', 'AnalytePage');
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
    $subcontent = [];

    $grid_items = [];

    if ($row->getSourceProperty('Why')) {
      $grid_items[] = [
        'label' => 'Why Get Tested?',
        'value' => $row->getSourceProperty('Why'),
        'format' => 'full_html',
      ];
    }

    if ($row->getSourceProperty('When')) {
      $grid_items[] = [
        'label' => 'When To Get Tested?',
        'value' => $row->getSourceProperty('When'),
        'format' => 'full_html',
      ];
    }

    if ($row->getSourceProperty('SampleRequired')) {
      $grid_items[] = [
        'label' => 'Sample Required?',
        'value' => $row->getSourceProperty('SampleRequired'),
        'format' => 'full_html',
      ];
    }

    if ($row->getSourceProperty('BottomNote')) {
      $grid_items[] = [
        'label' => 'Test Preparation Needed?',
        'value' => $row->getSourceProperty('BottomNote'),
        'format' => 'full_html',
      ];
    }

    if ($grid_items) {
      $paragraph = $this->createGridParagraph('At a Glance', $grid_items);

      $paragraphs[] = [
        'target_id'          => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];
    }

    $sample = '';
    if ($row->getSourceProperty('What')) {
      $sample .= $row->getSourceProperty('What');
    }

    if ($row->getSourceProperty('How')) {
      $sample .= '<h3>How is the sample collected for testing?</h3>';
      $sample .= $row->getSourceProperty('How');
    }

    if ($row->getSourceProperty('TestPrep')) {
      $sample .= '<h3>Is any test preparation needed to ensure the quality of the sample?</h3>';
      $sample .= $row->getSourceProperty('TestPrep');
    }

    if ($sample) {
      $paragraph = $this->createExpandableParagraph([
        'label' => 'What is being tested?',
        'value' => $sample,
        'format' => 'full_html',
        'hide_teaser' => FALSE,
      ]);

      $paragraphs[] = [
        'target_id'          => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];
    }

    $common_questions = [];
    if ($row->getSourceProperty('HowTest')) {
      $common_questions[] = [
        'label' => 'How is it used?',
        'value' => $row->getSourceProperty('HowTest'),
        'format' => 'full_html',
      ];
    }

    if ($row->getSourceProperty('WhenTest')) {
      $common_questions[] = [
        'label' => 'When is it ordered?',
        'value' => $row->getSourceProperty('WhenTest'),
        'format' => 'full_html',
      ];
    }

    if ($row->getSourceProperty('Required')) {
      $common_questions[] = [
        'label' => 'What does the test result mean?',
        'value' => $row->getSourceProperty('Required'),
        'format' => 'full_html',
      ];
    }

    if ($row->getSourceProperty('Freq')) {
      $common_questions[] = [
        'label' => 'Is there anything else I should know?',
        'value' => $row->getSourceProperty('Freq'),
        'format' => 'full_html',
      ];
    }

    $query = $this->select('AnalyteFAQ', 'faq')->fields('faq');
    $query->condition('faq.AnalytePageID', $row->getSourceProperty('ID'));
    $query->orderBy('faq.SortOrder');
    $questions = $query->execute();

    if (!empty($questions)) {
      foreach ($questions as $question) {
        if (!empty($question['Question'])) {
          $common_questions[] = [
            'label' => $question['Question'],
            'value' => $question['Answer'],
            'format' => 'full_html',
          ];
        }
      }
    }

    if ($common_questions) {
      $paragraph = $this->createAccordionParagraph('Common Questions', $common_questions);
      $paragraphs[] = [
        'target_id'          => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];
    }

    if ($row->getSourceProperty('SourcesContent')) {
      $paragraph = $this->createExpandableParagraph([
        'label' => 'View Sources',
        'value' => $row->getSourceProperty('SourcesContent'),
        'format' => 'full_html',
        'hide_teaser' => TRUE,
      ]);

      $paragraphs[] = [
        'target_id'          => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];
    }

    $row->setSourceProperty('ThisLink', $this->prepareLinks($row->getSourceProperty('ThisLink')));
    $row->setSourceProperty('OtherSite', $this->prepareLinks($row->getSourceProperty('OtherSite')));
    $row->setSourceProperty('Formal', $row->getSourceProperty('Formal'));

    $row->setSourceProperty('subcontent', $paragraphs);
    $row->setSourceProperty('Common', $this->prepareSynonyms($row->getSourceProperty('Common')));
    $row->setSourceProperty('alias', '/tests/' . $row->getSourceProperty('URLSegment'));
    return parent::prepareRow($row);
  }

}
