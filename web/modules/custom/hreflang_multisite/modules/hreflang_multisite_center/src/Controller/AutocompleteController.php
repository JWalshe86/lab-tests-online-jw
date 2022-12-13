<?php

namespace Drupal\hreflang_multisite_center\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Database\Database;

/**
 * Defines the Autocomplete Controller class.
 */
class AutocompleteController extends ControllerBase {

  /**
   * Returns the autocomplete label and node id.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request from the field.
   * @param string $countryCode
   *   The country code.
   * @param string $langcode
   *   The node's langcode.
   * @param string $type
   *   Content type the field is on.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Array of country paths associated with the node.
   */
  public function handleAutocomplete(Request $request, $countryCode, $langcode, $type) {
    $return = [];
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      // $typed_string = Unicode::strtolower(array_pop($typed_string));
      $typed_string = mb_strtolower(array_pop($typed_string));
      $connection = Database::getConnection($countryCode, 'hreflang_multisite');

      $query = $connection->select('node_field_data', 'n')
        ->fields('n', ['title', 'nid'])
        ->condition('n.title', '%' . $typed_string . '%', 'like')
        ->condition('n.type', $type, '=')
        ->condition('n.langcode', $langcode, '=')
        ->condition('n.status', 1, '=');

      $executed = $query->execute();
      $results = $executed->fetchAll(\PDO::FETCH_OBJ);

      foreach ($results as $row) {
        $return[] = [
          'value' => $row->title . ' (' . $row->nid . ')',
          'label' => $row->title,
        ];
      }
    }

    return new JsonResponse($return);
  }

}
