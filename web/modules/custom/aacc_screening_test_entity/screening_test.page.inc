<?php

/**
 * @file
 * Contains screening_test.page.inc.
 *
 * Page callback for Screening Test entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Screening Test templates.
 *
 * Default template: screening_test.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_screening_test(array &$variables) {
  // Fetch ScreeningTest Entity Object.
  $screening_test = $variables['elements']['#screening_test'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
