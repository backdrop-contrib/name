<?php

/**
 * @file
 * Contains \Drupal\name\NameAdminTest.
 *
 * Tests for the name module.
 */

namespace Drupal\name\Tests;

/**
 * Tests for the admin settings and custom format page.
 */
class NameAdminTest extends NameTestHelper {

  public static function getInfo() {
    return array(
      'name' => 'Admin Setting Pages',
      'description' => 'Various tests on the admin area settings.' ,
      'group' => 'Name',
    );
  }

  /**
   * The most basic test. This should only fail if there is a change to the
   * Drupal API.
   */
  function testAdminSettings() {
    global $base_path;

    // Default settings and system settings.
    // @todo: fix
    $this->drupalLogin($this->admin_user);

    // The default installed formats.
    $this->drupalGet('admin/config/regional/name');

    $row_template = array(
      'title'       => '//tbody/tr[{row}]/td[1]',
      'machine'     => '//tbody/tr[{row}]/td[2]',
      'pattern'     => '//tbody/tr[{row}]/td[3]',
      'formatted'   => '//tbody/tr[{row}]/td[4]',
      'edit'        => '//tbody/tr[{row}]/td[5]//li[contains(@class, "edit")]/a',
      'edit link'   => '//tbody/tr[{row}]/td[5]//li[contains(@class, "edit")]/a/@href',
      'delete'      => '//tbody/tr[{row}]/td[5]//li[contains(@class, "delete")]/a',
      'delete link' => '//tbody/tr[{row}]/td[5]//li[contains(@class, "delete")]/a/@href',
    );
    $all_values = array(
      1 => array(
        'title href' => url('admin/config/regional/name/settings'),
        'title' => t('Default'),
        'machine' => 'default',
        'pattern' => '((((t+ig)+im)+if)+is)+jc',
        'formatted' => 'Mr Joe John Peter Mark Doe Jnr., B.Sc., Ph.D. JOAN SUE DOE Prince ',
      ),
      2 => array(
        'title href' => url('admin/config/regional/name/manage/family'),
        'title' => t('Family'),
        'machine' => 'family',
        'pattern' => 'f',
        'formatted' => 'Doe DOE  ',
        'edit link' => url('admin/config/regional/name/manage/family'),
        'delete link' => url('admin/config/regional/name/manage/family/delete'),
      ),
      3 => array(
        'title href' => url('admin/config/regional/name/manage/full'),
        'title' => t('Full'),
        'machine' => 'full',
        'pattern' => '((((t+ig)+im)+if)+is)+jc',
        'formatted' => 'Mr Joe John Peter Mark Doe Jnr., B.Sc., Ph.D. JOAN SUE DOE Prince ',
        'edit' => t('Edit'),
        'edit link' => url('admin/config/regional/name/manage/full'),
        'delete' => t('Delete'),
        'delete link' => url('admin/config/regional/name/manage/full/delete'),
      ),
      4 => array(
        'title href' => url('admin/config/regional/name/manage/given'),
        'title' => t('Given'),
        'machine' => 'given',
        'pattern' => 'g',
        'formatted' => 'Joe JOAN Prince ',
        'edit' => t('Edit'),
        'edit link' => url('admin/config/regional/name/manage/given'),
        'delete' => t('Delete'),
        'delete link' => url('admin/config/regional/name/manage/given/delete'),
      ),
      5 => array(
        'title href' => url('admin/config/regional/name/manage/short_full'),
        'title' => t('Given Family'),
        'machine' => 'short_full',
        'pattern' => 'g+if',
        'formatted' => 'Joe Doe JOAN DOE Prince ',
        'edit link' => url('admin/config/regional/name/manage/short_full'),
        'delete link' => url('admin/config/regional/name/manage/short_full/delete'),
      ),
      6 => array(
        'title href' => url('admin/config/regional/name/manage/formal'),
        'title' => t('Title Family'),
        'machine' => 'formal',
        'pattern' => 't+if',
        'formatted' => 'Mr Doe DOE  ',
        'edit link' => url('admin/config/regional/name/manage/formal'),
        'delete link' => url('admin/config/regional/name/manage/formal/delete'),
      ),
    );

    foreach ($all_values as $id => $row) {
      $this->assertRow($row, $row_template, $id);
    }

    // Load the name settings form.
    $this->drupalGet('admin/config/regional/name/settings');

    // Fieldset rendering check
    $this->assertRaw('Format string help', 'Testing the help fieldgroup');

    $default_values = array(
      'name_settings[default_format]' => 't+ig+im+if+is+kc',
      'name_settings[sep1]' => ' ',
      'name_settings[sep2]' => ', ',
      'name_settings[sep3]' => '',
    );
    foreach ($default_values as $name => $value) {
      $this->assertField($name, $value);
    }
    // ID example
    $this->assertFieldById('edit-name-settings-sep1', ' ', t('Sep 3 default value.'));
    $post_values = $default_values;
    $post_values['name_settings[default_format]'] = '';

    $this->drupalPostForm('admin/config/regional/name/settings', $post_values, t('Save configuration'));
    $this->assertText(t('Default format field is required.'));
    $post_values['name_settings[default_format]'] = '     ';
    $this->drupalPostForm('admin/config/regional/name/settings', $post_values, t('Save configuration'));
    $this->assertText(t('Default format field is required.'));

    $test_values = array(
      'name_settings[default_format]' => 'c+ks+if+im+ig+t',
      'name_settings[sep1]' => '~',
      'name_settings[sep2]' => '^',
      'name_settings[sep3]' => '-',
    );
    $this->drupalPostForm('admin/config/regional/name/settings', $test_values, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    foreach ($test_values as $name => $value) {
      $this->assertField($name, $value);
    }

    // The default installed formats and the updated default format.
    $this->drupalGet('admin/config/regional/name');

    // @todo: Remove of fix.
    // $xpath = '//tr[@id="name-0"]/td[3]';
    // $this->assertEqual(current($this->xpath($xpath)), 'c+ks+if+im+ig+t', 'Default is equal to set default.');

    // Delete all existing formats.
    $formats = entity_load_multiple('name_format');
    array_walk($formats, function ($format) {
      if (!$format->isLocked()) {
        $format->delete();
      }
    });

    $this->drupalGet('admin/config/regional/name/add');
    $this->assertRaw('Format string help', 'Testing the help fieldgroup');
    $values = array('label' => '', 'id' => '', 'pattern' => '');
    $this->drupalPostForm('admin/config/regional/name/add', $values, t('Save format'));
    foreach (array(t('Name'), t('Machine-readable name'), t('Format')) as $title) {
      $this->assertText(t('!field field is required', array('!field' => $title)));
    }
    $values = array('label' => 'given', 'id' => '1234567890abcdefghijklmnopqrstuvwxyz_', 'pattern' => 'a');
    $this->drupalPostForm('admin/config/regional/name/add', $values, t('Save format'));
    $this->assertNoText(t('!field field is required', array('!field' => t('Format'))));
    $this->assertNoText(t('!field field is required', array('!field' => t('Machine-readable name'))));

    $values = array('label' => 'given', 'id' => '%&*(', 'pattern' => 'a');
    $this->drupalPostForm('admin/config/regional/name/add', $values, t('Save format'));
    $this->assertText(t('The machine-readable name must contain only lowercase letters, numbers, and underscores.'));

    $values = array('label' => 'default', 'id' => 'default', 'pattern' => 'a');
    $this->drupalPostForm('admin/config/regional/name/add', $values, t('Save format'));
    $this->assertText(t('The machine-readable name is already in use. It must be unique.'));

    $values = array('label' => 'Test', 'id' => 'test', 'pattern' => 'abc');
    $this->drupalPostForm('admin/config/regional/name/add', $values, t('Save format'));
    $this->assertText(t('Custom name format added.'));

    $row = array(
      'title href' => url('admin/config/regional/name/manage/test'),
      'title' => 'Test',
      'machine' => 'test',
      'pattern' => 'abc',
      'formatted' => 'abB.Sc., Ph.D. ab ab ',
      'edit link' => url('admin/config/regional/name/manage/test'),
      'delete link' => url('admin/config/regional/name/manage/test/delete'),
    );
    $this->assertRow($row, $row_template, 3);

    $values = array('label' => 'new name', 'pattern' => 'f+g');
    $this->drupalPostForm('admin/config/regional/name/manage/test', $values, t('Save'));
    // $this->assertText(t('Custom format new name has been updated.'));

    $row = array(
      'label' => $values['label'],
      'id' => 'test',
      'pattern' => $values['pattern'],
    );
    $this->assertRow($row, $row_template, 3);

    $this->drupalGet('admin/config/regional/name/manage/60');
    $this->assertResponse(404);

    $this->drupalGet('admin/config/regional/name/manage/60/delete');
    $this->assertResponse(404);

    $this->drupalPostForm('admin/config/regional/name/manage/test', array(), t('Delete'));
    $this->assertText(t('Are you sure you want to delete the custom format !title?', array('!title' => $values['label'])));

    $this->drupalPostForm(NULL, array('confirm' => 1), t('Delete'));
    $this->assertText(t('The custom name format !title has been deleted.', array('!title' => $values['label'])));

  }

  /**
   * @param $row
   * @param $row_template
   * @param $id
   * @return array
   */
  public function assertRow($row, $row_template, $id) {
    foreach ($row as $cell_code => $value) {
      if (isset($row_template[$cell_code])) {
        $xpath = str_replace('{row}', $id, $row_template[$cell_code]);
        $raw_xpath = $this->xpath($xpath);
        if (!is_array($raw_xpath)) {
          $results = '__MISSING__';
        }
        else {
          $results = current($raw_xpath);
        }
        $this->assertEqual($results, $value, "Testing {$cell_code} on row {$id} using '{$xpath}' and expecting '" . check_plain($value) . "', got '" . check_plain($results) . "'.");
      }
    }
  }
}