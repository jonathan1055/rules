<?php

namespace Drupal\rules\Logger;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\rules\Event\SystemLoggerEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Logger that dispatches a SystemLoggerEvent when a logger entry is made.
 */
class RulesLog implements LoggerInterface {
  use RfcLoggerTrait;
  use DependencySerializationTrait;

  /**
   * The dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * Constructs a new instance.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   An EventDispatcherInterface instance.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(EventDispatcherInterface $dispatcher, LogMessageParserInterface $parser) {
    $this->dispatcher = $dispatcher;
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Create a TypedData logger-entry object.
   * @see https://www.drupal.org/node/2625238
   */
  public function log($level, $message, array $context = []) {
    // Remove any backtraces since they may contain an unserializable variable.
    unset($context['backtrace']);

    // Convert PSR3-style messages to \Drupal\Component\Render\FormattableMarkup
    // style, so they can be translated at runtime.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);

    $logger_entry = [
      'uid' => $context['uid'],
      'type' => $context['channel'],
      'message' => $message,
      'variables' => $message_placeholders,
      'severity' => $level,
      'link' => $context['link'],
      'location' => $context['request_uri'],
      'referer' => $context['referer'],
      'hostname' => $context['ip'],
      'timestamp' => $context['timestamp'],
    ];

    // Dispatch logger_entry event.
    $event = new SystemLoggerEvent($logger_entry, ['logger_entry' => $logger_entry]);
    $this->dispatch(SystemLoggerEvent::EVENT_NAME, $event);
  }

  /**
   * Dispatch an event.
   *
   * Use this function to swap the parameter order for all events dispatched
   * by the Rules module.
   *
   * @param string $event_name
   *   The event name.
   * @param Event $event
   *   The event object.
   */
  public function dispatch($event_name, Event $event) {
    // Drupal 8.8 and 8.9 use Symfony 3.4 and Drupal 9.0 uses Symfony 4.4.
    // Starting with Symfony 4.3 the signature of the event dispatcher has the
    // parameters swapped round, the event object is first, followed by the
    // event name string. An exception is produced at core 9.1 if not swapped.
    // @todo Remove the check when Core 9.1 is the lowest supported version.
    // @see https://www.drupal.org/project/rules/issues/3172039
    if (version_compare(\Drupal::VERSION, '9.1', '>=')) {
      // The new signature, with the $event object first.
      $this->dispatcher->dispatch($event, $event_name);
    }
    else {
      // The existing signature, with $event_name string first.
      $this->dispatcher->dispatch($event_name, $event);
    }

  }

}
