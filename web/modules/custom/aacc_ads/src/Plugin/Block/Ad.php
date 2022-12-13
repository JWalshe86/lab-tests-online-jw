<?php

namespace Drupal\aacc_ads\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;

/**
 * Provides a 'Ad' block.
 *
 * @Block(
 *  id = "ad",
 *  admin_label = @Translation("Ad"),
 * )
 */
class Ad extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new test object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entity_type_manager, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['ad_id' => ''] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['ad_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ad ID'),
      '#description' => $this->t('The Ad ID is used for the ID of the div wrapper.'),
      '#default_value' => $this->configuration['ad_id'],
      '#required' => TRUE,
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '10',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['ad_id'] = $form_state->getValue('ad_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $view = \Drupal::routeMatch()->getParameter('view_id');

    /** @var \Drupal\node\Entity\Node $node */
    $node = \Drupal::routeMatch()->getParameter('node');

    if (!$node && !$view) {
      return [];
    }

    if ($view) {
      $build = $this->getViewAdBuild($view);
    }

    if ($node) {
      if (!$node instanceof NodeInterface) {
        $node = Node::load($node);
      }
      $build = $this->getNodeAdBuild($node);
    }

    $isTargetHealthCareProviders = $node && $node->hasField('field_legacy_istargethcp') && !empty($node->get('field_legacy_istargethcp')->first()) ? $node->get('field_legacy_istargethcp')->first()->getValue()['value'] : FALSE;
    $blockConfig = $this->getConfiguration();
    $config = \Drupal::config('aacc_ads.adoptions');

    // This is meant to be set per site to prevent production ad impressions.
    if ($config->get('ads_force_production') == '1') {
      $build['#ehsSite'] = $isTargetHealthCareProviders ? 'ehs.pro.labtest.labtest' : 'ehs.con.labtest.labtest';
    }
    else {
      $build['#ehsSite'] = $isTargetHealthCareProviders ? 'ehstest.pro.labtest.labtest' : 'ehstest.con.labtest.labtest';
    }

    // The only ad that has a bottom position is the bottom-ad.
    // Allowed values are b for bottom or t for top.
    $build['#verticalPosition'] = $blockConfig['ad_id'] == 'bottom-ad' ? 'b' : 't';
    $build['#theme'] = 'aacc_ads';
    $build['#adId'] = $blockConfig['ad_id'];

    $noAdDomains = $config->get('ad_free_domains');
    $noAdSubDomains = $config->get('ad_free_exclusion_domains');

    $build['#attached'] = [
      'library' => ['aacc_ads/aacc_ads'],
      'drupalSettings' => [
        'aacc_ads' => [
          'aacc_ads' => [
            'noAdDomains' => $noAdDomains,
            'noAdSubDomains' => $noAdSubDomains,
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * Begins building the render array for ads on a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return mixed
   *   The build array for ads on a view page.
   */
  protected function getNodeAdBuild(NodeInterface $node) {
    $adHCPMedicalSpecialityParams = [];
    $adHCPProfessionParams = [];
    $adCategoryParams = [];
    $adConditionParams = [];
    $keywordIds = [];
    $adKeyValueEntities = [];
    $currentLangcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    if (is_iterable($node->field_keywords)) {
      foreach ($node->field_keywords as $keyword) {
        $keywordIds[] = $keyword->target_id;
      }
    }

    $keywords = $this->getKeywordsFromIds($keywordIds);
    $isTargetHealthCareProviders = $node->hasField('field_legacy_istargethcp') && !empty($node->get('field_legacy_istargethcp')->first()) ? $node->get('field_legacy_istargethcp')->first()->getValue()['value'] : FALSE;

    if (!empty($keywords)) {
      // Pages that target providers get a different set up key values.
      if ($isTargetHealthCareProviders) {
        $adHCPMedicalSpecialityData = $this->getRelatedAdData('hcp_medical_specialty', $keywords, $currentLangcode);
        $adHCPMedicalSpecialityParams = $this->createAdJsParameters($adHCPMedicalSpecialityData['keyValues'], 'ims');

        $adHCPProfessionData = $this->getRelatedAdData('hcp_profession', $keywords, $currentLangcode);
        $adHCPProfessionParams = $this->createAdJsParameters($adHCPProfessionData['keyValues'], 'iprof');

        $adKeyValueEntities = array_merge($adKeyValueEntities, $adHCPProfessionData['adEntities'], $adHCPMedicalSpecialityData['adEntities']);
      }
      else {
        $adCategoryData = $this->getRelatedAdData('category', $keywords, $currentLangcode);
        $adCategoryParams = $this->createAdJsParameters($adCategoryData['keyValues'], 'mcat');

        $adConditionData = $this->getRelatedAdData('condition', $keywords, $currentLangcode);
        $adConditionParams = $this->createAdJsParameters($adConditionData['keyValues'], 'mcon');

        $adKeyValueEntities = array_merge($adKeyValueEntities, $adConditionData['adEntities'], $adCategoryData['adEntities']);
      }
    }

    // Merge all JS parameters together.
    $build['#adParams'] = array_merge($adConditionParams, $adCategoryParams, $adHCPMedicalSpecialityParams, $adHCPProfessionParams);

    $cacheTags = array_merge($this->getAdKeyValueCacheTags($adKeyValueEntities), $node->getCacheTags());
    $cacheContexts = array_merge($this->getAdKeyValueCacheContexts($adKeyValueEntities), $node->getCacheContexts());
    $build['#cache']['tags'] = Cache::mergeTags($this->getCacheTags(), $cacheTags);
    $build['#cache']['contexts'] = Cache::mergeContexts($this->getCacheContexts(), $cacheContexts);

    return $build;
  }

  /**
   * Begins building the render array for ads on a view page.
   *
   * @param string $view
   *   The view name.
   *
   * @return mixed
   *   The build array for ads on a view page.
   */
  protected function getViewAdBuild($view) {
    $adCategoryParams = [];
    $adConditionParams = [];
    $adKeyValueEntities = [];

    $searchPhrase = \Drupal::request()->query->get('keywords');
    // Separating the search phrase into different keywords more closely mimics
    // the way EHS would like ad key value matching to occur. eg. They would
    // like "Blood transfusion" to match for 'blood'.
    $keywords = explode(' ', $searchPhrase);

    if (!empty($keywords)) {
      $currentLangcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

      $adCategoryData = $this->getRelatedAdData('category', $keywords, $currentLangcode);
      $adCategoryParams = $this->createAdJsParameters($adCategoryData['keyValues'], 'mcat');

      $adConditionData = $this->getRelatedAdData('condition', $keywords, $currentLangcode);
      $adConditionParams = $this->createAdJsParameters($adConditionData['keyValues'], 'mcon');

      $adKeyValueEntities = array_merge($adKeyValueEntities, $adConditionData['adEntities'], $adCategoryData['adEntities']);
    }

    $build['#adParams'] = array_merge($adConditionParams, $adCategoryParams);
    $build['#cache']['tags'] = Cache::mergeTags($this->getCacheTags(), $this->getAdKeyValueCacheTags($adKeyValueEntities));
    $cacheContexts = array_merge($this->getViewCacheContexts($view), $this->getAdKeyValueCacheContexts($adKeyValueEntities));
    $build['#cache']['contexts'] = Cache::mergeContexts($this->getCacheContexts(), $cacheContexts);

    return $build;
  }

  /**
   * Gets cache contexts for a view.
   *
   * @param string $view
   *   The view name.
   *
   * @return array
   *   An array of cache contexts.
   */
  protected function getViewCacheContexts($view) {
    $cacheContexts = [];
    if ($view == 'search') {
      // Different keywords need to display different ads.
      $cacheContexts[] = 'url.query_args:keywords';
    }

    return $cacheContexts;
  }

  /**
   * Gets the ad key value cache contexts.
   *
   * @param array $adKeyValueEntities
   *   The ad key value entities.
   *
   * @return array
   *   An array of cache contexts.
   */
  protected function getAdKeyValueCacheContexts(array $adKeyValueEntities) {
    $cacheContexts = [];
    if (!empty($adKeyValueEntities)) {
      foreach ($adKeyValueEntities as $adKeyValueEntity) {
        $cacheContexts = array_merge($cacheContexts, $adKeyValueEntity->getCacheContexts());
      }
    }

    return $cacheContexts;
  }

  /**
   * Gets the ad key value cache tags.
   *
   * @param array $adKeyValueEntities
   *   The ad key value entities.
   *
   * @return array
   *   An array of cache tags.
   */
  protected function getAdKeyValueCacheTags(array $adKeyValueEntities) {
    $cacheTags = [];
    if (!empty($adKeyValueEntities)) {
      foreach ($adKeyValueEntities as $adKeyValueEntity) {
        $cacheTags = array_merge($cacheTags, $adKeyValueEntity->getCacheTags());
      }
    }

    return $cacheTags;
  }

  /**
   * Obtains keyword names from a taxonomy term ID.
   *
   * @param array $keywordIds
   *   An array of keyword IDs.
   *
   * @return array
   *   An array of keyword taxonomy names.
   */
  protected function getKeywordsFromIds(array $keywordIds) {
    $keywords = [];
    if (empty($keywordIds)) {
      return $keywords;
    }

    $taxonomyTerms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($keywordIds);

    foreach ($taxonomyTerms as $taxonomyTerm) {
      $keywords[] = $taxonomyTerm->name->value;
    }

    return $keywords;
  }

  /**
   * Get the related ad entities and ad key values from a set of keywords.
   *
   * @param string $adBundle
   *   The ad entity bundle.
   * @param array $adKeywords
   *   Ad keywords to try to match against.
   * @param string $langcode
   *   The langcode.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   Ad entities that are matched by the keywords.
   */
  protected function getRelatedAdData($adBundle, array $adKeywords, $langcode) {
    if (empty($adKeywords)) {
      return [];
    }

    $keywordWhereClause = $this->generateKeywordWhereClause($adKeywords);

    $queryString = "SELECT DISTINCT kv.field_ad_key_value_value, kv.entity_id, so.field_ad_sort_order_value
      FROM ads__field_ad_key_value kv
      INNER JOIN ads__field_ad_keywords akw ON akw.entity_id = kv.entity_id
      INNER JOIN ads__field_ad_sort_order so ON so.entity_id = kv.entity_id
      INNER JOIN taxonomy_term_field_data term_data ON term_data.tid = akw.field_ad_keywords_target_id
      INNER JOIN ads_field_data afd ON afd.id = kv.entity_id
      WHERE kv.bundle = :bundle
      AND afd.langcode = :langcode
      AND afd.status = 1
      $keywordWhereClause
      ORDER BY so.field_ad_sort_order_value ASC
      LIMIT 3";

    $results = $this->database->query($queryString, [
      'bundle' => $adBundle,
      'langcode' => $langcode,
    ], ['allow_delimiter_in_query' => TRUE])->fetchAllAssoc('entity_id', \PDO::FETCH_ASSOC);

    return $this->separateAdEntitiesAndKeyValues($results);

  }

  /**
   * Creates the where clause that matches node keywords to taxonomy terms.
   *
   * @param array $keywords
   *   An array of keywords.
   *
   * @return string
   *   A string that is used as a WHERE clause.
   */
  protected function generateKeywordWhereClause(array $keywords) {
    if (empty($keywords)) {
      return '';
    }

    $keywordWhereClause = 'AND (';

    $i = 0;
    foreach ($keywords as $keyword) {

      if ($i !== 0) {
        $keywordWhereClause .= ' OR ';
      }

      $keywordWhereClause .= "{$this->database->quote($keyword)} LIKE CONCAT('%', term_data.name, '%')";

      $i++;
    }

    $keywordWhereClause .= ') ';

    return $keywordWhereClause;
  }

  /**
   * Create the ad key/value JavaScript Parameters.
   *
   * @param array|null $adKeyValues
   *   The key values for an ad.
   * @param string $paramKey
   *   The parameter key used in the JavaScript.
   *
   * @return array
   *   An array of JavaScript parameters used in the EHS JS snippet.
   */
  protected function createAdJsParameters($adKeyValues, $paramKey) {
    $adParameters = [];
    $count = 1;

    foreach ($adKeyValues as $adKeyValue) {
      // The iprof key value should not have more than one result.
      // The JS for EHS does not use a count in the key for iprof.
      if ($paramKey == 'iprof') {
        $adParameters[] = $paramKey . '=' . $adKeyValue;
        break;
      }

      $adParameters[] = $paramKey . $count . '=' . $adKeyValue;
      $count++;
    }

    return $adParameters;
  }

  /**
   * Creates a tuple of ad key values and ad entities.
   *
   * @param array $adData
   *   Ad data containing ad key values and ad entity IDs.
   *
   * @return array
   *   A tuple of ad key values and ad entities.
   */
  protected function separateAdEntitiesAndKeyValues(array $adData) {
    $adKeyValues = [];
    $adEntities = [];

    foreach ($adData as $data) {
      $adKeyValues[] = $data['field_ad_key_value_value'];
      $adEntities[] = $this->entityTypeManager->getStorage('ads')->load($data['entity_id']);
    }

    return [
      'keyValues' => $adKeyValues,
      'adEntities' => $adEntities,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.path']);
  }

}
