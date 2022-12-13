<?php

namespace Drupal\aacc_admin;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides a trusted callback to alter the commerce cart block.
 *
 * @see olla_common_block_view_commerce_cart_alter()
 */
class AaccLtoFoundationIp2CountryCallback implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * Sets Olla common - #pre_render callback.
   */
  public static function preRender($uri, $title, $layout) {
    if (\Drupal::moduleHandler()->moduleExists('ip2country')) {
      $ip = \Drupal::request()->getClientIp();

      $ip = filter_var(
      $ip,
      FILTER_VALIDATE_IP,
      FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
      );
      if ($ip) {
        $country_code = \Drupal::service('ip2country.lookup')->getCountry($ip);
        if ($country_code == 'US') {
          // The include avails us $template variable.
          include 'inc/order-your-test.inc';
          return [
            '#markup' => $template,
            '#cache' => [
              'max-age' => 0,
            ],
          ];
        }
      }
    }
    return [
      '#markup' => '',
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
