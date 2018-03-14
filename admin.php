<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

hm_header();
?>
 <div id="main">
  <div id="welcomebox">
<?php errOut('admin');?>
   Welcome <?php echo cleanOutput($_SESSION['siteuser']['name']);?>. You are currently logged in as a <?php level();?>. This will give you access to the features listed below.</a>
  </div>
<?php
template('user_box',array('main' => 1));
template('course_box',array('main' => 1));
template('compliance_box',array('main' => 1,'manager' => 1));
template('sites_box',array('main' => 1));
?>
 </div>
<?php
hm_footer();
?>