<?php

namespace Drupal\rules\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Logger that stores Rules debug logs with the session service.
 *
 * This logger stores an array of Rules debug logs in the session under
 * the attribute named 'rules_debug_log'.
 */
class RulesDebugLog implements LoggerInterface {
  use LoggerTrait;

  /**
   * The session service.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * Constructs a RulesDebugLog object.
   *
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   The session service.
   */
  public function __construct(Session $session) {
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    // Remove any backtraces since they may contain an unserializable variable.
    unset($context['backtrace']);

    $localCopy = $this->session->get('rules_debug_log', []);

    // Append the new log to the $localCopy array.
    // In D7:
    // @code
    //   logs[] = [$msg, $args, $priority, microtime(TRUE), $scope, $path];
    // @endcode
    $localCopy[] = [
      'message' => $message,
      'context' => $context,
      /** @var \Psr\Log\LogLevel $level */
      'level' => $level,
      'timestamp' => $context['timestamp'],
      'scope' => $context['scope'],
      'path' => $context['path'],
    ];

    // Write the $localCopy array back into the session.
    // It now includes the new log.
    $this->session->set('rules_debug_log', $localCopy);
  }

  /**
   * Returns a structured array of log entries.
   *
   * @return array
   *   Array of stored log entries, keyed by an integer log line number. Each
   *   element of the array contains the following keys:
   *   - message: The log message, optionally with FormattedMarkup placeholders.
   *   - context: An array of message placeholder replacements.
   *   - level: \Psr\Log\LogLevel level.
   *   - timestamp: Microtime timestamp in float format.
   *   - scope: TRUE if there are nested logs for this entry.
   *   - path: Path to edit this component.
   */
  public function getLogs() {
    return (array) $this->session->get('rules_debug_log');
  }

  /**
   * Clears the logs entries from the storage.
   */
  public function clearLogs() {
    $this->session->remove('rules_debug_log');
  }

}
