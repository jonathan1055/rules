<?php

namespace Drupal\rules\Plugin\OptionsProvider;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Options provider to list all entity types.
 */
class EntityTypeOptions extends OptionsProviderBase {

  /**
   * @inheritdoc
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    $options = [];

    // Load all the entity types.
    $entity_types = \Drupal::service('entity_type.manager')->getDefinitions();
    foreach ($entity_types as $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface) {
        continue;
      }
      $options[$entity_type->id()] = $entity_type->getLabel()->__toString();
      // If the id differs from the label add the id in brackets for clarity.
      if (strtolower(str_replace('_', ' ', $entity_type->id())) != strtolower($entity_type->getLabel())) {
        $options[$entity_type->id()] .= ' (' . $entity_type->id() . ')';
      }
    }

    // Sort the result by value for ease of locating and selecting.
    asort($options);

    return $options;
  }

}
