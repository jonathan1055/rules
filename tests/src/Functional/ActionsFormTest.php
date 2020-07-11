<?php

namespace Drupal\Tests\rules\Functional;

/**
 * Tests that each Rules Action can be editted.
 *
 * @group RulesUi
 */
class ActionsFormTest extends RulesBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'ban', 'rules', 'typed_data'];

  /**
   * We use the minimal profile because we want to test local action links.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * A user account with administration permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->account = $this->drupalCreateUser([
      'administer rules',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->account);
  }

  /**
   * Test each action provided by Rules.
   *
   * Check that every action can be added to a rule and that the edit page can
   * be accessed. This ensures that the datatypes used in the definitions do
   * exist. This test does not execute the conditions or actions.
   *
   * @dataProvider dataActionsFormWidgets()
   */
  public function testActionsFormWidgets($id, $widgets = [], $values = [], $selectors = []) {
    $expressionManager = $this->container->get('plugin.manager.rules_expression');
    $storage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Create a rule.
    $rule = $expressionManager->createRule();
    // Add the action to the rule.
    $action = $expressionManager->createAction($id);
    $rule->addExpressionObject($action);
    // Save the configuration.
    $expr_id = 'test_action_' . str_replace(':', '_', $id);
    $config_entity = $storage->create([
      'id' => $expr_id,
      'expression' => $rule->getConfiguration(),
      // Specify a node event to make the node... selector values available.
      'events' => [['event_name' => 'rules_entity_update:node']],
    ]);
    $config_entity->save();
    // Edit the action and check that the page is generated without error.
    $this->drupalGet('admin/config/workflow/rules/reactions/edit/' . $expr_id . '/edit/' . $action->getUuid());
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Edit ' . $action->getLabel());

    // If any field values have been specified then fill in the form and save.
    if (!empty($values)) {

      // Switch to data selector if required by the test settings.
      if (!empty($selectors)) {
        foreach ($selectors as $name) {
          $this->pressButton('edit-context-definitions-' . $name . '-switch-button');
        }
      }

      // Fill each given field with the value provided.
      foreach ($values as $name => $value) {
        $this->fillField('edit-context-definitions-' . $name . '-setting', $value);
      }

      // Check that the action can be saved OK.
      $this->pressButton('Save');
      $assert->pageTextNotContains('Error message');
      $assert->addressEquals('admin/config/workflow/rules/reactions/edit/' . $expr_id);
      $assert->pageTextContains('You have unsaved changes.');

      // Save the rule.
      $this->pressButton('Save');
      $assert->pageTextContains("Reaction rule $expr_id has been updated");
    }

  }

  /**
   * Provides data for testActionsFormWidgets().
   *
   * @return array
   *   The test data, all optional except the test id. The elements are:
   *     test id
   *     array of field names to widget ids
   *     array of field names to values
   *     array of field names which should have the selector button pressed
   */
  public function dataActionsFormWidgets() {
    return [
      ['rules_data_calculate_value',
        // Widgets.
        [
          'input-1' => 'text-input',
          'operator' => 'text-input',
          'input-2' => 'text-input',
        ],
        // Values.
        [
          'input-1' => '5',
          'operator' => '+',
          'input-2' => '22',
        ],
        // Selectors.
      ],
      ['rules_data_convert'],
      ['rules_list_item_add'],
      ['rules_list_item_remove'],
      ['rules_data_set'],
      ['rules_entity_create:node'],
      ['rules_entity_create:user'],
      ['rules_entity_delete'],
      ['rules_entity_fetch_by_field'],
      ['rules_entity_fetch_by_id'],
      ['rules_entity_path_alias_create:entity:node'],
      ['rules_entity_save'],
      ['rules_node_make_sticky',
        [],
        ['node' => 'node'],
      ],
      ['rules_node_make_unsticky'],
      ['rules_node_publish'],
      ['rules_node_unpublish'],
      ['rules_node_promote'],
      ['rules_node_unpromote'],
      ['rules_path_alias_create'],
      ['rules_path_alias_delete_by_alias'],
      ['rules_path_alias_delete_by_path'],
      ['rules_send_account_email'],
      ['rules_email_to_users_of_role',
        ['message' => 'textarea'],
      ],
      ['rules_system_message'],
      ['rules_page_redirect'],
      ['rules_send_email',
        ['message' => 'textarea'],
        [
          'to' => 'node.uid.entity.mail.value',
          'subject' => 'Some testing subject',
          'message' => 'The main message',
        ],
        ['to'],
      ],
      ['rules_user_block'],
      ['rules_user_role_add'],
      ['rules_user_role_remove'],
      ['rules_user_unblock'],
      ['rules_variable_add'],
      ['rules_ban_ip'],
      ['rules_unban_ip'],
    ];
  }

}
