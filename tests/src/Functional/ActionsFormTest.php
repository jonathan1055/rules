<?php

namespace Drupal\Tests\rules\Functional;

/**
 * Tests that each Rules Action can be editted.
 *
 * @group RulesUi
 */
class ActionsFormTest extends RulesBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ban',
    'path_alias',
    'rules',
    'typed_data',
  ];

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

    // Create an article content type that we will use for testing.
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    $type->save();

    $this->account = $this->drupalCreateUser([
      'administer rules',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->account);

    // Create a named role for use in conditions and actions.
    $this->createRole(['administer nodes'], 'test-editor', 'Test Editor');
  }

  /**
   * Test each action provided by Rules.
   *
   * Check that every action can be added to a rule and that the edit page can
   * be accessed. This ensures that the datatypes used in the definitions do
   * exist. This test does not execute the conditions or actions.
   *
   * @dataProvider dataActionsFormWidgets
   */
  public function testActionsFormWidgets($id, $values = [], $widgets = [], $selectors = []) {
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
      // Specify a node event so that the node... selector values are available.
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
          // Check that the switch worked.
          $assert->elementExists('xpath', '//input[@id="edit-context-definitions-' . $name . '-switch-button" and contains(@value, "Switch to the direct input mode")]');
        }
      }

      // Fill each given field with the value provided.
      foreach ($values as $name => $value) {
        $this->fillField('edit-context-definitions-' . $name . '-setting', $value);
      }

      // Check that the action can be saved.
      $this->pressButton('Save');
      $assert->pageTextNotContains('Error message');
      $assert->pageTextContains('You have unsaved changes.');
      $assert->addressEquals('admin/config/workflow/rules/reactions/edit/' . $expr_id);

      // Check that re-edit and re-save works OK.
      $this->clickLink('Edit');
      $this->pressButton('Save');
      $assert->pageTextNotContains('Error message');
      $assert->addressEquals('admin/config/workflow/rules/reactions/edit/' . $expr_id);

      // Save the rule.
      $this->pressButton('Save');
      $assert->pageTextContains("Reaction rule $expr_id has been updated");
    }

  }

  /**
   * Provides data for testActionsFormWidgets().
   *
   * @return array
   *   The test data. This is an ordered array with elements that must appear in
   *   the following order:
   *   - Machine name of action being tested. This can be repeated.
   *   - (optional) Values to enter on the Context form. This is an associative
   *     array with keys equal to the field names and values equal to the field
   *     values.
   *   - (optional) Widget types we expect to see on the Context form. This is
   *     an associative array with keys equal to the field names as above, and
   *     values equal to expected widget type.
   *   - (optional) Names of fields for which the selector/direct input button
   *     needs pressing to 'data selection' before the field value is entered.
   */
  public function dataActionsFormWidgets() {
    return [
      ['rules_data_calculate_value',
        // Values.
        [
          'input-1' => '3',
          'operator' => '*',
          'input-2' => '4',
        ],
        // Widgets.
        [
          'input-1' => 'text-input',
          'operator' => 'text-input',
          'input-2' => 'text-input',
        ],
      ],
      ['rules_data_convert',
        ['value' => 'node.uid', 'target-type' => 'string'],
      ],
      ['rules_list_item_add',
        ['list' => 'node.uid', 'item' => '1'],
      ],
      ['rules_list_item_remove',
        ['list' => 'node.uid', 'item' => '1'],
      ],
      ['rules_data_set',
        ['data' => 'node.title', 'value' => 'abc'],
      ],
      ['rules_data_set',
        // Values.
        ['data' => 'node.title', 'value' => '@user.current_user_context:current_user.name.value'],
        // Widgets.
        [],
        // Selectors.
        ['value'],
      ],
      ['rules_entity_create:node',
        ['type' => 'article', 'title' => 'abc'],
      ],
      ['rules_entity_create:user',
        ['name' => 'fred'],
      ],
      ['rules_entity_delete',
        ['entity' => 'node'],
      ],
      ['rules_entity_fetch_by_field',
        ['type' => 'node', 'field-name' => 'abc', 'field-value' => 'node.uid'],
        [],
        ['field-value'],
      ],
      ['rules_entity_fetch_by_id',
        ['type' => 'node', 'entity-id' => 123],
      ],
      ['rules_entity_path_alias_create:entity:node',
        ['entity' => 'node', 'alias' => 'abc'],
      ],
      ['rules_entity_save',
        ['entity' => 'node', 'immediate' => TRUE],
      ],
      ['rules_node_make_sticky',
        ['node' => 'node'],
      ],
      ['rules_node_make_unsticky',
        ['node' => 'node'],
      ],
      ['rules_node_publish',
        ['node' => 'node'],
      ],
      ['rules_node_unpublish',
        ['node' => 'node'],
      ],
      ['rules_node_promote',
        ['node' => 'node'],
      ],
      ['rules_node_unpromote',
        ['node' => 'node'],
      ],
      ['rules_path_alias_create',
        ['source' => '/node/1', 'alias' => 'abc'],
      ],
      ['rules_path_alias_delete_by_alias',
        ['alias' => 'abc'],
      ],
      ['rules_path_alias_delete_by_path',
        ['path' => '/node/1'],
      ],
      ['rules_page_redirect',
        ['url' => '/node/1'],
      ],
      ['rules_send_account_email',
        ['user' => 'node.uid', 'email-type' => 'abc'],
      ],
      ['rules_email_to_users_of_role',
        ['roles' => 'editor', 'subject' => 'Hello', 'message' => 'Some text'],
        ['message' => 'textarea'],
      ],
      ['rules_system_message',
        ['message' => 'Some text'],
      ],
      ['rules_send_email',
        [
          'to' => 'test@example.com',
          'subject' => 'Some testing subject',
          'message' => 'Test with direct input of recipients',
        ],
        ['message' => 'textarea'],
      ],
      // This test will work when data selectors are converted to arrays.
      // @see https://www.drupal.org/project/rules/issues/2723259
      // @todo un-comment when the above is fixed.
      /*
      ['rules_send_email',
        [
          'to' => 'node.uid.entity.mail.value',
          'subject' => 'Some testing subject',
          'message' => 'Test with selector input of node author',
        ],
        ['message' => 'textarea'],
        ['to'],
      ],
       */
      ['rules_user_block',
        ['user' => '@user.current_user_context:current_user'],
        [],
        ['user'],
      ],
      ['rules_user_role_add',
        ['user' => '@user', 'roles' => 'test-editor'],
      ],
      ['rules_user_role_remove',
        ['user' => '@user', 'roles' => 'test-editor'],
      ],
      ['rules_user_unblock',
        ['user' => '@user'],
      ],
      ['rules_variable_add',
        ['type' => 'integer', 'value' => 'node.nid'],
      ],
      ['rules_ban_ip',
        ['ip' => ''],
      ],
      ['rules_ban_ip',
        ['ip' => '192.0.2.1'],
      ],
      ['rules_unban_ip',
        ['ip' => '192.0.2.1'],
      ],
    ];
  }

}
