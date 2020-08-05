<?php

namespace Drupal\rules\Context;

use Drupal\Core\Plugin\Context\ContextDefinitionInterface as ContextDefinitionInterfaceCore;

/**
 * Context definition information required by Rules.
 *
 * The core interface is extended to add properties that are necessary for
 * Rules.
 */
interface ContextDefinitionInterface extends ContextDefinitionInterfaceCore {

  /**
   * Constants for the context assignment restriction mode.
   *
   * @see ::getAssignmentRestriction()
   */
  const ASSIGNMENT_RESTRICTION_INPUT = 'input';
  const ASSIGNMENT_RESTRICTION_SELECTOR = 'selector';

  /**
   * Determines if the context value is allowed to be NULL.
   *
   * @return bool
   *   TRUE if NULL values are allowed, FALSE otherwise.
   */
  public function isAllowedNull();

  /**
   * Sets the "allow NULL value" behavior.
   *
   * @param bool $null_allowed
   *   TRUE if NULL values should be allowed, FALSE otherwise.
   *
   * @return $this
   */
  public function setAllowNull($null_allowed);

  /**
   * Determines if this context has an assignment restriction.
   *
   * @return string|null
   *   Either ASSIGNMENT_RESTRICTION_INPUT for contexts that are only allowed to
   *   be provided as input values, ASSIGNMENT_RESTRICTION_SELECTOR for contexts
   *   that must be provided as data selectors or NULL if there is no
   *   restriction for this context.
   */
  public function getAssignmentRestriction();

  /**
   * Sets the assignment restriction mode for this context.
   *
   * @param string|null $restriction
   *   Either ASSIGNMENT_RESTRICTION_INPUT for contexts that are only allowed to
   *   be provided as input values, ASSIGNMENT_RESTRICTION_SELECTOR for contexts
   *   that must be provided as data selectors or NULL if there is no
   *   restriction for this context.
   *
   * @return $this
   */
  public function setAssignmentRestriction($restriction);

  /**
   * Exports the definition as an array.
   *
   * @return array
   *   An array with values for all definition keys.
   */
  public function toArray();

  /**
   * Returns an options provider if there are defined options.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   (optional) The array of contexts to which the defined context belongs.
   *
   * @return \Drupal\Core\TypedData\OptionsProviderInterface|null
   *   The options provider, or NULL if no options are defined.
   *
   * @see ::getOptionsProviderDefinition()
   *
   * @todo getOptionsProvider() and getOptionsProviderDefinition() are taken
   * directly from core issue #2329937. Remove when that is committed.
   * @see https://www.drupal.org/project/drupal/issues/2329937
   */
  public function getOptionsProvider(array $contexts = NULL);

  /**
   * Returns the set options provider definition.
   *
   * @return string|null
   *   The options provider definition, or NULL if no options provider has been
   *   defined.
   *
   * @see ::getOptionsProvider()
   */
  public function getOptionsProviderDefinition();

}
