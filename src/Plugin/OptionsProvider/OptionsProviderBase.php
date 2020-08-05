<?php

namespace Drupal\rules\Plugin\OptionsProvider;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\OptionsProviderInterface;

/**
 * Base class for OptionsProvider for use in Rules actions and conditions.
 */
abstract class OptionsProviderBase implements OptionsProviderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    // These three functions are not required, but need to exist as the child
    // class is abstract.
    // @todo Is there a better way to do this?
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
  }

}
