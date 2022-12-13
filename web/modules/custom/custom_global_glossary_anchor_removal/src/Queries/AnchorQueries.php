<?php

namespace Drupal\custom_global_glossary_anchor_removal\Queries;

/**
 * Query for anchor links.
 */
class AnchorQueries {

  /**
   * Definition id list.
   *
   * @var string
   */
  private $definitionIds;

  /**
   * Node results.
   *
   * @var array|int
   */
  private $allNodes;

  /**
   * Create query instance.
   */
  public function __construct() {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'definition')
      ->execute();
    $this->definitionIds = implode('|', $query);

    $query = \Drupal::entityQuery('node');
    $group = $query
      ->orConditionGroup()
      ->condition('type', 'test')
      ->condition('type', 'article')
      ->condition('type', 'page')
      ->condition('type', 'condition')
      ->condition('type', 'definition')
      ->condition('type', 'news_item')
      ->condition('type', 'screening');

    $this->allNodes = $query
      ->condition($group)
      ->execute();

  }

  /**
   * Get ids.
   *
   * @return string
   *   Array of anchor ids.
   */
  public function getDefinitionIds() {
    return $this->definitionIds;
  }

  /**
   * Get node instances.
   *
   * @return array
   *   Array of nodes.
   */
  public function getAllNodes() {
    return $this->allNodes;
  }

}
