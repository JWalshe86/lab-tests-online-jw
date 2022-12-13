<?php

namespace Drupal\custom_global_glossary_anchor_removal\Batch;

use Drupal\node\Entity\Node;

/**
 * Anchor removal batch service.
 */
class BatchService {

  /**
   * Build Batch operations.
   *
   * @param array $nodesSet
   *   The entity ids.
   * @param string $definitionIds
   *   The definition ids.
   * @param array $context
   *   The batch context.
   */
  public static function createDataset(array $nodesSet, $definitionIds, array &$context) {
    $nodes = Node::loadMultiple($nodesSet);
    foreach ($nodes as $node) {
      // $view_builder = \Drupal::entityManager()->getViewBuilder('node');
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      $renderarray = $view_builder->view($node, 'full');
      $html = \Drupal::service('renderer')->renderPlain($renderarray);

      $doc = new \DOMDocument();
      $doc->loadHTML($html);
      foreach ($doc->getElementsByTagName('a') as $current => $anchor) {
        if ($path = parse_url($anchor->getAttribute('href'), PHP_URL_PATH)) {
          $regexOne = preg_match("/\/node.?\/($definitionIds)/i", $path);
          $regexTwo = preg_match("/\/glossary.?\/.[\s\S]*/i", $path);
          if ($regexOne || $regexTwo) {
            $context['results'][] = $node->id();
          }
        }
      }
    }
  }

  /**
   * Batch Finished callback.
   *
   * @param bool $success
   *   Success of the operation.
   * @param array $results
   *   Array of results for post processing.
   * @param array $operations
   *   Array of operations.
   */
  public static function processNewDataSet(bool $success, array $results, array $operations) {
    if ($success) {
      $modulePath = \Drupal::service('file_system')->realpath(\Drupal::service('module_handler')->getModule('custom_global_glossary_anchor_removal')->getPath());
      $configDatasetsPath = $modulePath . '/config/datasets/';
      $datasetFile = fopen($configDatasetsPath . "dataset.txt", "w") or die("Unable to open file!");

      // Duplicates are expected and needed in $results.
      $txt = implode("|", $results);
      fwrite($datasetFile, $txt);
      fclose($datasetFile);
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      dump('error while creating dataset');
    }
  }

  /**
   * Update node callback.
   *
   * @param array $nodesSet
   *   The entity ids.
   * @param string $definitionIds
   *   The definition ids.
   * @param array $context
   *   The batch context.
   */
  public static function updateNodes(array $nodesSet, $definitionIds, array &$context) {
    foreach ($nodesSet as $index => $nid) {
      $node = NULL;
      $bundle = NULL;
      $node = Node::load($nid);
      $bundle = $node->bundle();
      self::updateFieldValues($node, $bundle, $definitionIds);
    }
  }

  /**
   * Update node field values.
   *
   * @param mixed $entity
   *   Entity instances.
   * @param string $bundle
   *   Entity bundle id.
   * @param string $definitionIds
   *   The definition ids string.
   */
  public static function updateFieldValues($entity, $bundle, $definitionIds) {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity->getEntityTypeId(), $bundle);

    foreach ($fields as $index => $currentField) {
      $type = $currentField->getType();

      switch ($type) {
        case 'text':
        case 'text_long':
        case 'string_long':
        case 'text_with_summary':
          if (!empty($entity->get($index)->getValue()) && is_array($entity->get($index)->getValue()) && array_key_exists(0, $entity->get($index)->getValue()) && array_key_exists('value', $entity->get($index)->getValue()[0])) {
            $value = $entity->get($index)->getValue()[0]['value'];
            $format = $entity->get($index)->getValue()[0]['format'];
            $doc = new \DOMDocument();
            $doc->loadHTML(mb_convert_encoding($value, 'HTML-ENTITIES', 'utf-8'));
            $updateEntity = FALSE;

            foreach ($doc->getElementsByTagName('a') as $current => $anchor) {
              if ($path = parse_url($anchor->getAttribute('href'), PHP_URL_PATH)) {
                $regexOne = preg_match("/\/node.?\/($definitionIds)/i", $path);
                $regexTwo = preg_match("/\/glossary.?\/.[\s\S]*/i", $path);
                if ($regexOne || $regexTwo) {
                  $span = $doc->createElement('span', $anchor->nodeValue);
                  $anchor->parentNode->replaceChild($span, $anchor);
                  $updateEntity = TRUE;
                }
              }
            }

            if ($updateEntity) {
              $html = $doc->saveHTML();
              $dom = new \DOMDocument();
              $dom->loadHTML($html);
              $saved_dom = trim($dom->saveHTML());
              $start_dom = stripos($saved_dom, '<body>') + 6;
              $html = substr($saved_dom, $start_dom, strripos($saved_dom, '</body>') - $start_dom);

              $updateArray = [
                'value' => $html,
                'format' => $format,
              ];

              $entity->set($index, $updateArray);
              $entity->save();

            }
          }
          break;

        case 'entity_reference_revisions':
          $isParagraph = FALSE;
          if ($currentField && $currentField->get('settings') && array_key_exists('handler', $currentField->get('settings')) && $isParagraph = $currentField->get('settings')['handler']) {
            if ($isParagraph !== FALSE && strpos($currentField->get('settings')['handler'], ':paragraph') !== FALSE) {
              $paragraphRefs = $entity->get($currentField->getName())->referencedEntities();
              foreach ($paragraphRefs as $paragraph) {
                self::updateSubContentParagraphItems($paragraph, $definitionIds);
              }
            }
          }
          break;

        // END case: 'entity_reference_revisions'.
      }
    }
  }

  /**
   * Recursive for paragraph field inception.
   */
  public static function updateSubContentParagraphItems($entity, $definitionIds) {
    if ($entity !== NULL) {
      $bundle = $entity->bundle();
      $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity->getEntityTypeId(), $bundle);
      foreach ($fields as $index => $currentField) {
        $type = $currentField->getType();
        switch ($type) {
          case 'text':
          case 'text_long':
          case 'string_long':
          case 'text_with_summary':
            self::updateFieldValues($entity, $bundle, $definitionIds);
            break;

          case 'entity_reference_revisions':
            $isParagraph = FALSE;
            if ($currentField && $currentField->get('settings') && array_key_exists('handler', $currentField->get('settings')) && $isParagraph = $currentField->get('settings')['handler']) {
              if ($isParagraph !== FALSE && strpos($currentField->get('settings')['handler'], ':paragraph') !== FALSE) {
                $paragraphRefs = $entity->get($currentField->getName())->referencedEntities();
                foreach ($paragraphRefs as $paragraph) {
                  self::updateSubContentParagraphItems($paragraph, $definitionIds);
                }
              }
            }
            break;

          // END case: 'entity_reference_revisions'.
        }
      }
      return;
    }
  }

  /**
   * Batch completion callback.
   */
  public static function produceFinalReport($success, array $results, array $operations) {
    if ($success) {
      dump('all nodes updated appropriately');
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      dump('error while creating dataset');
    }
  }

}
