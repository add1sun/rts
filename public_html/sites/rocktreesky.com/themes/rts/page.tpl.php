<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language ?>" lang="<?php print $language->language ?>" dir="<?php print $language->dir ?>">
<head>
  <title><?php print $head_title ?></title>
  <?php print $head ?>
  <!-- No styles for IE6 and lower. -->
  <![if gte IE 7]>
    <?php print $styles ?>
  <![endif]>
  <?php print $scripts ?>
  <script src="/mint/?js" type="text/javascript"></script>
</head>

<body>
  <div id="header">
    <a href="<?php print base_path() ?>"><img src="<?php print base_path() . $directory ?>/images/rocktreesky.png" /></a>
  </div> <!-- /#header -->
  <!--[if lte IE 6]>
    Sorry but you are using an old and buggy browser so the pretty styling on this site is turned off for your visit. Please upgrade to the latest version of IE or switch to a modern browser like Firefox or Opera to see the site as it is intended.
  <![endif]-->
  <div id="sidebar">
    <div id="vocab">     
      <?php print $tree_link ?>
      <?php print $sky_link ?>
      <?php print $me_link ?>
      <?php print $rock_link ?>
    </div>
    <div id="nav"><?php print $nav ?></div>
    <?php print $search_box ?>
    <?php print $left ?>
  </div><!-- /#sidebar -->
  
  <div id="content">
    <?php //print $breadcrumb ?>
    <?php print $tabs ?>
    <?php print $messages ?>
    <?php print $help ?>
  
    <?php if ($title): ?>
      <h2><?php print $title ?></h2>
    <?php endif ?>
    <?php print $content ?>
  
    <?php print $feed_icons ?>
    <div id="bottom-bar" class="clear-block">
      <div id="bottom-left"><?php print $bottom_left ?></div>
      <div id="bottom-right"><?php print $bottom_right ?></div>
    </div><!-- /#bottom-bar -->
  </div><!-- /#content -->
          
  
  <div id="footer" class="clear-block"><?php print $footer_message . $footer ?></div>

  <?php print $closure ?>
</body>

</html>
