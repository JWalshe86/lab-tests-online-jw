<?php

namespace Drupal\aacc_webform\Validate;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form API callback. Validate element value.
 */
class BlacklistConstraint {

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
      'ask_a_laboratory_scientist--subject',
    ];

    if (isset($element['#webform_id'])
      && in_array($element['#webform_id'], $process_webforms)
      && $element['#webform_key'] === 'subject') {

      $value = $formState->getValue('subject');

      $config = \Drupal::configFactory()->getEditable('aacc_webform.blacklist');
      $blacklist_terms = $config->get('terms');
      $blacklist_error = $config->get('error_message');
      $terms = explode("\r\n", $blacklist_terms);

      if (is_array($terms)) {
        foreach ($terms as $term) {
          $search_term = trim($term);

          if (preg_match('/\b' . $search_term . '\b/i', $value)) {
            $formState->setError(
              $element,
              $blacklist_error
            );

            \Drupal::logger('aacc_webform')
              ->notice('Webform subject blocked<h2>Term:</h2><pre>@term</pre><h3>Subject:</h3><pre>@subject</pre>', [
                '@term' => $search_term,
                '@subject' => $value,
              ]);

            return;
          }
        }
      }
    }
  }

}
