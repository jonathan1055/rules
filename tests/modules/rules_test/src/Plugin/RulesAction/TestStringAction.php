<?php

namespace Drupal\rules_test\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a test action that concatenates a string to itself.
 *
 * @RulesAction(
 *   id = "rules_test_string",
 *   label = @Translation("Test action string."),
 *   category = @Translation("Tests"),
 *   context = {
 *     "text" = @ContextDefinition("string",
 *       label = @Translation("Text to concatenate")
 *     ),
 *   },
 *   configure_permissions = { "access test configuration" },
 *   provides = {
 *     "concatenated" = @ContextDefinition("string",
 *       label = @Translation("Concatenated result")
 *     ),
 *   }
 * )
 */
class TestStringAction extends RulesActionBase {

  /**
   * Concatenates the text with itself.
   *
   * @param string $text
   *   The text to concatenate.
   */
  protected function doExecute($text) {
    $this->setProvidedValue('concatenated', $text . $text);
  }

}
