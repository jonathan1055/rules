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
      '>' => '> (greather than)',
      'CONTAINS' => 'contains (for strings or arrays)',
      'IN' => 'in (for arrays or lists)',
    ];
  }

}
