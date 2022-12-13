<?php

namespace Drupal\aacc_ascls\Form;

use Drupal\aacc_ascls\TransmitQuestion;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration settings for ASCLS API Endpoints.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'aacc_ascls.api',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aacc_ascls_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('aacc_ascls.api');
    $transmitQuestion = new TransmitQuestion();
    $apiEndpoint = $transmitQuestion->asclsEndpoint();
    $prodEndpoint = (!empty($config->get('endpoint_prod'))) ? $config->get('endpoint_prod') : 'https://www.ascls.org/index.php?option=com_ascls&task=Question.remote';
    $devEndpoint = (!empty($config->get('endpoint_dev'))) ? $config->get('endpoint_dev') : 'http://ascls.cloudaccess.host/index.php?option=com_ascls&task=Question.remote';

    $form['endpoint_prod'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Endpoint for Production Environment only'),
      '#description' => $this->t(
        '
        <p><em> * ASCLS forms submitted to this endpoint are sent out live to multiple people.</em></p>
        <p><em> * Default: https://www.ascls.org/index.php?option=com_ascls&task=Question.remote</em></p>'),
      '#default_value' => $prodEndpoint,
    ];

    $form['endpoint_dev'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Endpoint for Development, Testing and Staging Environments'),
      '#description' => $this->t(
        '
        <p><em> * ASCLS forms submitted to this endpoint are safe for dev/test purposes.</em></p>
        <p><em> * Default: http://ascls.cloudaccess.host/index.php?option=com_ascls&task=Question.remote</em></p>
        <p>Please note:</p>
        <ul>
        <li>The IP for the dev/test environment must be whitelisted by<br />
        <a href="mailto:mikehill@decentraldigital.com">Decentral Digital and ASCLS</a>.<br />
        </li><li>This API endpoint is only available by request and has to be <br />
        manually set up by Decentral Digital.
        </li>
        </ul>'),
      '#default_value' => $devEndpoint,
    ];

    $form['use_dev_endpoints'] = [
      '#type' => 'select',
      '#title' => $this->t('Use Development Endpoint'),
      '#description' => $this->t(
        '
        <p><em> * Set to TRUE for dev/test/stage.  Set to FALSE for Production environment.</em></p>
        <p><strong>Your site is currently configured to use the following endpoint: <br />"%endpoint"</strong></p>',
        [
          '%endpoint' => $apiEndpoint,
        ]),
      '#default_value' => $config->get('use_dev_endpoints'),
      '#options' => [
        'TRUE' => $this
          ->t('TRUE'),
        'FALSE' => $this
          ->t('FALSE'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('aacc_ascls.api')
      ->set('endpoint_prod', $form_state->getValue('endpoint_prod'))
      ->set('endpoint_dev', $form_state->getValue('endpoint_dev'))
      ->set('use_dev_endpoints', $form_state->getValue('use_dev_endpoints'))
      ->save();
  }

}
