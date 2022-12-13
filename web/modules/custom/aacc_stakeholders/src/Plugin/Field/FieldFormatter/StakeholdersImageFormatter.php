<?php

namespace Drupal\aacc_stakeholders\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the 'stakeholder' formatter.
 *
 * @FieldFormatter(
 *   id = "stakeholder",
 *   label = @Translation("Stakeholder Link"),
 *   field_types = {
 *     "image"
 *   },
 * )
 */
class StakeholdersImageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    // Replace Link options with an option to link to the Stakeholder Link.
    $element['image_link']['#options']['stakeholder'] = t('Stakeholder Link');
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Image style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = t('Original image');
    }

    $link_types = [
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
      'stakeholder' => t('Linked to Stakeholder Link'),
    ];
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'stakeholder') {
      /** @var \Drupal\node\NodeInterface $entity */
      $entity = $items->getEntity();
      // If this stakeholder has a link filled out, use its url.
      if ($entity->hasField('field_links') && !$entity->get('field_links')->isEmpty()) {
        /** @var \Drupal\linkicon\LinkIconItem $link_item */
        $link_item = $entity->get('field_links')->first();
        $url = $link_item->getUrl();
        $url->new_window = TRUE;
      }
      // Else, default to the node url.
      else {
        $url = $this->getEntityLink($entity);
      }
    }
    if ($image_link_setting == 'content') {
      $url = $this->getEntityLink($items->getEntity());
    }
    if ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      $cache_contexts = [];
      if (isset($link_file)) {
        $image_uri = $file->getFileUri();
        // @todo Wrap in file_url_transform_relative(). This is currently
        // impossible. As a work-around, we currently add the 'url.site' cache
        // context to ensure different file URLs are generated for different
        // sites in a multisite setup, including HTTP and HTTPS versions of the
        // same site. Fix in https://www.drupal.org/node/2646744.
        $url = Url::fromUri(file_create_url($image_uri));
        $cache_contexts[] = 'url.site';
      }
      $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $elements[$delta] = [
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style_setting,
        '#url' => $url,
        '#cache' => [
          'tags' => $cache_tags,
          'contexts' => $cache_contexts,
        ],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityLink(EntityInterface $entity) {
    if (!$entity->isNew()) {
      // $url = $entity->urlInfo();
      $url = $entity->toUrl();
    }
  }

}
