<?php

namespace Drupal\aacc_migrate_engitel\Plugin\migrate\source;

use Drupal\migrate_source_mssql\Plugin\migrate\source\MssqlBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Aacc base mssql source plugin.
 *
 * @MigrateSource(
 *   id = "aacc_mssqlbase",
 *   database_state_key = "migrate"
 * )
 */
abstract class EngitelAaccBase extends MssqlBase {

  /**
   * The content language.
   *
   * @var string
   */
  protected $contentLanguage;

  /**
   * {@inheritdoc}
   */
  public function getDatabase() {
    if (!isset($this->database)) {
      $config = \Drupal::config('aacc_migrate_engitel.settings');
      $databases = $config->get('databases');
      if (isset($databases['source'])) {
        $this->database = $this->setUpDatabase($databases['source']);
      }
      else {
        $this->database = $this->setUpDatabase($this->configuration);
      }
    }
    return $this->database;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $metatags = "SELECT keyDescr, keyValue FROM Keywords
      WHERE keyObjId = " . $row->getSourceProperty('cntId') . ";";

    $sth = $this->database->prepare($metatags);
    $sth->execute();
    $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
    if ($metatags = reset($result)) {
      $row->setSourceProperty('metatags', serialize([
        'description' => $metatags['keyDescr'],
      ]));
      if (!empty($metatags['keyValue'])) {
        $keywords = explode(',', $metatags['keyValue']);
        if (!empty($keywords)) {
          $keywords = array_map('trim', $keywords);
          $keywords_cleared = [];
          foreach ($keywords as $key => $value) {
            if (!empty($value)) {
              $keywords_cleared[] = $value;
            }
          }
          $row->setSourceProperty('keywords', $keywords_cleared);
        }
      }
      else {
        $this->prepareInheritKeywords($row);
      }
    }
    else {
      $this->prepareInheritKeywords($row);
    }
    $row->setSourceProperty('cntBody', $this->removeFonts($row->getSourceProperty('cntBody')));
    return parent::prepareRow($row);
  }

  /**
   * Prepare inherit keywords.
   */
  protected function prepareInheritKeywords(Row &$row) {
    // Trying to use inherit keywords.
    $inherit_keywords = "SELECT keyDescr, keyValue FROM Keywords
      WHERE keyObjId = 1;";

    $sth = $this->database->prepare($inherit_keywords);
    $sth->execute();
    $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
    if ($inherit_keywords = reset($result)) {
      $keywords = explode(',', $inherit_keywords['keyValue']);
      if (!empty($keywords)) {
        $keywords = array_map('trim', $keywords);
        $row->setSourceProperty('keywords', $keywords);
      }
    }
  }

  /**
   * Return content language code.
   */
  public function getContentLanguage() {
    if (!$this->contentLanguage) {
      $config = \Drupal::config('aacc_migrate_engitel.settings');
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
   * Creation of accordion paragraphs.
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
            'value' => $item['value'],
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
   * Creation of grid paragraphs.
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
            'value' => $item['value'],
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
        'value' => $item['value'],
        'format' => $item['format'],
      ],
      'field_expand_show_all' => $item['expand'] ?? 0,
      'field_hide_teaser' => $item['hide_teaser'] ?? 1,
    ]);
    $paragraph->save();

    return $paragraph;
  }

  /**
   * Creation of screening paragraphs.
   */
  protected function createScreeningParagraph($label, $items) {
    $sub_items = [];

    foreach ($items as $item) {
      if (!empty($item['Content'])) {
        $data = [
          'type' => 'screening_test',
          'name' => $item['Title'],
          'field_body' => ['value' => $item['Content'], 'format' => 'full_html'],
          'uid' => 1,
          'langcode' => $this->getContentLanguage(),
        ];
        $screening_item = \Drupal::entityManager()
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
   * Remove font styles from text.
   */
  protected function removeFonts($text) {
    $text = preg_replace("/<font(.+?)>(.+?)<\/font>/", '$2', $text);
    return $text;
  }

}
