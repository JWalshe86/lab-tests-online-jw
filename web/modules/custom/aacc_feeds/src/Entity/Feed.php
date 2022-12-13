<?php

namespace Drupal\aacc_feeds\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\aacc_feeds\FeedInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Feed entity.
 *
 * @ingroup aacc_feeds
 * @category AACC_Feeds
 * @package AACC
 * @author jelmore@unleashed-technologies.com <jelmore@unleashed-technologies.com>
 * @license https://unleashed-technologies.com Unleashed-Technologies.com
 * @link https://labtestsonline.com
 *
 * @ContentEntityType(
 *   id = "aacc_feeds_feed",
 *   label = @Translation("AACC Feed"),
 *   label_collection = @Translation("AACC Feeds"),
 *   label_singular = @Translation("AACC Feed"),
 *   label_plural = @Translation("AACC Feeds"),
 *   label_count = @PluralTranslation(
 *     singular = "@count feed",
 *     plural = "@count feeds",
 *   ),
 *   handlers = {
 *   "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *   "list_builder" = "Drupal\aacc_feeds\Entity\Controller\FeedListBuilder",
 *   "views_data" = "Drupal\views\EntityViewsData",
 *   "form" = {
 *     "default" = "Drupal\aacc_feeds\Form\FeedForm",
 *     "add" = "Drupal\aacc_feeds\Form\FeedForm",
 *     "edit" = "Drupal\aacc_feeds\Form\FeedForm",
 *     "delete" = "Drupal\aacc_feeds\Form\FeedDeleteForm",
 *   },
 *   "access" = "Drupal\aacc_feeds\FeedAccessControlHandler",
 *   },
 *   base_table = "aacc_feed",
 *   admin_permission = "administer feed entity",
 *   fieldable = TRUE,
 *   entity_keys = {
 *   "id" = "id",
 *   "label" = "name",
 *   "uuid" = "uuid"
 *   },
 *   links = {
 *   "canonical" = "/aacc_feeds_feed/{aacc_feeds_feed}",
 *   "edit-form" = "/aacc_feeds_feed/{aacc_feeds_feed}/edit",
 *   "delete-form" = "/feed/{aacc_feeds_feed}/delete",
 *   "collection" = "/aacc_feeds_feed/list"
 *   },
 *   field_ui_base_route = "aacc_feeds.feed_settings",
 * )
 */
class Feed extends ContentEntityBase implements FeedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When an new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   Storage Object.
   * @param array $values
   *   Values Array.
   *
   * @return mixed
   *   Values array.
   */
  public static function preCreate(
        EntityStorageInterface $storage,
        array &$values
    ) {
    parent::preCreate($storage, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @return mixed
   *   Created Time.
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\user\UserInterface
   *   User Entity.
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   *
   * @return int|null
   *   User ID
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   *
   * @param int $uid
   *   User ID.
   *
   * @return $this|FeedInterface
   *   User ID
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\user\UserInterface $account
   *   Account Object.
   *
   * @return $this|FeedInterface
   *   User Entity.
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its contents
   * can be manipulated in the GUI.  The behaviour of the widgets
   * used can be determined here.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity Object.
   *
   * @return array|\Drupal\Core\Field\FieldDefinitionInterface[]|mixed
   *   Field array.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Feed entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Feed entity.'))
      ->setReadOnly(TRUE);

    // Name field for the feed.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Feed entity.'))
      ->setSettings(
        [
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        ]
    )
      ->setDisplayOptions(
        'view', [
          'label' => 'above',
          'type' => 'string',
          'weight' => -6,
        ]
    )
      ->setDisplayOptions(
        'form', [
          'type' => 'string_textfield',
          'weight' => -6,
        ]
    )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of Feed entity.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the feed was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the feed was last edited.'));
    return $fields;

  }

}
