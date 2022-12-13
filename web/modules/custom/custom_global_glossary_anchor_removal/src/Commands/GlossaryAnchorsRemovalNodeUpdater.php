<?php

namespace Drupal\custom_global_glossary_anchor_removal\Commands;

use Drush\Commands\DrushCommands;
use Drupal\custom_global_glossary_anchor_removal\Queries\AnchorQueries;

/**
 * Defines remove anchors command.
 */
class GlossaryAnchorsRemovalNodeUpdater extends DrushCommands {

  /**
   * Remove anchors command.
   *
   * @command remove:glossary:definition:anchors
   * @aliases remove-glossary-links
   * @usage remove:glossary:definition:anchors
   */
  public function removeAnchors() {
    $queryGenerator = new AnchorQueries();
    $definitionIds = $queryGenerator->getDefinitionIds();
    $modulePath = \Drupal::service('file_system')->realpath(\Drupal::service('module_handler')->getModule('custom_global_glossary_anchor_removal')->getPath());
    $configDatasetsPath = $modulePath . '/config/datasets/';
    $dataset = explode("|", file_get_contents($configDatasetsPath . "dataset.txt"));
    $dataset = array_chunk($dataset, 10, TRUE);

    $operations = [];
    foreach ($dataset as $index => $nodes) {
      $operations[] = [
        '\Drupal\custom_global_glossary_anchor_removal\Batch\BatchService::updateNodes',
        [
          $nodes,
          $definitionIds,
        ],
      ];
    }

    $batch = [
      'title' => t('Updating @num node(s)', ['@num' => count($operations)]),
      'operations' => $operations,
      'finished' => '\Drupal\custom_global_glossary_anchor_removal\Batch\BatchService::produceFinalReport',
    ];

    // Add batch operations as new batch sets.
    batch_set($batch);

    // Process the batch sets.
    drush_backend_batch_process();

  }

}
