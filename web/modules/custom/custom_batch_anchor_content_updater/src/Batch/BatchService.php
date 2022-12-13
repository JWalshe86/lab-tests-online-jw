<?php

namespace Drupal\custom_batch_anchor_content_updater\Batch;

use Drupal\node\Entity\Node;

/**
 * Anchor Batch Service.
 */
class BatchService {

  /**
   * Create batch items.
   */
  public static function createDataset($nodesSet, $searchNodeId, $replacementNodeId, $isRelativeReplacement, $includeRelatedContent, &$context) {

    $nodes = Node::loadMultiple($nodesSet);
    $url_alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $searchNodeId);
    $results = [];
    foreach ($nodes as $node) {
      // $view_builder = \Drupal::entityManager()->getViewBuilder('node');
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      $renderarray = $view_builder->view($node, 'full');
      $html = \Drupal::service('renderer')->renderPlain($renderarray);

      $doc = new \DOMDocument();
      $doc->loadHTML($html);
      foreach ($doc->getElementsByTagName('a') as $current => $anchor) {
        if ($path = parse_url($anchor->getAttribute('href'), PHP_URL_PATH)) {
          $regexOne = preg_match("/^\/node.?\/$searchNodeId$/i", $path);
          $regexTwo = $url_alias === $path;
          $regexThree = $searchNodeId === $path;
          if ($regexOne || $regexTwo || ($isRelativeReplacement && $regexThree)) {
            $context['results'][] = $node->id();
            $results[] = $node->id();
          }
        }
      }
    }

    $datasetFile = fopen("/tmp/custom_batch_anchor_content_updater-dataset.txt", "w") or die("Unable to open file!");

    // Duplicates are expected and needed in $results.
    $txt = implode("|", $results);
    fwrite($datasetFile, $txt);
    fclose($datasetFile);

    $dataset = explode("|", file_get_contents("/tmp/custom_batch_anchor_content_updater-dataset.txt"));
    $dataset = array_chunk($dataset, 10, TRUE);

    $operations = [];

    foreach ($dataset as $index => $nodes) {
      $operations[] = [
        '\Drupal\custom_batch_anchor_content_updater\Batch\BatchService::updateNodes',
        [
          $nodes,
          $searchNodeId,
          $replacementNodeId,
          $isRelativeReplacement,
          $includeRelatedContent,
        ],
      ];
    }

    $batch = [
      'title' => t('Updating @num node(s)', ['@num' => count($operations)]),
      'operations' => $operations,
      'finished' => '\Drupal\custom_batch_anchor_content_updater\Batch\BatchService::produceFinalReport',
    ];

    // Add batch operations as new batch sets.
    batch_set($batch);

  }

  /**
   * Update nodes batch callback.
   *
   * @param mixed $nodesSet
   *   A batch of items.
   * @param mixed $searchNodeId
   *   THe search node id.
   * @param mixed $replacementNodeId
   *   THe replacement node id.
   * @param mixed $isRelativeReplacement
   *   If the replacement is relative.
   * @param mixed $includeRelatedContent
   *   If the related content should be included.
   * @param array $context
   *   Batch context.
   */
  public static function updateNodes($nodesSet, $searchNodeId, $replacementNodeId, $isRelativeReplacement, $includeRelatedContent, array &$context) {
    foreach ($nodesSet as $index => $nid) {
      $node = NULL;
      $bundle = NULL;
      $node = Node::load($nid);

      if ($node === NULL) {
        continue;
      }

      $bundle = $node->bundle();
      self::updateFieldValues($node, $bundle, $searchNodeId, $replacementNodeId, $isRelativeReplacement, $includeRelatedContent);
    }
  }

  /**
   * Update a nodes values.
   *
   * @param mixed $entity
   *   The entity instance.
   * @param mixed $bundle
   *   The bundle.
   * @param mixed $searchNodeId
   *   THe search node id.
   * @param mixed $replacementNodeId
   *   THe replacement node id.
   * @param mixed $isRelativeReplacement
   *   If the replacement is relative.
   * @param mixed $includeRelatedContent
   *   If the related content should be included.
   */
  public static function updateFieldValues($entity, $bundle, $searchNodeId, $replacementNodeId, $isRelativeReplacement, $includeRelatedContent) {
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

            $url_alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $searchNodeId);

            foreach ($doc->getElementsByTagName('a') as $current => $anchor) {
              if ($path = parse_url($anchor->getAttribute('href'), PHP_URL_PATH)) {
                $regexOne = preg_match("/^\/node.?\/$searchNodeId$/i", $path);
                $regexTwo = $url_alias === $path;
                $regexThree = $searchNodeId === $path;
                if ($regexOne || $regexTwo || ($isRelativeReplacement && $regexThree)) {

                  $options = [
                    'absolute' => FALSE,
                    'https' => TRUE,
                  ];

                  $newAnchor = $doc->createElement('a', $anchor->nodeValue);

                  $replacementAlias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $replacementNodeId);
                  $replacementNode = Node::load($replacementNodeId);
                  $newAnchor->setAttribute('href', '/node/' . $replacementNodeId);

                  if ($anchor->hasAttributes()) {
                    foreach ($anchor->attributes as $attr) {
                      $allowed = [
                        'target',
                        'class',
                        'id',
                        'rel',
                        'title',
                        'data-entity-substitution',
                        'data-entity-type',
                      ];
                      if (in_array($attr->nodeName, $allowed)) {
                        $newAnchor->setAttribute($attr->nodeName, $attr->nodeValue);
                      }
                    }
                  }
                  if (!$newAnchor->hasAttribute('data-entity-substitution')) {
                    $newAnchor->setAttribute('data-entity-substitution', 'canonical');
                  }
                  if (!$newAnchor->hasAttribute('data-entity-type')) {
                    $newAnchor->setAttribute('data-entity-type', 'node');
                  }
                  if (!$newAnchor->hasAttribute('data-entity-uuid')) {
                    $newAnchor->setAttribute('data-entity-uuid', $replacementNode->uuid());
                  }
                  $anchor->parentNode->replaceChild($newAnchor, $anchor);
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
                self::updateSubContentParagraphItems($paragraph, $searchNodeId, $replacementNodeId, $isRelativeReplacement, $includeRelatedContent);
              }
            }
          }
          break;

        case 'entity_reference':
          // This case cannot be generic, it must be written for specific field
          // instances. This is true because entity_reference fields can be
          // used for a plethora of different reasons.
          $updateEntity = FALSE;
          if ($bundle === 'related_content' && !$isRelativeReplacement && $includeRelatedContent) {
            $entityRefsArray = $entity->get($index)->getValue();
            $allowedFields = [
              'field_related_articles' => 'article',
              'field_related_tests' => 'test',
              'field_basic_pages' => 'page',
              'field_related_news_items' => 'news_item',
              'field_related_conditions' => 'condition',
              'field_articles' => 'article',
            ];
            if (array_key_exists($index, $allowedFields) && count($entityRefsArray)) {
              $counter = 0;
              $replacementNodeBundle = Node::load($replacementNodeId)->bundle();
              foreach ($entityRefsArray as $key => $entityRef) {
                $currentNodePath = '/node/' . $entity->get($index)->getValue()[$counter]['target_id'];
                $currentNodeAlias = \Drupal::service('path_alias.manager')->getAliasByPath($currentNodePath);
                if ($entity->get($index)->getValue()[$counter]['target_id'] === $searchNodeId &&
                    $allowedFields[$index] === $replacementNodeBundle
                ) {
                  $entity->$index[$counter] = ['target_id' => $replacementNodeId];
                  $updateEntity = TRUE;
                }
                $counter++;
              }
            }
          }
          if ($updateEntity) {
            $entity->save();
          }
          break;

        case 'link':
          $links = $entity->get($index)->getValue();
          $updatedLinks = [];
          foreach ($links as $key => $link) {
            $thislink = $entity->get($index)->get($key)->getUrl();

            if ($thislink->isRouted() && count($thislink->getRouteParameters()) && array_key_exists('node', $thislink->getRouteParameters())) {
              // Is an Entity Reference.
              $newNode = Node::load($replacementNodeId);
              $updatedLinks[] = [
                'uri' => "entity:node/{$replacementNodeId}",
                'title' => $newNode->getTitle(),
                'options' => $links[$key]['options'],
              ];
            }
            else {
              $updatedLinks[] = $links[$key];
            }
          }
          $entity->set($index, $updatedLinks);
          $entity->save();
          break;
      }
    }
  }

  /**
   * Recursive for paragraph field inception.
   */
  public static function updateSubContentParagraphItems($entity, $searchNodeId, $replacementNodeId, $isRelativeReplacement, $includeRelatedContent) {
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
          case 'entity_reference':
          case 'link':
            self::updateFieldValues($entity, $bundle, $searchNodeId, $replacementNodeId, $isRelativeReplacement, $includeRelatedContent);
            break;

          case 'entity_reference_revisions':
            $isParagraph = FALSE;
            if ($currentField && $currentField->get('settings') && array_key_exists('handler', $currentField->get('settings')) && $isParagraph = $currentField->get('settings')['handler']) {
              if ($isParagraph !== FALSE && strpos($currentField->get('settings')['handler'], ':paragraph') !== FALSE) {
                $paragraphRefs = $entity->get($currentField->getName())->referencedEntities();
                foreach ($paragraphRefs as $paragraph) {
                  self::updateSubContentParagraphItems($paragraph, $searchNodeId, $replacementNodeId, $isRelativeReplacement, $includeRelatedContent);
                }
              }
            }
            break;
        }
      }
    }
  }

  /**
   * Batch completion callback.
   */
  public static function produceFinalReport($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      dump('all nodes updated appropriately');
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      dump('error while creating dataset');
    }

    $lockFile = '/tmp/custom_batch_anchor_content_updater-lock.txt';
    if (file_exists($lockFile)) {
      unlink($lockFile);
    }

  }

}
