<?php
require('inc/conf.php');
publicPage();
require('inc/sess.php');

if (is_logged_in()) go_home();

hm_header();
?>
 <div id="loginbox">
  <div class="left">
<?php
if (errCnt('userlogin')) errOut('userlogin');
else {
?>
   <p><font color="#990000" size="3" face="Arial, Helvetica, sans-serif"><br>
   <font size="5" color="#336666">Welcome</font><font size="4" color="#336666"> to your <?php echo MASTER_SITE_NAME_SHORT;?> Learning Library</font></font></p>
   <p><font color="#336666" size="3" face="Arial, Helvetica, sans-serif">Please login using your User Name and Password. </font> </p>
<?php } ?>
  </div>
  <div class="right">
   <form method="post" action="loginp.php">
    <label>User Name:</label><input type="text" name="uid" value="" size="20">
    <label>Password:</label><input type="password" name="pwd" value="" size="20">
    <input type="submit" name="submit" value="LOG-IN">
   </form>
  </div>
  <div class="clear"></div>
 </div>
 <div id="shadowbox"></div>
<?php
hm_footer();
?>