<?php

namespace Drupal\aacc_jumplist_migration\Commands;

use Drush\Commands\DrushCommands;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * A drush command file.
 *
 * @package Drupal\aacc_jumplist_migration\Commands
 */
class AACCJumplistMigration extends DrushCommands {

  /**
   * Drush command that populates the Main Top Menu one content type at a time.
   *
   * @param string $mid
   *   The UUID of the parent menu item.
   * @param string $type
   *   Content type to migrate.
   *
   * @command aacc_jumplist_migration:migrate
   * @aliases jumplist_migration
   *
   * @usage drush9_custom_commands:message mid type
   */
  public function migrate($mid, $type) {

    // Query database for all active nodes of the type provided.
    $database = \Drupal::database();
    $query = $database->select('node_field_data', 'n');

    $query->condition('n.type', $type, '=');
    $query->condition('n.status', 1, '=');
    $query->fields('n', ['nid', 'title']);
    $query->range(0, 1000);
    $query->orderBy('title');
    $results = $query->execute();
    foreach ($results as $result) {
      $this->output()->writeln($result->title);
      $menu_link = MenuLinkContent::create([
        'title' => $result->title,
        'link' => ['uri' => 'internal:/node/' . $result->nid],
        'menu_name' => 'new-navigation',
        'expanded' => FALSE,
        'parent' => 'menu_link_content:' . $mid,
      ]);
      $menu_link->save();
      // Do something with each $record.
    }
  }

  /**
   * Drush command that outputs how to look up the parent UUID.
   *
   * @command aacc_jumplist_migration:help
   * @aliases jumplist_help
   *
   * @usage drush9_custom_commands:help
   */
  public function help() {
    $message = 'To look up the UUID for the parent menu item, get the ID in the admin ui by hovering over the edit link in the admin menu UI and noting the number. Then run:';
    $message .= "\n" . 'drush -l aacc-lto.us sql:query "select id from menu_tree where metadata LIKE \'%\"ID\"%\';"';
    $message .= "\n" . 'Where ID is the number you noted above.';
    $message .= "\n" . 'You should get something like menu_link_content:4e58c362-f758-4947-9358-e000e3fd0664 ';
    $message .= "\n" . 'The ID will be everything after menu_link_content:';
    $this->output()->writeln($message);
  }

}
