<?php
/**
 * Implements hook_form_system_theme_settings_alter().
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   A keyed array containing the current state of the form.
 */
function uw_form_system_theme_settings_alter(&$form, &$form_state, $form_id = NULL)  {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  // Create the form using Forms API: http://api.drupal.org/api/7

  $form['uw'] = array(
    '#type' => 'fieldset',
    '#title' => 'UW Theme',
    '#weight' => -40,
  );

  $form['uw']['show_search'] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Show search (in header)'),
    '#default_value' => theme_get_setting('show_search'),
  );

  $form['uw']['search_default_site'] = array(
    '#type'          => 'radios',
    '#title'         => t('Default search site'),
    '#default_value' => theme_get_setting('search_default_site'),
    '#options' => drupal_map_assoc(array('this site', 'UW')),
    '#states' => array(
      // Hide the patch color settings if show_patch is unchecked
      'invisible' => array(
        'input[name="show_search"]' => array('checked' => FALSE),
      ),
    ),
  );

  $form['uw']['show_patch'] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Show patch (W logo)'),
    '#default_value' => theme_get_setting('show_patch'),
  );

  $form['uw']['patch_color'] = array(
    '#type'          => 'radios',
    '#title'         => t('Patch color (W logo)'),
    '#default_value' => theme_get_setting('patch_color'),
    '#options' => drupal_map_assoc(array('gold', 'purple')),
    '#states' => array(
      // Hide the patch color settings if show_patch is unchecked
      'invisible' => array(
        'input[name="show_patch"]' => array('checked' => FALSE),
      ),
    ),
  );

  $form['uw']['band_color'] = array(
    '#type'          => 'radios',
    '#title'         => t('Band color'),
    '#default_value' => theme_get_setting('band_color'),
    '#options' => drupal_map_assoc(array('purple', 'tan')),
  );

  $form['uw']['header'] = array(
    '#type' => 'fieldset',
    '#title' => t('Header image settings'),
  );
  $form['uw']['header']['default_header'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use the default header image'),
    '#default_value' => theme_get_setting('default_header', $key),
    '#tree' => FALSE,
    '#description' => t('Check here if you want the theme to use the header supplied with it.')
  );
  $form['uw']['header']['settings'] = array(
    '#type' => 'container',
    '#states' => array(
      // Hide the header settings when using the default header.
      'invisible' => array(
        'input[name="default_header"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['uw']['header']['settings']['header_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Path to custom header'),
    '#description' => t('The path to the file you would like to use as your header file instead of the default header.'),
    '#default_value' => theme_get_setting('header_path', $key),
  );
  $form['uw']['header']['settings']['header_upload'] = array(
    '#type' => 'file',
    '#title' => t('Upload header image'),
    '#maxlength' => 40,
    '#description' => t("If you don't have direct file access to the server, use this field to upload your header.")
  );

  $form['#validate'][] = 'uw_theme_settings_validate';
  $form['#submit'][] = 'uw_theme_settings_submit';

  // We are editing the $form in place, so we don't need to return anything.
}

/**
 * Validator for the system_theme_settings() form.
 */
function uw_theme_settings_validate($form, &$form_state) {
  // Handle file uploads.
  $validators = array('file_validate_is_image' => array());

  // Check for a new uploaded logo.
  $file = file_save_upload('header_upload', $validators);
  if (isset($file)) {
    // File upload was attempted.
    if ($file) {
      // Put the temporary file in form_values so we can save it on submit.
      $form_state['values']['header_upload'] = $file;
    }
    else {
      // File upload failed.
      form_set_error('header_upload', t('The header could not be uploaded.'));
    }
  }

  // If the user provided a path for a header file, make sure a file
  // exists at that path.
  if ($form_state['values']['header_path']) {
    $path = _system_theme_settings_validate_path($form_state['values']['header_path']);
    if (!$path) {
      form_set_error('header_path', t('The custom header path is invalid.'));
    }
  }
}

/**
 * Process system_theme_settings form submissions.
 */
function uw_theme_settings_submit($form, &$form_state) {
  $values = &$form_state['values'];

  // If the user uploaded a new header, save it to a permanent location
  // and use it in place of the default theme-provided file.
  if ($file = $values['header_upload']) {
    unset($values['header_upload']);
    $filename = file_unmanaged_copy($file->uri);
    $values['default_header'] = 0;
    $values['header_path'] = $filename;
    $values['toggle_header'] = 1;
  }

  // If the user entered a path relative to the system files directory for
  // a header image, store a public:// URI so the theme system can handle it.
  if (!empty($values['header_path'])) {
    $values['header_path'] = _system_theme_settings_validate_path($values['header_path']);
  }
}