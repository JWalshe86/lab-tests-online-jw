<?php

namespace Drupal\aacc_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;

/**
 * News item source plugin.
 *
 * @MigrateSource(
 *   id = "aacc_news_item",
 *   database_state_key = "migrate"
 * )
 */
class AaccNewsItem extends AaccSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('SiteTree_Live', 'tree')
      ->fields('tree');
    $query->leftJoin('NewsPage_Live', 'news', 'news.ID = tree.ID');
    $query->leftJoin('MemberArticlePage_Live', 'article', 'article.ID = tree.ID');
    $query->fields('news', [
      'HeadDate',
      'NewsSummary',
      'ThisSite',
      'WebSite',
      'Sources',
    ]);
    $query->fields('article', ['ArticleDate', 'ArticleSummary']);
    $query->leftJoin('Page_Live', 'page', 'page.ID = tree.ID');
    $query->fields('page', ['IncDate']);
    $query->condition('tree.className', ['NewsPage', 'MemberArticlePage'], 'IN');
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

    $class = $row->getSourceProperty('ClassName');

    // Set news_type property.
    if ($class == 'NewsPage') {
      $term_name = t('In the News');
    }
    else {
      $term_name = t('LTO Stat');
    }
    $terms = taxonomy_term_load_multiple_by_name($term_name, 'news_type');
    if (!empty($terms)) {
      $term = reset($terms);
    }
    else {
      $term = Term::create(
        [
          'name' => $term_name,
          'vid' => 'news_type',
        ]
      );
      $term->save();
    }

    $row->setSourceProperty('news_type', $term->id());

    $paragraphs = [];

    if ($sources = $row->getSourceProperty('Sources')) {
      $paragraph = $this->createExpandableParagraph([
        'label' => 'Sources',
        'value' => $this->prepareLinks($row->getSourceProperty('Sources')),
        'format' => 'full_html',
        'hide_teaser' => 1,
      ]);

      $paragraphs[] = [
        'target_id'          => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];
    }

    $row->setSourceProperty('subcontent', $paragraphs);

    if ($head_date = $row->getSourceProperty('ArticleDate')) {
      $row->setSourceProperty('HeadDate', $head_date);
    }
    $row->setSourceProperty('NewsSummary', $this->prepareLinks($row->getSourceProperty('NewsSummary')));
    if ($summary = $row->getSourceProperty('ArticleSummary')) {
      $row->setSourceProperty('NewsSummary', $this->prepareLinks($summary));
    }

    $row->setSourceProperty('Content', $this->prepareLinks($row->getSourceProperty('Content')));
    $row->setSourceProperty('alias', '/news/' . $row->getSourceProperty('URLSegment'));
    return parent::prepareRow($row);
  }

}
