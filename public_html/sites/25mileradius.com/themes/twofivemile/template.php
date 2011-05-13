<?php
// $Id: template.php,v 1.12 2008/08/31 22:45:35 shannonlucas Exp $
/**
 * @file The twofivemile theme.
 */

require_once path_to_twofivemiletheme() . '/twofivemile_utils.php';


/**
 * Return the path to the main twofivemile theme directory.
 */
function path_to_twofivemiletheme() {
  static $theme_path;
  
  if (!isset($theme_path)) {
    global $theme;
    if ($theme == 'twofivemile') {
      $theme_path = path_to_theme();
    }
    else {
      $theme_path = drupal_get_path('theme', 'twofivemile');
    }
  }
  return $theme_path;
}


/**
 * Implementation of hook_theme().
 *
 * @param array  $existing
 * @param string $type
 * @param string $theme
 * @param string $path
 *
 * @return array
 */
function twofivemile_theme($existing, $type, $theme, $path) {
  $funcs = array(
    'twofivemile_username' => array(
      'arguments' => $existing['theme_username'],
    ),
    'twofivemile_links'    => array(
      'arguments' => array(
        'links'      => NULL,
        'attributes' => array('class' => 'links'),
        'context'    => '',
      ),
    ),
     // My custom theme function for the user registration form.
    'user_register' => array(
       'arguments' => array('form' => NULL),
     ),
  );

  twofivemile_settings_init($theme);

  return $funcs;
}


/**
 * Display the list of available node types for node creation.
 *
 * @param $content array
 *
 * @return string The rendered HTML.
 */
function twofivemile_node_add_list($content) {
  $output = '';

  if ($content) {
    $output = '<dl class="node-type-list">';
    $class = 'odd';
    foreach ($content as $item) {
      $output .= '<dt class="' . $class . '">'. l($item['title'], $item['href'], $item['options']) .'</dt>';
      $output .= '<dd class="' . $class . '">'. filter_xss_admin($item['description']) .'</dd>';

      $class = ($class == 'odd') ? 'even' : 'odd';
    }
    $output .= '</dl>';
  }
  return $output;
}


/**
 * Decorates theme_username().
 *
 * @param object $object An instance of a node, comment, etc.
 *
 * @return string The decorated output from theme_username().
 */
function twofivemile_username($object) {
  $output = theme_username($object);

  if ((boolean)theme_get_setting('twofivemile_remove_not_verified')) {
    $to_strip = ' ('. t('not verified') .')';
    $output = str_replace($to_strip, '', $output);
  }
  
  //$output = t('Posted by !author', array('!author' => $output));
  $output = t('!author', array('!author' => $output));
  return $output;
}


/**
 * Prepare the user pictures for rendering.
 *
 * @param &$variables array The associative array of template arguments.
 */
function twofivemile_preprocess_user_picture(&$variables) {
  $variables['picture'] = '';

  if (variable_get('user_pictures', 0)) {
    $account = $variables['account'];
    if (!empty($account->picture) && file_exists($account->picture)) {
      $picture = file_create_url($account->picture);
    }
    else if (variable_get('user_picture_default', '')) {
      $picture = variable_get('user_picture_default', '');
    }
    else {
      $picture = path_to_twofivemiletheme() . '/user-icon.jpg';
    }

    if (isset($picture)) {
      $name = $account->name ? $account->name : variable_get('anonymous', t('Anonymous'));
      $alt  = t("@user's picture", array('@user' => $name));
      $attr = array('class' => 'user-picture');
      $variables['picture'] = theme('image', $picture, $alt, $alt, $attr, FALSE);
      
      // Link the picture if allowed.
      if (!empty($account->uid) && user_access('access user profiles')) {
        $attributes = array('attributes' => array('title' => t('View user profile.')), 'html' => TRUE);
        $variables['picture'] = l($variables['picture'], "user/$account->uid", $attributes);
      }
    }
  }
}

/**
 * Variables for user-profile.tpl.php.
 */
function twofivemile_preprocess_user_profile(&$vars) {
  $vars['gift_list'] = views_embed_view('gift_list', 'default', arg(1));
}

/**
 * Determine whether to show the date stamp for the given node.
 *
 * @param $type string The machine readable name of the type to check.
 *
 * @return boolean TRUE if the node is of a type that should show the date
 *         stamp, FALSE if not.
 */
function twofivemile_show_datestamp($type) {
  $default     = drupal_map_assoc(array('blog', 'forum', 'poll', 'story'));
  $valid_types = theme_get_setting('twofivemile_show_datestamp');
  $valid_types = (!empty($valid_types)) ? $valid_types : $default;

  return (array_key_exists($type, $valid_types) && ($valid_types[$type] === $type));
}


/**
 * Removes the spaces between words in the given string and returns an HTML
 * string with every other word wrapped in a span with the class "alt-color".
 *
 * @param $text string The text to render.
 *
 * @return string The rendered HTML.
 */
function twofivemile_alt_word_text($text = '') {
  if ((boolean)theme_get_setting('twofivemile_header_spaces')) {
    return $text;  
  }

  $words  = explode(' ', $text);
  $result = '';

  if (is_array($words)) {
    $alt = FALSE;
    foreach ($words as $word) {
      if ($alt) {
        $result .= '<span class="alt-color">' . $word . '</span>';
      }
      else {
        $result .= $word;
      }

      $alt = !$alt;
    }
  }

  return $result;
}


/**
 * Render the node terms with a text prefix and join them with a comma.
 *
 * @param $node object The node to render term links for.
 * @param $prefix string The text to show before the list of terms. By
 *        defaults the localized text 'Tags:' is used.
 * @param $separator string The character(s) to place between the terms. By
 *        default a comma is used.
 */
function twofivemile_render_terms($node, $prefix = NULL, $separator = ',') {
  $prefix = ($prefix == NULL) ? t('Tags:') : $prefix;
  $output = '';

  if (module_exists('taxonomy')) {
    $terms = taxonomy_link('taxonomy terms', $node);
  }
  else {
    $terms = array();
  }

  if (count($terms) > 0) {
    $output  .= $prefix . ' <ul class="links inline">';
    $rendered = twofivemile_list_of_links($terms);

    $i = 1;
    foreach ($rendered as $term) {
      $output .= '<li class="' . $term[1] . '">' . $term[0];

      if ($i < count($terms)) {
        $output .= $separator . ' ';
      }

      $output .= '</li>';

      $i++;
    }

    $output .= '</ul>';
  }

  return $output;
}


/**
 * Returns an array of rendered lists without any wrapping elements such as
 * <ul> and <li>.
 *
 * @param $links array A keyed array of links to be themed.
 *
 * @return array An array of arrays. The first element of each inner array
 *         will be the rendered link. The second element will be the CSS
 *         class that should be applied to any wrapping element of that
 *         link.
 */
function twofivemile_list_of_links($links) {
  $output = array();

  if (count($links) > 0) {
    $num_links = count($links);
    $i = 1;

    foreach ($links as $key => $link) {
      $class = $key;

      // Add first, last and active classes to the list of links to help
      // out themers.
      if ($i == 1) {
        $class .= ' first';
      }
      if ($i == $num_links) {
        $class .= ' last';
      }
      if (isset($link['href']) && ($link['href'] == $_GET['q'] || ($link['href'] == '<front>' && drupal_is_front_page()))) {
        $class .= ' active';
      }

      if (isset($link['href'])) {
        // Pass in $link as $options, they share the same keys.
        $output[] = array(l($link['title'], $link['href'], $link), $class);
      }
      else if (!empty($link['title'])) {
        // Some links are actually not links, but we wrap these in <span> for
        // adding title and class attributes
        if (empty($link['html'])) {
          $link['title'] = check_plain($link['title']);
        }
        $span_attributes = '';
        if (isset($link['attributes'])) {
          $span_attributes = drupal_attributes($link['attributes']);
        }
        $span = '<span'. $span_attributes .'>'. $link['title'] .'</span>';
        $output[] = array($span, $class);
      }

      $i++;
    }
  }

  return $output;
}


/**
 * Return the comment link to display for a node rendered as a teaser.
 *
 * @param $node object The node to render the comment link for.
 *
 * @return string The rendered comment link.
 */
function twofivemile_teaser_comment_link($node) {
  if (($node->comment_count > 0) && ($node->comment > COMMENT_NODE_DISABLED)) {
    $text = format_plural($node->comment_count,
                          '1 Comment', '@count Comments');
    
    if ($node->comment == COMMENT_NODE_READ_WRITE) {
      $title = t('Read comments or comment on @title',
                 array('@title' => $node->title));
    }
    else {
      $title = t('Read comments for @title', array('@title' => $node->title));
    }
    
    $options = array(
      'fragment'   => 'comments',
      'html'       => TRUE,
      'attributes' => array('title' => $title),
    );

    $output = l($text, 'node/' . $node->nid, $options);
  }
  else if ($node->comment == COMMENT_NODE_READ_WRITE) {
    $options = array(
      'fragment'   => 'comment-form',
      'html'       => TRUE,
      'attributes' => array('title' => t('Comment on @title', 
                                         array('@title' => $node->title))),
    );

    $output = l(t('Add a Comment'), 'comment/reply/' . $node->nid, $options);
  }

  return $output;
}


/**
 * Create the 'Continue reading' link for the bottom of posts.
 *
 * @param $node object The node to add the continue reading link to.
 *
 * @return string The link HTML.
 */
function twofivemile_read_more_link($node) {
  if ($node != NULL) {
    $link_text  = t('Continue reading...');
    $link_title = t('Continue reading !title.', array('!title' => $node->title));
    $options = array(
      'attributes' => array('title' => $link_title),
      'html' => TRUE,
    );
    return l($link_text, 'node/' . $node->nid, $options);
  }

  return '';
}


/**
 * Return a themed breadcrumb trail.
 *
 * @param $breadcrumb
 *   An array containing the breadcrumb links.
 * @return a string containing the breadcrumb output.
 */
function phptemplate_breadcrumb($breadcrumb) {
  if (!empty($breadcrumb)) {
    if (!(boolean)theme_get_setting('twofivemile_show_single_crumb')) {
        if (count($breadcrumb) == 1) {
            return '';
        }
    }

    $glue = theme_get_setting('twofivemile_breadcrumb_separator');
    $glue = (!empty($glue)) ? $glue : ' &raquo; ';
    return '<div class="breadcrumb">'. implode($glue, $breadcrumb) .'</div>';
  }
}


/**
 * Allow themable wrapping of all comments.
 *
 * @param $content string The comments to wrap.
 * @param $node object The node the comments belong to.
 *
 * @return string The rendered HTML.
 */
function phptemplate_comment_wrapper($content, $node) {
  if (!$content || $node->type == 'forum') {
    return '<div id="comments">'. $content .'</div>';
  }
  else {
    return '<div id="comments"><h2 class="comments">'. t('Comments') .'</h2>'. $content .'</div>';
  }
}


/**
 * Override or insert PHPTemplate variables into the templates.
 */
function phptemplate_preprocess_page(&$vars) {
  $vars['primary_links']   = menu_primary_links();
  $vars['secondary_links'] = menu_secondary_links();
  $vars['tabs2']           = menu_secondary_local_tasks();
  
  // Determine the header image if it is set, or add the JavaScript for
  // random header images.
  $header_img = theme_get_setting('twofivemile_header_image');
  $header_img = empty($header_img) ? '<random>' : $header_img;

  if ($header_img == '<random>') {
    $vars['closure'] .= _twofivemile_random_header_js();
  }
  else {
    $vars['styles'] .= _twofivemile_fixed_header_css($header_img) . "\n";
  }
}

/**
 * Override or insert PHPTemplate variables into the templates.
 */
function twofivemile_preprocess_location(&$vars) {
    $vars['map_link'] = location_map_link($vars['location'], 'On ');
}


/**
 * Overrides template_preprocess_comment().
 *
 * @param array $variables
 */
function phptemplate_preprocess_comment(&$variables) {
  $comment = $variables['comment'];
  $node    = $variables['node'];

  $variables['author']  = theme('username', $comment);
  $variables['content'] = $comment->comment;

  $params = array(
    '@date' => format_date($comment->timestamp, 'custom', 'M jS, Y'),
    '@time' => format_date($comment->timestamp, 'custom', 'g:i a'),
  );
  $variables['date']    = t('@date at @time', $params);

  $variables['links'] = theme('links', comment_links($comment, 0), NULL, 'comment-meta');
}


/**
 * Context-sensitive implementation of theme_links().
 *
 * @param array $links The associative array of link information.
 * @param array $attributes An associative array of attributes to apply to the
 *        links.
 * @param string $context The context that the links are being rendered as.
 *        This is used to determine which specific rendering technique to use.
 *        If no context is defined, or if it is unknown, the default
 *        theme_links is called.
 *
 * @return string The rendered HTML.
 */
function twofivemile_links($links, $attributes = array('class' => 'links'), $context = '') {
  if ($context == 'comment-meta') {
    return _twofivemile_comment_meta_links($links);
  }
  else {
    return theme_links($links, $attributes);
  }
}


/**
 *
 */
function _twofivemile_comment_meta_links($links, $attributes = array('class' => 'links')) {
  $titles    = array(
    'comment_edit'   => t('Edit'),
    'comment_delete' => t('Delete'),
    'comment_reply'  => t('Reply'),
    'comment_parent' => t('Parent'),
  );

  foreach ($titles as $key => $value) {
    if (array_key_exists($key, $links)) {
        $links[$key]['title'] = $value;
    }
  }

  // Are we on a reply to page?
  $is_reply = (strpos($_GET['q'], 'comment/reply/') === 0);

  // Suppress the reply link?
  $suppress = (theme_get_setting('twofivemile_suppress_comment_reply') == 1);
  
  if ($is_reply || $suppress) {
    unset($links[comment_reply]);
  }

  $output = array();
  foreach ($links as $key => $value) {
    $output[] = l($value['title'], $value['href']);
  }

  $glue = theme_get_setting('twofivemile_link_separator');
  $glue = ($glue !== NULL) ? $glue : ' | ';

  return implode($glue, $output);
}


/**
 * Returns the rendered local tasks. The default implementation renders
 * them as tabs. Overridden to split the secondary tasks.
 *
 * @ingroup themeable
 */
function phptemplate_menu_local_tasks() {
  return menu_primary_local_tasks();
}

function phptemplate_comment_submitted($comment) {
  return t('!datetime — !username',
    array(
      '!username' => theme('username', $comment),
      '!datetime' => format_date($comment->timestamp)
    ));
}

function phptemplate_node_submitted($node) {
  return t('!datetime — !username',
    array(
      '!username' => theme('username', $node),
      '!datetime' => format_date($node->created),
    ));
}


/**
 * Generates IE CSS links for LTR and RTL languages.
 *
 * @return string the IE style elements.
 */
function phptemplate_get_ie_styles() {
  global $language;

  $iecss = '<link type="text/css" rel="stylesheet" media="screen" href="'. base_path() . path_to_theme() .'/fix-ie.css" />';
  if (defined('LANGUAGE_RTL') && $language->direction == LANGUAGE_RTL) {
    $iecss .= '<style type="text/css" media="screen">@import "'. base_path() . path_to_theme() .'/fix-ie-rtl.css";</style>';
  }

  return $iecss;
}


/**
 * Read the theme settings' default values from the .info and save them into
 * the database.
 *
 * @param string $theme The actual name of theme that is being being checked.
 */
function twofivemile_settings_init($theme) {
  $themes = list_themes();

  // Get the default values from the .info file.
  $defaults = $themes[$theme]->info['settings'];

  // Get the theme settings saved in the database.
  $settings = theme_get_settings($theme);

  // Don't save the toggle_node_info_ variables.
  if (module_exists('node')) {
    foreach (node_get_types() as $type => $name) {
      unset($settings['toggle_node_info_' . $type]);
    }
  }

  // Save default theme settings.
  variable_set(
    str_replace('/', '_', 'theme_' . $theme . '_settings'),
    array_merge($defaults, $settings)
  );

  // Force refresh of Drupal internals.
  theme_get_setting('', TRUE);
}

/**
 * Custom override of user registration form.
 */
function twofivemile_user_register($form) {
  $output = '';
  //$output .= print_r($form);
  $form['user_registration_help']['#prefix'] = '<div id="reg-help">';
  $form['user_registration_help']['#suffix'] = '</div>';
  $output .= drupal_render($form);
  return $output;
}

/**
 * Override GMAP Location's silly page title.
 */
function twofivemile_gmap_location_node_page($count, $header, $map, $footer) {
  $output = '';
  if ($header) {
    $output .= "<p>$header</p>";
  }
  $output .= $map;
  if ($footer) {
    $output .= "<p>$footer</p>";
  }
  $output .= drupal_set_title('Map of posts');
  return $output;
}
