<?php

/**
 * @file
 * Command-line tools.
 */

use Drupal\Core\Database\Database;
use Drupal\redirect\Entity\Redirect;
use Drupal\node\Entity\Node;
use Drupal\pathauto\PathautoState;

/**
 * Implements hook_drush_command().
 */
function aacc_migrate_drush_command() {
  $items['aacc-migrate-generate-url-aliases'] = [
    'description' => 'Generate URL aliases for tests and conditions.',
    'options' => [],
    'arguments' => [],
    'examples' => [],
    'drupal dependencies' => ['pathauto'],
    'aliases' => ['aaccgua'],
  ];

  $items['aacc-migrate-redirects'] = [
    'description' => 'Create redirects from old urls.',
    'options' => [],
    'arguments' => [],
    'examples' => [],
    'drupal dependencies' => ['redirect'],
    'aliases' => ['aaccmr'],
  ];

  $items['aacc-migrate-engitel-redirects'] = [
    'description' => 'Create redirects from Engitel country site old urls.',
    'options' => [],
    'arguments' => [],
    'examples' => [],
    'drupal dependencies' => ['redirect'],
    'aliases' => ['aaccmer'],
  ];

  $items['aacc-migrate-hreflangs'] = [
    'description' => 'Create hreflangs.',
    'options' => [],
    'arguments' => [],
    'examples' => [],
    'drupal dependencies' => ['hreflang_multisite'],
    'aliases' => ['aacchref'],
  ];

  return $items;
}

/**
 * Regenerates URL aliases for an content type.
 */
function drush_aacc_migrate_hreflangs() {
  $centerNodes = aacc_migrate_get_center_nodes();

  foreach ($centerNodes as $centerNode) {
    aacc_migrate_find_and_generate_hreflangs($centerNode['entity_id'], $centerNode['field_oldpath_value']);
  }

  echo 'done hreflangs';
}

/**
 * Get all center nodes that have a field_oldpath_value.
 */
function aacc_migrate_get_center_nodes() {
  $connection = Database::getConnection();

  // I am ignoring values that start with http and https because
  // these were mistakenly entered by the client and are not representative
  // of the data that originally came from the migrated databases.
  $query = "SELECT n.entity_id, n.field_oldpath_value
  FROM node__field_oldpath n
  WHERE n.field_oldpath_value NOT LIKE 'https://%'
  AND n.field_oldpath_value NOT LIKE 'http://%'";

  return $connection->query($query)->fetchAll(PDO::FETCH_ASSOC);

}

/**
 * Find all matching country site nodes and generate the hreflangs.
 */
function aacc_migrate_find_and_generate_hreflangs($centerNodeId, $path) {
  $hreflangCenterManager = \Drupal::service('hreflang_multisite_center.manager');
  $hreflangCenterStorage = \Drupal::service('hreflang_multisite_center.manager_storage');

  $sites = $hreflangCenterManager->getHreflangSites();

  if (!$sites) {
    return;
  }

  foreach ($sites as $site) {
    $connection = $hreflangCenterStorage->getSiteConnection($site);
    $match = aacc_migrate_get_country_site_match_by_old_path($path, $connection);

    if (!empty($match)) {
      $hreflangCenterStorage->updateCenterSiteHreflangs($match[0], $centerNodeId, $site);
    }
  }
  $hreflangCenterStorage->updateSiteHreflangs($centerNodeId);
}

/**
 * Gets a country site node ID by the US site path.
 */
function aacc_migrate_get_country_site_match_by_old_path($path, $connection) {
  if (!$connection) {
    return FALSE;
  }
  $query = 'SELECT n.entity_id
    FROM node__field_oldpath n
    WHERE n.field_oldpath_value = :path
    OR n.field_oldpath_value = :path2';

  // KR uses lm instead of understanding.
  return $connection->query($query, [
    'path' => $path,
    'path2' => str_replace('understanding', 'lm', $path),
  ])->fetchCol();
}

/**
 * Regenerates URL aliases for an content type.
 */
function drush_aacc_migrate_generate_url_aliases($type) {
  $nids = aacc_migrate_get_nodes_by_type($type);
  foreach ($nids as $nid) {
    aacc_migrate_set_generate_path_alias_for_node($nid);
  }
  echo 'done' . $type;
}

/**
 * Get all nodes by type.
 */
function aacc_migrate_get_nodes_by_type($type) {
  $language = \Drupal::languageManager()->getDefaultLanguage();
  $query = \Drupal::entityQuery('node');
  $query->condition('type', $type);

  $query->condition('langcode', $language->getId());

  $ids = $query->execute();
  return $ids;
}

/**
 * Regenerates the path aliases for a node and saves the node.
 */
function aacc_migrate_set_generate_path_alias_for_node($nid) {
  $node = Node::load($nid);
  if (!$node) {
    return;
  }
  $node->path->pathauto = PathautoState::CREATE;
  $node->save();
}

/**
 * Create redirects.
 */
function drush_aacc_migrate_redirects() {
  $result = Database::getConnection('default', 'migrate')->select('SiteTree_Live', 'tree')->fields('tree')
    ->execute();
  $redirects = [];

  $test_tabs = [
    '/tab/glance',
    '/tab/sample',
    '/tab/test',
    '/tab/faq',
    '/tab/ask',
    '/tab/related',
  ];
  $screening_tabs = ['/article-sources/'];

  foreach ($result as $res) {
    $old_path = _aacc_migrate_get_old_full_path($res->ParentID, $res->URLSegment);
    $new_path = '';

    if ($node = _aacc_migrate_get_node_by_old_path($old_path)) {
      $full_node = node_load($node->entity_id);
      $new_path = 'node/' . $node->entity_id;
      $new_path = \Drupal::service('path.alias_manager')->getAliasByPath('/' . $new_path);
      $node_type = $full_node->getType();
      switch ($node_type) {
        case 'test':
          foreach ($test_tabs as $tab) {
            $redirects[] = ['source' => $old_path . $tab, 'target' => $new_path];
          }
          break;

        case 'condition':
          $paragraph = $full_node->get('field_subcontent')->first()->get('entity')->getTarget()->getValue();
          $body = $paragraph->get('field_body')->value;
          $matches = [];

          preg_match_all("/h2 class=\"section-header\"/", $body, $matches);
          $tab_id = 0;
          if (count($matches[0])) {
            while ($tab_id < count($matches[0]) - 1) {
              $tab_id++;
              $redirects[] = [
                'source' => $old_path . '/start/' . $tab_id,
                'target' => $new_path,
              ];
            }
          }
          break;

        case 'screening':
          foreach ($screening_tabs as $tab) {
            $redirects[] = [
              'source' => $old_path . $tab,
              'target' => $new_path,
            ];
          }
          break;

        default:
          break;
      }
      if (!empty($new_path) && strcasecmp('/' . $old_path, $new_path) !== 0) {
        $redirects[] = [
          'source' => $old_path,
          'target' => $new_path,
        ];
      }
    }
  }
  $repository = \Drupal::service('redirect.repository');
  $query = [];
  $language = \Drupal::service('language.default')->get();
  foreach ($redirects as $item) {
    $redirect_exist = $repository->findBySourcePath($item['source']);
    if (empty($redirect_exist)) {
      $redirect = Redirect::create();
      $redirect->setSource($item['source'], $query);
      $redirect->setRedirect($item['target']);
      $redirect->setStatusCode(301);
      $redirect->setLanguage($language);
      $redirect->save();
    }
  }
}

/**
 * Get full path in legacy db.
 */
function _aacc_migrate_get_old_full_path($parent_id, $url = '') {
  $query = Database::getConnection('default', 'migrate')
    ->select('SiteTree_Live', 'tree')
    ->fields('tree', ['ID', 'ParentID', 'URLSegment']);
  $query->condition('tree.ID', $parent_id);
  $res = $query->execute();
  if ($object = $res->fetchObject()) {
    $url = $object->URLSegment . '/' . $url;
    return _aacc_migrate_get_old_full_path($object->ParentID, $url);
  }
  return $url;
}

/**
 * Try to find old path in field_oldpath field.
 */
function _aacc_migrate_get_node_by_old_path($old_path) {
  $check_node = db_select('node__field_oldpath', 'old_path')->fields('old_path')
    ->condition('old_path.field_oldpath_value', $old_path)->execute();
  if ($node = $check_node->fetchObject()) {
    return $node;
  }
  else {
    $exploded_path = explode('/', $old_path);
    if (count($exploded_path) > 1) {
      array_pop($exploded_path);
      return _aacc_migrate_get_node_by_old_path(implode('/', $exploded_path));
    }
    return NULL;
  }
}

/**
 * Create Engitel redirects.
 */
function drush_aacc_migrate_engitel_redirects() {
  $redirects = [];
  // Engitel redirects are simple because we do not have to worry about child
  // pages loaded using query parameters.
  // Get all nodes on the site that have the old URL path field filled out.
  $nodesWithOldUrls = aacc_migrate_get_nodes_with_old_url_values();

  if (!$nodesWithOldUrls) {
    return;
  }

  foreach ($nodesWithOldUrls as $nodesWithOldUrl) {
    // Get the value of the field_oldpath field.
    $old_path = $nodesWithOldUrl['field_oldpath_value'];
    // Get the current alias for the node.
    $new_path = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $nodesWithOldUrl['entity_id']);

    if (strpos($old_path, 'http') === 0) {
      $old_path = parse_url($old_path, PHP_URL_PATH);
    }

    $old_path = ltrim($old_path, '/');

    if (!empty($new_path) && strcasecmp($old_path, $new_path) !== 0) {
      $redirects[] = ['source' => $old_path, 'target' => $new_path];
      $redirects[] = ['source' => $old_path . '.html', 'target' => $new_path];

    }
  }

  $repository = \Drupal::service('redirect.repository');
  $query = [];
  $language = \Drupal::service('language.default')->get();
  foreach ($redirects as $item) {
    $redirect_exist = $repository->findBySourcePath($item['source']);
    if (empty($redirect_exist)) {
      $redirect = Redirect::create();
      $redirect->setSource($item['source'], $query);
      $redirect->setRedirect($item['target']);
      $redirect->setStatusCode(301);
      $redirect->setLanguage($language);
      $redirect->save();
    }
  }
}

/**
 * Get all nodes that have a value for field_oldpath.
 */
function aacc_migrate_get_nodes_with_old_url_values() {
  $results = db_select('node__field_oldpath', 'old_path')
    ->fields('old_path', ['entity_id', 'field_oldpath_value'])
    ->execute();

  $results = $results->fetchAllAssoc('entity_id', PDO::FETCH_ASSOC);

  return $results;
}
