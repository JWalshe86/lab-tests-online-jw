<?php

namespace Drupal\aacc_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the item's URL to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "subcontent_entities",
 *   label = @Translation("Subcontent Entities"),
 *   description = @Translation("Adds the subcontent entities to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class Subcontent extends ProcessorPluginBase {
  /**
   * The fields helper.
   *
   * @var \Drupal\search_api\Utility\FieldsHelperInterface|null
   */
  protected $fieldsHelper;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];
    if (!$datasource) {
      $definition = [
        'label' => $this->t('Add Subcontent Entities'),
        'description' => $this->t('Adds a field for subcontent entities'),
        'type' => 'text',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['aacc_subcontent_entities'] = new ProcessorProperty($definition);
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    foreach ($this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'aacc_subcontent_entities') as $field_reference) {
      $node = $item->getOriginalObject()->getValue();
      if (in_array($node->getType(), ['test', 'condition', 'screening'])) {
        $index_value = '';
        foreach ($node->field_subcontent->getValue() as $value) {
          if ($value) {
            $item = \Drupal::entityTypeManager()
              ->getStorage('paragraph')
              ->load($value['target_id']);
            switch ($item->getType()) {
              case 'accordion':
                foreach ($item->field_accordion_items->getValue() as $item) {
                  $accordion_item = \Drupal::entityTypeManager()
                    ->getStorage('paragraph')
                    ->load($item['target_id']);
                  $index_value .= $accordion_item->field_body->value . ' ';
                }
                break;

              case 'expandable':
                $index_value .= $item->field_body->value . ' ';
                break;

              case 'grid':
                foreach ($item->field_text_areas->getValue() as $text_id) {
                  $text_area = \Drupal::entityTypeManager()
                    ->getStorage('paragraph')
                    ->load($text_id['target_id']);
                  $index_value .= $text_area->field_body->value . ' ';
                }
                break;

              case 'two_column':
                $index_value .= $item->field_left_body->value . ' ';
                $index_value .= $item->field_right_body->value . ' ';
                break;

            }
          }
        }
        $field_reference->addValue($index_value);
      }
    }
  }

  /**
   * Retrieves the fields helper.
   *
   * @return \Drupal\search_api\Utility\FieldsHelperInterface
   *   The fields helper.
   */
  public function getFieldsHelper() {
    return $this->fieldsHelper ?: \Drupal::service('search_api.fields_helper');
  }

}
