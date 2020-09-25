<?php

namespace Drupal\rules\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event that is fired when a user logs in.
 *
 * @see rules_user_login()
 */
class UserLoginEvent extends GenericEvent {

  const EVENT_NAME = 'rules_user_login';

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  public $account;

  /**
   * Constructs the object.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account of the user logged in.
   */
  public function __construct(UserInterface $account) {
    $this->account = $account;
    // Set arguments as would be done in the parent __construct().
    $this->arguments = ['account' => $account];
  }

}
