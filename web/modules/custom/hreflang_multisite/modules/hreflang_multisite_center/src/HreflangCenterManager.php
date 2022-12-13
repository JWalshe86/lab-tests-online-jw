<?php

namespace Drupal\hreflang_multisite_center;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\Core\Site\Settings;

/**
 * Hreflang Center Manager.
 */
class HreflangCenterManager {

  /**
   * The center sites database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The languages available to reference for hreflangs.
   *
   * @var array|null
   */
  protected $languages;

  /**
   * Hreflang Center Storage.
   *
   * @var \Drupal\hreflang_multisite_center\HreflangCenterStorage
   */
  protected $hreflangCenterStorage;

  /**
   * Constructs a new EntityListBuilder object.
   */
  public function __construct(Connection $connection, HreflangCenterStorage $hreflangCenterStorage) {
    $this->connection = $connection;
    $hreflangConnectionInfo = Database::getConnectionInfo('hreflang_multisite');
    $this->languages = $hreflangConnectionInfo ?? NULL;
    $this->hreflangCenterStorage = $hreflangCenterStorage;
  }

  /**
   * Attaches the hreflang reference fields functionality to the node form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function attachNodeFields(array &$form, FormStateInterface $form_state) {
    if (!$this->languages) {
      return;
    }

    $node = $form_state->getFormObject()->getEntity();

    if (!$node) {
      return;
    }

    $nodeId = $node->Id();

    $form['hreflang_multisite'] = [
      '#type' => 'details',
      '#title' => t('Hreflang References'),
      '#description' => t('Link this page to the corresponding page on each country site.'),
      '#group' => 'advanced',
    ];

    $sites = self::getHreflangSites();

    if (!$sites) {
      return;
    }

    $defaults = [];

    foreach ($sites as $site) {
      $countryCode = self::getHreflangSiteCountryCode($site);
      // If no country code, do not create a field and show a center site.
      if (!$countryCode || $this->isCenterSite($countryCode)) {
        continue;
      }

      $siteDefaultLanguage = self::getHreflangSiteDefaultLanguage($site);
      $references = $this->hreflangCenterStorage->getReferencesByCenterNode($nodeId);

      foreach ($references as $reference) {
        // We want to show the reference for the site's default language.
        if ($reference['referenced_language'] == $siteDefaultLanguage) {
          $defaults[$reference['referenced_country']] = AutocompleteManager::getEntityLabels($reference['referenced_nid'], $reference['referenced_language'], $reference['referenced_country']);
        }
      }
      $form['hreflang_multisite'][$countryCode] = [
        '#type' => 'textfield',
        '#title' => 'Site: ' . $countryCode,
        '#size' => 60,
        '#autocomplete_route_name' => 'hreflang_multisite_center.autocomplete',
        '#autocomplete_route_parameters' => [
          'countryCode' => $countryCode,
          'langcode' => $siteDefaultLanguage,
          'type' => $node->getType(),
        ],
        '#default_value' => $defaults[$countryCode] ?? '',
      ];
    }

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
        $form['actions'][$action]['#submit'][] = __class__ . '::nodeSaveHrefLangReferences';
      }
    }
  }

  /**
   * Submit handler for the node forms that saves the hreflangs.
   *
   * @param array $form
   *   The node form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The node form state.
   */
  public static function nodeSaveHrefLangReferences(array &$form, FormStateInterface $form_state) {
    $hreflangCenterStorage = \Drupal::service('hreflang_multisite_center.manager_storage');
    $node = $form_state->getFormObject()->getEntity();
    $baseNodeId = $node->Id();
    $sites = self::getHreflangSites();

    // Delete all site hreflang references because they may be outdated.
    $hreflangCenterStorage->deleteSiteReferencesByBaseNodeId($baseNodeId);
    // Delete all center hreflang references.
    $hreflangCenterStorage->deleteByBase($baseNodeId);

    foreach ($sites as $site) {
      if (!$form_state->getValue($site) && !self::isCenterSite($site)) {
        continue;
      }

      // If we are on the center site, pass reference to other sites.
      if (self::isCenterSite($site)) {
        $referencedNodeId = $baseNodeId;
      }
      else {
        $input = $form_state->getValue($site);
        $referencedNodeId = AutocompleteManager::extractEntityIdFromAutocompleteInput($input);
      }

      $hreflangCenterStorage->updateCenterSiteHreflangs($referencedNodeId, $baseNodeId, $site);
    }
    // Save hreflang data to each site.
    $hreflangCenterStorage->updateSiteHreflangs($baseNodeId);
  }

  /**
   * Obtains an array of all possible sites that can be referenced.
   *
   * @return array|bool
   *   An array of all sites that can be referenced.
   */
  public static function getHreflangSites() {
    $siteData = Settings::get('hreflang_multisite');

    if (is_array($siteData)) {
      return array_keys(Settings::get('hreflang_multisite'));
    }

    return FALSE;
  }

  /**
   * Gets the hreflang site country code from a key.
   *
   * @param string $key
   *   The site key.
   *
   * @return bool
   *   Returns false if no country code.
   */
  public static function getHreflangSiteCountryCode($key) {
    $siteData = Settings::get('hreflang_multisite');

    if (isset($siteData[$key]['country_code'])) {
      return $siteData[$key]['country_code'];
    }

    return FALSE;
  }

  /**
   * Gets a sites default language.
   *
   * @param string $key
   *   The country code key.
   *
   * @return string|bool
   *   The default language code or false if it doesn't exist.
   */
  public static function getHreflangSiteDefaultLanguage($key) {
    $siteData = Settings::get('hreflang_multisite');

    if (isset($siteData[$key]['default_language'])) {
      return $siteData[$key]['default_language'];
    }

    return FALSE;
  }

  /**
   * Gets the hreflang site url from a site key.
   *
   * @param string $key
   *   The site key.
   *
   * @return bool
   *   Returns false if no key.
   */
  public static function getHreflangSiteUrl($key) {
    $siteData = Settings::get('hreflang_multisite');

    if (isset($siteData[$key]['site_url'])) {
      return $siteData[$key]['site_url'];
    }

    return FALSE;
  }

  /**
   * Checks if we are on the center site.
   *
   * @param string $key
   *   The key of the site to check.
   *
   * @return bool
   *   Returns true if the key is the center site.
   */
  public static function isCenterSite($key) {
    $siteData = Settings::get('hreflang_multisite');

    if (isset($siteData[$key]['center_site'])) {
      return TRUE;
    }

    return FALSE;
  }

}
