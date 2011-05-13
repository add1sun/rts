<?php
//print_r($node->taxonomy);
?>
<div id="node-<?php print $node->nid; ?>" class="node<?php if ($sticky) { print ' sticky'; } ?><?php if (!$status) { print ' node-unpublished'; } ?>">

<?php print $picture ?>

<?php if ($page == 0): ?>
  <h2><a href="<?php print $node_url ?>" title="<?php print $title ?>"><?php print $title ?></a></h2>
<?php endif; ?>

  <div class="content">
    <?php print $content ?>
    <?php if ($denmark && $page == 1) : ?>
      <div id="denmark-series"><img src="/sites/rocktreesky.com/themes/rts/images/dk.png" />This post is part of a larger series about <a href="/tags/denmark">Denmark</a>.</div>
    <?php endif; ?>
    <?php if ($me_stuff) : ?>
      <div id="me-stuff" class="clear-block"><?php print $me_stuff; ?></div>
    <?php endif; ?>
  </div>

  <div class="meta">
    <div class="meta-term"><?php print $meta_term ?></div>
    
    <?php if ($submitted): ?>
      <div class="submitted"><?php print $submitted; ?></div>
    <?php endif; ?>
    
    <?php if ($taxonomy): ?>
      <div class="tags"><?php print $terms ?></div>
    <?php endif;?>
  </div>

  <?php if ($links): ?>
    <div class="extra"><?php print $links; ?></div>
  <?php endif; ?>
<div class="divider"></div>

</div>