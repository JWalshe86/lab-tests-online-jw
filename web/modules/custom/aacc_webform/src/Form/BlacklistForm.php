<?php

namespace Drupal\aacc_webform\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Blacklist Settings Form.
 */
class BlacklistForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'aacc_webform.blacklist',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'blacklist_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('aacc_webform.blacklist');

    $form['blacklisted_markup'] = [
      '#type' => 'markup',
      '#markup' => new TranslatableMarkup('Enter terms to blacklist when an Ask a Laboratory Scientist form is submitted.'),
    ];

    $form['blacklisted_terms'] = [
      '#type' => 'textarea',
      '#title' => new TranslatableMarkup('Subject terms to blacklist'),
      '#description' => new TranslatableMarkup('Please enter terms to block in the subject of the webform submission. Enter one term per line.'),
      '#default_value' => $config->get('terms'),
    ];

    $form['blacklisted_error_message'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Validation error message'),
      '#description' => new TranslatableMarkup('Enter a message when one of the blacklisted terms is found.'),
      '#default_value' => $config->get('error_message'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => new TranslatableMarkup('Save'),
      '#button_type' => 'primary',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('aacc_webform.blacklist')
      ->set('blacklisted_terms', $form_state->getValue('terms'))
      ->set('blacklisted_error_message', $form_state->getValue('error_message'))
      ->save();
  }

}
