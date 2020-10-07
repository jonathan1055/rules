<?php

namespace Drupal\rules\Plugin\OptionsProvider;

use Drupal\Core\Session\AccountInterface;

/**
 * Options provider to return a choice of data comparison operators.
 */
class ComparisonOperatorOptions extends OptionsProviderBase {

  /**
   * @inheritdoc
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return [
      '==' => '==',
      '<' => '< (less than)',
      '>' => '> (greater than)',
      'CONTAINS' => 'CONTAINS (for strings or arrays)',
      'IN' => 'IN (for arrays or lists)',
    ];
  }

}
