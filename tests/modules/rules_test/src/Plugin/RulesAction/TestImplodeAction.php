<?php

namespace Drupal\rules_test\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a test action that converts an array to a comma delimited string.
 *
 * @RulesAction(
 *   id = "rules_test_implode",
 *   label = @Translation("Test implode action"),
 *   category = @Translation("Tests"),
 *   context_definitions = {
 *     "data" = @ContextDefinition("string",
 *       label = @Translation("Data to implode"),
 *       multiple = TRUE
 *     ),
 *   },
 *   configure_permissions = { "access test configuration" },
 *   provides = {
 *     "concatenated" = @ContextDefinition("string",
 *       label = @Translation("Concatenated result"),
 *       description = @Translation("The concatenated text.")
 *     ),
 *   }
 * )
 */
class TestImplodeAction extends RulesActionBase {

  /**
   * Converts to a comma delimited string.
   *
   * @param array $data
   *   The data to implode.
   */
  protected function doExecute(array $data) {
    $this->setProvidedValue('concatenated', implode(',', $data));
  }

}
