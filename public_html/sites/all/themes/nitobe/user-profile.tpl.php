<?php
// $Id: user-profile.tpl.php,v 1.2 2007/08/07 08:39:36 goba Exp $

/**
 * @file user-profile.tpl.php
 * Default theme implementation to present all user profile data.
 *
 * To check for all available data within $profile, use the code below.
 *
 *   <?php print '<pre>'. check_plain(print_r($profile, 1)) .'</pre>'; ?>
 *
 * @see user-profile-category.tpl.php
 *      where the html is handled for the group.
 * @see user-profile-field.tpl.php
 *      where the html is handled for each item in the group.
 *
 * @see template_preprocess_user_profile()
 */
?>
<div class="profile">
  <?php //print '<pre>'. check_plain(print_r($profile, 1)) .'</pre>'; ?>
  <?php print $profile['Details']; ?>
  <?php print $gift_list; ?>
</div>
