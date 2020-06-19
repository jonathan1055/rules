<?php

namespace Drupal\rules\Context;

// Use Drupal\Core\Extension\ModuleHandlerInterface;
// Currently have \Drupal::moduleHandler() instead.
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
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * An array of data type => widget id pairs.
   *
   * @var array
   */
  protected $widgetList;

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
   * An array of standard data types and their corresponding widget id.
   *
   * This covers all the data types provided by Core and Typed Data API
   * Enhancements, apart from 'binary' and 'map'.
   *
   * @return array
   *   An array of data type => widget id.
   */
  protected static function getStandardWidgetList() {
    return [
      // Single line text input.
      'any' => 'text_input',
      'boolean' => 'text_input',
      'email' => 'text_input',
      'entity' => 'text_input',
      'entity_reference' => 'text_input',
      'float' => 'text_input',
      'integer' => 'text_input',
      'language' => 'text_input',
      'language_reference' => 'text_input',
      'string' => 'text_input',
      'uri' => 'text_input',

      // Multi-line text area.
      'list' => 'textarea',
      'text' => 'textarea',

      // Date.
      'datetime_iso8601' => 'datetime',
      'timestamp' => 'datetime',

      // Date range.
      'duration_iso8601' => 'datetime_range',
      'timespan' => 'datetime_range',

      // Selection list.
      'none-yet' => 'select',
    ];
  }

  /**
   * Derive a widget id from a datatype.
   *
   * The widget id is used to generate a form element provided by Typed Data.
   * Modules can implement hook_typed_data_widgetlist_alter() to declare which
   * widget to use for their custom data types.
   *
   * @todo this function should be moved into the typed_data module. But whilst
   * still developing the integration of widgets in Rules it can stay here.
   *
   * @param string $dataType
   *   The datatype to check.
   *
   * @return string
   *   The id of the widget to use when building the input form.
   */
  public function getWidgetId($dataType) {
    if (!isset($this->widgetList)) {
      $this->widgetList = static::getStandardWidgetList();
      // Allow other modules to add to the datatype -> widget id list by
      // invoking all hook_typed_data_widgetlist_alter() implementations.
      \Drupal::moduleHandler()->alter('typed_data_widgetlist', $this->widgetList);
    }
    // Return the widget id for this data type. If none, default to 'broken' id.
    return isset($this->widgetList[$dataType]) ? $this->widgetList[$dataType] : 'broken';
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetSettings() {
    return [];
  }

}
