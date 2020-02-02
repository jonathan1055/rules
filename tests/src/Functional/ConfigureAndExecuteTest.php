<?php

namespace Drupal\Tests\rules\Functional;

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
  protected static $modules = ['node', 'rules'];

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
      'administer rules',
      'administer site configuration',
    ]);
  }

  /**
   * Tests creation of a rule and then triggering its execution.
   */
  public function testConfigureAndExecute() {
    $this->drupalLogin($this->account);

    $this->drupalGet('admin/config/workflow/rules');

    // Set up a rule that will show a system message if the title of a node
    // matches "Test title".
    $this->clickLink('Add reaction rule');

    $this->fillField('Label', 'Test rule');
    $this->fillField('Machine-readable name', 'test_rule');
    $this->fillField('React on event', 'rules_entity_presave:node');
    $this->pressButton('Save');

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

    // Edit the rule and negate the condition.
    $this->drupalGet('admin/config/workflow/rules/reactions/edit/test_rule');
    $this->clickLink('Edit', 0);
    $this->getSession()->getPage()->checkField('negate');
    $this->pressButton('Save');
    // One more save to permanently store the rule.
    $this->pressButton('Save');

    // Need to clear cache so that the edited version will be used.
    // @todo Can this be removed when
    // https://www.drupal.org/project/rules/issues/2769511 is fixed?
    drupal_flush_all_caches();

    // Create node with same title and check that the message is not shown.
    $this->drupalGet('node/add/article');
    $this->fillField('Title', 'Test title');
    $this->pressButton('Save');
    $assert->pageTextNotContains('Title matched "Test title"!');
  }

  /**
   * Tests user input in context form for 'multiple' valued context variables.
   */
  public function testMultipleInputContext() {
    $this->drupalLogin($this->account);

    $this->drupalGet('admin/config/workflow/rules');

    // Set up a rule that will check the node type of a newly-created node.
    // The node type is the 'multiple' valued textarea we will test.
    $this->clickLink('Add reaction rule');

    $this->fillField('Label', 'Test rule');
    $this->fillField('Machine-readable name', 'test_rule');
    $this->fillField('React on event', 'rules_entity_insert:node');
    $this->pressButton('Save');

    $this->clickLink('Add condition');

    // Use node_is_of_type because the types field has 'multiple = TRUE'.
    $this->fillField('Condition', 'rules_node_is_of_type');
    $this->pressButton('Continue');

    $this->fillField('context_definitions[node][setting]', 'node');

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
    $this->fillField('context_definitions[types][setting]', implode($suboptimal_user_input));
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
    $config_factory = $this->container->get('config.factory');
    $rule = $config_factory->get('rules.reaction.test_rule');
    $this->assertEquals($expected_config_value, $rule->get('expression.conditions.conditions.0.context_values.types'));
  }

  /**
   * Tests the implementation of assignment restriction in context form.
   */
  public function testAssignmentRestriction() {
    $this->drupalLogin($this->account);

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

  /**
   * Tests that a default empty configuration is added when none is suppiled.
   *
   * This checks the methods addCondition and addAction, which are alternatives
   * for createCondition and createAction as used in the test above.
   */
  public function testEmptyConfig() {
    $this->drupalLogin($this->account);

    $expression_manager = $this->container->get('plugin.manager.rules_expression');
    $storage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');

    // Create a rule and add a condition via ConditionExpressionContainer
    // without specifying a second parameter for the configuation.
    $rule = $expression_manager->createRule();
    $rule->addCondition('rules_list_contains');
    // Add an action via ActionExpressionContainer without a config parameter.
    $rule->addAction('rules_list_item_add');
    $config_entity = $storage->create([
      'id' => 'test_rule',
      'expression' => $rule->getConfiguration(),
    ]);
    $config_entity->save();

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Check that the rule edit page can be displayed.
    $this->drupalGet('admin/config/workflow/rules/reactions/edit/test_rule');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Edit reaction rule');
  }

}
