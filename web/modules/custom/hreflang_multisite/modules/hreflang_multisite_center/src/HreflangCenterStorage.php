<?php

namespace Drupal\hreflang_multisite_center;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;

/**
 * Manages saving hreflangs to the center site and country sites.
 */
class HreflangCenterStorage {
  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a BookOutlineStorage object.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Gets the hreflang center id for the hreflang_multisite_center_ref table.
   *
   * This is currently center {node id}-{langcode}-{country code}.
   *
   * @param string $nodeId
   *   The center site node id.
   * @param string $language
   *   The langcode of the referenced node.
   * @param string $countryCode
   *   The country code of the referenced node.
   *
   * @return string
   *   The primary key string.
   */
  public function getHreflangCenterId($nodeId, $language, $countryCode) {
    return "{$nodeId}-{$language}-{$countryCode}";
  }

  /**
   * Inserts an hreflang into the center site.
   *
   * @param array $data
   *   The hreflang data.
   */
  public function insert(array $data) {
    try {
      $this->connection->merge('hreflang_multisite_center_ref')
        ->key(['id' => $data['id']])
        ->fields([
          'base_nid' => $data['base_nid'],
          'referenced_nid' => $data['referenced_nid'],
          'referenced_url' => $data['referenced_url'],
          'referenced_language' => $data['referenced_language'],
          'referenced_country' => $data['referenced_country'],
        ])
        ->execute();

    }
    catch (\Exception $e) {
      \Drupal::messenger()->addMessage(t('An error occurred and hreflang processing did not complete. Please notify your administrator.'), 'error');
      \Drupal::logger('hreflang_multisite_center')->error($e->getMessage());
    }
  }

  /**
   * Deletes an hreflang on the center site by base node id.
   *
   * @param string $baseNodeId
   *   The base node id.
   */
  public function deleteByBase($baseNodeId) {
    try {
      $this->connection->delete('hreflang_multisite_center_ref')
        ->condition('base_nid', $baseNodeId, '=')
        ->execute();
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addMessage(t('An error occurred and hreflang deletion did not complete. Please notify your administrator.'), 'error');
      \Drupal::logger('hreflang_multisite_center')->error($e->getMessage());
    }

  }

  /**
   * Deletes references on a country site according to node and referenced node.
   *
   * @param string $referencedNodeId
   *   The referenced site's node id.
   * @param string $baseNodeId
   *   The base site's node id.
   * @param string $site
   *   The country site.
   */
  public function deleteReference($referencedNodeId, $baseNodeId, $site) {
    try {
      $this->connection->delete('hreflang_multisite_center_ref')
        ->condition('base_nid', $baseNodeId, '=')
        ->condition('referenced_nid', $referencedNodeId)
        ->condition('referenced_country', $site)
        ->execute();
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addMessage(t('An error occurred and hreflang deletion did not complete. Please notify your administrator.'), 'error');
      \Drupal::logger('hreflang_multisite_center')->error($e->getMessage());
    }
  }

  /**
   * Deletes a sites hreflangs by center base node id.
   *
   * @param string $baseNodeId
   *   The center site base node id.
   * @param \Drupal\Core\Database\Connection $siteConnection
   *   The site database connection.
   *
   * @return bool
   *   Returns false if no connection.
   */
  public function deleteSiteHreflangByBaseNodeId($baseNodeId, Connection $siteConnection) {
    if (!$siteConnection) {
      return FALSE;
    }

    try {
      $siteConnection->delete('hreflang_multisite')
        ->condition('base_nid', $baseNodeId, '=')
        ->execute();
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addMessage(t('An error occurred and hreflang deletion did not complete. Please notify your administrator.'), 'error');
      \Drupal::logger('hreflang_multisite_center')->error($e->getMessage());
    }
  }

  /**
   * Gets all languages for a node.
   *
   * So that we can add the hreflangs of other languages without the editor
   * having to choose all languages for a node.
   *
   * @param string $nodeId
   *   The node id.
   * @param \Drupal\Core\Database\Connection $siteConnection
   *   The database connection.
   *
   * @return bool
   *   Returns false if no connection.
   */
  public function getReferencedNodeLanguages($nodeId, Connection $siteConnection) {

    if (!$siteConnection) {
      return FALSE;
    }

    return $siteConnection->select('node_field_data', 'n')
      ->fields('n', ['langcode'])
      ->condition('nid', $nodeId, '=')
      ->condition('status', 1, '=')
      ->execute()
      ->fetchCol();
  }

  /**
   * Obtains node aliases for a node.
   *
   * @param string $source
   *   The source url.
   * @param \Drupal\Core\Database\Connection $siteConnection
   *   The site's connection.
   *
   * @return bool
   *   Returns false if no connection.
   */
  public function getReferencedNodeAliases($source, Connection $siteConnection) {
    if (!$siteConnection) {
      return FALSE;
    }

    return $siteConnection->select('path_alias', 'ua')
      ->fields('ua', ['alias', 'langcode'])
      ->condition('path', $source, '=')
      ->execute()
      ->fetchAllAssoc('langcode', \PDO::FETCH_ASSOC);
  }

  /**
   * Confirms or creates an alias for a node.
   *
   * We use this method to obtain country site aliases with their url prefixes
   * for nodes.
   *
   * @param string $language
   *   The language of the node.
   * @param string $defaultLanguage
   *   The default language of the site.
   * @param array $aliases
   *   An array of the node aliases. This does not contain the language prefix.
   * @param string $nodeId
   *   The node id.
   * @param string $siteUrl
   *   The country site URL.
   *
   * @return string
   *   Returns the alias for the node.
   */
  public function confirmOrCreateAlias($language, $defaultLanguage, array $aliases, $nodeId, $siteUrl) {
    $alias = $siteUrl;

    // We always prefix other languages within sites with the language code.
    if ($language != $defaultLanguage) {
      $alias .= '/' . $language;
    }

    $alias .= $aliases[$language]['alias'] ?? '/node/' . $nodeId;

    return $alias;
  }

  /**
   * Gets all hreflangs on the center site from a center node id.
   *
   * @param string $nodeId
   *   The center site node id.
   *
   * @return mixed
   *   Returns an associative array of hreflangs.
   */
  public function getReferencesByCenterNode($nodeId) {
    $query = 'SELECT href.id, href.referenced_nid, href.referenced_language, href.referenced_country, href.referenced_url
      FROM hreflang_multisite_center_ref href
      WHERE base_nid = :nodeId';

    return $this->connection->query($query, [
      'nodeId' => $nodeId,
    ])->fetchAllAssoc('id', \PDO::FETCH_ASSOC);
  }

  /**
   * Gets a site's node title from the site's node id.
   *
   * @param string $nodeId
   *   The site's node id.
   * @param string $langcode
   *   The langcode of the node.
   * @param \Drupal\Core\Database\Connection $siteConnection
   *   The sites database connection.
   *
   * @return bool
   *   Returns false if no connection.
   */
  public function getSiteNodeTitleFromNodeId($nodeId, $langcode, Connection $siteConnection) {
    if (!$siteConnection) {
      return FALSE;
    }
    $query = 'SELECT n.title
      FROM node_field_data n
      WHERE nid = :nodeId
      AND langcode = :langcode
      LIMIT 1';

    return $siteConnection->query($query, [
      'nodeId' => $nodeId,
      'langcode' => $langcode,
    ])->fetchCol();
  }

  /**
   * Deletes all country site hreflangs by center site node id.
   *
   * @param string $baseNodeId
   *   The center site base node id.
   */
  public function deleteSiteReferencesByBaseNodeId($baseNodeId) {
    $siteHreflangs = $this->getReferencesByCenterNode($baseNodeId);
    $referencedSites = $this->getReferencedSites($siteHreflangs);
    foreach ($referencedSites as $referencedSite) {
      $siteConnection = $this->getSiteConnection($referencedSite);
      $this->deleteSiteHreflangByBaseNodeId($baseNodeId, $siteConnection);
    }
  }

  /**
   * Updates the center site hreflangs for a particular site and node.
   *
   * @param string $referencedNodeId
   *   The referenced site's node id.
   * @param string $baseNodeId
   *   The center site node id.
   * @param string $site
   *   The referenced site code.
   */
  public function updateCenterSiteHreflangs($referencedNodeId, $baseNodeId, $site) {
    $hreflangCenterManager = \Drupal::service('hreflang_multisite_center.manager');

    $siteConnection = $this->getSiteConnection($site);

    $hrefData = [];
    $hrefData['base_nid'] = $baseNodeId;
    $hrefData['referenced_nid'] = $referencedNodeId;
    $hrefData['referenced_country'] = $site;
    $defaultLanguage = $hreflangCenterManager->getHreflangSiteDefaultLanguage($site);
    $siteUrl = $hreflangCenterManager->getHreflangSiteUrl($site);
    $nodeLanguages = $this->getReferencedNodeLanguages($referencedNodeId, $siteConnection);
    $source = '/node/' . $referencedNodeId;
    $nodeAliases = $this->getReferencedNodeAliases($source, $siteConnection);

    foreach ($nodeLanguages as $nodeLanguage) {
      $hrefData['referenced_language'] = $nodeLanguage;
      $hrefData['referenced_url'] = $this->confirmOrCreateAlias($nodeLanguage, $defaultLanguage, $nodeAliases, $referencedNodeId, $siteUrl);
      $hrefData['id'] = $this->getHreflangCenterId($baseNodeId, $nodeLanguage, $site);
      $this->insert($hrefData);
    }
  }

  /**
   * Updates hreflangs for each country site that are referenced by the center.
   *
   * @param string $baseNodeId
   *   The center site base node id.
   */
  public function updateSiteHreflangs($baseNodeId) {

    try {
      $hreflangData = $this->getReferencesByCenterNode($baseNodeId);
      $sitesReferenced = $this->getReferencedSites($hreflangData);

      foreach ($sitesReferenced as $site) {
        $siteConnection = $this->getSiteConnection($site);
        $preparedData = $this->prepareSiteData($hreflangData, $site, $baseNodeId);
        $this->saveHreflangDataToSite($preparedData, $siteConnection);
      }
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addMessage(t('An error occurred and hreflang processing did not complete. Please notify your administrator.'), 'error');

      \Drupal::logger('hreflang_multisite_center')->error($e->getMessage());
    }

  }

  /**
   * Saves hreflang data to a country site.
   *
   * @param array $data
   *   The data containing all hreflang information.
   * @param \Drupal\Core\Database\Connection $siteConnection
   *   The country site database connection.
   */
  protected function saveHreflangDataToSite(array $data, Connection $siteConnection) {
    [$nodeId, $data, $countryCode, $baseNodeId] = $data;
    $siteConnection->merge('hreflang_multisite')
      ->key(['nid' => $nodeId])
      ->fields([
        'href_lang_data' => $data,
        'referenced_country' => $countryCode,
        'base_nid' => $baseNodeId,
      ])
      ->execute();
  }

  /**
   * Obtains a database connection for a country site.
   *
   * @param string $countryCode
   *   The country code of the site to connect to.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection to the site.
   */
  public function getSiteConnection($countryCode) {
    return Database::getConnection($countryCode, 'hreflang_multisite');
  }

  /**
   * Retrieves an array of referenced sites from an array of hreflangs.
   *
   * @param array $hreflangs
   *   An array of hreflangs.
   *
   * @return array
   *   An array of referenced site codes.
   */
  public function getReferencedSites(array $hreflangs) {
    $referencedSites = [];

    foreach ($hreflangs as $hreflang) {
      if (isset($hreflang['referenced_country']) && !isset($referencedSites[$hreflang['referenced_country']])) {
        $referencedSites[] = $hreflang['referenced_country'];
      }
    }

    return $referencedSites;
  }

  /**
   * Prepares data for insertion into the hreflang_multisite table.
   *
   * @param array $hreflangs
   *   An array of hreflang data.
   * @param string $countryCode
   *   The country code.
   * @param int $baseNodeId
   *   The base node id.
   *
   * @return array|bool
   *   An array of prepared data for insertion into site hreflang databases.
   */
  private function prepareSiteData(array $hreflangs, $countryCode, $baseNodeId) {
    $preparedData = [];

    foreach ($hreflangs as $hreflang) {
      // Because all references are from the center site to the country sites,
      // we need to get the node id from that reference to the site so that we
      // can store that as the primary key on the country site.
      if ($hreflang['referenced_country'] == $countryCode) {
        $referencedNodeId = $hreflang['referenced_nid'];
      }

      $preparedData[] = [
        'referenced_language' => $hreflang['referenced_language'],
        'referenced_country' => $hreflang['referenced_country'],
        'referenced_url' => $hreflang['referenced_url'],
      ];
    }

    // Something has gone wrong if there isn't a reference to the site.
    if (!isset($referencedNodeId)) {
      return FALSE;
    }

    $preparedData = serialize($preparedData);

    return [$referencedNodeId, $preparedData, $countryCode, $baseNodeId];
  }

}
