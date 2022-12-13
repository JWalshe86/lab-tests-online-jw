<?php

namespace Drupal\aacc_ads\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Ad Key Value type entity.
 *
 * @ConfigEntityType(
 *   id = "ads_type",
 *   label = @Translation("Ad Key Value type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\aacc_ads\AdsTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\aacc_ads\Form\AdsTypeForm",
 *       "edit" = "Drupal\aacc_ads\Form\AdsTypeForm",
 *       "delete" = "Drupal\aacc_ads\Form\AdsTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\aacc_ads\AdsTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "ads_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "ads",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/ads_type/{ads_type}",
 *     "add-form" = "/admin/structure/ads_type/add",
 *     "edit-form" = "/admin/structure/ads_type/{ads_type}/edit",
 *     "delete-form" = "/admin/structure/ads_type/{ads_type}/delete",
 *     "collection" = "/admin/structure/ads_type"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "locked",
 *     "pattern",
 *   }
 * )
 */
class AdsType extends ConfigEntityBundleBase implements AdsTypeInterface {

  /**
   * The Ad Key Value type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Ad Key Value type label.
   *
   * @var string
   */
  protected $label;

}
