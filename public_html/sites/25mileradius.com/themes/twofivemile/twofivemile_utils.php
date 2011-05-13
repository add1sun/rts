<?php
// $Id: twofivemile_utils.php,v 1.4 2008/06/26 19:09:09 shannonlucas Exp $
/**
 * @file Utility functions for the twofivemile theme.
 */

define('twofivemile_CACHE_HDR', 'twofivemile.headers.list');
define('twofivemile_HEADER_PATH', drupal_get_path('theme', 'twofivemile') . '/headers');
define('twofivemile_HEADER_IMG_MASK',
       '.+\.jpg$|.+\.JPG$|.+\.jpeg*|.+\.JPEG*|.+\.gif$|.+\.GIF$|.+\.png$|.+\.PNG$');

/**
 * Retrieve a list of the images in the headers directory and provide each
 * with a pretty name. Pretty names are generated from the image's path within
 * the headers directory using these rules:
 *    1. '/' is replaced with ' / '
 *    2. '_' is replaced with ' '.
 *    3. '.***' extension is removed.
 *
 * @param $refresh bool If TRUE, reload the image list and flush the cached
 *        version.
 *
 * @return array
 */
function _twofivemile_get_header_list($refresh = FALSE) {
  $cached = cache_get(twofivemile_CACHE_HDR);
  $files = (!empty($cached)) ? $cached->data : NULL;

  if (($files == NULL) OR ($refresh == TRUE)) {
    $files = file_scan_directory(twofivemile_HEADER_PATH,
                                 twofivemile_HEADER_IMG_MASK,
                                 array('.', '..', 'CVS', '.svn'));
    foreach ($files as $filename => $data) {
      $name = substr($filename, strlen(twofivemile_HEADER_PATH) + 1);
      $name = preg_replace('/\//', ' / ', $name);
      $name = preg_replace('/_/', ' ', $name);
      $name = preg_replace('/\.(\w{3,4}$)/', '', $name);

      $data->pretty_name = $name;
    }

    // Cache the list for a week.
    cache_set(twofivemile_CACHE_HDR, $files, 'cache', time() + 604800);
  }

  return $files;
}


/**
 * Generate the JavaScript for rotating the header image.
 *
 * @return string The JavaScript for rotating the header.
 */
function _twofivemile_random_header_js() {
  global $base_url;

  $files = _twofivemile_get_header_list();
  $names = array();

  foreach ($files as $file => $data) {
    $names[] = $base_url . '/' . $file;
  }

  $name_js = drupal_to_js($names);

  $js = <<<EOJS
<script type="text/javascript">
  $(document).ready(function() {
    var names = {$name_js};
    $('#navphoto').css('background-image', 'url(' + names[Math.floor(Math.random() * names.length)] + ')');
  });
</script>
EOJS;

  return $js;
}


/**
 * Return the CSS to place inline to choose the header background image.
 *
 * @param string $filename The filename relative to the theme,
 *
 * @return string The CSS to add to the header.
 */
function _twofivemile_fixed_header_css($filename) {
  global $base_url;

  $url    = $base_url . '/' . $filename;
  $output = '<style type="text/css">#navphoto{background-image:url(%s);}</style>';

  return sprintf($output, $url);
}
