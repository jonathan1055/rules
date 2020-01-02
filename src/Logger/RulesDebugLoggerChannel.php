<?php

namespace Drupal\rules\Logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Messenger\MessengerInterface;
use Psr\Log\LoggerInterface;

/**
 * Logs rules log entries in the available loggers.
 */
class RulesDebugLoggerChannel extends LoggerChannel {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * A configuration object with rules settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Static storage of log entries.
   *
   * @var array
   */
  protected $logs = [];

  /**
   * Creates RulesDebugLoggerChannel object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(LoggerInterface $logger, ConfigFactoryInterface $config_factory, MessengerInterface $messenger) {
    parent::__construct('rules_debug');
    $this->logger = $logger;
    $this->config = $config_factory;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    // Log message only if rules logging setting is enabled.
    $config = $this->config->get('rules.settings');
    if ($config->get('debug_log.system_debug')) {
      if ($this->levelTranslation[$config->get('system_log.log_level')] >= $this->levelTranslation[$level]) {
        parent::log($level, $message, $context);
      }
    }
    if ($config->get('debug_log.enabled')) {
      $rfc_level = $level;
      if (is_string($level)) {
        $rfc_level = $this->levelTranslation[$level];
      }
      if ($this->levelTranslation[$config->get('debug_log.log_level')] >= $rfc_level) {
        $this->logs[] = [
          'level' => $level,
          'message' => $message,
          'context' => $context,
        ];
        $this->messenger->addMessage($message, $level);
      }
    }
  }

  /**
   * Returns the structured array of entries.
   *
   * @return array
   *   Array of stored log entries.
   */
  public function getLogs() {
    return $this->logs;
  }

  /**
   * Clears the static logs entries cache.
   */
  public function clearLogs() {
    $this->logs = [];
  }

}
