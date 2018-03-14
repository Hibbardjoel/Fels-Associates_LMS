<?php
require('inc/conf.php');
publicPage();
require('inc/sess.php');

hm_header();
?>
<div id="homepage">
 <img src="<?php theme();?>/images/welcome_box.png" width="750" height="119" usemap="#Map" border="0">
 <map name="Map">
  <area shape="circle" coords="655,37,37" href="/login.php">
 </map>
</div>
<?php
hm_footer();
?>