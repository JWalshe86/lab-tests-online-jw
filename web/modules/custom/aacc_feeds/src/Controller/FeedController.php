<?php

namespace Drupal\aacc_feeds\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\Response;
use Drupal\node\Entity\Node;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Filters and Formats Feed content.
 */
class FeedController extends ControllerBase {

  /**
   * The feed.
   *
   * @var \Drupal\aacc_feeds\FeedInterface
   */
  protected $aaccFeedsFeed;

  /**
   * The target content type.
   *
   * @var string
   */
  protected $contentType;

  /**
   * The options.
   *
   * @var array
   */
  protected $feedOptions;

  /**
   * Language code.
   *
   * @var string
   */
  protected $langcode;

  /**
   * Content view mode.
   *
   * @var string
   */
  protected $viewMode;

  /**
   * Field content type string.
   *
   * @var string
   */
  protected $fieldContentType;

  /**
   * The link.
   *
   * @var string
   */
  protected $fieldLinkFormat;

  /**
   * The sub content id string.
   *
   * @var string
   */
  protected $fieldPaidSubcontent;

  /**
   * The path map.
   *
   * @var array
   */
  protected $feedPathMap;

  /**
   * The terms for paid states.
   *
   * @var array
   */
  protected $getPaidTerms;

  /**
   * The feed mapping.
   *
   * @var array
   */
  protected $feedFieldsMap;

  /**
   * The feed field map.
   *
   * @var array
   */
  protected $feedItemFieldsMap;

  /**
   * FeedController constructor.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct() {
    $this->feedOptions = $_GET;
    $this->langcode = $this->languageManager()->getCurrentLanguage()->getId();

    $this->viewMode = 'default';
    $this->feedFieldsMap = $this->getFieldsMap();
    $this->feedItemFieldsMap = $this->getItemFieldsMap();
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param int $aacc_feeds_feed
   *   Entity ID.
   * @param string $content_type
   *   String denoting the content type to be returned.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   Returns appropriate access result based on
   *   perms, roles and feed client field.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(AccountInterface $account, $aacc_feeds_feed, $content_type) {
    $this->setAaccFeedsFeed($aacc_feeds_feed);
    $this->setContentFields($content_type);

    $accountRoles = $account->getRoles();
    if (in_array('administrator', $accountRoles)) {
      return AccessResult::allowed();
    }
    elseif (in_array('feeds_admin', $accountRoles)) {
      return AccessResult::allowed();
    }
    elseif ($account->hasPermission('administer feed entity')) {
      return AccessResult::allowed();
    }
    elseif ($this->feedCustomAccessCheck($account) && in_array('feed_client', $accountRoles)) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden("You do not have access to this content.");
  }

  /**
   * Check if the requesting Account matches the client on the Feed entity.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Requesting account.
   *
   * @return bool
   *   Boolean.
   */
  public function feedCustomAccessCheck(AccountInterface $account) {
    if (is_null($this->aaccFeedsFeed)) {
      header('HTTP/1.1 204 Incorrect Data: Invalid Feed Entity ID.');
    }
    else {
      $clients = $this->aaccFeedsFeed->get('field_client')->referencedEntities();
      $fieldClient = array_shift($clients);

      if ($fieldClient->id() === $account->id()) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
  }

  /**
   * Inserts a node of type 'access_log_' each time the API endpoint is called.
   *
   * @param int $aacc_feeds_feed
   *   Feed ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function accessLog($aacc_feeds_feed) {
    if ($this->aaccFeedsFeed->hasField('field_client')) {
      $clients = $this->aaccFeedsFeed->get('field_client')->referencedEntities();
      $client = array_pop($clients);
    }
    $node = Node::create(['type' => 'access_log_']);
    $node->set('field_feed_access_id', $this->aaccFeedsFeed);
    $node->set('title', 'API Endpoint accessed for ' . $this->aaccFeedsFeed->label());
    // Client specifically wants this value in the log.
    $current_path = \Drupal::service('path.current')->getPath();
    if (UrlHelper::isValid($current_path)) {
      $node->set('field_feed_access_url', $current_path);
    }
    $node->set('field_requesting_user', $client->get('mail')->value);
    $node->set('field_feed_timestamp', $_SERVER['REQUEST_TIME']);
    $node->set('field_access_param_since', $this->feedOptions['since']);
    $node->set('field_access_param_deleted', $this->feedOptions['deleted']);
    $node->set('field_access_param_format', $this->feedOptions['_format']);
    $node->save();

    $this->purgeLogs($aacc_feeds_feed);
    \Drupal::messenger()->addMessage("API Access Logged./n");

  }

  /**
   * Purges logs related to the given feed id.
   *
   * @param int $aacc_feeds_feed
   *   Feed id.
   *
   * @return bool
   *   True when successful.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function purgeLogs($aacc_feeds_feed) {
    // Get feed log limit.
    if ($this->aaccFeedsFeed->hasField('field_log_limit')) {
      $logLimit = $this->aaccFeedsFeed->get('field_log_limit')->value;
    }
    else {
      return TRUE;
    }
    // Get log entry count.
    $logs = $this->entityTypeManager()->getStorage('node')->loadByProperties([
      'type' => 'access_log_',
      'field_feed_access_id' => $aacc_feeds_feed,
    ]);
    $logCount = count($logs);
    // Get difference between count and limit.
    $diff = $logCount - $logLimit;
    if ($diff > 0) {
      // Get log entries sorted oldest to newest limit by difference.
      $storage_handler = \Drupal::entityTypeManager()->getStorage("node");
      $ids = \Drupal::entityQuery('node')
        ->condition('type', 'access_log_')
        ->condition('field_feed_access_id', $aacc_feeds_feed, '=')
        ->sort('created', 'ASC')
        ->range(0, $diff)
        ->execute();
      $oldLogs = $storage_handler
        ->loadMultiple($ids);
      $storage_handler->delete($oldLogs);
    }
    return TRUE;
  }

  /**
   * Constructor helper.
   *
   * @param string $content_type
   *   String identifying content entity type to be returned.
   */
  public function setContentFields($content_type) {
    $this->contentType = $content_type;

    $this->fieldContentType = 'field_selected_' . $this->contentType . 's';
    $this->fieldLinkFormat = 'field_' . $this->contentType . '_links';
    $this->fieldPaidSubcontent = 'field_' . $this->contentType . '_sub_content';

  }

  /**
   * Set the AACC Feeds object.
   *
   * @param int $id
   *   Feed id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function setAaccFeedsFeed($id) {
    $this->aaccFeedsFeed = $this->entityTypeManager()->getStorage('aacc_feeds_feed')->load($id);
  }

  /**
   * Sets the Feed Options array.
   *
   * @param array $options
   *   The Request parameters.
   */
  public function setFeedOptions(array $options) {
    $this->feedOptions = $options;
  }

  /**
   * Loads an array with all possible paths for the licensed content.
   *
   * @return array
   *   Array of licensed paths.
   */
  private function getAllValidPaths() {
    $allPaths = [];
    $oldPathField = 'field_oldpath';
    $allContentTypes = [
      'condition',
      'screening',
      'test',
    ];
    // Collect all licensed content nodes.
    foreach ($allContentTypes as $contentType) {
      if ($this->aaccFeedsFeed->hasField('field_selected_' . $contentType . 's')) {
        $list = $this->aaccFeedsFeed->get('field_selected_' . $contentType . 's')->referencedEntities();

        if (count($list) > 0) {
          foreach ($list as $content) {
            // Collect every possible known url path for this content node.
            // First we'll get the legacy URLs so conveniently saved
            // for us during the new site build.
            if ($content->hasField($oldPathField)) {
              $oldPaths = $content->get('field_oldpath');
              foreach ($oldPaths as $path) {
                $tmp = $path->getValue();
                $allPaths[] = '/' . $tmp['value'];
              }
            }
            // Now, the Canonical Drupal path.
            $allPaths[] = '/node/' . $content->id();
            // And finally, the Path Alias.
            $allPaths[] = $content->toUrl()->toString();
          }
        }
      }
    }
    return $allPaths;
  }

  /**
   * Base call function for the routed path.
   *
   * @param int $aacc_feeds_feed
   *   Feed ID.
   * @param string $content_type
   *   Content Entity type identifier.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   JSON or XML encoded content.
   */
  public function getFeed($aacc_feeds_feed, $content_type) {
    $this->setAaccFeedsFeed($aacc_feeds_feed);
    $this->accessLog($aacc_feeds_feed);
    $this->setContentFields($content_type);
    $this->feedPathMap = $this->getAllValidPaths();
    $data = ['items' => []];

    if ($this->aaccFeedsFeed->hasField($this->fieldContentType)) {
      $list = $this->aaccFeedsFeed->get($this->fieldContentType)->referencedEntities();

      if (count($list) > 0) {
        foreach ($list as $content) {
          if ($content->changed->value >= $this->feedOptions['since']) {
            $data['items'][] = $this->normalize($content);
          }
        }
      }
    }
    unset($list);
    unset($content);
    return $this->getEncodedResponse($data);
  }

  /**
   * Custom normalizer for our custom content entity.
   *
   * This was necessary in order to use the client's
   * non-standard URL for the feed.
   *
   * @param \Drupal\node\Entity\Node $content
   *   Test || Screening || Condition content node.
   *
   * @return array
   *   Array of one node's content mapped and normalized for the feed.
   */
  private function normalize(Node $content) {
    $item = [];
    $field_defs = $content->getFieldDefinitions();

    foreach ($field_defs as $key => $value) {

      switch ($key) {
        case 'field_keywords':
          $keywords = $content->get($key)->referencedEntities();
          $value = $this->getKeywords($keywords);
          break;

        case 'field_test_synonyms':
        case 'field_formal_name':
          $formal_names = $content->get($key)->getValue();
          $value = $this->getTextValue($formal_names);
          break;

        case 'field_reviewed':
          $reviewedDate = $content->get($key)->getValue();
          $value = (!empty($reviewedDate)) ? strval(strtotime($reviewedDate[0]['value'])) : NULL;
          break;

        case 'field_subcontent':
          $subcontent = $content->get($key)->getValue();
          $value = $this->getSubContent($subcontent);
          break;

        default:
          $value = $content->get($key)->getValue();
          break;
      }
      $item[$key] = $this->flatten($value);
    }
    $item = $this->getFeedFields($item);
    return $item;
  }

  /**
   * Simply flattens out most of the arrays for clarity.
   *
   * @param mixed $value
   *   Content of the given field.
   *
   * @return mixed
   *   String|array of field value without the [0]['value'].
   */
  private function flatten($value) {
    if (isset($value[0]) && isset($value[0]['value'])) {
      $value = $value[0]['value'];
    }
    return $value;
  }

  /**
   * Maps the required feed fields.
   *
   * Maps them with the appropriate
   * content fields and formats them.
   *
   * @param array $item
   *   Entity instance.
   *
   * @return array
   *   Array of Feed fields of content ready for encoding.
   */
  private function getFeedFields(array $item) {
    $feedData = [];
    foreach ($this->feedItemFieldsMap as $key => $value) {
      if (is_string($value) && isset($item[$value]) && !empty($item[$value])) {
        $feedData[$key] = $item[$value];
      }
      else {
        switch ($key) {
          case 'link':
            $feedData[$key] = $this->getLink();
            break;

          case 'location':
            $feedData[$key] = $this->getLocation();
            break;

          case 'objectType':
            $feedData[$key] = $this->getObjectType();
            break;

          case 'contentType':
            $feedData[$key] = $this->getSubpageType();
            break;

          case 'workflow':
            $feedData[$key] = $this->getWorkflow();
            break;

          case 'path':
            $feedData[$key] = $this->getPath($item);
            break;

          case 'action':
            $feedData[$key] = $this->getAction();
            break;

          case 'description':
            $feedData[$key] = '';
            break;

          default:
            break;
        }
      }
    }
    return $feedData;
  }

  /**
   * Returns the content path for the feed.
   *
   * @param array $item
   *   Entity instance.
   *
   * @return string
   *   string value of entity path.
   */
  private function getPath(array $item) {
    $path = $item['path'][0]['alias'];
    return $path;
  }

  /**
   * Returns a CSV string of the content keywords.
   *
   * @param array $keywords
   *   Array of keyword references.
   *
   * @return string
   *   CSV string of content keywords
   */
  private function getKeywords(array $keywords) {
    $words = [];
    foreach ($keywords as $word) {
      $words[] = $word->getName();
    }
    return implode(',', $words);
  }

  /**
   * Returns a CSV string of the content values.
   *
   * @param array $values
   *   Array of values.
   *
   * @return string
   *   CSV string.
   */
  private function getTextValue(array $values) {
    $words = [];
    foreach ($values as $word) {
      $words[] = $word['value'];
    }
    return implode(',', $words);
  }

  /**
   * Populates getPaidTerms field.
   */
  private function getPaidSubContentTerms() {
    // Load the Sub Content type terms that are
    // selected for this feed and content type.
    $paidSubContent = $this->aaccFeedsFeed->get($this->fieldPaidSubcontent)->referencedEntities();
    // Make a simple array of the term labels for easy searching.
    $this->getPaidTerms = [];
    foreach ($paidSubContent as $paid) {
      $this->getPaidTerms[] = $paid->label();
    }
  }

  /**
   * Returns the correct sub_content_type term.
   *
   * Checks for a field_sub_content_type term attached to the given paragraph.
   * If it exists, it returns it.  Otherwise, it checks and returns a term
   * based on the client's preferences for handling this until they get
   * all the field_sub_content_type terms set for all the paragraphs on all
   * the feed content.
   *
   * If there is no term set, we return all sub-content
   * for screenings and conditions.  For tests, we either return all
   * OR only 'At a Glance' based on the settings for Test Sub Content set
   * in the Feed Entity.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The given Paragraph object.
   *
   * @return string
   *   A string matching the appropriate taxonomy term.
   */
  private function getParagraphSubContentTerm(Paragraph $paragraph) {
    $subContentTerms = [];
    $term = 'Always Include in Feed';
    // Load Sub Content Terms for this paragraph.
    if ($paragraph->hasField('field_sub_content_type')) {
      $subContentTerms = $paragraph->get('field_sub_content_type')->referencedEntities();
    }
    if (!empty($subContentTerms)) {
      // Has the paragraph been tagged??
      $subContentTerm = $subContentTerms[0];
      if (!empty($subContentTerm)) {
        $term = $subContentTerm->label();
      }
      else {
        $subContentTerms = [];
        $term = 'Always Include in Feed';
      }
    }
    if (empty($subContentTerms)) {
      // Paragraph has not been tagged.
      // If it is a Screening or Condition, always return everything.
      // ElseIf it is a test AND getPaidTerms only contains 'At a Glance'
      // Only return the 'At a Glance' grid paragraph.
      // Else return everything.
      if ($this->contentType == 'test') {
        if ($this->getPaidTerms == ['At a Glance']) {
          if ($paragraph->hasField('field_label')) {
            $paragraphLabel = $paragraph->get('field_label')->value;
          }
          else {
            $paragraphLabel = $paragraph->label();
          }
          $term = ($paragraphLabel == 'At a Glance') ? $paragraphLabel : 'Not Feeds Related';
        }
      }
    }
    return $term;
  }

  /**
   * Get sub content for term.
   *
   * @param string|null $term
   *   The term id.
   *
   * @return string
   *   The content type.
   */
  public static function getSubContentByTerm(?string $term): string {
    $sub_content_type = '';

    // Ignore these terms and output an empty string in Feed.
    $ignore_types = [
      'Always Include in Feed',
    ];

    if (!is_null($term) && !in_array($term, $ignore_types)) {
      $sub_content_type = $term;
    }

    return $sub_content_type;
  }

  /**
   * Returns the sub-content paragraphs as text.
   *
   * @param array $subcontent
   *   Array of paragraph entities.
   *
   * @return array
   *   Returns array of paragraph content as text/html.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  private function getSubContent(array $subcontent) {
    $results = [];
    $this->getPaidSubContentTerms();

    // Roll through the subcontent paragraphs for this content entity.
    foreach ($subcontent as $element) {
      $paragraph = Paragraph::load($element['target_id']);
      $term = $this->getParagraphSubContentTerm($paragraph);

      // If $term is 'Always Include in Feed
      // OR we find a match and it is one of the acceptable paragraph types
      // Then we will add it to the feed.
      if (($term == 'Always Include in Feed') || (in_array($term, $this->getPaidTerms))) {
        $ptype = $paragraph->getType();
        switch ($ptype) {
          /*
           * Tests:
          grid
          accordion
          media_gallery
          related_content
          columns
          expandable
          navigation
          looking_for_buttons

          Conditions:
          media_gallery
          related_content
          accordion
          columns
          expandable
          grid
          navigation
          text_area

          Screenings:
          screening_tests
          media_gallery
          related_content
          accordion
          columns
          expandable
          grid
          navigation
          text_area
          related_screening_item

          All Paragraph Types used on feed content:
          accordion
          columns
          expandable
          grid
          looking_for_buttons
          media_gallery
          navigation
          related_content
          related_screening_item
          screening_tests
          text_area
           */
          case 'accordion':
            $results[] = $this->getParagraphAccordion($paragraph, $term);
            break;

          case 'columns':
            $results[] = $this->getParagraphColumns($paragraph, $term);
            break;

          case 'expandable':
            $results[] = $this->getParagraphExpandable($paragraph, $term);
            break;

          case 'grid':
            $results[] = $this->getParagraphGrid($paragraph, $term);
            break;

          case 'looking_for_buttons':
          case 'media_gallery':
          case 'navigation':
            // Do not include per client preference as of Nov. 8, 2018.
            break;

          case 'related_content':
            $results[] = $this->getParagraphRelatedContent($paragraph, $term);
            break;

          case 'related_screening_item':
            $results[] = $this->getParagraphRelatedScreeningItem($paragraph, $term);
            break;

          case 'screening_tests':
            $results[] = $this->getParagraphScreeningTests($paragraph, $term);
            break;

          case 'text_area':
            $results[] = $this->getParagraphTextArea($paragraph, $term);
            break;

          default:
            break;
        }
      }
    }
    return $results;
  }

  /**
   * Returns the paragraph type: accordion.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   Paragraph entity to be formatted.
   * @param string|null $term
   *   The subcontent type term.
   *
   * @return array
   *   Text content of paragraph type accordion.
   */
  private function getParagraphAccordion(Paragraph $paragraph, ?string $term = ''): array {
    $name = $paragraph->get('field_label')->value;
    $text = "";
    if ($paragraph->hasField('field_accordion_items')) {
      $entities = $paragraph->get('field_accordion_items')->referencedEntities();
      // $paragraph->get('field_sub_content')
      foreach ($entities as $entity) {
        if ($entity->hasField('field_label')) {
          $text .= "<h3>" . $entity->get('field_label')->value . "</h3>";
        }
        $text .= $this->cleanText($entity->get('field_body')->value);
      }
    }

    // Set the subcontent_type based on the term variable.
    $sub_content_type = self::getSubContentByTerm($term);

    return [
      'label' => $name,
      'subcontent_type' => $sub_content_type,
      'content' => $text,
    ];
  }

  /**
   * Returns the paragraph type: columns.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   Paragraph entity to be formatted.
   * @param string|null $term
   *   The subcontent type term.
   *
   * @return array
   *   Text content of paragraph type columns.
   */
  private function getParagraphColumns(Paragraph $paragraph, ?string $term = ''): array {
    $name = $paragraph->get('field_label')->value;
    $entities = $paragraph->get('field_column')->referencedEntities();
    $text = "";
    foreach ($entities as $entity) {
      if ($entity->hasField('field_label')) {
        $text .= "<h3>" . $entity->get('field_label')->value . "</h3>";
      }
      $text .= $this->cleanText($entity->get('field_body')->value);
    }

    // Set the subcontent_type based on the term variable.
    $sub_content_type = self::getSubContentByTerm($term);

    return [
      'label' => $name,
      'subcontent_type' => $sub_content_type,
      'content' => $text,
    ];
  }

  /**
   * Returns the paragraph type: expandable.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   Paragraph entity to be formatted.
   * @param string|null $term
   *   The subcontent type term.
   *
   * @return array
   *   Text content of paragraph type expandable.
   */
  private function getParagraphExpandable(Paragraph $paragraph, ?string $term = ''): array {
    $name = $paragraph->get('field_label')->value;
    $text = "<h3>" . $name . "</h3>";
    $text .= $this->cleanText($paragraph->get('field_body')->value);

    // Set the subcontent_type based on the term variable.
    $sub_content_type = self::getSubContentByTerm($term);

    return [
      'label' => $name,
      'subcontent_type' => $sub_content_type,
      'content' => $text,
    ];
  }

  /**
   * Returns the paragraph type: grid.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   Paragraph entity to be formatted.
   * @param string|null $term
   *   The subcontent type term.
   *
   * @return array
   *   Text content of the paragraph type: grid
   */
  private function getParagraphGrid(Paragraph $paragraph, ?string $term = ''): array {
    $name = $paragraph->get('field_label')->value;
    $entities = $paragraph->get('field_text_areas')->referencedEntities();
    $text = "";
    foreach ($entities as $entity) {
      if ($entity->hasField('field_label')) {
        $text .= "<h3>" . $entity->get('field_label')->value . "</h3>";
      }
      $text .= $this->cleanText($entity->get('field_body')->value);
    }

    // Set the subcontent_type based on the term variable.
    $sub_content_type = self::getSubContentByTerm($term);

    return [
      'label' => $name,
      'subcontent_type' => $sub_content_type,
      'content' => $text,
    ];
  }

  /**
   * Returns the paragraph type: related_content.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   Paragraph entity to be formatted.
   * @param string|null $term
   *   The subcontent type term.
   *
   * @return array
   *   Text content of paragraph type related_content.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  private function getParagraphRelatedContent(Paragraph $paragraph, ?string $term = ''): array {
    $name = $paragraph->get('field_label')->value;
    $subFields = [
      'field_articles' => 'Articles',
      'field_related_conditions' => 'Related Conditions',
      'field_elsewhere_on_the_web' => 'Elsewhere on the Web',
      'field_related_news_items' => 'Related News Items',
      'field_related_articles' => 'Related Articles',
      'field_related_media' => 'Related Media',
      'field_related_screenings' => 'Related Screenings',
      'field_related_tests' => 'Related Tests',
    ];
    $text = "<ul>";
    foreach ($subFields as $field => $fieldName) {
      $text .= "<li>" . $fieldName . "<ul>";
      // @todo other subFields may require special handling below.
      switch ($field) {
        case 'field_elsewhere_on_the_web':
          $entities = $paragraph->get($field);

          foreach ($entities as $entity) {
            $text .= '<li><a href="' . $entity->__get('uri') . '">' . $entity->__get('title') . '</a></li>';
          }
          break;

        case 'field_related_screenings':
          $entities = ($paragraph->hasField($field)) ? $paragraph->get($field)->referencedEntities() : [];

          foreach ($entities as $entity) {
            $text .= '<li><a href="' . $entity->__get('uri') . '">' . $entity->__get('title') . '</a></li>';
          }
          break;

        default:
          $entities = ($paragraph->hasField($field)) ? $paragraph->get($field)->referencedEntities() : [];

          /** @var \Drupal\node\NodeInterface $entity */
          foreach ($entities as $entity) {
            $url = '/' . $entity->toUrl()->getInternalPath();

            $text .= '<li><a href="' . $url . '">' . $entity->label() . '</a></li>';
          }
          break;
      }
      $text .= "</ul></li>";
    }
    $text .= "</ul>";
    $text = $this->cleanText($text);

    // Set the subcontent_type based on the term variable.
    $sub_content_type = self::getSubContentByTerm($term);

    return [
      'label' => $name,
      'subcontent_type' => $sub_content_type,
      'content' => $text,
    ];
  }

  /**
   * Returns the paragraph type: related_screening_item.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   Paragraph entity to be formatted.
   * @param string|null $term
   *   The subcontent type term.
   *
   * @return array
   *   Text content of paragraph type related_screening_item.
   */
  private function getParagraphRelatedScreeningItem(Paragraph $paragraph, ?string $term = ''): array {
    $name = $paragraph->label();
    $entities = $paragraph->get('field_related_screening_group')->referencedEntities();
    $text = "";
    foreach ($entities as $entity) {
      $text .= "<h3>" . $entity->label() . "</h3>";
    }

    // Set the subcontent_type based on the term variable.
    $sub_content_type = self::getSubContentByTerm($term);

    return [
      'label' => $name,
      'subcontent_type' => $sub_content_type,
      'content' => $text,
    ];
  }

  /**
   * Returns the paragraph type: screening_tests.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   Paragraph entity to be formatted.
   * @param string|null $term
   *   The subcontent type term.
   *
   * @return array
   *   Text content of paragraph type screening_tests.
   */
  private function getParagraphScreeningTests(Paragraph $paragraph, ?string $term = ''): array {
    $name = $paragraph->get('field_label')->value;

    // Set the subcontent_type based on the term variable.
    $sub_content_type = self::getSubContentByTerm($term);

    return [
      'label' => $name,
      'subcontent_type' => $sub_content_type,
      'content' => '',
    ];
  }

  /**
   * Returns the paragraph type: text_area.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   Paragraph entity to be formatted.
   * @param string|null $term
   *   The subcontent type term.
   *
   * @return array
   *   Text content of paragraph type text_area.
   */
  private function getParagraphTextArea(Paragraph $paragraph, ?string $term = ''): array {
    $name = $paragraph->get('field_label')->value;
    $text = $paragraph->get('field_body')->value;

    // Set the subcontent_type based on the term variable.
    $sub_content_type = self::getSubContentByTerm($term);

    return [
      'label' => $name,
      'subcontent_type' => $sub_content_type,
      'content' => $text,
    ];
  }

  /**
   * Strips the text per AACC Feeds directives.
   *
   * Removes images and triggers reformatting of links.
   *
   * @param string $text
   *   Feed content text to be cleaned.
   *
   * @return null|string|string[]
   *   Cleaned string.
   */
  private function cleanText($text) {
    // Strip out image tags.
    $text = preg_replace("/<img[^>]+\>/i", " ", $text);

    $text = $this->findAndFormatLinks($text);
    return $text;
  }

  /**
   * Find and Replace html anchors in content text.
   *
   * Some get turned into JS Callbacks.
   * Others get stripped out.
   *
   * @param string $html
   *   Content value to be parsed.
   *
   * @return null|string|string[]
   *   Parsed content value with corrected links.
   */
  private function findAndFormatLinks(string $html) {
    $linkFormat = $this->getLinkFormat();
    $regexp = '/<a\s[^>]*href=\"([^\"]*)\"[^>]*>(.*)<\/a>/siU';
    $html = preg_replace_callback(
      $regexp,
      function ($matches) use ($linkFormat) {
        $link_url = $matches[1];
        $link_title = $matches[2];

        if ($linkFormat == 'remove') {
          return $link_title;
        }
        else {
          if ((substr($link_url, 0, 4) == 'http') && (!stristr($link_url, 'labtestsonline'))) {
            // IF this is an external link, JS it and return it.
            if ($linkFormat == 'all' || $linkFormat == 'external') {
              return sprintf('<a href="javascript:showDetail(\'%s\', \'%s\')">%s</a>', $link_title, $link_url, $link_title);
            }
          }
          else {
            // ELSE it is an internal link
            // Make sure we only have the path.
            if (substr($link_url, 0, 4) == 'http') {
              $stripProtocol = explode('://', $link_url);
              $pos = strpos($stripProtocol[1], '/');
              $link_url = substr($stripProtocol[1], $pos);
            }
            if ($linkFormat == 'all' || $linkFormat == 'internal') {
              // Compare to Included Content Master Path array
              // IF found this is a link to content that is part of the feed
              // return the $linkFormat JS pattern format.
              if (in_array($link_url, $this->feedPathMap, TRUE)) {
                return sprintf('<a href="javascript:showDetail(\'%s\', \'%s\')">%s</a>', $link_title, $link_url, $link_title);
              }
            }
          }

        }
        return $link_title;
      },
      $html);
    return $html;
  }

  /**
   * Returns the selected Link Format method for this client.
   *
   * @return string
   *   The name of the link format method.
   */
  public function getLinkFormat() {
    $linkFormat = $this->aaccFeedsFeed->get($this->fieldLinkFormat)->getValue();
    return $linkFormat[0]['value'];
  }

  /**
   * Returns static text for feed content field 'link'.
   *
   * @return string
   *   Default value of link per legacy feed structure.
   */
  private function getLink() {
    return 'labtestsonline.org';
  }

  /**
   * Returns static text for feed content field 'location'.
   *
   * @return string
   *   Default value of location per legacy feed structure.
   */
  private function getLocation() {
    return 'https://www.labtestsonline.org';
  }

  /**
   * Returns static text for feed content field 'objectType'.
   *
   * @return string
   *   Default value of objectType per legacy feed structure.
   */
  private function getObjectType() {
    return 'Content';
  }

  /**
   * Returns text for feed content field 'subpageType'.
   *
   * //@todo This needs to return 'WITHOUT_SUBPAGES'
   * //if no sub-content is chosen.
   *
   * @return string
   *   Default value of subpage type per legacy feed structure.
   */
  private function getSubpageType() {
    return 'WITH_SUBPAGES';
  }

  /**
   * Returns static array for feed content field 'workflow'.
   *
   * @return array
   *   Default value of workflow array per legacy feed structure.
   */
  private function getWorkflow() {
    return ['name' => 'StdWorkflow', 'workflowStage' => 'Live'];
  }

  /**
   * Returns static text for feed content field 'action'.
   *
   * @return string
   *   Default value of 'add' per legacy feed structure.
   */
  private function getAction() {
    return 'add';
  }

  /**
   * Builds and returns the Feed Fields Map.
   *
   * Key == Feed field name
   * val == Drupal field name.
   *
   * @return array
   *   Fields Map array.
   */
  private function getFieldsMap() {
    return [
      'title' => ucfirst($this->contentType) . ' feed',
      'link' => $this->getLink(),
      'description' => ucfirst($this->contentType) . ' feed',
      'lastBuildDate' => date('d M y H:i:s T'),
      'items' => [],
    ];
  }

  /**
   * Builds and returns the Item Fields Map.
   *
   * If val is blank, then there is
   * a function to handle that one.
   *
   * @return array
   *   Item Fields Map array.
   */
  private function getItemFieldsMap() {
    return [
      'title' => 'title',
      'link' => $this->getLink(),
      'description' => 'body',
      'pubdate' => 'created',
      'id' => 'nid',
      'vid' => 'vid',
      // Local pretty url path.
      'path' => '',
      'guid' => 'uuid',
      // Seems to default to 'Content'.
      'objectType' => '',
      // Seems to default to 'WITH_SUBPAGES'.
      'contentType' => '',
      // Seems to default to 'http://www.labtestsonline.org'
      'location' => '',
      'workflow' => [
        'name' => 'StdWorkflow',
        'workflowStage' => 'Live',
      ],
      'publishDate' => 'revision_timestamp',
      'reviewed' => 'field_reviewed',
      // This seems to just be the text 'add'.
      'action' => 'action',
      'alsoKnownAs' => 'field_test_synonyms',
      'formalName' => 'field_formal_name',
      // CSV of all keywords.
      'keywords' => 'field_keywords',
      // Have to roll through these and keep or hide based on aacc_feeds_feed.
      'components' => 'field_subcontent',
    ];
  }

  /**
   * Returns content as JSON or XML based on $this->feedOptions.
   *
   * @param array $data
   *   Filtered array of requested content.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   JSON|XML encoded format of the data.
   */
  private function getEncodedResponse(array $data) {
    $data = array_merge($this->feedFieldsMap, $data);
    switch ($this->feedOptions['_format']) {
      case 'xml':
        // $data = json_encode($data);
        $output = $this->getXml($data);
        break;

      case 'json':
        $output = json_encode($data);
        break;

      default:
        $output = json_encode($data);
        break;
    }

    $response = new Response();
    $response->setContent($output);
    return $response;
  }

  /**
   * Returns assembled content array formatted as XML.
   *
   * @param array $array
   *   Content array.
   * @param bool $xml
   *   XML handle.
   * @param string $sub_element
   *   Parent Element key.
   *
   * @return mixed
   *   XML formatted content.
   */
  private function getXml(array $array, $xml = FALSE, $sub_element = 'items') {
    if ($xml === FALSE) {
      $xml = new \SimpleXMLElement('<result/>');
    }

    foreach ($array as $key => $value) {
      // Replaces numbered 'components' and 'items' sub-elements
      // with a singular key name.
      if (is_int($key)) {
        switch ($sub_element) {
          case 'items':
            $key = 'item';
            break;

          case 'components':
            $key = 'component';
            break;

          default:
            break;
        }
      }
      $value = str_replace('&nbsp;', '&#160;', $value);
      if (is_array($value)) {
        $this->getXml($value, $xml->addChild($key), $key);
      }
      else {
        $xml->addChild($key, htmlspecialchars($value));
      }
    }

    return $xml->asXML();
  }

}
