<?php

/**
 * @file
 * Contains external_link.page.inc.
 *
 * Page callback for External Link entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for External Link templates.
 *
 * Default template: external_link.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_external_link(array &$variables) {
  // Fetch ExternalLink Entity Object.
  $external_link = $variables['elements']['#external_link'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
