<?php

namespace Drupal\aacc_feeds\Commands;

use Drupal\taxonomy\Entity\Term;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
class FeedCommands extends DrushCommands {

  /**
   * Sets up taxonomy terms for tagging sub-content.
   *
   * @param string $lang
   *   Argument provided to the drush command:
   *   ['en', 'br', 'cn', 'es', 'hu', 'it', 'kr', 'tr', 'uk'].
   *
   * @command feed:installSubContentTerms
   * @aliases af-terms
   * @usage feed:install_sub_content_terms en
   *   Populates Taxonomy 'sub_content_types'
   *   with default terms for the input language code.
   */
  public function installSubContentTerms($lang) {
    // Adds default terms to the Sub_Content taxonomy
    // required by this module.
    $vocab = 'sub_content_types';
    $termList['br'] = [];
    $termList['cn'] = [];
    $termList['es'] = [];
    $termList['hu'] = [];
    $termList['it'] = [];
    $termList['kr'] = [];
    $termList['tr'] = [];
    $termList['uk'] = [
      'Condition Sub-Content Sections' => [
        'About',
        'Related Pages',
        'Sources',
        'Summary',
      ],
      'Screening Sub-Content Sections' => [
        'Additional Resources',
        'Intro',
        'Recommendations',
        'Sources',
      ],
      'Test Sub-Content Sections' => [
        'At a Glance',
        'Common Questions',
        'Related Pages',
        'Sources',
        'What is being Tested?',
      ],
      'Not Feeds Related' => [],
      'Always Include in Feed' => [],
    ];
    $termList['en'] = [
      'Condition Sub-Content Sections' => [
        'About',
        'Related Pages',
        'Sources',
        'Summary',
      ],
      'Screening Sub-Content Sections' => [
        'Additional Resources',
        'Intro',
        'Recommendations',
        'Sources',
      ],
      'Test Sub-Content Sections' => [
        'At a Glance',
        'Common Questions',
        'Related Pages',
        'Sources',
        'What is being Tested?',
      ],
      'Not Feeds Related' => [],
      'Always Include in Feed' => [],
    ];

    if (isset($termList[$lang])) {
      foreach ($termList[$lang] as $term => $subterms) {
        $parent = $this->insertTerm($vocab, $term);
        $parent_id = $parent->id();
        foreach ($subterms as $subterm) {
          $this->insertTerm($vocab, $subterm, $parent_id);
        }
      }
      $this->output()->writeln("Sub-Content Taxonomy set up for AACC Feeds.");
    }
    else {
      $this->output()->writeln(
        "You must provide a one of the AACC-enabled language codes with this command
         ('en', 'br', 'cn', 'es', 'hu', 'it', 'kr', 'tr', 'uk')."
          );
    }
  }

  /**
   * Inserts individual subterm at the proper level.
   *
   * @param string $vocab
   *   Taxonomy ID.
   * @param string $term
   *   New Taxonomy Term.
   * @param string|int $parent_id
   *   Parent Term id.
   *
   * @return string|object
   *   Either the incoming term string or
   *   the term object if created successfully.
   */
  private function insertTerm($vocab, $term, $parent_id = 0) {
    try {
      $term = Term::create([
        'parent' => [$parent_id],
        'name' => $term,
        'vid' => $vocab,
      ]);
      $term->save();
    }
    catch (\Exception $e) {
      echo 'Caught exception: ', $e->getMessage(), "\n";
    }
    return $term;
  }

  /**
   * Drush function to send a test email for summary.
   *
   * @param int $feed_id
   *   An id of a single feed to process.
   * @param string $test_email
   *   An email to send the email to.
   * @param bool $interval
   *   An optional interval string to process.
   *
   * @command feed:test-mail
   * @aliases af-mail
   * @usage feed:test-mail 1 me@email.com
   *
   * @return bool
   *   Returns whether successful or not.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testMail($feed_id, $test_email, $interval = FALSE) {
    module_load_include('module', 'aacc_feeds');

    if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
      $this->output()->writeln('A test email to sent to is required.');
      return FALSE;
    }

    $intervals = [
      'weekly' => 'Weekly',
      'daily' => 'Daily',
      'monthly' => 'Monthly',
    ];

    // Default to weekly if not set or incorrectly set.
    $interval = (!$interval || !isset($intervals[strtolower($interval)]))
      ? $intervals['weekly']
      : $intervals[strtolower($interval)];

    $feeds = \Drupal::entityTypeManager()->getStorage('aacc_feeds_feed')->loadByProperties([
      'field_notification_interval' => $interval,
    ]);
    $adjustedTimestamp = _aacc_feeds_timestamp_adjustment($interval);

    $test_feed = [];
    foreach ($feeds as $feed) {
      if ($feed->id() == $feed_id) {
        $test_feed[] = $feed;
        break;
      }
    }

    // Check that the feed id supplied is valid or returns a matching feed
    // for the interval value defined.
    if (!count($test_feed)) {
      $this->output()->writeln('No feeds were found with that id.');
      return FALSE;
    }

    try {
      // Send out the test email using the normal function.
      aacc_feeds_content_change_summary_mail($test_feed, $adjustedTimestamp, $test_email);
      $this->output()->writeln(sprintf('A test email was sent to %s.', $test_email));
      return TRUE;
    }
    catch (\Exception $e) {
      // Display any exceptions in the console.
      $this->output()->writeln(sprintf('Caught exception: %s', $e->getMessage()));
      return FALSE;
    }
  }

}
