<?php

namespace Drupal\aacc_reports\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Content Type Usage Report Form.
 */
class ContentTypeUsageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_type_usage_form';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['usage_listing'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Content Type'),
        $this->t('Total Published Content'),
      ],
      '#empty' => $this->t('There are no results found'),
      '#tableselect' => FALSE,
    ];

    // Load all the available types and sort before processing.
    $config_types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();

    $types = [];
    foreach ($config_types as $config_type) {
      $types[$config_type->get('name')] = [
        'id' => $config_type->id(),
        'name' => $config_type->get('name'),
      ];
    }

    ksort($types);

    // Get the total count for each type.
    $weight = 0;
    foreach ($types as $type) {
      $type_id = $type['id'];
      $type_name = $type['name'];

      // Get all the node ids that are published.
      $nids = \Drupal::entityQuery('node')
        ->condition('type', $type_id)
        ->condition('status', NodeInterface::PUBLISHED)
        ->execute();

      $form['usage_listing'][$type_id]['#weight'] = $weight;

      $form['usage_listing'][$type_id]['name'] = [
        '#plain_text' => $type_name,
      ];

      $form['usage_listing'][$type_id]['type'] = [
        '#plain_text' => ($nids) ? number_format(count($nids)) : 0,
      ];

      $weight++;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

}
