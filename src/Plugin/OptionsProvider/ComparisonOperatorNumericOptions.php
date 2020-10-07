<?php

namespace Drupal\rules\Plugin\OptionsProvider;

use Drupal\Core\Session\AccountInterface;

/**
 * Options provider to return a choice of numeric comparison operators.
 */
class ComparisonOperatorNumericOptions extends OptionsProviderBase {

  /**
   * @inheritdoc
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return [
      '==' => '==',
      '<'  => '< (less than)',
      '<=' => '<= (less than or equal to)',
      '>'  => '> (greather than)',
      '>=' => '>= (greather than or equal to)',
    ];
  }

}
