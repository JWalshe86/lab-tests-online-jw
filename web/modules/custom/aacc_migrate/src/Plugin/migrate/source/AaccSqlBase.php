<?php

namespace Drupal\aacc_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Aacc base source plugin.
 *
 * @MigrateSource(
 *   id = "aacc_sqlbase",
 *   database_state_key = "migrate"
 * )
 */
abstract class AaccSqlBase extends SqlBase {

  /**
   * The Property mapping.
   *
   * @var string[]
   */
  protected $migrationMapping = [
    'ConditionPage' => 'condition',
    'LightboxGlossaryPage' => 'definition',
    'AnalytePage' => 'test',
    'WellnessPage' => 'screening',
    'NewsPage' => 'news_item',
    'FeaturePage' => '',
  ];

  /**
   * The content language id.
   *
   * @var string
   */
  protected $contentLanguage;

  /**
   * Return language code.
   */
  protected function getContentLanguage() {
    if (!$this->contentLanguage) {
      $config = \Drupal::config('aacc_migrate.settings');
      $language = $config->get('language');
      if (!empty($language)) {
        $this->contentLanguage = $language;
      }
      else {
        $language = \Drupal::service('language.default')->get();
        $this->contentLanguage = $language->getId();
      }
    }
    return $this->contentLanguage;
  }

  /**
   * Return full url for current content.
   */
  protected function getOldUrl($parent_id, $url = '') {
    $query = $this->select('SiteTree_Live', 'tree')
      ->fields('tree', [
        'ParentID',
        'URLSegment',
      ]);
    $query->condition('tree.ID', $parent_id);
    $res = $query->execute();
    if ($object = $res->fetchObject()) {
      $url = $object->URLSegment . '/' . $url;
      return $this->getOldUrl($object->ParentID, $url);
    }
    return $url;
  }

  /**
   * Return migration id by legacy entity id.
   */
  public function getMigrationByLegacyId($id) {
    $query = $this->select('SiteTree_Live', 'tree')
      ->fields('tree', [
        'className',
        'ParentID',
      ]);
    $query->condition('tree.ID', $id);
    $res = $query->execute();
    if ($obj = $res->fetchObject()) {
      if (isset($this->migrationMapping[$obj->className])) {
        if ($obj->className == 'WellnessPage' && $obj->ParentID != 1239) {
          return NULL;
        }
        return $this->migrationMapping[$obj->className];
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('language', $this->getContentLanguage());

    $row->setSourceProperty('metatags', serialize([
      'title' => $row->getSourceProperty('MetaTitle'),
      'description' => ($description = $row->getSourceProperty('TestDescription')) ? $description : $row->getSourceProperty('MetaDescription'),
    ]));

    $keywords = explode(',', $row->getSourceProperty('MetaKeywords'));
    if (!empty($keywords)) {
      foreach ($keywords as $key => $keyword) {
        if (!empty(trim($keyword))) {
          $keywords[$key] = trim($keyword);
        }
      }
    }
    $test_keywords = array_merge(explode(',', $row->getSourceProperty('TestKeywords')),
      explode(',', $row->getSourceProperty('SampleKeywords')),
      explode(',', $row->getSourceProperty('FAQKeywords')),
      explode(',', $row->getSourceProperty('AskKeywords')),
      explode(',', $row->getSourceProperty('LinksKeywords')),
      explode(',', $row->getSourceProperty('SourcesKeywords'))
    );
    if (!empty($test_keywords)) {
      foreach ($test_keywords as $key => $keyword) {
        if (!empty(trim($keyword))) {
          $test_keywords[$key] = trim($keyword);
        }
      }
    }
    $result_keywords = array_merge($keywords, $test_keywords);
    $row->setSourceProperty('keywords', $result_keywords);
    $row->setSourceProperty('Title', $row->getSourceProperty('Title'));

    return parent::prepareRow($row);
  }

  /**
   * Prepare sitetree_link in content of entities.
   */
  protected function prepareLinks($content) {
    $matches = [];

    preg_match_all("/\[sitetree_link[, ]id=(.+?)\]/", $content, $matches);

    if (!empty($matches[1])) {
      foreach ($matches[1] as $match) {
        $query = $this->select('SiteTree_Live', 'tree')
          ->fields('tree', [
            'ParentID',
            'URLSegment',
          ]);
        $query->condition('tree.ID', $match);
        $res = $query->execute();
        if ($res) {
          foreach ($res as $value) {
            $href = $this->getOldUrl($value['ParentID'], $value['URLSegment']);
            $content = preg_replace("/\[sitetree_link[, ]id=" . $match . "\]/", '/' . $href, $content);
          }
        }
      }
    }

    $content = preg_replace("/href=\"(?!\/)(?!http)(.+?)\"/", 'href="/$1"', $content);

    return $content;
  }

  /**
   * Creation accordion paragraphs.
   */
  protected function createAccordionParagraph($label, $items) {
    $sub_items = [];

    foreach ($items as $item) {
      if (!empty($item['value'])) {
        $paragraph = Paragraph::create([
          'id' => NULL,
          'type' => 'accordion_item',
          'field_label' => $item['label'],
          'field_body' => [
            'value' => $this->prepareLinks($item['value']),
            'format' => $item['format'],
          ],
        ]);
        $paragraph->save();
        $sub_items[] = $paragraph;
      }
    }

    $paragraph = Paragraph::create([
      'id' => NULL,
      'type' => 'accordion',
      'field_label' => $label,
      'field_accordion_items' => $sub_items,
    ]);
    $paragraph->save();

    return $paragraph;
  }

  /**
   * Creation grid paragraphs.
   */
  protected function createGridParagraph($label, $items) {
    $sub_items = [];

    foreach ($items as $item) {
      if (!empty($item['value'])) {
        $text_item = Paragraph::create([
          'id' => NULL,
          'type' => 'text_area',
          'field_label' => $item['label'],
          'field_body' => [
            'value' => $this->prepareLinks($item['value']),
            'format' => $item['format'],
          ],
        ]);
        $text_item->save();
        $sub_items[] = $text_item;
      }
    }

    $paragraph = Paragraph::create([
      'id' => NULL,
      'type' => 'grid',
      'field_label' => $label,
      'field_text_areas' => $sub_items,
    ]);
    $paragraph->save();

    return $paragraph;
  }

  /**
   * Creation explandable paragraphs.
   */
  protected function createExpandableParagraph($item) {
    $paragraph = Paragraph::create([
      'id' => NULL,
      'type' => 'expandable',
      'field_label' => $item['label'],
      'field_body' => [
        'value' => $this->prepareLinks($item['value']),
        'format' => $item['format'],
      ],
      'field_expand_show_all' => $item['expand'] ?? 0,
      'field_hide_teaser' => $item['hide_teaser'] ?? 1,
    ]);
    $paragraph->save();

    return $paragraph;
  }

  /**
   * Creation screening paragraphs.
   */
  protected function createScreeningParagraph($label, $items) {
    $sub_items = [];

    foreach ($items as $item) {
      if (!empty($item['Content'])) {
        $sub_screenings = $this->select('SiteTree_Live', 'tree')->fields('tree');
        $sub_screenings->condition('tree.ParentID', $item['ID']);
        $sub_screenings->condition('tree.className', 'WellnessPage');
        $result = $sub_screenings->execute();
        $sub_content = '';
        if ($result) {
          foreach ($result as $sub_screening) {
            $sub_content .= '<h2>' . $sub_screening['Title'] . '</h2>';
            $sub_content .= $this->prepareLinks($sub_screening['Content']);
          }
        }
        $data = [
          'type' => 'screening_test',
          'name' => $item['Title'],
          'field_body' => [
            'value' => $this->prepareLinks($item['Content']) . $sub_content,
            'format' => 'full_html',
          ],
          'uid' => 1,
          'langcode' => $this->getContentLanguage(),
        ];
        $screening_item = \Drupal::entityTypeManager()
          ->getStorage('screening_test')
          ->create($data);
        $screening_item->save();
        $sub_items[] = $screening_item;
      }
    }

    $paragraph = Paragraph::create([
      'id' => NULL,
      'type' => 'screening_tests',
      'field_label' => $label,
      'field_screening_test_item' => $sub_items,
    ]);
    $paragraph->save();

    return $paragraph;
  }

  /**
   * Prepare synonyms.
   */
  protected function prepareSynonyms($synonyms) {
    $expoded = explode(';', $synonyms);
    $return = array_map('trim', $expoded);
    return $return;
  }

}
