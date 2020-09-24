<?php

namespace Drupal\rules\Event;

/**
 * Event that is fired when a user logs out.
 *
 * @see rules_user_logout()
 */
class UserLogoutEvent extends GenericEvent {

  const EVENT_NAME = 'rules_user_logout';

}
