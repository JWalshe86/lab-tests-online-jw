<?php

namespace Drupal\label_as_formatted\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Sympfony\Component\Intl\Collator;

/**
 * Sort Handler for Labels.
 *
 * This sorts allows entity Labels to be sorted, even if it contains HTML.
 *
 * @ViewsSort("label_as_formatted")
 */
class LabelAsFormatted extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function postExecute(&$values) {
    $titles = [];
    $rows_sorted = [];

    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $collator = \Collator::create($language);
    $sort_field = $this->options['sort_field'];

    foreach ($values as $row_key => $row) {
      $str = $row->$sort_field ?? $row->_entity->$sort_field->value;
      $titles[$row_key] = strip_tags($str);
    }
    $collator->asort($titles);
    foreach ($titles as $sorted_row_key => $title) {
      $rows_sorted[$sorted_row_key] = $values[$sorted_row_key];
    }

    $values = $rows_sorted;

    if ($this->options['order'] == 'desc') {
      array_reverse($values);
    }
  }

  /**
   * Basic options for all sort criteria.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if ($this->canExpose()) {
      $this->showExposeButton($form, $form_state);
    }
    $form['op_val_start'] = ['#value' => '<div class="clearfix">'];
    $this->showSortForm($form, $form_state);
    $form['sort_field'] = [
      '#title' => $this->t('Field to sort'),
      '#type' => 'textfield',
      '#default_value' => $this->options['sort_field'],
    ];
    $form['op_val_end'] = ['#value' => '</div>'];
    if ($this->canExpose()) {
      $this->showExposeForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['order'] = ['default' => 'ASC'];
    $options['sort_field'] = ['default' => 'title'];
    return $options;
  }

}
