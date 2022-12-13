<?php

namespace Drupal\aacc_feeds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class aacc Feed module Settings Form.
 *
 * @ingroup aacc_feeds
 * @category AACC_Feeds
 * @package AACC
 * @author jelmore@unleashed-technologies.com <jelmore@unleashed-technologies.com>
 * @license https://unleashed-technologies.com Unleashed-Technologies.com
 * @link https://labtestsonline.com
 */
class FeedSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'aacc_feeds.notifications',
    ];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'aacc_feeds_settings';
  }

  /**
   * Define the form used for AACCFeed settings.
   *
   * @param array $form
   *   Form Array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form_state object.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('aacc_feeds.notifications');

    $form['feed_settings']['#markup'] = 'Settings form for AACC Feeds.
    <p>The following tokens may be used in any of the fields below to personalize these messages for your clients.</p>
    <ul>
      <li>@feed_title</li>
      <li>@feed_url</li>
      <li>@client_email</li>
      <li>@client_name</li>
    </ul>
    <p>In addition to the tokens listed above, the following tokens are available only for the Content Change Summary email subject and body.</p>
    <ul>
      <li>@summary</li>
      <li>@feed_content</li>
      <li>@content_type</li>
    </ul>';

    $subjectCreate = (!empty($config->get('create_feed_subject'))) ? $config->get('create_feed_subject') : 'Feed created: @title';
    $bodyCreate = (!empty($config->get('create_feed_body'))) ? $config->get('create_feed_body') : 'Hello @client_name! Your new feed has been created and is called \'@feed_title\'.';

    $form['create_feed_subject'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email Subject for Feed Creation.'),
      '#default_value' => $subjectCreate,
    ];

    $form['create_feed_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email Message Body for Feed Creation.'),
      '#default_value' => $bodyCreate,
    ];

    $subjectEdit = (!empty($config->get('edit_feed_subject'))) ? $config->get('edit_feed_subject') : 'Feed Edited: @title';
    $bodyEdit = (!empty($config->get('edit_feed_body'))) ? $config->get('edit_feed_body') : 'Hello @client_name! Your feed has been edited and is called \'@feed_title\'.';

    $form['edit_feed_subject'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email Subject for Feed Edit.'),
      '#default_value' => $subjectEdit,
    ];

    $form['edit_feed_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email Message Body for Feed Edit.'),
      '#default_value' => $bodyEdit,
    ];

    $subjectDelete = (!empty($config->get('delete_feed_subject'))) ? $config->get('delete_feed_subject') : 'Feed Deleted: @title';
    $bodyDelete = (!empty($config->get('delete_feed_body'))) ? $config->get('delete_feed_body') : 'Hello @client_name! Your feed \'@feed_title\' has been deleted.';

    $form['delete_feed_subject'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email Subject for Feed Delete.'),
      '#default_value' => $subjectDelete,
    ];

    $form['delete_feed_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email Message Body for Feed Delete.'),
      '#default_value' => $bodyDelete,
    ];

    $subjectContentChangeSummary = (!empty($config->get('content_change_summary_subject'))) ? $config->get('content_change_summary_subject') : 'Feed Content Change Summary: @title';
    $bodyContentChangeSummary = (!empty($config->get('content_change_summary_body'))) ? $config->get('content_change_summary_body') : 'Hello @client_name! Your feed content has changed.  @summary';

    $form['content_change_summary_subject'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email Subject for Content Change Summary.'),
      '#default_value' => $subjectContentChangeSummary,
    ];

    $form['content_change_summary_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email Message Body for Content Change Summary.'),
      '#default_value' => $bodyContentChangeSummary,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   Form Array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form_state object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('aacc_feeds.notifications')
      ->set('create_feed_body', $form_state->getValue('create_feed_body'))
      ->set('create_feed_subject', $form_state->getValue('create_feed_subject'))
      ->set('edit_feed_body', $form_state->getValue('edit_feed_body'))
      ->set('edit_feed_subject', $form_state->getValue('edit_feed_subject'))
      ->set('delete_feed_body', $form_state->getValue('delete_feed_body'))
      ->set('delete_feed_subject', $form_state->getValue('delete_feed_subject'))
      ->set('content_change_summary_body', $form_state->getValue('content_change_summary_body'))
      ->set('content_change_summary_subject', $form_state->getValue('content_change_summary_subject'))
      ->save();
  }

}
