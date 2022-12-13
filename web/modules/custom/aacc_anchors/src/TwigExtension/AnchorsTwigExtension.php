<?php

namespace Drupal\aacc_anchors\TwigExtension;

use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class Twig extention for Table of contents.
 */
class AnchorsTwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getTokenParsers() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeVisitors() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTests() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('paragraph_item_anchor', [
        $this,
        'paragraphItemAnchor',
      ], ['is_safe' => ['html']]),
      new \Twig_SimpleFunction('paragraph_item_anchor_label', [
        $this,
        'paragraphItemAnchorLabel',
      ], ['is_safe' => ['html']]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperators() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'aacc_anchors.twig.extension';
  }

  /**
   * Get an accordion anchor tag for the paragraph item.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The paragraph object for the item.
   *
   * @return string
   *   The tag for the accordion anchor.
   */
  public static function paragraphItemAnchor(Paragraph $paragraph) {
    return aacc_anchors_generate_anchor_tag($paragraph);
  }

  /**
   * Get an accordion anchor tag for the paragraph item.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The paragraph object for the item.
   *
   * @return string
   *   The tag for the accordion anchor.
   */
  public static function paragraphItemAnchorLabel(Paragraph $paragraph) {
    $roles = \Drupal::currentUser()->getRoles();

    $administrators = [
      'administrator',
      'content_manager',
    ];

    $is_admin = FALSE;
    foreach ($roles as $role) {
      if (in_array($role, $administrators)) {
        $is_admin = TRUE;
        break;
      }
    }

    $id = aacc_anchors_generate_anchor_id($paragraph);

    return $is_admin ? sprintf('<div class="accordion-label">#%s</div>', $id) : '';
  }

}
