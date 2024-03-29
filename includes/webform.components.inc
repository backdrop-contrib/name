<?php

/**
 * @file
 * Name Field module name component for the Webform module.
 */

/**
 * Implements Webform callback _webform_defaults_COMPONENT().
 */
function _webform_defaults_name() {
  $elements = name_element_info();
  $element = $elements['name_element'];

  $component = array(
    'name' => '',
    'form_key' => NULL,
    'pid' => 0,
    'weight' => 0,
    'value' => $element['#default_value'],
    'required' => 0,
    'extra' => array(
      'display_format' => 'default',
      'csv_format' => 'default',
      'table_format' => 'default',
      'enabled_components' => $element['#minimum_components'],
      'minimum_components' => $element['#minimum_components'],
      'components' => $element['#components'],
      'description' => '',
      'use_real_name' => 0,
      'private' => 0,
    ),
  );
  foreach (_name_translations() as $key => $label) {
    $component['extra']['components'][$key]['default_value'] = '';
  }
  return $component;
}

/**
 * Implements Webform callback _webform_edit_COMPONENT().
 */
function _webform_edit_name($name) {
  $components = _name_translations();

  $format_options = array('default' => t('System default')) + name_get_custom_format_options();

  $form['extra']['display_format'] = array(
    '#type' => 'select',
    '#title' => t('Display format'),
    '#default_value' => $name['extra']['display_format'],
    '#required' => TRUE,
    '#options' => $format_options,
  );
  $form['extra']['table_format'] = array(
    '#type' => 'select',
    '#title' => t('Table format'),
    '#default_value' => $name['extra']['table_format'],
    '#required' => TRUE,
    '#options' => $format_options,
  );
  // Add an option for individual components. This machine name is not valid.
  $format_options += array('separate components' => t('As separate components'));
  $form['extra']['csv_format'] = array(
    '#type' => 'select',
    '#title' => t('Export format'),
    '#default_value' => $name['extra']['csv_format'],
    '#required' => TRUE,
    '#options' => $format_options,
  );

  $form['extra']['enabled_components'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Enabled components'),
    '#default_value' => $name['extra']['enabled_components'],
    '#required' => TRUE,
    '#element_validate' => array('_name_field_minimal_component_requirements'),
    '#description' => t('Only the selected components will be rendered on the webform.'),
    '#options' => $components,
  );

  // Most options are stored in the "extra" array, which stores any settings
  // unique to a particular name type.
  $form['extra']['minimum_components'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Minimum components'),
    '#default_value' => $name['extra']['minimum_components'],
    '#required' => TRUE,
    '#element_validate' => array('_name_field_minimal_component_requirements'),
    '#description' => t('The minimal set of components required before the field is considered completed enough to save.'),
    '#options' => $components,
  );

  $user_field_name = config_get('name.settings', 'name_user_preferred');
  $user_field = $user_field_name?field_info_instance('user', $user_field_name, 'user') : NULL;
  $form['extra']['use_real_name'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use users real name field as default'),
    '#default_value' => $user_field ? $name['extra']['use_real_name'] : 0,
    '#description' => t('This option is only available if you have selected a name field to override the users login name.'),
    '#disabled' => !$user_field,
  );

  foreach ($components as $key => $label) {
    $form['extra']['components'][$key] = array(
      '#type' => 'fieldset',
      '#title' => t('@label component seettings', array('@label' => $label)),
    );
    // @todo .
    $type = ($key == 'title' || $key == 'generational') ? 'select' : 'textfield';
    $form['extra']['components'][$key]['type'] = array(
      '#type' => 'value',
      '#value' => $type,
    );
    $form['extra']['components'][$key]['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => $name['extra']['components'][$key]['title'],
    );
    // New feature. This prevents PHP notices.
    if (!isset($name['extra']['components'][$key]['default_value'])) {
      $name['extra']['components'][$key]['default_value'] = '';
    }
    $form['extra']['components'][$key]['default_value'] = array(
      '#type' => 'textfield',
      '#title' => t('Default value'),
      '#default_value' => $name['extra']['components'][$key]['default_value'],
      '#description' => t('Tokens are allowed. Real name support will override this setting.'),
    );
    $form['extra']['components'][$key]['title_display'] = array(
      '#type' => 'select',
      '#title' => t('Component title display'),
      '#default_value' => $name['extra']['components'][$key]['title_display'],
      '#options' => array(
        'title' => t('above'),
        'description' => t('below'),
        'placeholder' => t('placeholder'),
        'none' => t('hidden'),
      ),
    );
    $form['extra']['components'][$key]['size'] = array(
      '#type' => 'number',
      '#title' => t('HTML size property'),
      '#default_value' => $name['extra']['components'][$key]['size'],
      '#size' => 10,
      '#min' => 1,
      '#max' => 255,
    );
    $form['extra']['components'][$key]['inline_css'] = array(
      '#type' => 'textfield',
      '#title' => t('Additional inline styles'),
      '#default_value' => $name['extra']['components'][$key]['inline_css'],
    );
    $form['extra']['components'][$key]['max_length'] = array(
      '#type' => 'number',
      '#title' => t('Maximum length'),
      '#default_value' => empty($name['extra']['components'][$key]['max_length']) ? 255 : $name['extra']['components'][$key]['max_length'],
      '#required' => TRUE,
      '#size' => 10,
      '#min' => 1,
      '#max' => 255,
    );
    if ($key == 'title' || $key == 'generational') {
      $options = implode("\n", array_filter(explode("\n", $name['extra']['components'][$key]['options'])));
      $form['extra']['components'][$key]['options'] = array(
        '#type' => 'textarea',
        '#name_component' => $key,
        '#title' => t('Options'),
        '#default_value' => $name['extra']['components'][$key]['options'],
        '#required' => TRUE,
        '#element_validate' => array('_webform_name_options_validate'),
        '#description' => t("Enter one !title per line. Prefix a line using '--' to specify a blank value text. For example: '--Please select a !label'.", array('!label' => $label)),
      );
      if (module_exists('taxonomy')) {
        // @todo Make the labels more generic.
        // Generational suffixes may be also imported from one or more vocabularies
        // using the tag '[vocabulary:xxx]', where xxx is the vocabulary id. Terms
        // that exceed the maximum length of the generational suffix are not added
        // to the options list.
        $form['extra']['components'][$key]['options']['#description'] .= ' ' . t("Options may be also imported from one or more vocabularies using the tag '[vocabulary:xxx]', where xxx is the vocabulary machine-name or id. Terms that exceed the maximum length of the component are not added to the options list.");
      }

      $form['extra']['components'][$key]['sort_options'] = array(
        '#type' => 'checkbox',
        '#title' => t('Sort options'),
        '#default_value' => !empty($name['extra']['components'][$key]['sort_options']),
      );
    }
  }
  return $form;
}

/**
 * Custom validation callback.
 */
function _webform_name_options_validate($element, &$form_state) {
  module_load_include('inc', 'name', 'includes/name.content');
  _name_options_validate($element['#value'], $form_state['values']['extra']['components'][$element['#name_component']]['max_length'],
      $element['#title'], implode('][', $element['#array_parents']));
}

/**
 * Implements Webform callback _webform_render_COMPONENT().
 */
function _webform_render_name($name, $value = NULL, $filter = TRUE) {
  global $user;
  $enabled_components = array_filter($name['extra']['enabled_components']);
  foreach ($name['extra']['components'] as $key => &$info) {
    if ($key == 'title' || $key == 'generational') {
      $field = array(
        'settings' => array(
          $key . '_options' => $info['options'],
          'max_length' => array($key => $info['max_length']),
          'sort_options' => array($key => $info['sort_options']),
        ),
      );
      $info['options'] = name_field_get_options($field, $key);
    }
    $info['maxlength'] = $info['max_length'];
    if (empty($enabled_components[$key])) {
      $info['exclude'] = TRUE;
    }
  }
  $default_value = array();
  if (empty($value)) {
    $found = FALSE;
    if (!empty($name['extra']['use_real_name'])) {
      if ($user_field_name = config_get('name.settings', 'name_user_preferred')) {
        name_username_alter_preload($user);
        if ($items = field_get_items('user', $user, $user_field_name)) {
          $item = reset($items);
          $found = TRUE;
          foreach ($enabled_components as $component_key) {
            $default_value[$component_key] = isset($item[$component_key]) ? $item[$component_key] : '';
          }
        }
      }
    }
    if (!$found) {
      $token_data = array(
        'user' => $user,
      );
      foreach ($enabled_components as $component_key) {
        if (!isset($name['extra']['components'][$component_key]['default_value'])) {
          $name['extra']['components'][$component_key]['default_value'] = '';
        }
        $default_value[$component_key] = $name['extra']['components'][$component_key]['default_value'];
        if (strlen($default_value[$component_key])) {
          $default_value[$component_key] = token_replace($default_value[$component_key], $token_data, array('clear' => TRUE, 'sanitize' => FALSE));
        }
      }
    }

    foreach ($enabled_components as $component_key) {
      $max_length = $name['extra']['components'][$component_key]['max_length'];
      if (backdrop_strlen($default_value[$component_key]) > $max_length) {
        $default_value[$component_key] = backdrop_substr($default_value[$component_key], 0, $max_length);
      }
    }
  }

  $form_item = array(
    '#type' => 'name_element',
    '#title' => $filter?_webform_filter_xss($name['name']) : $name['name'],
    '#required' => $name['required'],
    '#weight' => $name['weight'],
    '#description' => $filter?_webform_filter_descriptions($name['extra']['description']) : $name['extra']['description'],
    '#default_value' => $filter?_webform_filter_values($name['value']) : $name['value'],
    '#prefix' => '<div class="webform-component-name" id="webform-component-' . $name['form_key'] . '">',
    '#suffix' => '</div>',
    '#minimum_components' => $name['extra']['minimum_components'],
    '#components' => $name['extra']['components'],
    '#default_value' => empty($value) ? $default_value : $value,
    '#theme_wrappers' => array('webform_element'),
  );

  return $form_item;
}

/**
 * Implements Webform callback _webform_display_COMPONENT().
 */
function _webform_display_name($name, $value, $format = 'html') {
  $themed_name = theme('name_item', array(
    'item' => $value,
    'format' => $name['extra']['display_format'],
  ));

  return array(
    '#title' => $name['name'],
    '#weight' => $name['weight'],
    '#theme' => 'webform_display_textfield',
    '#theme_wrappers' => $format == 'html'?array('webform_element') : array('webform_element_text'),
//    '#post_render' => array('webform_element_wrapper'),
    '#component' => $name,
    '#format' => $format,
    '#value' => $themed_name,
    '#field_prefix' => '',
    '#field_suffix' => '',
  );
}

/**
 * @todo Implement _webform_analysis_component()?
 */

/**
 * Implements Webform callback _webform_table_COMPONENT().
 */
function _webform_table_name($name, $value) {
  $themed_name = theme('name_item', array(
    'item' => $value,
    'format' => $name['extra']['table_format'],
  ));

  return check_plain($themed_name);
}

/**
 * Implements Webform callback _webform_csv_headers_COMPONENT().
 */
function _webform_csv_headers_name($name, $export_options) {
  $header = array();
  if ($name['extra']['csv_format'] == 'separate components') {
    $components = array_filter($name['extra']['enabled_components']);
    $header[0] = $header[1] = array_fill(0, count($components), '');
    $header[1][0] = $name['name'];
    // Preserve ordering.
    $header[2] = array();
    $nt = _name_translations();
    foreach ($components as $key) {
      $header[2][] = $nt[$key];
    }
  }
  else {
    $header[0] = '';
    $header[1] = '';
    $header[2] = $name['name'];
  }
  return $header;
}

/**
 * Implements Webform callback _webform_csv_format_COMPONENT().
 */
function _webform_csv_data_name($name, $export_options, $value) {
  if ($name['extra']['csv_format'] == 'separate components') {
    if (empty($value)) {
      $value = array();
    }
    $value += array(
      'title' => '',
      'given' => '',
      'middle' => '',
      'family' => '',
      'generational' => '',
      'credentials' => '',
    );
    $return = array();
    foreach (array_filter($name['extra']['enabled_components']) as $key) {
      $return[] = $value[$key];
    }
    return $return;
  }
  else {
    $themed_name = theme('name_item', array(
      'item' => $value,
      'format' => $name['extra']['table_format'],
    ));

    return $themed_name;
  }
}
