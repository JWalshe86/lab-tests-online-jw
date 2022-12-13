<?php

namespace Drupal\aacc_duplicate_keywords\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Url;

/**
 * Class Duplicate Keyword management Form.
 *
 * @package Drupal\aacc_duplicate_keywords\Form
 */
class DuplicateKeywordsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aacc_duplicate_keywords_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $rows = [];

    $userInput = $form_state->getUserInput();
    if (!empty($userInput['keyword_terms'])) {
      $keywordIds = $this->processSelectedKeywords($userInput['keyword_terms']);

      $rows = array_merge($this->getStakeholders($keywordIds), $this->getNodes($keywordIds), $this->getAdEntities($keywordIds));
    }

    $form['keyword_terms'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => [
        'target_bundles' => ['keyword'],
      ],
      '#tags' => TRUE,
      '#element_validate' => ['\Drupal\Core\Entity\Element\EntityAutocomplete::validateEntityAutocomplete'],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Search For Duplicates'),
    ];

    $form['duplicate_keyword_entities'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Type'),
        $this->t('Terms'),
        $this->t('Edit'),
      ],
      '#empty' => t('There are no items yet. Enter one or more keywords to compare.'),
    ];

    foreach ($rows as $key => $row) {
      [$title, $type, $terms, $edit] = $row;
      $editUrl = Url::fromUri('internal:' . $edit);
      $editLink = Link::fromTextAndUrl('Edit', $editUrl)->toRenderable();
      $form['duplicate_keyword_entities'][$key] = [
        'Title' => [
          '#plain_text' => $title,
        ],
        'Type' => [
          '#plain_text' => $type,
        ],
        'Terms' => [
          '#plain_text' => implode(', ', $terms),
        ],
        'Edit' => [
          $editLink,
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * Separate and extract the keyword IDs from the autocomplete field data.
   *
   * @param string $keywords
   *   A string of keywords from an entity reference autocomplete field.
   *
   * @return array
   *   An array of taxonomy term IDs.
   */
  protected function processSelectedKeywords($keywords) {
    $keywords = explode(',', $keywords);
    $keywordIds = [];
    foreach ($keywords as $keyword) {
      $keywordIds[] = EntityAutocomplete::extractEntityIdFromAutocompleteInput($keyword);
    }

    return $keywordIds;
  }

  /**
   * Get stakeholder row data.
   *
   * @param array $keywordIds
   *   An array of taxonomy term IDs.
   *
   * @return array
   *   An array containing the row data.
   */
  protected function getStakeholders(array $keywordIds) {
    $rows = [];

    if (empty($keywordIds)) {
      return $rows;
    }

    $stakeholderResults = \Drupal::entityQuery('node')
      ->condition('type', 'stakeholder')
      ->condition('field_sponsor_imps.entity.field_sponsor_keyword', $keywordIds, 'in')
      ->execute();

    $stakeholders = \Drupal::entityTypeManager()->getStorage('node')
      ->loadMultiple($stakeholderResults);

    foreach ($stakeholders as $stakeholder) {
      $termsList = [];
      $editUrl = '/node/' . $stakeholder->id() . '/edit';
      $termParagraphs = $stakeholder->get('field_sponsor_imps')->referencedEntities();
      foreach ($termParagraphs as $termParagraph) {
        $terms = $termParagraph->get('field_sponsor_keyword')->referencedEntities();

        foreach ($terms as $term) {
          $termsList[] = $term->getName();
        }
      }

      $rows[] = [
        $stakeholder->getTitle(),
        $stakeholder->getType(),
        $termsList,
        $editUrl,
      ];
    }

    return $rows;
  }

  /**
   * Get node row data.
   *
   * @param array $keywordIds
   *   An array of taxonomy term IDs.
   *
   * @return array
   *   An array containing the row data.
   */
  protected function getNodes(array $keywordIds) {
    $rows = [];

    if (empty($keywordIds)) {
      return $rows;
    }

    $results = \Drupal::entityQuery('node')
      ->condition('type', 'stakeholder', '<>')
      ->condition('field_keywords', $keywordIds, 'in')
      ->execute();

    $nodes = \Drupal::entityTypeManager()->getStorage('node')
      ->loadMultiple($results);

    foreach ($nodes as $node) {
      $termsList = [];
      $editUrl = '/node/' . $node->id() . '/edit';
      $terms = $node->get('field_keywords')->referencedEntities();

      foreach ($terms as $term) {
        $termsList[] = $term->getName();
      }

      $rows[] = [$node->getTitle(), $node->getType(), $termsList, $editUrl];
    }

    return $rows;
  }

  /**
   * Get Ad entity row data.
   *
   * @param array $keywordIds
   *   An array of taxonomy term IDs.
   *
   * @return array
   *   An array containing the row data.
   */
  protected function getAdEntities(array $keywordIds) {
    $rows = [];

    if (empty($keywordIds)) {
      return $rows;
    }

    $results = \Drupal::entityQuery('ads')
      ->condition('field_ad_keywords', $keywordIds, 'in')
      ->execute();

    $ads = \Drupal::entityTypeManager()->getStorage('ads')
      ->loadMultiple($results);

    foreach ($ads as $ad) {
      $termsList = [];
      $editUrl = '/admin/structure/ads/' . $ad->id() . '/edit';
      $terms = $ad->get('field_ad_keywords')->referencedEntities();

      foreach ($terms as $term) {
        $termsList[] = $term->getName();
      }

      $rows[] = [
        $ad->get('name')->value,
        'Ad Key Value: ' . $ad->get('type')->getValue()[0]['target_id'],
        $termsList,
        $editUrl,
      ];
    }

    return $rows;
  }

}
