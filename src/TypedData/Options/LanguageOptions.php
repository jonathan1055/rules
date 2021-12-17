<?php

namespace Drupal\rules\TypedData\Options;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Options provider to list all languages enabled on the site.
 */
class LanguageOptions extends OptionsProviderBase {

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    $languageManager = \Drupal::service('language_manager');
    $languages = $languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
    $default = $languageManager->getDefaultLanguage()->getId();
    $options = [LanguageInterface::LANGCODE_NOT_SPECIFIED => $this->t('Not specified')];
    foreach ($languages as $langcode => $language) {
      $options[$langcode] = $language->getName() . ($langcode == $default ? ' - default' : '') . ' (' . $langcode . ')';
    }
    return $options;
  }

}
