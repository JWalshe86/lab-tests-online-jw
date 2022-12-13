<?php

namespace Drupal\aacc_ads\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides a div for including a interstitial advertising block.
 *
 * @Block(
 *   id = "aacc_ads_interstitial",
 *   admin_label = @Translation("Interstitial Ad")
 * )
 */
class InterstitialAd extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getconfiguration();

    $form['aacc_interstitial_div_id'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Div ID'),
      '#description'    => $this->t('The div ID for this Ad'),
      '#default_value'  => $config['aacc_interstitial_div_id'] ?? 'labtest-labtest-inter',
    ];

    $form['aacc_interstitial_site'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Div ID'),
      '#description'    => $this->t('The default EHS site variable. eg. ehs.con.labtest.labtest'),
      '#default_value'  => $config['aacc_interstitial_site'] ?? 'ehs.con.labtest.labtest',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['aacc_interstitial_div_id'] = $values['aacc_interstitial_div_id'];
    $this->configuration['aacc_interstitial_site'] = $values['aacc_interstitial_site'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    if (!empty($config['aacc_interstitial_div_id'])) {
      return [
        '#theme' => 'aacc_interstitial_ad',
        '#div_id' => $config['aacc_interstitial_div_id'],
        '#site' => $config['aacc_interstitial_site'],
      ];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if (($node = \Drupal::routeMatch()->getParameter('node')) && ($node instanceof NodeInterface)) {
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
