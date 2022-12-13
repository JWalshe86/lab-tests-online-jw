<?php

namespace Drupal\aacc_ads\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdOptions.
 *
 * @package Drupal\aacc_ads\Form
 */
class AdsOptions extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'aacc_ads.adoptions',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ad_options';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('aacc_ads.adoptions');
    $form['ad_free_domains'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ad-free Domains'),
      '#description' => $this->t('Javascript regular expressions are created from new RegExp. You must escape periods. Separate each with a comma. Please do not include protocol (ie, http://) in domains. Examples: nih\.gov'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('ad_free_domains'),
    ];
    $form['ad_free_exclusion_domains'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ad-free Exclusion Domains'),
      '#description' => $this->t('Javascript regular expressions are created from new RegExp. You m ust escape periods. These are subdomains of the Ad-free domains where ads should be displayed if the referrer URL matches any of these. Separate each with a comma. Please do not include protocol (ie, http://) in domains. Examples: nlm\.nih\.gov'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('ad_free_exclusion_domains'),
    ];
    $form['ads_force_production'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use production ads'),
      '#description' => $this->t('Use the production EHS ad URLs. This is meant for testing purposes only. This value should remain unchecked. Set this value in settings.php per environment.'),
      '#default_value' => $config->get('ads_force_production'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('aacc_ads.adoptions')
      ->set('ad_free_domains', $form_state->getValue('ad_free_domains'))
      ->set('ad_free_exclusion_domains', $form_state->getValue('ad_free_exclusion_domains'))
      ->set('ads_force_production', $form_state->getValue('ads_force_production'))
      ->save();
  }

}
