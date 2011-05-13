<?php
// $Id: page.tpl.php,v 1.10 2008/08/31 22:46:42 shannonlucas Exp $
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language ?>" lang="<?php print $language->language ?>" dir="<?php print $language->dir ?>">
  <head>
    <title><?php print $head_title ?></title>
    <?php print $head ?>
    <?php print $styles ?>
    <?php print $scripts ?>
    <!--[if IE]>
      <?php print phptemplate_get_ie_styles() . "\n"; ?>
    <![endif]-->
  </head>
  <body class="<?php print $body_classes; ?>">
    <div id="page" class="container_16">
      <div id="header">
        <div id="headerTitle" class="grid_12">
          <?php if (isset($logo)): ?>
            <a href="<?php print check_url($front_page); ?>" title="<?php print check_plain($site_name); ?>"><img src="<?php print check_url($logo); ?>" alt="<?php print $site_title; ?>" id="logo" /></a>
          <?php endif; ?>
          <div id="title-wrapper" style="float: left;">
            <h1><a href="<?php print check_url($front_page); ?>" title="<?php print check_plain($site_name); ?>"><?php print nitobe_alt_word_text(check_plain($site_name)); ?></a></h1>
            <?php if (isset($site_slogan)): ?>
              <div id="site-slogan"><?php print check_plain($site_slogan); ?></div>
            <?php endif; ?>
          </div> <!-- /#title-wrapper -->
        </div> <!-- /#headerTitle -->
        <div id="search-top" class="grid_4"><?php if ($search_box) { print $search_box; } ?></div>
        <div class="clear"></div>
        <?php if ($header): ?><div id="top-header" class="grid_16"><?php print $header; ?></div><div class="clear"></div><?php endif; ?>
        <div id="headerLinks" class="grid_16">
          <?php if (isset($primary_links)) : ?>
            <?php print theme('links', $primary_links, array('id' => 'topLinks', 'class' => 'grid_16')) ?>
          <?php endif; ?>
          <?php if (isset($secondary_links)) : ?>
            <div class="clear"></div>
            <?php print theme('links', $secondary_links, array('id' => 'subLinks', 'class' => 'links secondary-links grid_16')) ?>
          <?php endif; ?>
        </div><!-- #headerLinks -->
        <div class="clear"></div>
        <div id="navphoto" class="grid_16"></div>
      </div><!-- /#header -->
      <div class="rule-top">&nbsp;</div>
      <?php if ($mission): ?><div id="mission" class="grid_16"><?php print $mission; ?></div><?php endif; ?>
      <div id="center">
        <?php print $breadcrumb; ?>
        <?php if (!empty($title)): ?>
          <div class="headline grid_12 alpha">
            <?php print '<h2'. ($tabs ? ' class="with-tabs grid_8 alpha"' : ' class="grid_8 alpha"') .'>'. $title .'</h2>'; ?>
            <?php if (!empty($node) && nitobe_show_datestamp($node->type)): ?>
              <div class="timestamp grid_4 omega"><?php print format_date($node->created, 'custom', 'd M Y'); ?></div>
            <?php endif; ?>
          </div><!-- #headline -->
          <div class="clear"></div>
        <?php endif; ?>
        <?php if (!empty($tabs)):?>
            <div id="tabs-wrapper" class="grid_12 alpha">
                <ul class="tabs primary"><?php print $tabs; ?></ul>
                <?php if ($tabs2): ?>
                    <ul class="tabs secondary"><?php print $tabs2; ?></ul>
                <?php endif; ?>
            </div>
            <div class="clear"></div>
        <?php endif; ?>
        <?php if ($show_messages && $messages): print $messages; endif; ?>
        <?php print $help; ?>
        <div class="clear-block">
          <?php print $content ?>
        </div>
      </div> <!-- /#center -->
      <?php if (isset($right) || isset($left)): ?>
        <div id="sidebar-right" class="sidebar">
          <?php
            //-----------------------------------------------------------------
            // $left is printed here in case the previous theme had blocks in
            // the 'left' region. This would be the case if switching from
            // Garland which places the navigation block in the 'left' region.
            if (isset($left)) print $left;
            if (isset($right)) print $right;
          ?>
        </div><!-- /#sidebar-right -->
      <?php endif; ?>
      <div class="clear">&nbsp;</div>
      <?php if ($bottom): ?>
        <div id="bottomPad">&nbsp;</div>
        <div id="bottom-hr" class="rule">&nbsp;</div>
        <div id="bottom" class="grid_16">
          <div id="bottom-bar">
            <?php print $bottom; ?>
          </div>
        </div>
        <div class="clear">&nbsp;</div>
        <br />
      <?php endif; ?>
      <div class="rule">&nbsp;</div>
      <div id="footer">
        <?php print $footer_message . $footer ?>
      </div>
    </div> <!-- /#page -->
  <?php print $closure; ?>
  </body>
</html>
