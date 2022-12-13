<?php

namespace Drupal\hreflang_multisite;

use Drupal\Core\Database\Connection;

/**
 * Hreflang Storage service.
 */
class HreflangStorage {

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
   * Obtains the hreflang data for a node id.
   *
   * @param string $nodeId
   *   The node id.
   *
   * @return mixed
   *   The hreflang data used to form hreflangs.
   */
  public function getReferenceDataByNode($nodeId) {
    $query = 'SELECT href.href_lang_data
      FROM hreflang_multisite href
      WHERE nid = :nodeId';

    $data = $this->connection->query($query, [
      'nodeId' => $nodeId,
    ])->fetchField();

    return unserialize($data);
  }

  /**
   * Returns hreflang references by node id.
   *
   * @param string $nodeId
   *   The node id.
   *
   * @return mixed
   *   Hreflang references for the country site.
   */
  public function getReferenceByNode($nodeId) {
    $query = 'SELECT href.referenced_country, href.base_nid
      FROM hreflang_multisite href
      WHERE nid = :nodeId';

    return $this->connection->query($query, [
      'nodeId' => $nodeId,
    ])->fetchAll(\PDO::FETCH_ASSOC);

  }

}
