<?php

namespace Drupal\rules\Plugin\OptionsProvider;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Options provider for entity bundles.
 *
 * The returned top-level array is keyed on the bundle label, with nested arrays
 * keyed on the bundle machine name.
 */
class EntityBundleOptions extends OptionsProviderBase {

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

      // Get the bundles for this entity type.
      $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type->id());

      // Transform the $bundles array into a form suitable for select options.
      array_walk($bundles, function (&$value, $key) {
        // Flatten to just the label text.
        $value = (string) $value['label'];
        // If the key differs from the label add the key in brackets.
        if (strtolower(str_replace('_', ' ', $key)) != strtolower($value)) {
          $value .= ' (' . $key . ')';
        }
      });
      $options[(string) $entity_type->getLabel()] = $bundles;

    }

    // Sort the result by key, which is the group name.
    ksort($options);

    return $options;
  }

}
