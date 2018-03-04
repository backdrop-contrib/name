<?php

namespace Drupal\name\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\name\NameFormatParser;

/**
 * Helper test class with some added functions for testing.
 */
abstract class NameTestBase extends WebTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'field_ui',
    'node',
    'name',
  ];

  protected $instance;
  protected $web_user;
  protected $admin_user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->web_user = $this->drupalCreateUser([]);
    $this->admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer content types',
      'access content',
      'access administration pages',
      'administer node fields',
    ]);
  }

  /**
   * Helper function.
   */
  protected function assertNoFieldCheckedByName($name, $message = '') {
    $elements = $this->xpath('//input[@name=:name]', [':name' => $name]);
    return $this->assertTrue(isset($elements[0]) && empty($elements[0]['checked']), $message ? $message : t('Checkbox field @name is not checked.', [
      '@name' => $name,
    ]), t('Browser'));
  }

  /**
   * Helper function.
   */
  protected function assertFieldCheckedByName($name, $message = '') {
    $elements = $this->xpath('//input[@name=:name]', [':name' => $name]);
    return $this->assertTrue(isset($elements[0]) && !empty($elements[0]['checked']), $message ? $message : t('Checkbox field @name is checked.', [
      '@name' => $name,
    ]), t('Browser'));
  }

  /**
   * Helper function.
   */
  function assertNameFormat($name_components, $type, $object, $format, $expected, array $options = []) {
    $this->assertNameFormats($name_components, $type, $object, [$format => $expected], $options);
  }

  /**
   * Helper function.
   */
  function assertNameFormats($name_components, $type, $object, array $names, array $options = []) {
    foreach ($names as $format => $expected) {
      $value = NameFormatParser::parse($name_components, $format, ['object' => $object, 'type' => $type]);
      $this->assertIdentical($value, $expected, t("Name value for '@name' was '@actual', expected value '@expected'. Components were: %components", [
        '@name' => $format,
        '@actual' => $value,
        '@expected' => $expected,
        '%components' => implode(' ', $name_components),
      ]));
    }
  }

}
