<?php

namespace Drupal\aacc_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Definiton source plugin.
 *
 * @MigrateSource(
 *   id = "aacc_keyword",
 *   database_state_key = "migrate"
 * )
 */
class AaccKeyword extends AaccSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('SiteTree_Live', 'tree');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return iterator_count($this->getIterator());
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'ID' => $this->t('Term ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'ID' => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Set tags property.
    $row->setSourceProperty('old_path', $this->getOldUrl($row->getSourceProperty('ParentID'), $row->getSourceProperty('URLSegment')));
    return parent::prepareRow($row);
  }

  /**
   * Implementation of MigrateSource::performRewind().
   *
   * We could simply execute the query and be functionally correct, but
   * we will take advantage of the PDO-based API to optimize the query up-front.
   */
  protected function initializeIterator() {
    $ad_key = $this->select('AdCategoryKey', 'keywords')->fields('keywords')->execute();
    $ad_condition_key = $this->select('AdConditionKey', 'keywords')->fields('keywords')->execute();
    $ad_hc_key = $this->select('AdHCPMedicalSpecialtyKey', 'keywords')->fields('keywords')->execute();
    $ad_hc_prof_key = $this->select('AdHCPProfessionKey', 'keywords')->fields('keywords')->execute();
    $meta_keywords = $this->select('SiteTree_Live', 'tree')->fields('tree', ['MetaKeywords'])->execute();
    $test_keywords = $this->select('AnalytePage_Live', 'analyte')->fields('analyte', [
      'TestKeywords',
      'SampleKeywords',
      'FAQKeywords',
      'AskKeywords',
      'LinksKeywords',
      'SourcesKeywords',
    ])->execute();

    $result = [];
    $result = $this->explodeKeywords($ad_key, $result);
    $result = $this->explodeKeywords($ad_condition_key, $result);
    $result = $this->explodeKeywords($ad_hc_key, $result);
    $result = $this->explodeKeywords($ad_hc_prof_key, $result);
    $meta_keywords_prepared = [];
    foreach ($meta_keywords as $keyword) {
      $meta_keywords_prepared[] = ['Keywords' => $keyword['MetaKeywords']];
    }
    $test_keywords_prepared = [];
    foreach ($test_keywords as $keyword) {
      $test_keywords_prepared[] = ['Keywords' => $keyword['TestKeywords']];
      $test_keywords_prepared[] = ['Keywords' => $keyword['SampleKeywords']];
      $test_keywords_prepared[] = ['Keywords' => $keyword['FAQKeywords']];
      $test_keywords_prepared[] = ['Keywords' => $keyword['AskKeywords']];
      $test_keywords_prepared[] = ['Keywords' => $keyword['LinksKeywords']];
      $test_keywords_prepared[] = ['Keywords' => $keyword['SourcesKeywords']];
    }
    $result = $this->explodeKeywords($test_keywords_prepared, $result);
    $result = $this->explodeKeywords($meta_keywords_prepared, $result);

    return new \ArrayIterator($result);
  }

  /**
   * Explode keywords from query result.
   */
  private function explodeKeywords($query_result, $result) {
    foreach ($query_result as $key) {
      if (!empty($key['Keywords'])) {
        $keywords = explode(',', $key['Keywords']);
        if (count($keywords)) {
          foreach ($keywords as $keyword) {
            if (!empty(trim($keyword))) {
              $result[trim($keyword)] = [
                'ID' => trim($keyword),
                'title' => trim($keyword),
              ];
            }
          }
        }
      }
    }
    return $result;
  }

}
