<?php
//krumo(get_defined_vars());
?>
  <div id="node-<?php print $node->nid; ?>" class="<?php print $classes; ?>"<?php print $attributes; ?>>

    <?php print $user_picture; ?>

    <?php print render($title_prefix); ?>
    <?php if (!$page): ?>
      <h2<?php print $title_attributes; ?>><a href="<?php print $node_url; ?>"><?php print $title; ?></a></h2>
    <?php endif; ?>
    <?php print render($title_suffix); ?>

    <div class="content"<?php print $content_attributes; ?>>
      <?php
        // Hide the comments and terms and render them later.
        hide($content['comments']);
        hide($content['taxonomy_vocabulary_7']);
        hide($content['field_category']);
        // Hide links. We don't need the add comment link with the form there.
        hide($content['links']);
        print render($content);
      ?>
  </div>

  <div class="meta">    
    <?php if ($display_submitted): ?>
      <div class="submitted">
        <?php print $submitted; ?>
      </div>
    <?php endif; ?>

      <?php if ($content['field_category']): ?>
        <div class="category">
          <strong><?php print render($content['field_category']); ?></strong>
        </div>
      <?php endif; ?>
    
      <?php if ($content['taxonomy_vocabulary_7']): ?>
        <div class="tags">
          <?php print render($content['taxonomy_vocabulary_7']); ?>
        </div>
      <?php endif; ?>
  </div>

  <?php if ($content['comments']): ?>
    <div class="comments">
      <?php print render($content['comments']); ?>
    </div>
  <?php endif; ?>

</div>