<?php

namespace Drupal\aacc_ads\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Ad Key Value edit forms.
 *
 * @ingroup aacc_ads
 */
class AdsForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\aacc_ads\Entity\Ads $entity */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addStatus($this->t('Created the %label Ad Key Value.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addStatus($this->t('Saved the %label Ad Key Value.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.ads.canonical', ['ads' => $entity->id()]);
  }

}
