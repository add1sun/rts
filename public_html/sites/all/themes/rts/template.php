<?php

/**
 * Preprocessing for the page.tpl.php.
 */
function rts_preprocess_page(&$vars) {
  $vars['rock_link'] = rts_term_link('rock');
  $vars['tree_link'] = rts_term_link('tree');
  $vars['sky_link'] = rts_term_link('sky');
  $vars['me_link'] = rts_term_link('me');
  // Don't print page title on the meta vocab term pages.
  if (in_array(arg(2), array(45, 46,47,48))) {
    $vars['title'] = '';
  }
  // Make the contact page title lower case.
  elseif (arg(0) == 'contact') {
    $vars['title'] = 'contact';
  }
  return $vars;
}

/**
 * Preprocessing for the node.tpl.php.
 */
function rts_preprocess_node(&$vars) {
  // Split $terms by vocab.
  foreach ($vars['node']->taxonomy as $term) {
    // Terms in the Meta vocab are special.
    if ($term->vid == 1) {
      $vars['meta_term'] = rts_term_link($term->name);
    }
    // Everything else can be sent along as a term link.
    else {
      $terms[] = array('title' => $term->name, 'href' => 'taxonomy/term/'. $term->tid);
      // Create a special case for nodes with the Denmark term.
      if ($term->name == 'Denmark') {
        $vars['denmark'] = TRUE;
      }
    }
  }
  $vars['terms'] = theme('links', $terms, array('class' => 'links inline'));
  
  // Add the me region to the about me node.
  if (arg(0) == 'node' && arg(1) == 1) {
    $vars['me_stuff'] = theme('blocks', 'me');
  }
  return $vars;
}

/**
 * Process a single field within a view.
 *
 * This preprocess function isn't normally run, as a function is used by
 * default, for performance. However, by creating a template, this
 * preprocess should get picked up.
 */
function rts_preprocess_views_view_field__frontpage__title(&$vars) {
  // Grab the meta term.
  $term = db_result(db_query("SELECT td.name FROM {term_data} td JOIN {term_node} tn ON td.tid = tn.tid WHERE tn.nid = %d AND tn.tid IN(45, 46, 47, 48)", $vars['row']->nid));
  $vars['meta_term'] = rts_term_link($term);
}


/**
 * Preprocessing for the comment.tpl.php.
 */
function rts_preprocess_comment(&$vars) {
  // Add an op class to comments that I've made.
  if ($vars['comment']->uid == $vars['node']->uid) {
  $vars['post_author'] = 'op';
  }
}

/**
 * Override how submitted date is displayed.
 *
 * We use this rather than overriding the $submitted var so it will get
 * passed through the check to see of the node type displays it.
 */
function rts_node_submitted($node) {
  return t('!datetime', array('!datetime' => format_date($node->created, 'custom', 'F d, Y'),));
}

/**
 * Theme a term as a link. Used for term nav in sidebar.
 *
 * @param $name
 *  Term name.
 *
 * @return HTML for term links wrapped in <div> tags.
 */
function rts_term_link($name) {
  $term = taxonomy_get_term_by_name($name);
  $link = '<div class="'. $term[0]->name .' meta-vocab">'. l($term[0]->name, 'taxonomy/term/'. $term[0]->tid, array('attributes' => array('title' => $term[0]->description))) .'</div>';
  return $link;
}

/**
 * Use my own RSS feed icon.
 */
function rts_feed_icon($url, $title) {
  if ($image = theme('image', path_to_theme() .'/images/smallbrownrss.png', t('Syndicate content'), $title)) {
    return '<a href="'. check_url($url) .'" class="feed-icon">'. $image .'</a>';
  }
}

/**
 * Render a taxonomy term page HTML output.
 *
 * @param $tids
 *   An array of term ids.
 * @param $result
 *   A pager_query() result, such as that performed by taxonomy_select_nodes().
 *
 * @ingroup themeable
 */
function rts_taxonomy_term_page($tids, $result) {
  drupal_add_css(drupal_get_path('module', 'taxonomy') .'/taxonomy.css');

  $output = '';

  // Only display the description if we have a single term, to avoid clutter and confusion.
  if (count($tids) == 1) {
    $term = taxonomy_get_term($tids[0]);
    $description = $term->description;

    // Check that a description is set.
    if (!empty($description)) {
      $output .= '<div class="taxonomy-term-description '. $term->name .'">';
      $output .= '<strong>'. filter_xss_admin($term->name) .'</strong>: '. filter_xss_admin($description);
      $output .= '</div>';
    }
  }

  $output .= taxonomy_render_nodes($result);

  return $output;
}

/**
 * Override the search form (theme, not block) to remove the silly label.
 */
function rts_search_theme_form($form) {
  unset($form['search_theme_form']['#title']);
  return drupal_render($form);
}