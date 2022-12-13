<?php

namespace Drupal\aacc_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\ProcessPluginBase;

/**
 * Get language code by language name.
 *
 * @MigrateProcessPlugin(
 *   id = "linkit_replace"
 * )
 *
 * @endcode
 */
class LinkitReplace extends ProcessPluginBase {

  /**
   * The current migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    $matches = [];

    preg_match_all("/\[sitetree_link,id=(.+?)\]/", $value, $matches);

    if (!empty($matches[1])) {
      $source = $this->migration->getSourcePlugin();
      foreach ($matches[1] as $match) {
        $migration = $source->getMigrationByLegacyId($match);
        if (!empty($migration)) {
          $destination_ids = $this->handleMigration($migrateExecutable, $migration, $match);
          if (!empty($destination_ids)) {
            $value = preg_replace("/\[sitetree_link,id=" . $match . "\]/", '/node/' . $destination_ids['dest'], $value);
          }
        }
      }
    }

    return $value;
  }

  /**
   * Handle migration.
   */
  protected function handleMigration($migrateExecutable, $migration, $value) {
    $stub_migration_options = [
      'dest' => [
        [
          'plugin' => 'migration',
          'migration' => $migration,
          'source' => 'migration_source',
        ],
      ],
    ];
    $source = [
      'migration_source' => $value,
    ];
    $new_row = new Row($source, []);
    $migrateExecutable->processRow($new_row, $stub_migration_options, $match);
    $destination = $new_row->getDestination();
    return $destination;
  }

}
