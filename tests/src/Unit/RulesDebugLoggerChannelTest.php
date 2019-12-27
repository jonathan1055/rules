<?php

namespace Drupal\Tests\rules\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\rules\Logger\RulesDebugLoggerChannel;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass \Drupal\rules\Logger\RulesDebugLoggerChannel
 * @group Rules
 */
class RulesDebugLoggerChannelTest extends UnitTestCase {

  /**
   * The Drupal service container.
   *
   * @var \Drupal\Core\DependencyInjection\Container
   */
  protected $container;

  /**
   * The Rules logger.channel.rules service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $rulesLogger;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $container = new ContainerBuilder();
    $this->rulesLogger = $this->prophesize(LoggerInterface::class)->reveal();
    $container->set('logger.channel.rules', $this->rulesLogger);
    $this->messenger = new TestMessenger();
    $container->set('messenger', $this->messenger);
    \Drupal::setContainer($container);
    $this->container = $container;
  }

  /**
   * Tests LoggerChannel::log().
   *
   * @param string $psr3_message_level
   *   Expected PSR3 log level.
   * @param int $rfc_message_level
   *   Expected RFC 5424 log level.
   * @param int $log
   *   Is system logging enabled.
   * @param int $debug_screen
   *   Is screen logging enabled.
   * @param string $psr3_log_error_level
   *   Minimum required PSR3 log level at which to log.
   * @param int $expect_system_log
   *   Amount of logs to be created.
   * @param int $expect_screen_log
   *   Amount of messages to be created.
   * @param string $message
   *   Log message.
   *
   * @dataProvider providerTestLog
   *
   * @covers ::log
   */
  public function testLog($psr3_message_level, $rfc_message_level, $log, $debug_screen, $psr3_log_error_level, $expect_system_log, $expect_screen_log, $message) {
    $this->clearMessages();

    $config = $this->getConfigFactoryStub([
      'rules.settings' => [
        'system_log' => [
          'log_level' => $psr3_log_error_level,
        ],
        'debug_log' => [
          'enabled' => $debug_screen,
          'system_debug' => $log,
          'log_level' => $psr3_log_error_level,
        ],
      ],
    ]);
    $channel = new RulesDebugLoggerChannel($this->rulesLogger, $config, $this->messenger);
    $logger = $this->prophesize(LoggerInterface::class);
    $logger->log($rfc_message_level, $message, Argument::type('array'))
      ->shouldBeCalledTimes($expect_system_log);

    $channel->addLogger($logger->reveal());

    $channel->log($psr3_message_level, $message);

    $messages = $this->messenger->all();
    if ($expect_screen_log > 0) {
      $this->assertNotNull($messages);
      $this->assertArrayEquals([$psr3_message_level => [$message]], $messages);
    }
    else {
      $this->assertNull($messages);
    }
  }

  /**
   * Clears the statically stored messages.
   *
   * @param null|string $type
   *   (optional) The type of messages to clear. Defaults to NULL which causes
   *   all messages to be cleared.
   *
   * @return $this
   */
  protected function clearMessages($type = NULL) {
    if (isset($type)) {
      $this->messenger->deleteByType($type);
    }
    else {
      $this->messenger->deleteAll();
    }
    return $this;
  }

  /**
   * Data provider for self::testLog().
   */
  public function providerTestLog() {
    return [
      [
        'psr3_message_level' => LogLevel::DEBUG,
        'rfc_message_level' => RfcLogLevel::DEBUG,
        'system_log_enabled' => 0,
        'screen_log_enabled' => 0,
        'min_psr3_level' => LogLevel::DEBUG,
        'expected_system_logs' => 0,
        'expected_screen_logs' => 0,
        'message' => 'apple',
      ],
      [
        'psr3_message_level' => LogLevel::DEBUG,
        'rfc_message_level' => RfcLogLevel::DEBUG,
        'system_log_enabled' => 0,
        'screen_log_enabled' => 1,
        'min_psr3_level' => LogLevel::DEBUG,
        'expected_system_logs' => 0,
        'expected_screen_logs' => 1,
        'message' => 'pear',
      ],
      [
        'psr3_message_level' => LogLevel::CRITICAL,
        'rfc_message_level' => RfcLogLevel::CRITICAL,
        'system_log_enabled' => 1,
        'screen_log_enabled' => 0,
        'min_psr3_level' => LogLevel::DEBUG,
        'expected_system_logs' => 1,
        'expected_screen_logs' => 0,
        'message' => 'banana',
      ],
      [
        'psr3_message_level' => LogLevel::CRITICAL,
        'rfc_message_level' => RfcLogLevel::CRITICAL,
        'system_log_enabled' => 1,
        'screen_log_enabled' => 1,
        'min_psr3_level' => LogLevel::DEBUG,
        'expected_system_logs' => 1,
        'expected_screen_logs' => 1,
        'message' => 'carrot',
      ],
      [
        'psr3_message_level' => LogLevel::CRITICAL,
        'rfc_message_level' => RfcLogLevel::CRITICAL,
        'system_log_enabled' => 1,
        'screen_log_enabled' => 0,
        'min_psr3_level' => LogLevel::DEBUG,
        'expected_system_logs' => 1,
        'expected_screen_logs' => 0,
        'message' => 'orange',
      ],
      [
        'psr3_message_level' => LogLevel::CRITICAL,
        'rfc_message_level' => RfcLogLevel::CRITICAL,
        'system_log_enabled' => 1,
        'screen_log_enabled' => 1,
        'min_psr3_level' => LogLevel::DEBUG,
        'expected_system_logs' => 1,
        'expected_screen_logs' => 1,
        'message' => 'kumquat',
      ],
      [
        'psr3_message_level' => LogLevel::INFO,
        'rfc_message_level' => RfcLogLevel::INFO,
        'system_log_enabled' => 1,
        'screen_log_enabled' => 0,
        'min_psr3_level' => LogLevel::CRITICAL,
        'expected_system_logs' => 0,
        'expected_screen_logs' => 0,
        'message' => 'cucumber',
      ],
      [
        'psr3_message_level' => LogLevel::INFO,
        'rfc_message_level' => RfcLogLevel::INFO,
        'system_log_enabled' => 1,
        'screen_log_enabled' => 1,
        'min_psr3_level' => LogLevel::CRITICAL,
        'expected_system_logs' => 0,
        'expected_screen_logs' => 0,
        'message' => 'dragonfruit',
      ],
    ];
  }

}
