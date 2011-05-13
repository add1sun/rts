<div class="location vcard"><div class="adr">
<?php echo $name; ?>
<?php if ($street) {?>
<div class="street-address"><?php
  echo $street;
  if ($additional) {
    echo ' '. $additional;
  }
?></div>
<?php }?>
<?php
  if ($city || $province || $postal_code) {
    $city_province_postal = array();

    if ($city) {
      $city_province_postal[] = '<span class="locality">'. $city .'</span>';
    }
    if ($province) {
      $city_province_postal[] = '<span class="region">'. $province .'</span>';
    }
    if ($postal_code) {
      $city_province_postal[] = '<span class="postal-code">'. $postal_code .'</span>';
    }

    echo implode(', ', $city_province_postal);
  }
?>
</div></div>

<div class="radius-map-link">Within the <?php print l('Radius', 'map/posts'); ?></div>
<div class="google-map-link"><?php echo $map_link; ?></div>