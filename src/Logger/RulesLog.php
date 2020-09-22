<?php

namespace Drupal\rules\Logger;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\rules\Event\SystemLoggerEvent;
use Psr\Log\LoggerInterface;
// use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;

/**
 * Logger that dispatches a SystemLoggerEvent when a logger entry is made.
 */
class RulesLog implements LoggerInterface {
  use RfcLoggerTrait;
  use DependencySerializationTrait;

  /**
   * The dispatcher.
   *
   * \Symfony\Component\EventDispatcher\EventDispatcherInterface
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
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
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $dispatcher
   *   An EventDispatcher instance.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(ContainerAwareEventDispatcher $dispatcher, LogMessageParserInterface $parser) {
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
        if (version_compare(\Drupal::VERSION, '9.1', '>=')) {
      // The new way, with $event first.
      $this->dispatcher->dispatch($event, SystemLoggerEvent::EVENT_NAME);
    }
    else {
      // Replicate the existing dispatch signature.
      $this->dispatcher->dispatch(SystemLoggerEvent::EVENT_NAME, $event);
    }

  }

}
