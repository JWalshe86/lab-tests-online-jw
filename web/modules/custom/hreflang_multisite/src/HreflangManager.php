<?php

namespace Drupal\hreflang_multisite;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;

/**
 * Hreflang Data Manager service.
 */
class HreflangManager {

  /**
   * The Href Lang data storage.
   *
   * @var \Drupal\hreflang_multisite\HreflangStorage
   */
  protected $hreflangStorage;

  /**
   * Constructs a new EntityListBuilder object.
   */
  public function __construct(HreflangStorage $hreflangStorage) {
    $this->hreflangStorage = $hreflangStorage;
  }

  /**
   * Attaches submit handlers to the node form.
   *
   * @param array $form
   *   The node form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The node form state.
   */
  public function attachNodeSubmitHandlers(array &$form, FormStateInterface $form_state) {
    // We do not want to act on preview or delete.
    foreach (['submit', 'publish', 'unpublish'] as $action) {
      if (!empty($form['actions'][$action])) {
        $form['actions'][$action]['#submit'][] = __class__ . '::updateHreflangReferences';
      }
    }
  }

  /**
   * Sends an api request to the center site that a node has been updated.
   *
   * @param array $form
   *   The node form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The node form state.
   */
  public static function updateHreflangReferences(array &$form, FormStateInterface $form_state) {
    try {
      $hreflangStorage = \Drupal::service('hreflang_multisite.manager_storage');
      $node = $form_state->getFormObject()->getEntity();
      $nodeId = $node->id();

      $reference = $hreflangStorage->getReferenceByNode($nodeId);
      if (!$reference) {
        return;
      }

      $reference = reset($reference);

      $serializedEntityId = json_encode([
        'nodeId' => $nodeId,
        'referencedCountry' => $reference['referenced_country'],
        'baseNodeId' => $reference['base_nid'],
      ]);

      $client = \Drupal::httpClient();
      $restData = Settings::get('hreflang_multisite_rest');
      $request = $client->get($restData['rest_center_url'] . '/rest/session/token');
      $csrf_token = $request->getBody()->getContents();

      $response = $client
        ->post($restData['rest_center_url'] . '/hreflang_rest_api/hreflang_update_resource', [
          'auth' => [$restData['rest_username'], $restData['rest_password']],
          'json' => $serializedEntityId,
          'headers' => [
            'Content-Type' => 'application/json',
            'X-CSRF-Token' => $csrf_token,
          ],
          'query' => ['_format' => 'json'],
        ]);
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addMessage(t('An error occurred while updating hreflangs. Please notify your administrator.'), 'error');
      \Drupal::logger('hreflang_multisite_center')->error($e->getMessage());
    }

  }

  /**
   * Sends a api request to the center site that a node has been deleted.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object which is a node.
   */
  public function hreflangRestDeleteResource(EntityInterface $entity) {
    if ($entity->getEntityTypeId() !== 'node') {
      return;
    }

    $entityId = $entity->id();
    $reference = $this->hreflangStorage->getReferenceByNode($entityId);
    if (!$reference) {
      return;
    }

    $reference = reset($reference);
    $serializedEntityId = json_encode([
      'nodeId' => $entityId,
      'referencedCountry' => $reference['referenced_country'],
      'baseNodeId' => $reference['base_nid'],
    ]);

    $client = \Drupal::httpClient();
    $restData = Settings::get('hreflang_multisite_rest');
    $request = $client->get($restData['rest_center_url'] . '/rest/session/token');
    $csrf_token = $request->getBody()->getContents();

    $response = $client
      ->post($restData['rest_center_url'] . '/hreflang_rest_api/hreflang_delete_resource', [
        'auth' => [$restData['rest_username'], $restData['rest_password']],
        'json' => $serializedEntityId,
        'headers' => [
          'Content-Type' => 'application/json',
          'X-CSRF-Token' => $csrf_token,
        ],
        'query' => ['_format' => 'json'],
      ]);
  }

}
