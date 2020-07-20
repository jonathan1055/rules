<?php

namespace Drupal\rules\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;
use Drupal\rules\Exception\InvalidArgumentException;
use Drupal\user\UserInterface;

/**
 * Provides a 'User has roles(s)' condition.
 *
 * @Condition(
 *   id = "rules_user_has_role",
 *   label = @Translation("User has role(s)"),
 *   category = @Translation("User"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User"),
 *       description = @Translation("Specifies the user account to check."),
 *     ),
 *     "roles" = @ContextDefinition("entity:user_role",
 *       label = @Translation("Roles"),
 *       description = @Translation("Specifies the roles to check for."),
 *       multiple = TRUE,
 *       list_options_callback = "userRolesListOptions"
 *     ),
 *     "operation" = @ContextDefinition("string",
 *       label = @Translation("Matching multiple roles"),
 *       description = @Translation("Specify if the user must have <em>all</em> the roles selected or <em>any</em> of the roles selected."),
 *       assignment_restriction = "input",
 *       default_value = "AND",
 *       list_options_callback = "operationListOptions",
 *       required = FALSE
 *     ),
 *   }
 * )
 *
 * @todo Add access callback information from Drupal 7.
 */
class UserHasRole extends RulesConditionBase {

  /**
   * Evaluate if user has role(s).
   *
   * @param \Drupal\user\UserInterface $account
   *   The account to check.
   * @param \Drupal\user\RoleInterface[] $roles
   *   Array of user roles.
   * @param string $operation
   *   Either "AND": user has all of roles.
   *   Or "OR": user has at least one of all roles.
   *   Defaults to "AND".
   *
   * @return bool
   *   TRUE if the user has the role(s).
   */
  protected function doEvaluate(UserInterface $account, array $roles, $operation = 'AND') {

    $rids = array_map(function ($role) {
      return $role->id();
    }, $roles);

    switch ($operation) {
      case 'OR':
        return (bool) array_intersect($rids, $account->getRoles());

      case 'AND':
        return (bool) !array_diff($rids, $account->getRoles());

      default:
        throw new InvalidArgumentException('Either use "AND" or "OR". Leave empty for default "AND" behavior.');
    }
  }

  /**
   * Returns an array of user role options.
   *
   * @return array
   *   An array of user roles keyed on role name.
   */
  public function userRolesListOptions() {
    return user_role_names(TRUE);
  }

  /**
   * Returns an array of role matching options.
   *
   * @return array
   *   An array of logic operations for multiple role matching.
   */
  public function operationListOptions() {
    return [
      'AND' => $this->t('All selected roles (and)'),
      'OR' => $this->t('Any selected role (or)'),
    ];
  }

}
