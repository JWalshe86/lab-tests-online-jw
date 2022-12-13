<?php

namespace Drupal\aacc_foundation_magellan_field_format\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;

/**
 * Plugin implementation of the 'magellan_link_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "magellan_link_field_formatter",
 *   label = @Translation("Magellan link field formatter"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions"
 *   }
 * )
 */
class MagellanLinkFieldFormatter extends EntityReferenceFormatterBase {

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'magellan_label' => 'uuid',
      'magellan_clean' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settingsForm = [];

    $settingsForm['magellan_label'] = [
      '#title' => $this->t('Magellan Section Label'),
      '#type' => 'select',
      '#options' => $this->getMagellanFieldLabelOptions($this->fieldDefinition),
      '#default_value' => $this->getSetting('magellan_label'),
      '#description' => t('This label will be used as both the link text as well as for the anchor name (after modification). The label used here should be the same used when rendering the section content.'),
    ];

    $settingsForm['magellan_clean'] = [
      '#title' => $this->t('Clean Magellan Anchor Name'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('magellan_clean'),
      '#description' => t('If selected the label is cleaned when used as the anchor name. This is helpful if the label could have spaces or other characters that may not be suitable for an anchor name'),
    ];

    return $settingsForm + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    $summary[] = t('Display Entity Label as a Foundation Magellan link');

    return $summary;
  }

  /**
   * Wraps the transliteration service.
   *
   * @return \Drupal\Component\Transliteration\TransliterationInterface
   *   Transliteration Interface.
   */
  protected function transliteration() {
    if (!$this->transliteration) {
      $this->transliteration = \Drupal::transliteration();
    }
    return $this->transliteration;
  }

  /**
   * Create the Magellan Anchor and Link label.
   *
   * @param string $labelProvider
   *   The field or property to use.
   * @param \Drupal\Core\Entity\EntityInterface $item
   *   The entity to create the anchor from.
   * @param bool $clean
   *   Whether or not to clean the label, removing spaces.
   *
   * @return array
   *   An array with the first item as the anchor and the second the label.
   */
  public static function createMagellanAnchorLabel($labelProvider, EntityInterface $item, $clean = TRUE) {
    if ($labelProvider === 'uuid') {
      $magellanLabel = $magellanAnchor = $item->uuid();
    }
    elseif ($labelProvider === 'label') {
      if ($item->get('parent_field_name')->value == 'field_subcontent') {
        $magellanLabel = $magellanAnchor = $item->get('field_label')->value;
      }
      else {
        $magellanLabel = $magellanAnchor = $item->label();
      }
    }
    else {
      // Use the reference entity's field.
      /** @var \Drupal\Core\Entity\ContentEntityBase $item */
      $field = $item->get($labelProvider);

      // Load the field's referenced entity to get the label.
      if ($field->getFieldDefinition()->getType() === 'entity_reference') {
        /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $field */
        $target_type = $field
          ->getFieldDefinition()
          ->getSetting('target_type');
        $target_entity = \Drupal::entityTypeManager()
          ->getStorage($target_type)
          ->load($field->getValue()[0]['target_id']);
        if ($target_entity) {
          $magellanLabel = $magellanAnchor = $target_entity->label();
        }
        else {
          // Unable to load referenced entity.
          $magellanLabel = $magellanAnchor = t('Unable to load referenced entity.');
        }
      }
      else {
        // If the field is not an entity reference, use it's value.
        $magellanLabel = $magellanAnchor = $field->getValue()[0]['value'];
      }
    }

    // Transliterate and remove spaces in anchor.
    if ($clean) {
      $transliteratedMagellanAnchor = \Drupal::transliteration()->transliterate($magellanAnchor, $item->activeLangcode);
      $magellanAnchor = preg_replace('/[^\da-z]/i', '_', $transliteratedMagellanAnchor);
    }

    return [$magellanAnchor, $magellanLabel];

  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $links = [];
    $magellanLabelProvider = $this->getSetting('magellan_label');
    $magellanClean = ($this->getSetting('magellan_clean'));

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $item) {

      if ($magellanLabelProvider == 'label' && $item->hasField('field_label') == FALSE) {
        continue;
      }

      [$magellanAnchor, $magellanLabel] = $this->createMagellanAnchorLabel($magellanLabelProvider, $item, $magellanClean);

      $links[$delta] = [
        '#type' => 'link',
        '#title' => $magellanLabel,
        '#url' => Url::fromUri('internal:#' . $magellanAnchor),
      ];

      // Set cache tags.
      if (!empty($target_entity)) {
        // Merge referenced entity's cache tags to invalidate if the referenced
        // entity changes.
        $links[$delta]['#cache']['tags'] = Cache::mergeTags($item->getCacheTags(), $target_entity->getCacheTags());
      }
      else {
        $links[$delta]['#cache']['tags'] = $item->getCacheTags();
      }
    }

    // Display the links as a Foundation menu with links.
    if (!empty($links)) {
      $elements = [
        '#theme' => 'item_list',
        '#items' => $links,
        '#list_type' => 'ul',
        '#attributes' => [
          'class' => [
            'magellan-link-field',
            'menu',
            'vertical',
          ],
          'data-magellan' => '',
        ],
      ];
    }

    return $elements;
  }

  /**
   * Identify fields of the target Entity that can be used as the section label.
   *
   * The fields that are available must meet the following requirements:
   *   - Have a cardinality of 1 (are not a multi-valued field)
   *   - Are string or an entity reference.
   *
   * @return array
   *   Fields that can be used as the label.
   */
  public static function getMagellanFieldLabelOptions(FieldDefinitionInterface $fieldDefinition) {
    $fieldOptions = [];

    $fieldOptions['uuid'] = t('Entity UUID');

    // Only Entity References have Entity labels.
    if ($fieldDefinition->getType() === 'entity_reference' || ($fieldDefinition->getType() == 'entity_reference_revisions' && $fieldDefinition->getName() == 'field_subcontent')) {
      $fieldOptions['label'] = t('Entity Label');
    }

    $fieldSettings = $fieldDefinition->getSettings();
    if (!empty($fieldSettings['target_type'])
    && !empty($fieldSettings['handler_settings'])
    && !empty($fieldSettings['handler_settings']['target_bundles'])) {
      // Load fields from on target entities.
      $entityManager = \Drupal::service('entity_field.manager');
      foreach ($fieldSettings['handler_settings']['target_bundles'] as $targetEntityBundle) {
        if (!empty($fieldSettings['target_type'])) {
          $targetEntityFieldDefinitions = $entityManager->getFieldDefinitions($fieldSettings['target_type'], $targetEntityBundle);
          foreach ($targetEntityFieldDefinitions as $targetEntityFieldDefinition) {
            if ($targetEntityFieldDefinition instanceof FieldConfig) {
              // Only use fields that can have labels or are strings.
              if (in_array($targetEntityFieldDefinition->getType(), [
                'string',
                'entity_reference',
              ])) {
                // Only want to work with single value fields.
                if ($targetEntityFieldDefinition->getFieldStorageDefinition()
                  ->getCardinality() === 1
                ) {
                  $fieldOptions[$targetEntityFieldDefinition->getName()] = t('@fieldlabel: (@fieldName)', [
                    '@fieldlabel' => $targetEntityFieldDefinition->getLabel(),
                    '@fieldName' => $targetEntityFieldDefinition->getName(),
                  ]);
                }
              }
            }
          }
        }
      }
    }

    return $fieldOptions;
  }

}
