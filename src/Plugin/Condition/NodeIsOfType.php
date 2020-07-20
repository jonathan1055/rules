<?php

namespace Drupal\rules\Plugin\Condition;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Drupal\rules\Core\RulesConditionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Node is of type' condition.
 *
 * @Condition(
 *   id = "rules_node_is_of_type",
 *   label = @Translation("Node is of type"),
 *   category = @Translation("Content"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Node"),
 *       description = @Translation("Specifies the node for which to evaluate the condition."),
 *       assignment_restriction = "selector"
 *     ),
 *     "types" = @ContextDefinition("string",
 *       label = @Translation("Content types"),
 *       description = @Translation("Select all the allowed node types."),
 *       multiple = TRUE,
 *       list_options_callback = "nodeTypesListOptions"
 *     ),
 *   }
 * )
 */
class NodeIsOfType extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity.manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a NodeIsOfType object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity.manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * Check if a node is of a specific set of types.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check for a type.
   * @param string[] $types
   *   An array of type names as strings.
   *
   * @return bool
   *   TRUE if the node type is in the array of types.
   */
  protected function doEvaluate(NodeInterface $node, array $types) {
    return in_array($node->getType(), $types);
  }

  /**
   * Returns an array of node types that exist in the system.
   *
   * @return array
   *   An array of node types keyed on the node type machine name.
   */
  public function nodeTypesListOptions() {
    $options = [];

    $node_types = $this->entityManager->getStorage('node_type')->loadMultiple();

    foreach ($node_types as $node_type) {
      $options[$node_type->id()] = $node_type->label();
      // If the id differs from the label add the id in brackets for clarity.
      if (strtolower(str_replace('_', ' ', $node_type->id())) != strtolower($node_type->label())) {
        $options[$node_type->id()] .= ' (' . $node_type->id() . ')';
      }
    }

    return $options;
  }

}
