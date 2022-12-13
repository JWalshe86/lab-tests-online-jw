<?php

namespace Drupal\hreflang_multisite_center;

use Drupal\Component\Utility\Tags;

/**
 * Autocomplete Manager service.
 */
class AutocompleteManager {

  /**
   * Retrieves the entity label from a node Id, langcode and country code.
   *
   * @param string $nodeId
   *   The node id.
   * @param string $langcode
   *   The langcode of the node.
   * @param string $countryCode
   *   The site code/country code.
   *
   * @return mixed|string
   *   A string containing the entity label.
   */
  public static function getEntityLabels($nodeId, $langcode, $countryCode) {
    $hreflangCenterStorage = \Drupal::service('hreflang_multisite_center.manager_storage');

    $countryConnection = $hreflangCenterStorage->getSiteConnection($countryCode);
    $entityLabel = $hreflangCenterStorage->getSiteNodeTitleFromNodeId($nodeId, $langcode, $countryConnection);

    if (!$entityLabel) {
      return 'Error. No title available.';
    }

    $entityLabel = reset($entityLabel);

    // Labels containing commas or quotes must be wrapped in quotes.
    $entityLabel .= ' (' . $nodeId . ')';
    $entityLabel = Tags::encode($entityLabel);

    return $entityLabel;
  }

  /**
   * Extracts the entity ID from the autocompletion result.
   *
   * @param string $input
   *   The input coming from the autocompletion result.
   *
   * @return mixed|null
   *   An entity ID or NULL if the input does not contain one.
   */
  public static function extractEntityIdFromAutocompleteInput($input) {
    $match = NULL;

    // Take "label (entity id)', match the ID from inside the parentheses.
    if (preg_match("/.+\s\(([^\)]+)\)/", $input, $matches)) {
      $match = $matches[1];
    }

    return $match;
  }

}
