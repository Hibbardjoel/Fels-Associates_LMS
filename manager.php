<?php
require('inc/conf.php');
accessLevel(6);
require('inc/sess.php');

hm_header();
?>
 <div id="main">
<?php errOut('manager');?>
  <div id="welcomebox">
Welcome <?php echo cleanOutput($_SESSION['siteuser']['name']); ?>. You are currently logged in as a <?php level();?>.<br>If you need to contact support, please <a href="mailto:<?php echo SUPPORT_EMAIL;?>">click here</a>, or email: support@felsandassociates.com</div>
  </div>
<?php
template('user_box',array('main' => 1));
template('course_box',array('main' => 1));
template('compliance_box',array('main' => 1,'manager' => 1));
?>
 </div>
<?php
hm_footer();
?>