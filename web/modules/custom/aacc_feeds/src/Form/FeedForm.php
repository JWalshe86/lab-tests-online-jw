<?php

namespace Drupal\aacc_feeds\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Form controller for the aacc_feed entity edit forms.
 *
 * @ingroup aacc_feeds
 * @category AACC_Feeds
 * @package AACC
 * @author jelmore@unleashed-technologies.com <jelmore@unleashed-technologies.com>
 * @license https://unleashed-technologies.com Unleashed-Technologies.com
 * @link https://labtestsonline.com
 */
class FeedForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   *
   * @return array
   *   Form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\aacc_feeds\Entity\Feed $entity */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['langcode'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => LanguageInterface::STATE_CONFIGURABLE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   *
   * @return bool
   *   Status of the entity save.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    $entity = $this->entity;
    if ($status == SAVED_UPDATED) {
      \Drupal::messenger()->addMessage($this->t(
            'The feed %feed has been updated.',
            ['%feed' => $entity->toLink()->toString()]
        ));

    }
    else {
      \Drupal::messenger()->addMessage($this->t(
            'The feed %feed has been added.',
            ['%feed' => $entity->toLink()->toString()]
        ));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $status;
  }

}
