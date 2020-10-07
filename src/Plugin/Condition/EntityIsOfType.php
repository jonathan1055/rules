<?php

namespace Drupal\rules\Plugin\Condition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesConditionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Entity is of type' condition.
 *
 * @Condition(
 *   id = "rules_entity_is_of_type",
 *   label = @Translation("Entity is of type"),
 *   category = @Translation("Entity"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity for which to evaluate the condition."),
 *       assignment_restriction = "selector"
 *     ),
 *     "type" = @ContextDefinition("string",
 *       label = @Translation("Type"),
 *       description = @Translation("The entity type specified by the condition."),
 *       options_provider = "\Drupal\rules\Plugin\OptionsProvider\EntityTypeOptions",
 *       assignment_restriction = "input"
 *     ),
 *   }
 * )
 *
 * @todo Add access callback information from Drupal 7?
 */
class EntityIsOfType extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an EntityIsOfType object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type.manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Check if the provided entity is of a specific type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check for a type.
   * @param string $type
   *   The type to check for.
   *
   * @return bool
   *   TRUE if the entity is of the provided type.
   */
  protected function doEvaluate(EntityInterface $entity, $type) {
    $entity_type = $entity->getEntityTypeId();

    // Check to see whether the entity's type matches the specified value.
    return $entity_type == $type;
  }

}
