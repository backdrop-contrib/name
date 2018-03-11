<?php

/**
 * @file
 * Post update functions for Name.
 */

/**
 * Adds the default list format.
 */
function name_post_update_create_name_list_format() {
  $default_list = \Drupal::entityManager()->getStorage('name_list_format')->load('default');
  if ($default_list) {
    if (!$default_list->locked) {
      $default_list->locked = TRUE;
      $default_list->save();
      drupal_set_message(t('Default name list format was set to locked.'));
    }
    else {
      drupal_set_message(t('Nothing required to action.'));
    }
  }
  else {
    $default_list = entity_create('name_list_format', [
      'id' => 'default',
      'label' => 'Default',
      'locked' => true,
      'status' => true,
      'delimiter' => ', ',
      'and' => 'text',
      'delimiter_precedes_last' => 'never',
      'el_al_min' => 3,
      'el_al_first' => 1,
    ]);
    $default_list->save();
    drupal_set_message(t('Default name list format was added.'));
  }
  // @todo: maybe parse all defined field settings to discover all variations?
}

/**
 * Corrects the field formatter settings for new name list type settings.
 */
function name_post_update_formatter_settings() {
  $field_storage_configs = \Drupal::entityManager()
      ->getStorage('field_storage_config')
      ->loadByProperties(['type' => 'name']);
  $default_settings = [
    "format" => "default",
    "markup" => FALSE,
    "output" => "default",
    "list_format" => "default",
  ];

  foreach ($field_storage_configs as $field_storage) {
    $field_name = $field_storage->getName();
    $fields = \Drupal::entityManager()
      ->getStorage('field_config')
      ->loadByProperties(['field_name' => $field_name]);
    foreach ($fields as $field) {
      $properties = [
        'targetEntityType' => $field->getTargetEntityTypeId(),
        'bundle' => $field->getTargetBundle()
      ];
      $view_displays = \Drupal::entityManager()
          ->getStorage('entity_view_display')
          ->loadByProperties($properties);
      foreach ($view_displays as $view_display) {
        if ($component = $view_display->getComponent($field_name)) {
          $settings = $component->settings;
          $settings['list_format'] = isset($settings['multiple']) && $settings['multiple'] == 'default' ? '' : 'default';
          $settings = array_intersect_key($settings, $default_settings);
          $settings += $default_settings;
          $view_display->setComponent($field_name, array(
              'type' => 'name_default',
              'settings' => $settings,
            ) + $component)->save();
        }
      }
    }
  }

  drupal_set_message(t('New name list formatter settings are implemented. Please review any name display settings that used inline lists.'));
}
