<?php

/**
 * Preprocessing for the page.tpl.php.
 */
//function rts_preprocess_page(&$vars) {  

//}

/**
 * Preprocessing for the node.tpl.php.
 */
function rts_preprocess_node(&$vars) {  
  // Change the $submitted var to what I want.
  $vars['submitted'] = format_date($vars['node']->created, 'custom', 'd F Y');
  return $vars;
}
