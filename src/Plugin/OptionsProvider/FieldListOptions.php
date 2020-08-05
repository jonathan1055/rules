<?php

namespace Drupal\rules\Plugin\OptionsProvider;

use Drupal\Core\Session\AccountInterface;

/**
 * Options provider to return all fields in the system.
 */
class FieldListOptions extends OptionsProviderBase {

  /**
   * @inheritdoc
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    $options = [];

    // Load all the fields in the system.
    $fields = \Drupal::service('entity_field.manager')->getFieldMap();

    // Add each field to our options array.
    foreach ($fields as $entity_fields) {
      foreach ($entity_fields as $field_name => $field) {
        $options[$field_name] = $field_name . ' (' . $field['type'] . ')';
      }
    }

    // Sort the result by value for ease of locating and selecting.
    asort($options);

    return $options;
  }

}
