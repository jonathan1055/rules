<?php

namespace Drupal\Tests\rules\Functional;

use Drupal\rules\Context\ContextConfig;

/**
 * Tests that a rule can be configured and triggered when a node is edited.
 *
 * @group RulesUi
 */
class ConfigureAndExecuteTest extends RulesBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'ban',
    'comment',
    'node',
    'rules',
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
      'create article content',
      'edit any article content',
      'administer rules',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->account);
  }

  /**
   * Helper function to create a reaction rule.
   *
   * @param string $label
   *   The label for the new rule.
   * @param string $machine_name
   *   The internal machine-readable name.
   * @param string $event
   *   The name of the event to react on.
   * @param string $description
   *   Optional description for the reaction rule.
   *
   * @return ReactionRule
   *   The rule object created.
   */
  protected function createRule($label, $machine_name, $event, $description = '') {
    $this->drupalGet('admin/config/workflow/rules');
    $this->clickLink('Add reaction rule');
    $this->fillField('Label', $label);
    $this->fillField('Machine-readable name', $machine_name);
    $this->fillField('React on event', $event);
    $this->fillField('Description', $description);
    $this->pressButton('Save');
    $this->assertSession()->pageTextContains('Reaction rule ' . $label . ' has been created');
    $config_factory = $this->container->get('config.factory');
    $rule = $config_factory->get('rules.reaction.' . $machine_name);
    return $rule;
  }

  /**
   * Tests creation of a rule and then triggering its execution.
   */
  public function testConfigureAndExecute() {
    // Set up a rule that will show a system message if the title of a node
    // matches "Test title".
    $this->createRule('Test rule', 'test_rule', 'rules_entity_presave:node');

    $this->clickLink('Add condition');
    $this->fillField('Condition', 'rules_data_comparison');
    $this->pressButton('Continue');

    $this->fillField('context_definitions[data][setting]', 'node.title.0.value');
    $this->fillField('context_definitions[value][setting]', 'Test title');
    $this->pressButton('Save');

    $this->clickLink('Add action');
    $this->fillField('Action', 'rules_system_message');
    $this->pressButton('Continue');

    $this->fillField('context_definitions[message][setting]', 'Title matched "Test title"!');
    $this->fillField('context_definitions[type][setting]', 'status');
    $this->pressButton('Save');

    // One more save to permanently store the rule.
    $this->pressButton('Save');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Add a node now and check if our rule triggers.
    $this->drupalGet('node/add/article');
    $this->fillField('Title', 'Test title');
    $this->pressButton('Save');
    $assert->pageTextContains('Title matched "Test title"!');

    // Add a second node with the same title and check the rule triggers again.
    // This tests that the cache update (or non-update) works OK.
    // @see https://www.drupal.org/project/rules/issues/3108494
    $this->drupalGet('node/add/article');
    $this->fillField('Title', 'Test title');
    $this->pressButton('Save');
    $assert->pageTextContains('Title matched "Test title"!');

    // Disable rule and make sure it doesn't get triggered.
    $this->drupalGet('admin/config/workflow/rules');
    $this->clickLink('Disable');

    $this->drupalGet('node/add/article');
    $this->fillField('Title', 'Test title');
    $this->pressButton('Save');
    $assert->pageTextNotContains('Title matched "Test title"!');

    // Re-enable the rule and make sure it gets triggered again.
    $this->drupalGet('admin/config/workflow/rules');
    $this->clickLink('Enable');

    $this->drupalGet('node/add/article');
    $this->fillField('Title', 'Test title');
    $this->pressButton('Save');
    $assert->pageTextContains('Title matched "Test title"!');

    // Edit the rule and negate the condition.
    $this->drupalGet('admin/config/workflow/rules/reactions/edit/test_rule');
    $this->clickLink('Edit', 0);
    $this->getSession()->getPage()->checkField('negate');
    $this->pressButton('Save');
    // One more save to permanently store the rule.
    $this->pressButton('Save');

    // Create node with same title and check that the message is not shown.
    $this->drupalGet('node/add/article');
    $this->fillField('Title', 'Test title');
    $this->pressButton('Save');
    $assert->pageTextNotContains('Title matched "Test title"!');
  }

  /**
   * Test to add each condition provided by Rules.
   *
   * @param string $id
   *   The id of the condition.
   * @param string $desc
   *   The text description of the condition.
   * @param array $required
   *   Array of fields to fill. The key is form field name and the value is the
   *   text to fill into the field. This should hold just the fields which are
   *   required and do not have any default value. Use an empty array if there
   *   are no fields or all fields have a default.
   * @param array $defaults
   *   Array of defaults which should be stored without having to select the
   *   value in the form.
   *
   * @dataProvider dataAddConditions()
   */
  public function testAddConditions($id, $desc, array $required = [], array $defaults = []) {
    $label = 'Check condition ' . $id;
    $this->createRule($label, 'test_rule', 'rules_entity_presave:node', "$desc\nid=$id");
    $this->clickLink('Add condition');
    $this->fillField('Condition', $id);
    $this->pressButton('Continue');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Save the form. If $required is not empty then we should get error(s) so
    // verify this, then fill in the specified fields and try to save again.
    $this->pressButton('Save');
    if (!empty($required)) {
      // Check that an error message is shown.
      $assert->pageTextContains('Error message');
      // Fill in the required fields.
      foreach ($required as $field => $value) {
        $this->fillField($field, $value);
      }
      $this->pressButton('Save');
    }
    // Assert that the rule has saved correctly with no error message.
    $assert->pageTextNotContains('Error message');
    $assert->pageTextContains('Edit reaction rule "' . $label . '"');
    $assert->pageTextContains($desc);
    $this->pressButton('Save');
    $assert->pageTextNotContains('Error message');
    $assert->pageTextContains('Reaction rule ' . $label . ' has been updated');
    // @todo Check that all values ($required and $defaults) have been stored.
  }

  /**
   * Provides data for testAddConditions().
   *
   * The format of the array is:
   *   key => [
   *     rules condition id,
   *     text description,
   *     array of required values,
   *     array of default values,
   *    ]
   * For consistency the key is the rules id without the rules_ prefix and with
   * spaces instead of _ and only the first word capitalized.
   *
   * @return array
   *   The test data.
   */
  public function dataAddConditions() {
    // Instead of directly returning the full set of test data, create variable
    // $data to hold it. This allows for manipulation before the final return.
    $data = [
      // Data.
      'Data comparison' => [
        'rules_data_comparison',
        'Data Comparison',
        ['context_definitions[data][setting]' => 'node.status.value', 'context_definitions[value][setting]' => TRUE],
        ['operation' => '=='],
      ],
      'Data is empty' => [
        'rules_data_is_empty',
        'Data value is empty',
        ['context_definitions[data][setting]' => 'node.uid.entity.name.value'],
      ],
      'List contains' => [
        'rules_list_contains',
        'List contains item',
        ['context_definitions[list][setting]' => 'node.uid', 'context_definitions[item][setting]' => 'something'],
      ],
      'List count is' => [
        'rules_list_count_is',
        'List Count Comparison',
        ['context_definitions[list][setting]' => 'node.uid.entity.roles', 'context_definitions[value][setting]' => 2],
        ['operator' => '=='],
      ],
      // Entity.
      'Entity has field' => [
        'rules_entity_has_field',
        'Entity has field',
        ['context_definitions[entity][setting]' => 'node', 'context_definitions[field][setting]' => 'mail'],
      ],
      'Entity is new' => [
        'rules_entity_is_new',
        'Entity is new',
        ['context_definitions[entity][setting]' => 'node'],
      ],
      'Entity is of bundle' => [
        'rules_entity_is_of_bundle',
        'Entity is of bundle', [
          'context_definitions[entity][setting]' => 'node',
          'context_definitions[type][setting]' => 'node',
          'context_definitions[bundle][setting]' => 'article',
        ],
      ],
      'Entity is of type' => [
        'rules_entity_is_of_type',
        'Entity is of TYPE',
        ['context_definitions[entity][setting]' => 'node', 'context_definitions[type][setting]' => 'user'],
      ],
      // Node.
      'Node is promoted' => [
        'rules_node_is_promoted',
        'Node is promoted',
        ['context_definitions[node][setting]' => 'node'],
      ],
      'Node is published' => [
        'rules_node_is_published',
        'Node is published',
        ['context_definitions[node][setting]' => 'node'],
      ],
      'Node is sticky' => [
        'rules_node_is_sticky',
        'Node is sticky',
        ['context_definitions[node][setting]' => 'node'],
      ],
      'Node is of type' => [
        'rules_node_is_of_type',
        'Node is of type',
        ['context_definitions[node][setting]' => 'node', 'context_definitions[types][setting][]' => 'article'],
      ],
      // Path.
      'Path alias exists' => [
        'rules_path_alias_exists',
        'Path alias exists',
        ['context_definitions[alias][setting]' => 'something'],
        ['context_definitions[language][setting]' => 'something'],
      ],
      'Path has alias' => [
        'rules_path_has_alias',
        'Path has alias',
        ['context_definitions[path][setting]' => 'something'],
        ['context_definitions[language][setting]' => 'something'],
      ],
      // User.
      'Entity field access' => [
        'rules_entity_field_access',
        'User has entity field access', [
          'context_definitions[user][setting]' => '@user.current_user_context:current_user',
          'context_definitions[entity][setting]' => 'node',
          'context_definitions[field][setting]' => 'changed',
        ],
        ['context_definitions[operation][setting]' => 'view'],
      ],
      'User has role' => [
        'rules_user_has_role',
        'User has role',
        ['context_definitions[user][setting]' => 'someone', 'context_definitions[roles][setting][]' => 'authenticated'],
        ['operation' => 'AND'],
      ],
      'User is blocked' => [
        'rules_user_is_blocked',
        'User is blocked',
        ['context_definitions[user][setting]' => 'someone'],
      ],
    ];
    // Can use unset to remove an item. Use return [$data['The key to test']] to
    // selectively test just one item, or return $data to test everything.
    return $data;
  }

  /**
   * Test to add each action provided by Rules.
   *
   * @param string $id
   *   The id of the action.
   * @param string $desc
   *   The text description of the action.
   * @param array $required
   *   Array of fields to fill. The key is form field name and the value is the
   *   text to fill into the field. This should hold just the fields which are
   *   required and do not have any default value. Use an empty array if there
   *   are no fields or all fields have a default.
   * @param array $defaults
   *   Array of defaults which should be stored without having to select the
   *   value in the form.
   *
   * @dataProvider dataAddActions()
   */
  public function testAddActions($id, $desc, array $required = [], array $defaults = []) {
    $label = 'Check action ' . $id;
    $this->createRule($label, 'test_rule', 'rules_entity_presave:node', "$desc\nid=$id");
    $this->clickLink('Add action');
    $this->fillField('Action', $id);
    $this->pressButton('Continue');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Save the form. If $required is not empty then we should get error(s) so
    // verify this, then fill in the specified fileds and try to save again.
    $this->pressButton('Save');
    if (!empty($required)) {
      // Check that an error message is shown.
      $assert->pageTextContains('Error message');
      // Fill in the required fields.
      foreach ($required as $field => $value) {
        // If the field is a role and the value is blank then get the role name.
        if ($field == 'context_definitions[roles][setting][]' && $value == '') {
          $value = $this->account->getRoles()[1];
        }
        $this->fillField($field, $value);
      }
      $this->pressButton('Save');
    }
    // Assert that the rule has saved correctly with no error message.
    $assert->pageTextNotContains('Error message');
    $assert->pageTextContains('Edit reaction rule "' . $label . '"');
    $assert->pageTextContains($desc);
    $this->pressButton('Save');
    $assert->pageTextNotContains('Error message');
    $assert->pageTextContains('Reaction rule ' . $label . ' has been updated');
    // @todo Check that all values ($required and $defaults) have been stored.
  }

  /**
   * Provides data for testAddActions().
   *
   * The format of the array is:
   *   key => [
   *     rules action id,
   *     text description,
   *     array of required values,
   *     array of default values,
   *    ]
   * For consistency the key is the rules id without the rules_ prefix and with
   * spaces instead of _ and only the first word capitalized.
   *
   * @return array
   *   The test data.
   */
  public function dataAddActions() {
    // Instead of directly returning the full set of test data, create variable
    // $data to hold it. This allows for manipulation before the final return.
    $data = [
      // Ban.
      'Ban ip' => [
        'rules_ban_ip',
        'Ban an IP address',
        [],
        ['context_definitions[ip][setting]' => ''],
      ],
      // Comment.
      'Entity create comment' => [
        'rules_entity_create:comment',
        'Entity Create Comment',
        [
          'context_definitions[comment_type][setting]' => 'comment',
          'context_definitions[entity_id][setting]' => '5',
          'context_definitions[entity_type][setting]' => 'node',
        ],
      ],
      // Content.
      'Entity create node' => [
        'rules_entity_create:node',
        'Create a new content',
        ['context_definitions[type][setting]' => 'article', 'context_definitions[title][setting]' => 'Cakes'],
      ],
      // System.
      'System message' => [
        'rules_system_message',
        'Show a message on the site',
        ['context_definitions[message][setting]' => 'Here is the news', 'context_definitions[type][setting]' => 'status'],
        ['context_definitions[repeat][setting]' => 'no'],
      ],
      // User.
      'User add role' => [
        'rules_user_role_add',
        'Add user role',
        ['context_definitions[user][setting]' => 'someone', 'context_definitions[roles][setting][]' => ''],
      ],
      'User block' => [
        'rules_user_block',
        'Block a user',
        ['context_definitions[user][setting]' => 'someone'],
      ],
      'User remove role' => [
        'rules_user_role_remove',
        'Remove user role',
        ['context_definitions[user][setting]' => 'someone', 'context_definitions[roles][setting][]' => ''],
      ],

    ];

    // rules_entity_create:comment fails with PluginNotFoundException:
    // 'The "entity:comment:comment" plugin does not exist'. Hence remove this
    // test case temporarily. It works OK via interactive UI but not in tests.
    // @todo Fix this.
    unset($data['Entity create comment']);

    // Can use unset to remove an item. Use return [$data['The key to test']] to
    // selectively test just one item, or return $data to test everything.
    return $data;
  }

  /**
   * Tests creating and altering two rules reacting on the same event.
   */
  public function testTwoRulesSameEvent() {
    $expressionManager = $this->container->get('plugin.manager.rules_expression');
    $storage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Create a rule that will show a system message when updating a node whose
    // title contains "Two Rules Same Event".
    $rule1 = $expressionManager->createRule();
    // Add the condition to the rule.
    $rule1->addCondition('rules_data_comparison',
        ContextConfig::create()
          ->map('data', 'node.title.value')
          ->setValue('operation', 'contains')
          ->setValue('value', 'Two Rules Same Event')
    );
    // Add the action to the rule.
    $message1 = 'RULE ONE is triggered';
    $rule1->addAction('rules_system_message',
        ContextConfig::create()
          ->setValue('message', $message1)
          ->setValue('type', 'status')
    );
    // Add the event and save the rule configuration.
    $config_entity = $storage->create([
      'id' => 'rule1',
      'label' => 'Rule One',
      'events' => [['event_name' => 'rules_entity_presave:node']],
      'expression' => $rule1->getConfiguration(),
    ]);
    $config_entity->save();

    // Add a node and check that rule 1 is triggered.
    $this->drupalPostForm('node/add/article', ['title[0][value]' => 'Two Rules Same Event'], 'Save');
    $node = $this->drupalGetNodeByTitle('Two Rules Same Event');
    $assert->pageTextContains($message1);

    // Repeat to create a second similar rule.
    $rule2 = $expressionManager->createRule();
    // Add the condition to the rule.
    $rule2->addCondition('rules_data_comparison',
        ContextConfig::create()
          ->map('data', 'node.title.value')
          ->setValue('operation', 'contains')
          ->setValue('value', 'Two Rules Same Event')
    );
    // Add the action to the rule.
    $message2 = 'RULE TWO is triggered';
    $rule2->addAction('rules_system_message',
        ContextConfig::create()
          ->setValue('message', $message2)
          ->setValue('type', 'status')
    );
    // Add the event and save the rule configuration.
    $config_entity = $storage->create([
      'id' => 'rule2',
      'label' => 'Rule Two',
      'events' => [['event_name' => 'rules_entity_presave:node']],
      'expression' => $rule2->getConfiguration(),
    ]);
    $config_entity->save();

    // Edit the node and check that both rules are triggered.
    $this->drupalPostForm('node/' . $node->id() . '/edit/', [], 'Save');
    $assert->pageTextContains($message1);
    $assert->pageTextContains($message2);

    // Disable rule 2.
    $this->drupalGet('admin/config/workflow/rules');
    $this->clickLinkByHref('disable/rule2');

    // Edit the node and check that only rule 1 is triggered.
    $this->drupalPostForm('node/' . $node->id() . '/edit/', [], 'Save');
    $assert->pageTextContains($message1);
    $assert->pageTextNotContains($message2);

    // Re-enable rule 2.
    $this->drupalGet('admin/config/workflow/rules');
    $this->clickLinkByHref('enable/rule2');

    // Check that both rules are triggered.
    $this->drupalPostForm('node/' . $node->id() . '/edit/', [], 'Save');
    $assert->pageTextContains($message1);
    $assert->pageTextContains($message2);

    // Edit rule 1 and change the message text in the action.
    $message1updated = 'RULE ONE has a new message.';
    $this->drupalGet('admin/config/workflow/rules/reactions/edit/rule1');
    $this->clickLink('Edit', 1);
    $this->fillField('context_definitions[message][setting]', $message1updated);
    // Save the action then save the rule.
    $this->pressButton('Save');
    $this->pressButton('Save');

    // Check that rule 1 now shows the updated text message.
    $this->drupalPostForm('node/' . $node->id() . '/edit/', [], 'Save');
    $assert->pageTextNotContains($message1);
    $assert->pageTextContains($message1updated);
    $assert->pageTextContains($message2);

    // Delete rule 1.
    $this->drupalGet('admin/config/workflow/rules');
    $this->clickLinkByHref('delete/rule1');
    $this->pressButton('Delete');

    // Check that only Rule 2's message is shown.
    $this->drupalPostForm('node/' . $node->id() . '/edit/', [], 'Save');
    $assert->pageTextNotContains($message1);
    $assert->pageTextNotContains($message1updated);
    $assert->pageTextContains($message2);

    // Disable rule 2.
    $this->drupalGet('admin/config/workflow/rules');
    $this->clickLinkByHref('disable/rule2');

    // Check that neither rule's message is shown.
    $this->drupalPostForm('node/' . $node->id() . '/edit/', [], 'Save');
    $assert->pageTextNotContains($message1);
    $assert->pageTextNotContains($message1updated);
    $assert->pageTextNotContains($message2);

  }

  /**
   * Tests user input in context form for 'multiple' valued context variables.
   */
  public function testMultipleInputContext() {
    // Set up a rule. The event is not relevant, we just want a rule to use.
    // $rule = $this->createRule('Test Multiple Input', 'test_rule', 'rules_entity_insert:node');
    $this->drupalGet('admin/config/workflow/rules');
    $this->clickLink('Add reaction rule');
    $this->fillField('Label', 'Test Multiple Input via UI');
    $this->fillField('Machine-readable name', 'test_rule');
    $this->fillField('React on event', 'rules_entity_insert:node');
    $this->pressButton('Save');

    // Add action rules_send_email because the 'to' field has 'multiple = TRUE'
    // rendered as a textarea that we can use for this test.
    $this->clickLink('Add action');
    $this->fillField('Action', 'rules_send_email');
    $this->pressButton('Continue');

    $suboptimal_user_input = [
      "  \r\nwhitespace at beginning of input\r\n",
      "text\r\n",
      "trailing space  \r\n",
      "\rleading terminator\r\n",
      "  leading space\r\n",
      "multiple words, followed by primitive values\r\n",
      "0\r\n",
      "0.0\r\n",
      "128\r\n",
      " false\r\n",
      "true \r\n",
      "null\r\n",
      "terminator r\r",
      "two empty lines\n\r\n\r",
      "terminator n\n",
      "terminator nr\n\r",
      "whitespace at end of input\r\n        \r\n",
    ];
    $this->fillField('context_definitions[to][setting]', implode($suboptimal_user_input));

    // Set the other required field. These play no part in the test.
    $this->fillField('context_definitions[subject][setting]', 'Hello');
    $this->fillField('context_definitions[message][setting]', 'Hello');

    $this->pressButton('Save');

    // One more save to permanently store the rule.
    $this->pressButton('Save');

    // Now examine the config to ensure the user input was parsed properly
    // and that blank lines, leading and trailing whitespace, and wrong line
    // terminators were removed.
    $expected_config_value = [
      "whitespace at beginning of input",
      "text",
      "trailing space",
      "leading terminator",
      "leading space",
      "multiple words, followed by primitive values",
      "0",
      "0.0",
      "128",
      "false",
      "true",
      "null",
      "terminator r",
      "two empty lines",
      "terminator n",
      "terminator nr",
      "whitespace at end of input",
    ];
    // Need to get the $rule again, as the existing $rule does not have the
    // changes added above and $rule->get('expression.actions...) is empty.
    // @todo Is there a way to refersh $rule and not have to get it again?
    $config_factory = $this->container->get('config.factory');
    $config_factory->clearStaticCache();
    $rule = $config_factory->get('rules.reaction.test_rule');

    $to = $rule->get('expression.actions.actions.0.context_values.to');
    $this->assertEquals($expected_config_value, $to);
  }

  /**
   * Tests the implementation of assignment restriction in context form.
   */
  public function testAssignmentRestriction() {
    $expression_manager = $this->container->get('plugin.manager.rules_expression');
    $storage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');

    // Create a rule.
    $rule = $expression_manager->createRule();

    // Add a condition which is unrestricted.
    $condition1 = $expression_manager->createCondition('rules_data_comparison');
    $rule->addExpressionObject($condition1);
    // Add a condition which is restricted to 'selector' for 'node'.
    $condition2 = $expression_manager->createCondition('rules_node_is_of_type');
    $rule->addExpressionObject($condition2);

    // Add an action which is unrestricted.
    $action1 = $expression_manager->createAction('rules_system_message');
    $rule->addExpressionObject($action1);
    // Add an action which is restricted to 'input' for 'type'.
    $action2 = $expression_manager->createAction('rules_variable_add');
    $rule->addExpressionObject($action2);

    // As the ContextFormTrait is action/condition agnostic it is not necessary
    // to check a condition restricted to input, because the check on action2
    // covers this. Likewise we do not need an action restricted by selector
    // because condition2 covers this. Save the rule to config. No event needed.
    $config_entity = $storage->create([
      'id' => 'test_rule',
      'expression' => $rule->getConfiguration(),
    ]);
    $config_entity->save();

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Display the rule edit page to show the actions and conditions.
    $this->drupalGet('admin/config/workflow/rules/reactions/edit/test_rule');

    // Edit condition 1, assert that the switch button is shown for value and
    // that the default entry field is regular text entry not a selector.
    $this->drupalGet('admin/config/workflow/rules/reactions/edit/test_rule/edit/' . $condition1->getUuid());
    $assert->buttonExists('edit-context-definitions-value-switch-button');
    $assert->elementExists('xpath', '//input[@id="edit-context-definitions-value-setting" and not(contains(@class, "rules-autocomplete"))]');

    // Edit condition 2, assert that the switch button is NOT shown for node
    // and that the entry field is a selector with class rules-autocomplete.
    $this->drupalGet('admin/config/workflow/rules/reactions/edit/test_rule/edit/' . $condition2->getUuid());
    $assert->buttonNotExists('edit-context-definitions-node-switch-button');
    $assert->elementExists('xpath', '//input[@id="edit-context-definitions-node-setting" and contains(@class, "rules-autocomplete")]');

    // Edit action 1, assert that the switch button is shown for message and
    // that the default entry field is a regular text entry not a selector.
    $this->drupalGet('admin/config/workflow/rules/reactions/edit/test_rule/edit/' . $action1->getUuid());
    $assert->buttonExists('edit-context-definitions-message-switch-button');
    $assert->elementExists('xpath', '//input[@id="edit-context-definitions-message-setting" and not(contains(@class, "rules-autocomplete"))]');

    // Edit action 2, assert that the switch button is NOT shown for type and
    // that the entry field is a regular text entry not a selector.
    $this->drupalGet('admin/config/workflow/rules/reactions/edit/test_rule/edit/' . $action2->getUuid());
    $assert->buttonNotExists('edit-context-definitions-type-switch-button');
    $assert->elementExists('xpath', '//input[@id="edit-context-definitions-type-setting" and not(contains(@class, "rules-autocomplete"))]');
  }

}
