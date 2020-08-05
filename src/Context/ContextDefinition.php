<?php

namespace Drupal\rules\Context;

use Drupal\Core\Plugin\Context\ContextDefinition as ContextDefinitionCore;
use Drupal\Component\Plugin\Exception\ContextException;

/**
 * Extends the core context definition class with useful methods.
 */
class ContextDefinition extends ContextDefinitionCore implements ContextDefinitionInterface {

  /**
   * The mapping of config export keys to internal properties.
   *
   * @var array
   */
  protected static $nameMap = [
    'type' => 'dataType',
    'label' => 'label',
    'description' => 'description',
    'multiple' => 'isMultiple',
    'required' => 'isRequired',
    'default_value' => 'defaultValue',
    'constraints' => 'constraints',
    'allow_null' => 'allowNull',
    'assignment_restriction' => 'assignmentRestriction',
    'options_provider' => 'optionsProviderDefinition',
  ];

  /**
   * Whether the context value is allowed to be NULL or not.
   *
   * @var bool
   */
  protected $allowNull = FALSE;

  /**
   * The assignment restriction of this context.
   *
   * @var string|null
   *
   * @see \Drupal\rules\Context\ContextDefinitionInterface::getAssignmentRestriction()
   */
  protected $assignmentRestriction = NULL;

  /**
   * The options provider definition.
   *
   * @var string|null
   */
  protected $optionsProviderDefinition = NULL;

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $values = [];
    $defaults = get_class_vars(__CLASS__);
    foreach (static::$nameMap as $key => $property_name) {
      // Only export values for non-default properties.
      if ($this->$property_name !== $defaults[$property_name]) {
        $values[$key] = $this->$property_name;
      }
    }
    return $values;
  }

  /**
   * Creates a definition object from an exported array of values.
   *
   * @param array $values
   *   The array of values, as returned by toArray().
   *
   * @return static
   *   The created definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   *   If the required classes are not implemented.
   */
  public static function createFromArray(array $values) {
    if (isset($values['class']) && !in_array(ContextDefinitionInterface::class, class_implements($values['class']))) {
      throw new ContextException('ContextDefinition class must implement ' . ContextDefinitionInterface::class . '.');
    }
    // Default to Rules context definition class.
    $values['class'] = isset($values['class']) ? $values['class'] : ContextDefinition::class;
    if (!isset($values['value'])) {
      $values['value'] = 'any';
    }

    $definition = $values['class']::create($values['value']);
    foreach (array_intersect_key(static::$nameMap, $values) as $key => $name) {
      $definition->$name = $values[$key];
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedNull() {
    return $this->allowNull;
  }

  /**
   * {@inheritdoc}
   */
  public function setAllowNull($null_allowed) {
    $this->allowNull = $null_allowed;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssignmentRestriction() {
    return $this->assignmentRestriction;
  }

  /**
   * {@inheritdoc}
   */
  public function setAssignmentRestriction($restriction) {
    $this->assignmentRestriction = $restriction;
    return $this;
  }

  /**
   * Defines the options provider to be used.
   *
   * See \Drupal\Core\TypedData\TypedDataManager::getOptionsProvider() for
   * supported definitions. In addition, specified option provider classes may
   * implement \Drupal\Core\Plugin\Context\ContextAwareOptionsProviderInterface
   * in order to provide options depending on the available contexts.
   *
   * @param string|null $provider_definition
   *   The options provider definition; e.g. the class name.
   *
   * @return $this
   *
   * @see ::getOptionsProviderDefinition()
   *
   * @todo The functions setOptionsProviderDefinition(), getOptionsProvider()
   * and getOptionsProviderDefinition() are taken directly from core issue
   * #2329937 patch #61. Remove from Rules when that is committed.
   * @see https://www.drupal.org/project/drupal/issues/2329937
   */
  public function setOptionsProviderDefinition($provider_definition) {
    $this->optionsProviderDefinition = $provider_definition;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsProvider(array $contexts = NULL) {
    if (isset($this->optionsProviderDefinition)) {
      $data_definition = $this->getDataDefinition()
        ->setOptionsProviderContext(ContextAwareOptionsProviderInterface::class, 'setContexts', [$contexts]);

      $provider = \Drupal::typedDataManager()
        ->getOptionsProvider($data_definition);

      return $provider;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsProviderDefinition() {
    return $this->optionsProviderDefinition;
  }

}
