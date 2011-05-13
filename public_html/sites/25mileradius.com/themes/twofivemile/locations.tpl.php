<?php if (count($locations)) {?>
<strong><?php echo count($locations) > 1 ? t('Locations') : t('Location');?></strong>
<?php
  foreach ($locations as $location) {
    echo '<div class="node-location">' . $location . '</div>';
  }
}

