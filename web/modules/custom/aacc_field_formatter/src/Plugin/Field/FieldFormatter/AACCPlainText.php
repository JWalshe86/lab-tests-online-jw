<?php

namespace Drupal\aacc_field_formatter\Plugin\Field\FieldFormatter;

// Use Drupal\Core\Entity\EntityManagerInterface;.
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'string' formatter.
 *
 * @FieldFormatter(
 *   id = "aacc_string",
 *   label = @Translation("AACC Plain text (Allow html tags"),
 *   field_types = {
 *     "string",
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class AACCPlainText extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a StringFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['link_to_entity'] = FALSE;
    $options['allowed_html_tags'] = '';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $entity_type = $this->entityManager->getDefinition($this->fieldDefinition->getTargetEntityTypeId());

    $form['link_to_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to the @entity_label', ['@entity_label' => $entity_type->getLabel()]),
      '#default_value' => $this->getSetting('link_to_entity'),
    ];

    $form['allowed_html_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed HTML Tags'),
      '#default_value' => $this->getSetting('allowed_html_tags'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($allowed_html_tags = $this->getSetting('allowed_html_tags')) {
      $summary[] = $this->t('Allowed HTML Tags: @allowed_html_tags', ['@allowed_html_tags' => $allowed_html_tags]);
    }
    if ($this->getSetting('link_to_entity')) {
      $entity_type = $this->entityManager->getDefinition($this->fieldDefinition->getTargetEntityTypeId());
      $summary[] = $this->t('Linked to the @entity_label', ['@entity_label' => $entity_type->getLabel()]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $url = NULL;
    if ($this->getSetting('link_to_entity')) {
      // $url = $items->getEntity()->urlInfo('revision');
      $url = $items->getEntity()->toUrl('revision');
    }

    foreach ($items as $delta => $item) {
      $view_value = $this->viewValue($item);
      if ($url) {
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $view_value,
          '#url' => $url,
        ];
      }
      else {
        $elements[$delta] = $view_value;
      }
    }
    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   The textual output generated as a render array.
   */
  protected function viewValue(FieldItemInterface $item) {
    $template = '{{ value|nl2br }}';
    if ($allowed_html_tags = $this->getSetting('allowed_html_tags')) {
      $template = '{{ value|striptags(\'' . $allowed_html_tags . '\')|raw }}';
    }

    return [
      '#type' => 'inline_template',
      '#template' => $template,
      '#context' => ['value' => $item->value],
    ];
  }

}
