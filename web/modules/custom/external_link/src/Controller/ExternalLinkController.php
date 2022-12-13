<?php

namespace Drupal\external_link\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\external_link\Entity\ExternalLinkInterface;

/**
 * Class ExternalLinkController.
 *
 *  Returns responses for External Link routes.
 *
 * @package Drupal\external_link\Controller
 */
class ExternalLinkController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a External Link  revision.
   *
   * @param int $external_link_revision
   *   The External Link  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($external_link_revision) {
    $external_link = \Drupal::service('entity_type.manager')->getStorage('external_link')->loadRevision($external_link_revision);
    $view_builder = \Drupal::service('entity_type.manager')->getViewBuilder('external_link');
    return $view_builder->view($external_link);
  }

  /**
   * Page title callback for a External Link  revision.
   *
   * @param int $external_link_revision
   *   The External Link  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($external_link_revision) {
    $external_link = \Drupal::service('entity_type.manager')->getStorage('external_link')->loadRevision($external_link_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $external_link->label(),
      '%date' => \Drupal::service('date.formatter')->format($external_link->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a External Link .
   *
   * @param \Drupal\external_link\Entity\ExternalLinkInterface $external_link
   *   A External Link  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ExternalLinkInterface $external_link) {
    $account = $this->currentUser();
    $langcode = $external_link->language()->getId();
    $langname = $external_link->language()->getName();
    $languages = $external_link->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $external_link_storage = \Drupal::service('entity_type.manager')->getStorage('external_link');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $external_link->label(),
    ]) : $this->t('Revisions for %title',
      [
        '%title' => $external_link->label(),
      ]
    );
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all external link revisions") || $account->hasPermission('administer external link entities')));
    $delete_permission = (($account->hasPermission("delete all external link revisions") || $account->hasPermission('administer external link entities')));

    $rows = [];

    $vids = $external_link_storage->revisionIds($external_link);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\external_link\Entity\ExternalLinkInterface $revision */
      $revision = $external_link_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        $date = \Drupal::service('date.formatter')->format($revision->revision_timestamp->value, 'short');
        if ($vid != $external_link->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.external_link.revision', [
            'external_link' => $external_link->id(),
            'external_link_revision' => $vid,
          ]));
        }
        else {
          $link = $external_link->toLink($date)->toString();
          // $link = $external_link->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => [
                '#markup' => $revision->revision_log_message->value,
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('external_link.revision_revert_translation_confirm', [
                'external_link' => $external_link->id(),
                'external_link_revision' => $vid,
                'langcode' => $langcode,
              ]
              ) :
              Url::fromRoute('external_link.revision_revert_confirm', [
                'external_link' => $external_link->id(),
                'external_link_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('external_link.revision_delete_confirm', [
                'external_link' => $external_link->id(),
                'external_link_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['external_link_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
