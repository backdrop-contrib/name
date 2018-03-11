<?php

namespace Drupal\name;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Token;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\HtmlEscapedText;
use Drupal\name\Render\NameListFormattableMarkup;
use Drupal\Component\Utility\Html;

/**
 * Primary name formatter for an array of name components.
 *
 * This service should be used for any name formatting requests and direct
 * calls to the "name.format_parser" service should be avoided.
 *
 * Usage:
 *   \Drupal::service('name.formatter')->format().
 */
class NameFormatter implements NameFormatterInterface {

  use StringTranslationTrait;

  /**
   * The name format parser.
   *
   * @var \Drupal\name\NameFormatParser
   */
  protected $parser;

  /**
   * The name format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nameFormatStorage;

  /**
   * The name list format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $listFormatStorage;

  /**
   * Language manager for retrieving the default langcode when none is specified.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The factory for configuration objects.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Settings for the formatter.
   *
   * Values include:
   * - sep1: First defined separator.
   * - sep2: Seconddefined separator.
   * - sep3: Third defined separator.
   * - markup: To markup the individual components.
   *
   * @var array
   */
  protected $settings = [
    'sep1' => ' ',
    'sep2' => ', ',
    'sep3' => '',
    'markup' => FALSE,
  ];

  /**
   * The token replacement system.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a name formatter object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\name\NameFormatParser $parser
   *   The name format parser.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Utility\Token $token
   *   The token replacement system.
   * @todo: Assess if tokens should be supported before the first proper
   *        release of 8.x-2.x.
   */
  public function __construct(EntityManagerInterface $entity_manager, NameFormatParser $parser, LanguageManagerInterface $language_manager, TranslationInterface $translation, ConfigFactoryInterface $config_factory, Token $token) {
    $this->nameFormatStorage = $entity_manager->getStorage('name_format');
    $this->listFormatStorage = $entity_manager->getStorage('name_list_format');
    $this->parser = $parser;
    $this->languageManager = $language_manager;
    $this->stringTranslation = $translation;
    $this->configFactory = $config_factory;
    $config = $this->configFactory->get('name.settings');
    $this->settings = [
      'sep1' => $config->get('sep1'),
      'sep2' => $config->get('sep2'),
      'sep3' => $config->get('sep3'),
    ];
    // @todo: Assess if needed.
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($key, $value) {
    $this->settings[$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    return isset($this->settings[$key]) ? $this->settings[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $components, $type = 'default', $langcode = NULL) {
    $format_string = $this->getNameFormatString($type);
    $name_string = $this->parser->parse($components, $format_string, $this->settings);

    if ($this->settings['markup']) {
      $name = new FormattableMarkup($name_string, []);
    }
    else {
      $name = new HtmlEscapedText($name_string, []);
    }

    if (!empty($components['url'])) {
      $name = new FormattableMarkup('<a href=":link">:name</a>', [
        ':link' => $components['url']->toString(),
        ':name' => $name,
      ]);
    }

    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function formatList(array $items, $type = 'default', $list_type = 'default', $langcode = NULL) {
    $name_count = count($items);

    // Avoid any computations if none or one names only.
    if (!$name_count) {
      return '';
    }
    if ($name_count == 1) {
      $item = reset($items);
      return $this->format($item, $type, $langcode);
    }

    $settings = $this->getListSettings($list_type);

    // Removed names that don't need to be formatted.
    if ($settings['el_al_min'] && $name_count > $settings['el_al_min']) {
      $items = array_slice($items, 0, $settings['el_al_first']);
    }
    $names = [];
    foreach ($items as $item) {
      $names[] = $this->format($item, $type, $langcode);
    }

    if ($name_count > $settings['el_al_min']) {
      $etal = $this->t('et al', [], ['context' => 'name']);
      if ($this->settings['markup']) {
        $etal = new FormattableMarkup('<em>@etal</em>', ['@etal' => $etal]);
      }
      if (count($names) == 1) {
        return $this->t('@name@delimiter @etal', [
          '@name' => reset($names),
          '@delimiter' => trim($settings['delimiter']),
          '@etal' => $etal,
        ]);
      }
      else {
        $names = new NameListFormattableMarkup($names, $settings['delimiter']);
        return $this->t('@names@delimiter @etal', [
          '@names' => $names,
          '@delimiter' => trim($settings['delimiter']),
          '@etal' => $etal,
        ]);
      }
    }
    else {
      $t_args = [
        '@lastname' => array_pop($names),
        '@names' => new NameListFormattableMarkup($names, $settings['delimiter']),
        '@delimiter' => trim($settings['delimiter']),
      ];
      if ($settings['and'] == 'text') {
        $t_args['@and'] = t('and', [], ['context' => 'name']);
      }
      else {
        $t_args['@and'] = t('&', [], ['context' => 'name']);
      }

      // Strange rule from http://citationstyles.org/downloads/specification.html.
      if (($settings['delimiter_precedes_last'] == 'contextual' && $name_count > 2)
          || $settings['delimiter_precedes_last'] == 'always') {
        return t('@names@delimiter @and @lastname', $t_args);
      }
      else {
        return t('@names @and @lastname', $t_args);
      }
    }
  }

  /**
   * Helper function to get the format pattern.
   *
   * @param string $format
   *   The ID of the preferred format to use. This will fallback to the default
   *   format if the format can not be loaded.
   *
   * @return string
   *   The pattern to parse.
   */
  protected function getNameFormatString($format) {
    $config = $this->nameFormatStorage->load($format);
    if (!$format) {
      $config = $this->nameFormatStorage->load('default');
    }
    return $config->get('pattern');
  }

  /**
   * Helper function to get the format list settings.
   *
   * @param string $format
   *   The ID of the preferred format to use. This will fallback to the default
   *   format if the format can not be loaded.
   *
   * @return array
   *   The settings to use to format the list.
   */
  protected function getListSettings($format) {
    /* @var \Drupal\name\Entity\NameListFormat $listFormat */
    $listFormat = $this->listFormatStorage->load($format);
    if (!$format) {
      $listFormat = $this->listFormatStorage->load('default');
    }
    $settings = [
      'delimiter' => $listFormat->delimiter,
      'and' => $listFormat->and,
      'delimiter_precedes_last' => $listFormat->delimiter_precedes_last,
      'el_al_min' => $listFormat->el_al_min,
      'el_al_first' => min([$listFormat->el_al_min, $listFormat->el_al_first]),
    ];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastDelimitorTypes() {
    return [
      'text' => $this->t('Textual (and)'),
      'symbol' => $this->t('Ampersand (&amp;)'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLastDelimitorBehaviors() {
    return [
      'never' => $this->t('Never (i.e. "J. Doe and T. Williams")'),
      'always' => $this->t('Always (i.e. "J. Doe<strong>,</strong> and T. Williams")'),
      'contextual' => $this->t('Contextual (i.e. "J. Doe and T. Williams" <em>or</em> "J. Doe, S. Smith<strong>,</strong> and T. Williams")'),
    ];
  }

}
