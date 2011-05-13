<?php
// $Id: comment.tpl.php,v 1.4 2008/08/31 12:31:49 shannonlucas Exp $

$comment_class = 'comment' . (($comment->new) ? ' comment-new' : '') . 
                 ' ' . $status . ' ' . $zebra;
?>
<div class="<?php print $comment_class; ?>">
  <div class="content">
    <?php if (!empty($picture)) { print $picture; } ?>
    <?php if ($comment->new): ?>
      <span class="new"><?php print $new ?></span>
    <?php endif; ?>
    <?php if (!empty($title)) { ?><h3><?php print $title ?></h3><?php } ?>
    <?php print $content ?>
    <div class="clear"></div>
    <?php if ($signature): ?>
      <div class="user-signature clear-block">
        <?php print $signature ?>
      </div>
    <?php endif; ?>
    <div class="clear"></div>
  </div>
  <div class="comment-meta">
    <span>
      <strong><?php print $author; ?></strong> | <?php print $date; ?>
      <?php if ($links): ?> | <?php print $links; ?><?php endif; ?>
    </span>
  </div>
</div>
