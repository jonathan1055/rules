<?php

namespace Drupal\rules\Event;

// This is not required. Can swap the arguments OK and get reduced deprecation count.

// Drupal\Component\EventDispatcher\Event was introduced in Drupal core 9.1 to
// assist with deprecations and the transition to Symfony 5.
// @todo Remove this when core 9.1 is the lowest supported version.
// @see https://www.drupal.org/project/scheduler/issues/3166688
// if (!class_exists('Drupal\Component\EventDispatcher\Event')) {
//   class_alias('Symfony\Component\EventDispatcher\GenericEvent', 'Drupal\Component\EventDispatcher\Event');
// }

use Symfony\Component\EventDispatcher\GenericEvent as SymfonyGenericEvent;

/**
 * Class from which all Rules events are extended from.
 */
class GenericEvent extends SymfonyGenericEvent {
}
