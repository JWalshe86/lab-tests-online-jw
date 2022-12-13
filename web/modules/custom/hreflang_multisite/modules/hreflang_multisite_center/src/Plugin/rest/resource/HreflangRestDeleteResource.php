<?php

namespace Drupal\hreflang_multisite_center\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\hreflang_multisite_center\HreflangCenterStorage;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "hreflang_rest_delete_resource",
 *   label = @Translation("Hreflang Rest Delete Resource"),
 *   uri_paths = {
 *     "canonical" = "/hreflang_rest_api/hreflang_delete_resource",
 *     "https://www.drupal.org/link-relations/create" = "/hreflang_rest_api/hreflang_delete_resource"
 *
 *   }
 * )
 */
class HreflangRestDeleteResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The HreflangCenterStorage.
   *
   * @var \Drupal\hreflang_multisite_center\HreflangCenterStorage
   *   The hreflang center storage.
   */
  protected $hreflangCenterStorage;

  /**
   * Constructs a new HreflangRestDeleteResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   * @param \Drupal\hreflang_multisite_center\HreflangCenterStorage $hreflangCenterStorage
   *   The hreflang center storage.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    HreflangCenterStorage $hreflangCenterStorage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->hreflangCenterStorage = $hreflangCenterStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('hreflang_multisite_center'),
      $container->get('current_user'),
      $container->get('hreflang_multisite_center.manager_storage')
    );
  }

  /**
   * Responds to POST requests.
   *
   * Updates and removes hreflang values after a node deletion.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post($data) {
    if (!$this->currentUser->hasPermission('restful post hreflang_rest_delete_resource')) {
      throw new AccessDeniedHttpException();
    }
    try {
      $data = json_decode($data, TRUE);
      $referencedCountry = $data['referencedCountry'];
      $referencedNodeId = $data['nodeId'];
      $baseNodeId = $data['baseNodeId'];
      // Delete all hreflangs for this base node id.
      $this->hreflangCenterStorage->deleteSiteReferencesByBaseNodeId($baseNodeId);
      // Delete all center site hreflang records for the referenced site id.
      $this->hreflangCenterStorage->deleteReference($referencedNodeId, $baseNodeId, $referencedCountry);
      // Update all hreflangs.
      $this->hreflangCenterStorage->updateSiteHreflangs($baseNodeId);

      return new ResourceResponse($data);
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addMessage(t('An error occurred while updating hreflangs. Please notify your administrator.'), 'error');
      \Drupal::logger('hreflang_multisite_center')->error($e->getMessage());
    }
  }

}
