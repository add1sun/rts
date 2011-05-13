<?php
// $Id: theme-settings.php,v 1.8 2008/08/31 22:44:09 shannonlucas Exp $
/**
 * @file Provides the settings for the Notibe theme.
 */

require_once drupal_get_path('theme', 'twofivemile') . '/twofivemile_utils.php';

/**
 * Implementation of THEMEHOOK_settings().
 *
 * @param array $settings An array of saved settings for this
 *        theme.
 *
 * @return array A form array.
 */
function phptemplate_settings($settings) {
  $form = array();
  
  //--------------------------------------------------------------------------
  // Get the header image list.
  $images  = _twofivemile_get_header_list(TRUE);
  $options = array('<random>' => 'Random Header Image');

  foreach ($images as $filename => $data) {
    $options[$filename] = $data->pretty_name;
  }

  //--------------------------------------------------------------------------
  // The setting for the header image.
  $current = empty($settings['twofivemile_header_image']) ? '' : $settings['twofivemile_header_image'];
  $default = in_array($current, array_keys($options)) ? $current : '<random>';
  $form['twofivemile_header_image'] = array(
    '#type'          => 'select',
    '#title'         => t('Header image'),
    '#options'       => $options,
    '#default_value' => $default,
  );

  //--------------------------------------------------------------------------
  // Remove or keep the spacing between words in the page header?
  $default = (!isset($settings['twofivemile_header_spaces'])) ? FALSE : (boolean)$settings['twofivemile_header_spaces'];
  $form['twofivemile_header_spaces'] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Allow page header spaces'),
    '#default_value' => $default,
    '#description'   => t('If checked, the spaces between words in the header will not be removed and no CSS will be applied to alternating words.'),
  );

  //--------------------------------------------------------------------------
  // Show the breadcrumb trail if it only contains the 'Home' link?
  $default = empty($settings['twofivemile_show_single_crumb']) ? FALSE : (boolean)$settings['twofivemile_show_single_crumb'];
  $form['twofivemile_show_single_crumb'] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Show single item breadcrumb trail'),
    '#default_value' => $default,
    '#description'   => t('By default the breadcrumb trail will be hidden if it contains just the link to the top level page. Check this box to override that behavior.'),
  );  

  //--------------------------------------------------------------------------
  // Breadcrumb separator.
  $default = empty($settings['twofivemile_breadcrumb_separator']) ? ' &raquo; ' : $settings['twofivemile_breadcrumb_separator'];
  $form['twofivemile_breadcrumb_separator'] = array(
    '#type'          => 'textfield',
    '#title'         => t("Breadcrumb separator"),
    '#default_value' => $default,
    '#description'   => t('The characters that will be used to separate the crumbs in the breadcrymb trail. Any necessary whitespace should be included here.'),
  );

  //--------------------------------------------------------------------------
  // Strip the ' (not verified)' from output usernames?
  $default = empty($settings['twofivemile_remove_not_verified']) ? FALSE : (boolean)$settings['twofivemile_remove_not_verified'];
  $form['twofivemile_remove_not_verified'] = array(
    '#type'          => 'checkbox',
    '#title'         => t("Strip '(not verified)' from usernames"),
    '#default_value' => $default,
    '#description'   => t("Normally, when an anonymous visitors posts a comment, their name is suffixed with '%verified'. Checking this will prevent that text from being added.",
                          array('%verified' => ' (not verified)')),
  );

  //--------------------------------------------------------------------------
  // Strip the ' (not verified)' from output usernames?
  $default = empty($settings['twofivemile_suppress_comment_reply']) ? FALSE : (boolean)$settings['twofivemile_suppress_comment_reply'];
  $form['twofivemile_suppress_comment_reply'] = array(
    '#type'          => 'checkbox',
    '#title'         => t("Suppress 'Reply' link in comments"),
    '#default_value' => $default,
    '#description'   => t('Suppresses the reply link to prevent vistors from replying to individual comments.'),
  );

  //--------------------------------------------------------------------------
  // Which content types to show a datestamp in the headline for?
  $default = $settings['twofivemile_show_datestamp'];
  $default = (!empty($default)) ? $default : array('blog', 'forum', 'poll', 'story');
  $types   = node_get_types('types');
  $options = array();

  foreach($types as $name => $type_obj) {
    $options[$name] = $type_obj->name;
  }

  $form['twofivemile_show_datestamp'] = array(
    '#type'        => 'checkboxes',
    '#title'       => t('Show date stamp'),
    '#default_value' => $default,
    '#options'     => $options,
    '#description' => t('Content types selected here will display a date stamp in their headline.'),
  );

  return $form;
}
