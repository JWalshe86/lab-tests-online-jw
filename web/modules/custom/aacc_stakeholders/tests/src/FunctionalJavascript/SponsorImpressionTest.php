<?php

namespace Drupal\Tests\aacc_stakeholders\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
// Use Drupal\FunctionalJavascriptTests\JavascriptTestBase;.
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the sponsor impressions display and tracking functionality.
 *
 * @group aacc_stakeholders
 */
/**
 * Class SponsorImpressionTest extends JavascriptTestBase {.
 */
class SponsorImpressionTest extends WebDriverTestBase {

  use TestFileCreationTrait;

  /**
   * The term id of the Sponsor taxonomy term.
   *
   * @see aacc_stakeholders.module.
   */
  const SPONSOR_TID = 12;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'aacc_stakeholders',
  ];

  /**
   * The keyword.
   *
   * @var Drupal\taxonomy\Entity\Term
   */
  protected $keyword;

  /**
   * The node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * The Sponsorship level.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $sponsorshipLevel;

  /**
   * The Sponsor node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $sponsor;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $node_type = NodeType::create([
      'type' => 'condition',
      'name' => 'Condition',
    ]);
    $node_type->save();

    $field_storage = FieldStorageConfig::loadByName('node', 'field_keywords');
    $field_storage->save();
    $field = FieldConfig::create([
      'bundle' => 'condition',
      'field_storage' => $field_storage,
    ]);
    $field->save();

    $keyword = Term::create([
      'vid' => 'keyword',
      'name' => $this->randomString(),
    ]);
    $keyword->save();
    $this->keyword = $keyword;

    $sponsor_term = Term::create([
      'vid' => 'stakeholder_type',
      'name' => 'Sponsor',
      'tid' => self::SPONSOR_TID,
    ]);
    $sponsor_term->save();

    $sponsorship_level = Term::create([
      'vid' => 'sponsorship_level',
      'name' => 'Sponsorship level 1',
      'field_imp_per_mo' => 100,
    ]);
    $sponsorship_level->save();
    $this->sponsorshipLevel = $sponsorship_level;

    $sponsored_keyword = Paragraph::create([
      'type' => 'sponsor_keyword',
      'field_impressions' => 0,
      'field_sponsor_keyword' => $keyword->id(),
    ]);
    $sponsored_keyword->save();

    $image_files = $this->getTestFiles('image');
    $image = $image_files[0];

    $sponsor_logo = File::create([
      'uri' => $image->uri,
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $sponsor_logo->save();

    $sponsor = Node::create([
      'type' => 'stakeholder',
      'title' => 'Sponsor A',
      'field_sponsor_level' => $sponsorship_level->id(),
      'field_stakeholder_type' => self::SPONSOR_TID,
      'field_sponsor_imps' => [$sponsored_keyword],
      'field_logo' => [
        'target_id' => $sponsor_logo->id(),
        'alt' => 'Sponsor A logo ALT',
        'title' => 'Sponsor A logo TITLE',
      ],
    ]);
    $sponsor->save();
    $this->sponsor = $sponsor;

    $node = Node::create([
      'type' => 'condition',
      'title' => $this->randomString(),
      'field_body' => $this->randomString(),
      'field_keywords' => [$this->keyword->id()],
    ]);
    $node->save();
    $this->node = $node;

    $this->drupalPlaceBlock('stakeholder_display', [
      'id' => 'stakeholder_display',
      'region' => 'content',
    ]);
  }

  /**
   * Tests that a keyword triggers a Sponsor display and impression tracking.
   */
  public function testSponsoredImpressionLiveTracking() {
    $sponsor = Node::load($this->sponsor->id());
    $sponsored_keyword = $sponsor->field_sponsor_imps->first()->entity;
    $this->assertEquals(0, (int) $sponsored_keyword->field_impressions->value);

    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextContains('Proudly sponsored by...');
    $this->assertSession()->linkByHrefExists($this->sponsor->toUrl()->toString());
    $sponsored_keyword = Paragraph::load($sponsored_keyword->id());
    $this->assertEquals(1, (int) $sponsored_keyword->field_impressions->value);
  }

  /**
   * Waits for the given time or until the given JS condition becomes TRUE.
   *
   * @param string $condition
   *   JS condition to wait until it becomes TRUE.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 1000.
   * @param string $message
   *   (optional) A message to display with the assertion. If left blank, a
   *   default message will be displayed.
   *
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   *
   * @see \Behat\Mink\Driver\DriverInterface::evaluateScript()
   */
  protected function assertJsCondition($condition, $timeout = 1000, $message = '') {
    $message = $message ?: "Javascript condition met:\n" . $condition;
    $result = $this->getSession()->getDriver()->wait($timeout, $condition);
    $this->assertNotEmpty($result, $message);
  }

  /**
   * Waits for jQuery to become active and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

}
