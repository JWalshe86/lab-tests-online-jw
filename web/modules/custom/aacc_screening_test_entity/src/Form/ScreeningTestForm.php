<?php

namespace Drupal\aacc_screening_test_entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Screening Test edit forms.
 *
 * @ingroup aacc_screening_test_entity
 */
class ScreeningTestForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\aacc_screening_test_entity\Entity\ScreeningTest $entity */
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
        $messenger = \Drupal::messenger();
        $messenger->addMessage($this->t('Created the %label Screening Test.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $messenger = \Drupal::messenger();
        $messenger->addMessage($this->t('Saved the %label Screening Test.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.screening_test.canonical', ['screening_test' => $entity->id()]);
  }

}
