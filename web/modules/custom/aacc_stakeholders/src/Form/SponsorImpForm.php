<?php

namespace Drupal\aacc_stakeholders\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Stakeholder SponsorImp Form.
 *
 * @package Drupal\aacc_stakeholders\Form
 */
class SponsorImpForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sponsor_imp_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['sponsor'] = [
      '#type' => 'select',
      '#title' => 'Select Sponsor',
      '#options' => $this->getSponsorList(),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#ajax' => [
        'callback' => 'aacc_stakeholder_impression_callback',
        'wrapper' => 'impression-table',
      ],
      '#value' => $this->t('Submit'),
    ];

    $form['table_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => t('Impression Reports'),
    ];

    $form['table_wrapper']['current_data_table'] = [
      '#type' => 'table',
      '#caption' => t('Current'),
      '#header' => [
        t('Keyword'),
        t('Impression Limit'),
        t('Impressions'),
      ],
      '#empty' => t('Please select a sponsor.'),
      '#prefix' => '<div id="impression-table">',
    ];

    $form['table_wrapper']['data_table'] = [
      '#type' => 'table',
      '#caption' => t('Previous Months'),
      '#header' => [
        [
          'data' => t('Date'),
          'field' => 'date',
          'sort' => 'asc',
        ],
        t('Impression Limit'),
        t('Keyword/Impressions'),
      ],
      '#empty' => t('Please select a sponsor.'),
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Get sponsor select options.
   *
   * @return array
   *   Array of sponsors
   */
  private function getSponsorList() {
    $options = [];
    $query = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->condition('type', 'stakeholder', '=')
      ->condition('status', 1, '=')
      ->condition('field_stakeholder_type', 12, '=');
    $result = $query->execute();
    if ($result) {
      foreach (array_values($result) as $id) {
        $stakeholder = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($id);
        $options[$stakeholder->id()] = $stakeholder->title->value;
      }
    }
    return $options;
  }

}
