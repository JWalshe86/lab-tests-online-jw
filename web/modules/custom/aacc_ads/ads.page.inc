<?php

/**
 * @file
 * Contains ads.page.inc.
 *
 * Page callback for Ad Key Value entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Ad Key Value templates.
 *
 * Default template: ads.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_ads(array &$variables) {
  // Fetch Ads Entity Object.
  $ads = $variables['elements']['#ads'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
