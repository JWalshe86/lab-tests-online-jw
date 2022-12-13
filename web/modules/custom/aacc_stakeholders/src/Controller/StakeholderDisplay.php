<?php

namespace Drupal\aacc_stakeholders\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RenderContext;
use Symfony\Component\HttpFoundation\Response;
use Drupal\views\Views;

/**
 * Stakeholder Display route.
 *
 * @package Drupal\aacc_stakeholders\Controller
 */
class StakeholderDisplay extends ControllerBase {

  /**
   * Display.
   *
   * @return string
   *   Return Hello string.
   */
  public function display($nid) {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    if ($node) {
      $stakeholder = $this->selectStakeholder($node);
      if ($stakeholder) {
        // Deny any page caching on the current request.
        \Drupal::service('page_cache_kill_switch')->trigger();

        $response = new Response();
        $output = '<div class="block_title"><h4>' . t('Proudly sponsored by...') . '</h4></div>' . $stakeholder;
        $response->setContent($output);
        $response->setMaxAge(0);
      }
      elseif ($view = Views::getView('articles')) {
        $response = new HtmlResponse();
        $context = new RenderContext();

        /** @var \Drupal\Core\Render\RendererInterface $renderer */
        $renderer = \Drupal::service('renderer');

        $result = $renderer->executeInRenderContext($context, function () use ($view) {
          $view->setDisplay('random_article');
          $view->preExecute();
          $view->execute();
          return $view->render();
        });

        if (!$context->isEmpty()) {
          $bubbleableMetadata = $context->pop();
          BubbleableMetadata::createFromObject($result)->merge($bubbleableMetadata);
        }

        $content = \Drupal::service('renderer')->renderRoot($result);
        $output = '<div class="block_title"><h4>' . t('Learn more about...') . '</h4></div>' . $content;
        $response->getCacheableMetadata()->addCacheTags($node->getCacheTags());
        $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($result));
        $response->getCacheableMetadata()->setCacheMaxAge(120);
        $response->setContent($output);
      }
      return $response;
    }
  }

  /**
   * Select Stakeholder.
   *
   * @return string
   *   Return rendered output of a stakeholder
   */
  private function selectStakeholder($node) {
    $stakeholder_keyword_ids = [];
    foreach ($node->field_keywords as $item) {
      $stakeholder_keyword_ids[] = $item->target_id;
    }
    if ($stakeholder_keyword_ids) {
      $keyword_para_ids = $this->getKeywordParagraphsByKeyword($stakeholder_keyword_ids);
      if ($keyword_para_ids) {
        $stakeholder_ids = $this->getStakeholdersWithKeywords($keyword_para_ids);
        if ($stakeholder_ids) {
          $output = $this->getWinner($stakeholder_ids, $keyword_para_ids);
          if ($output) {
            return $output->jsonSerialize();
          }
        }
      }
    }

    return '';

  }

  /**
   * Get Keyword Paragraphs By Keyword.
   *
   * @return array
   *   Return array of keyword paragraph ids
   */
  private function getKeywordParagraphsByKeyword($keywords) {
    $query = \Drupal::entityTypeManager()
      ->getStorage('paragraph')
      ->getQuery()
      ->condition('type', 'sponsor_keyword', '=');
    $group = $query->orConditionGroup();
    foreach ($keywords as $keyword) {
      $group->condition('field_sponsor_keyword', $keyword, '=');
    }
    $query->condition($group);
    $result = $query->execute();
    if ($result) {
      return array_values($result);
    }
    return [];
  }

  /**
   * Get Stakeholders With Keywords.
   *
   * @return array
   *   Return array of stakeholder ids
   */
  private function getStakeholdersWithKeywords($keyword_ids) {
    $query = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->condition('type', 'stakeholder', '=')
      ->condition('status', 1, '=')
      ->condition('field_stakeholder_type', SPONSOR_TID, '=');
    $group = $query->orConditionGroup();
    foreach ($keyword_ids as $id) {
      $group->condition('field_sponsor_imps', $id, '=');
    }
    $query->condition($group);
    $result = $query->execute();
    if ($result) {
      return array_values($result);
    }
    return [];
  }

  /**
   * Get Winner.
   *
   * @return string
   *   Return rendered output of the winning stakeholder
   */
  private function getWinner($stakeholder_ids, $keyword_para_ids) {
    $stakeholders = [];
    $keywords = [];
    $lottery = [];
    $lottery_total = 0;
    foreach ($stakeholder_ids as $id) {
      $stakeholder = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($id);
      if ($stakeholder) {
        $sponsor_level = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->load($stakeholder->field_sponsor_level->target_id);
        foreach ($stakeholder->field_sponsor_imps as $item) {
          if (in_array($item->target_id, $keyword_para_ids)) {
            $keyword = \Drupal::entityTypeManager()
              ->getStorage('paragraph')
              ->load($item->target_id);

            if ($keyword->field_impressions->value < $sponsor_level->field_imp_per_mo->value) {
              $stakeholders[$id] = $stakeholder;
              $keywords[$item->target_id] = $keyword;
              $lottery[$item->target_id] = [
                'lottery_balls' => $sponsor_level->field_imp_per_mo->value - $keyword->field_impressions->value,
                'sponsor_id' => $id,
              ];
              $lottery_total += $sponsor_level->field_imp_per_mo->value - $keyword->field_impressions->value;

            }
          }
        }
      }
    }
    if (empty($lottery)) {
      return '';
    }
    $lottery_pool = [];
    foreach ($lottery as $key => $item) {
      for ($i = 0; $i < $item['lottery_balls']; $i++) {
        $lottery_pool[] = $key;
      }
    }
    $randomNumber = mt_rand(0, ($lottery_total - 1));
    $winner = $stakeholders[$lottery[$lottery_pool[$randomNumber]]['sponsor_id']];
    $keywords[$lottery_pool[$randomNumber]]->field_impressions->value += 1;
    $keywords[$lottery_pool[$randomNumber]]->save();
    $vb = \Drupal::entityTypeManager()->getViewBuilder('node');
    $view = $vb->view($winner, 'teaser_carousel');
    return \Drupal::service('renderer')->render($view);
  }

}
