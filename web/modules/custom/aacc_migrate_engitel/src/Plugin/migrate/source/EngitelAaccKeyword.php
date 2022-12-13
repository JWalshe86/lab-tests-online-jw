<?php

namespace Drupal\aacc_migrate_engitel\Plugin\migrate\source;

/**
 * Definiton source plugin.
 *
 * @MigrateSource(
 *   id = "engitel_aacc_keyword",
 *   database_state_key = "migrate"
 * )
 */
class EngitelAaccKeyword extends EngitelAaccBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = 'SELECT * FROM dbo.Keywords';
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function countQuery() {
    $count_query = 'SELECT count(*) FROM dbo.Keywords';
    return $count_query;
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
      'keyValue' => $this->t('Kyeword ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'keyValue' => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * Implementation of MigrateSource::performRewind().
   *
   * We could simply execute the query and be functionally correct, but
   * we will take advantage of the PDO-based API to optimize the query up-front.
   */
  protected function initializeIterator() {
    $this->getDatabase();

    $sth = $this->database->prepare($this->query());
    $sth->execute();
    $this->result = $sth->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($this->result as $key_value) {
      if (!empty($key_value['keyValue'])) {
        $keywords = explode(',', $key_value['keyValue']);
        $keyword_base = $key_value;
        if (count($keywords)) {
          foreach ($keywords as $keyword) {
            $keyword_new = $keyword_base;
            $keyword_new['keyValue'] = trim($keyword);
            if (!empty(trim($keyword))) {
              $result[$keyword_new['keyValue']] = $keyword_new;
            }
          }
        }
      }
    }

    return new \ArrayIterator($result);
  }

}
