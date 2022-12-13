<?php

namespace Drupal\aacc_ads\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Ads Type entity Form.
 *
 * @package Drupal\aacc_ads\Form
 */
class AdsTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $ads_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $ads_type->label(),
      '#description' => $this->t("Label for the Ad Key Value type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $ads_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\aacc_ads\Entity\AdsType::load',
      ],
      '#disabled' => !$ads_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $ads_type = $this->entity;
    $status = $ads_type->save();

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addStatus($this->t('Created the %label Ad Key Value type.', [
          '%label' => $ads_type->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addStatus($this->t('Saved the %label Ad Key Value type.', [
          '%label' => $ads_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($ads_type->toUrl('collection'));
  }

}
