<?php

namespace Drupal\rules_ban\Plugin\Condition;

use Drupal\ban\BanIpManagerInterface;
use Drupal\rules\Core\RulesConditionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'IP address is blocked' condition.
 *
 * @Condition(
 *   id = "rules_ip_is_banned",
 *   label = @Translation("IP address is banned"),
 *   category = @Translation("Ban"),
 *   context = {
 *     "ip" = @ContextDefinition("string",
 *       label = @Translation("IP Address"),
 *       description = @Translation("Determine if an IP address is banned using the Ban Module."),
 *       default_value = NULL,
 *       required = FALSE
 *     )
 *   }
 * )
 */
class IpIsBanned extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ban.ip_manager')
    );
  }

  /**
   * Constructs a IpIsBanned object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ban\BanIpManagerInterface $ban_manager
   *   The ban manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BanIpManagerInterface $ban_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->banManager = $ban_manager;
  }

  /**
   * Checks if an IP address is banned.
   *
   * @param string $ip
   *   The IP address to check.
   *
   * @return bool
   *   TRUE if the IP address is banned.
   */
  public function doEvaluate($ip) {
    return $this->banManager->isBanned($ip);
  }

}
