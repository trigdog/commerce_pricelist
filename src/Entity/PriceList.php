<?php

namespace Drupal\commerce_pricelist\Entity;

use Drupal\commerce\ConditionGroup;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\commerce_pricelist\PriceListInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Defines the Price list entity.
 *
 * @ingroup commerce_pricelist
 *
 * @ContentEntityType(
 *   id = "price_list",
 *   label = @Translation("Price list"),
 *   bundle_label = @Translation("Price list type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_pricelist\PriceListListBuilder",
 *     "views_data" = "Drupal\commerce_pricelist\Entity\PriceListViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\commerce_pricelist\Form\PriceListForm",
 *       "add" = "Drupal\commerce_pricelist\Form\PriceListForm",
 *       "edit" = "Drupal\commerce_pricelist\Form\PriceListForm",
 *       "delete" = "Drupal\commerce_pricelist\Form\PriceListDeleteForm",
 *     },
 *     "access" = "Drupal\commerce_pricelist\PriceListAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_pricelist\PriceListHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "price_list",
 *   admin_permission = "administer price list entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/price_list/{price_list}",
 *     "add-form" = "/price_list/add/{price_list_type}",
 *     "edit-form" = "/price_list/{price_list}/edit",
 *     "delete-form" = "/price_list/{price_list}/delete",
 *     "collection" = "/admin/commerce/price_lists",
 *   },
 *   bundle_entity_type = "price_list_type",
 *   field_ui_base_route = "entity.price_list_type.edit_form"
 * )
 */
class PriceList extends ContentEntityBase implements PriceListInterface {
  use EntityChangedTrait;
  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', (bool) $published);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Price list entity.'))
      ->setReadOnly(TRUE);
    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The Price list type/bundle.'))
      ->setSetting('target_type', 'price_list_type')
      ->setRequired(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Price list entity.'))
      ->setReadOnly(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Whether the pricelist is enabled.'))
      ->setDefaultValue(TRUE)
      ->setRequired(TRUE)
      ->setSettings([
          'on_label' => t('Enabled'),
          'off_label' => t('Disabled'),
      ])
      ->setDisplayOptions('form', [
          'type' => 'options_buttons',
          'weight' => 0,
      ]);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Price list entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Price list entity.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['conditions'] = BaseFieldDefinition::create('commerce_plugin_item:commerce_condition')
      ->setLabel(t('Conditions'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
          'type' => 'commerce_conditions',
          'weight' => 3,
          'settings' => [
              'entity_types' => ['user'],
          ],
      ]);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this pricelist in relation to other pricelists.'))
      ->setDefaultValue(0);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Price list is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions() {
    $conditions = [];
    foreach ($this->get('conditions') as $field_item) {
      /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItemInterface $field_item */
      $conditions[] = $field_item->getTargetInstance();
    }
    return $conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(User $user) {
    $conditions = $this->getConditions();
    if (!$conditions) {
      // no conditions always apply.
      return TRUE;
    }
    $user_conditions = array_filter($conditions, function ($condition) {
      /** @var \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface $condition */
      return $condition->getEntityTypeId() == 'user';
    });

    $user_conditions = new ConditionGroup($user_conditions, 'AND');

    return $user_conditions->evaluate($user);
  }

}
