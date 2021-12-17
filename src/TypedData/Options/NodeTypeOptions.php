<?php

namespace Drupal\rules\TypedData\Options;

use Drupal\Core\Session\AccountInterface;

/**
 * Options provider to list all node types.
 */
class NodeTypeOptions extends OptionsProviderBase {

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    $options = [];

    // Load all the node types.
    $node_types = \Drupal::service('entity_type.manager')->getStorage('node_type')->loadMultiple();

    foreach ($node_types as $node_type) {
      $options[$node_type->id()] = $node_type->label();
      // If the id differs from the label add the id in brackets for clarity.
      if (strtolower(str_replace('_', ' ', $node_type->id())) != strtolower($node_type->label())) {
        $options[$node_type->id()] .= ' (' . $node_type->id() . ')';
      }
    }

    // Sort the result by value for ease of locating and selecting.
    asort($options);

    return $options;
  }

}
