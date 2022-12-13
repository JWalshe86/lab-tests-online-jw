<?php

namespace Drupal\migrate_source_mssql\Plugin\migrate\source;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * Sources whose data may be fetched via DBTNG.
 *
 * By default, an existing database connection with key 'migrate' and target
 * 'default' is used. These may be overridden with explicit 'key' and/or
 * 'target' configuration keys. In addition, if the configuration key 'database'
 * is present, it is used as a database connection information array to define
 * the connection.
 */
abstract class MssqlBase extends SourcePluginBase implements ContainerFactoryPluginInterface {

  /**
   * The query string.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $query;

  /**
   * The PDO object.
   *
   * @var \PDO
   */
  protected $database;

  /**
   * State service for retrieving database info.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The count of the number of batches run.
   *
   * @var int
   */
  protected $batch = 0;

  /**
   * Number of records to fetch from the database during each batch.
   *
   * A value of zero indicates no batching is to be done.
   *
   * @var int
   */
  protected $batchSize = 0;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state')
    );
  }

  /**
   * Prints the query string when the object is used as a string.
   *
   * @return string
   *   The query string.
   */
  public function __toString() {
    return (string) $this->query();
  }

  /**
   * Gets the database connection object.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function getDatabase() {
    if (!isset($this->database)) {
      $this->database = $this->setUpDatabase($this->configuration);
    }
    return $this->database;
  }

  /**
   * Gets a connection to the referenced database.
   *
   * This method will add the database connection if necessary.
   *
   * @param array $db_info
   *   Configuration for the source database connection. The keys are:
   *    'key' - The database connection key.
   *    'target' - The database connection target.
   *    'database' - Database configuration array as accepted by
   *      Database::addConnectionInfo.
   *
   * @return \Drupal\Core\Database\Connection
   *   The connection to use for this plugin's queries.
   */
  protected function setUpDatabase(array $db_info) {
    $driver = $db_info['driver'];

    switch ($driver) {
      case 'ODBC':
        $dsn = $conf['dsn'];
        $connection = new \PDO("odbc:$dsn", $db_info['username'], $db_info['password']);
        break;

      case 'DBLIB':
        $connection = new \PDO("dblib:host=" . $db_info['servername'] . ";dbname=" . $db_info['database'], $db_info['username'], $db_info['password']);
        break;

      case 'SQLSRV':
        $connection = new \PDO("sqlsrv:Server=" . $db_info['servername'] . ";Database=" . $db_info['database'], $db_info['username'], $db_info['password']);
        break;
    }

    return $connection;
  }

  /**
   * Implementation of MigrateSource::performRewind().
   *
   * We could simply execute the query and be functionally correct, but
   * we will take advantage of the PDO-based API to optimize the query up-front.
   */
  protected function initializeIterator() {
    $this->getDatabase();

    $sth = $this->database->prepare($this->query());
    $sth->execute();
    $this->result = $sth->fetchAll(\PDO::FETCH_ASSOC);

    return new \ArrayIterator($this->result);
  }

  /**
   * Position the iterator to the following row.
   */
  protected function fetchNextRow() {
    $this->getIterator()->next();
    // We might be out of data entirely, or just out of data in the current
    // batch. Attempt to fetch the next batch and see.
    if ($this->batchSize > 0 && !$this->getIterator()->valid()) {
      $this->fetchNextBatch();
    }
  }

  /**
   * Prepares query for the next set of data from the source database.
   */
  protected function fetchNextBatch() {
    $this->batch++;
    unset($this->iterator);
    $this->getIterator()->rewind();
  }

  /**
   * Create query.
   */
  abstract public function query();

  /**
   * Create count query.
   */
  abstract public function countQuery();

  /**
   * {@inheritdoc}
   */
  public function count() {
    if (!$this->database) {
      $this->getDatabase();
    }
    $stmt = $this->database->prepare($this->countQuery());
    $stmt->execute();

    $count = (int) $stmt->fetchColumn();
    return $count;
  }

}
