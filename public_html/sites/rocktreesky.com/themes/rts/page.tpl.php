
  <div id="header">
    <a href="<?php print base_path() ?>"><img src="<?php print base_path() . $directory ?>/images/rocktreesky.png" /></a>
  </div> <!-- /#header -->

  <div id="sidebar">
    <?php print render($page['left']); ?>
  </div><!-- /#sidebar -->
  
  <div id="content">
    <?php if ($tabs): ?>
    <div class="tabs"><?php print render($tabs); ?></div>
    <?php endif; ?>
    <?php print $messages ?>
    <?php print render($page['help']); ?>
  
    <?php if ($title): ?>
      <?php print render($title_prefix); ?>
      <h2><?php print $title ?></h2>
      <?php print render($title_suffix); ?>
    <?php endif ?>
    <?php print render($page['content']) ?>
  
    <?php print $feed_icons ?>
    <div id="bottom-bar" class="clearfix">
      <?php print render($page['bottom']) ?>
      <?php print render($page['bottom_left']) ?>
      <?php print render($page['bottom_right']) ?>
    </div><!-- /#bottom-bar -->
  </div><!-- /#content -->

  <div id="footer" class="clearfix"><?php print render($page['footer']) ?></div>
