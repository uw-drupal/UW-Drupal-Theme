<?php

function uw_js_alter(&$javascript) {
  // Swap out jQuery to use an updated version of the library.
  $javascript['misc/jquery.js']['data'] = '//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js';
  $javascript['misc/jquery.js']['version'] = null;
}

function uw_preprocess_page(&$variables) {
  global $theme_path;
  $base_path = base_path();

  drupal_add_js("window.jQuery || document.write('<script src=\"$base_path$theme_path/js/jquery-1.8.3.min.js\"><' + '/script>');", array('type' => 'inline', 'group' => JS_LIBRARY, 'weight' => -19.9999999, 'every_page' => TRUE));
  drupal_add_css('https://fonts.googleapis.com/css?family=Open+Sans%3A300italic%2C400italic%2C400%2C300', 'external');

  $variables['patch_color'] = theme_get_setting('patch_color');
  $variables['band_color'] = theme_get_setting('band_color');
  $variables['default_logo'] = theme_get_setting('default_logo');
  $variables['show_patch'] = theme_get_setting('show_patch');
}

function uw_menu_tree__main_menu(array $tree) {
  return '<ul role="menu" aria-hidden="true" class="dropdown-menu">'. $tree['tree'] .'</ul>';
}

function uw_menu_tree__main_menu__level1(array $tree) {
  // note: context forced to be top level menu links
  return '<div class="nav-collapse collapse"><ul class="nav" role="menubar">'. $tree['tree'] .'</ul></div>';
}

function uw_block_view_alter(&$data, $block ) {
  // change the theme wrapper for the first level to force context
  if ($block->region == 'dropdowns') {
    $data['content']['#theme_wrappers'] = array('menu_tree__main_menu__level1');
  }
}

function uw_menu_link(array $variables) {

  $element = $variables['element'];

  if ($element['#theme'] != 'menu_link__main_menu') {
    return;
  }

  $sub_menu = '';
  $caret = '';

  if ( $element['#original_link']['expanded']
        && $element['#original_link']['has_children']) {
    $caret = '<b class="caret"></b>';

    $element['#attributes'] = array(
      'aria-haspopup' => 'true',
      'role' => 'menuitem',
      'class'=> 'dropdown'
    );

    $element['#localized_options'] = array(
      'attributes' => array(
        'class'       => array('dropdown-toggle'),
        'data-hover' => array('dropdown'),
        'data-toggle' => array('dropdown'),
        'tabindex'    => array('0')
      ),
      'html' => TRUE
    );
  } else {
    unset($element['#attributes']['class']);
  }

  if ($element['#below']) {
    $sub_menu = drupal_render($element['#below']);
  }

  $output = l($element['#title'] . $caret , $element['#href'], $element['#localized_options'] );

  return '<li' . drupal_attributes($element['#attributes']) . '>' . $output . $sub_menu . "</li>\n";
}