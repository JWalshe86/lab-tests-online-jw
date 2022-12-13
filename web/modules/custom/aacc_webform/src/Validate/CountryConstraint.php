<?php

namespace Drupal\aacc_webform\Validate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Form API callback. Validate element value.
 */
class CountryConstraint {

  /**
   * Blacklists words from subject.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param array $form
   *   The complete form structure.
   */
  public static function validate(array &$element, FormStateInterface $formState, array &$form) {
    $process_webforms = [
      'ask_a_laboratory_scientist--country',
    ];

    if (isset($element['#webform_id'])
      && in_array($element['#webform_id'], $process_webforms)
      && $element['#webform_key'] === 'country') {

      $value = $formState->getValue('country');

      if (is_null($value)) {
        $formState->setError(
          $element,
          new TranslatableMarkup('Country is required.')
        );
      }
    }
  }

}
