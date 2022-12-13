<?php

namespace Drupal\custom_global_glossary_anchor_removal\Commands;

use Drush\Commands\DrushCommands;
use Drupal\custom_global_glossary_anchor_removal\Queries\AnchorQueries;

/**
 * Provides anchor removal command.
 */
class GlossaryAnchorsRemovalDatasetGenerator extends DrushCommands {

  /**
   * Defines remove anchor data set command.
   *
   * @command remove:glossary:definition:anchors:dataset
   * @aliases remove-glossary-links-dataset-generator
   * @usage remove:glossary:definition:anchors:dataset
   */
  public function removeAnchorsDatasetGenerator() {
    $queryGenerator = new AnchorQueries();
    $definitionIds = $queryGenerator->getDefinitionIds();
    $allNodes = $queryGenerator->getAllNodes();
    $operations = [];

    $allNodes = array_chunk($allNodes, 10, TRUE);

    foreach ($allNodes as $index => $nodes) {
      $operations[] = [
        '\Drupal\custom_global_glossary_anchor_removal\Batch\BatchService::createDataset',
        [
          $nodes,
          $definitionIds,
        ],
      ];
    }

    $batch = [
      'title' => t('Updating @num node(s)', ['@num' => count($operations)]),
      'operations' => $operations,
      'finished' => '\Drupal\custom_global_glossary_anchor_removal\Batch\BatchService::processNewDataSet',
    ];

    // Add batch operations as new batch sets.
    batch_set($batch);

    // Process the batch sets.
    drush_backend_batch_process();

  }

}
