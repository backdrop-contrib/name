<?php

/**
 * @file
 */

/**
 * Implements hook_features_export_options().
 */
function namefield_features_export_options() {
  $name_formats = array('default' => t('Default')) + name_get_custom_format_options();
  return $name_formats;
}

/**
 * Implements hook_features_export().
 */
function namefield_features_export($data, &$export, $module_name) {
  // Needed?
  $export['dependencies']['namefield'] = 'name';

  foreach ($data as $name) {
    $export['features']['namefield'][$name] = $name;
  }

  $pipe = array();
  return $pipe;
}

/**
 * Implements hook_features_export_render().
 */
function namefield_features_export_render($module_name, $data, $export = NULL) {
  $code = array();
  $code[] = '  $formats = array();';
  $code[] = '';

  foreach ($data as $name) {
    if ($name == 'default') {
      $format = name_settings();
      $format['machine_name'] = 'default';
      $format['name'] = t('Default');
      $format = (object) $format;
    }
    else {
      $format = (object) config_get('name.format.' . $name);
    }
    if ($format) {
      $format_export = features_var_export($format, '  ');
      $format_identifier = features_var_export($format->machine_name);
      $code[] = "  // Exported format: {$format->name}";
      $code[] = "  \$formats[{$format_identifier}] = {$format_export};";
      $code[] = "";
    }
  }

  $code[] = '  return $formats;';
  $code = implode("\n", $code);
  return array('name_default_formats' => $code);
}

/**
 * Implements hook_features_export_rebuild().
 */
function namefield_features_rebuild($module) {
  if ($defaults = features_get_default('namefield', $module)) {
    foreach ($defaults as $format) {
      $format = (object) $format;
      if ($format->machine_name == 'default') {
        $settings = name_settings();
        if (!empty($format->default_format)) {
          $settings['default_format'] = $format->default_format;
        }
        foreach (array('sep1', 'sep2', 'sep3') as $key) {
          if (isset($format->$key)) {
            $settings[$key] = $format->$key;
          }
        }
        config_set('name.settings', 'name_settings', $settings);
      }
      else {
        if (isset($format->ncfid)) {
          unset($format->ncfid);
        }
        $config = config('name.format.' . $format->machine_name);
        $config->set('name', $format->name);
        $config->set('format', $format->format);
        $config->save();
      }
    }
  }
}

/**
 * Implements hook_features_revert().
 */
function namefield_features_revert($module) {
  return namefield_features_rebuild($module);
}
