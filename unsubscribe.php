<?php
require('inc/conf.php');
publicPage();

$default_error = true;

if ($_GET['id']) {
 $try = decryptUnsubURL($_GET['id']);
 if (is_array($try)) {
  $user_id = intval($try['user_id']);
  $site_id = intval($try['site_id']);
  if ($site_id == SITE_ID) {
   $check = hm_result("SELECT userid FROM users".SITE_SUFFIX." WHERE userid='$user_id' LIMIT 1;");
   if ($check == $user_id) {
    $status = hm_result("SELECT unsubscribed FROM users".SITE_SUFFIX." WHERE userid='$user_id' LIMIT 1;");
    if ($status < 1) {
     hm_query("UPDATE users".SITE_SUFFIX." SET unsubscribed='1' WHERE userid='$user_id' LIMIT 1;");
     err('You have been unsubscribed successfully.','unsubscribe',1);
    } else err('You have already been unsubscribed.','unsubscribe',1);
   } else err('User not found. Failed to unsubscribe.','unsubscribe');
  } else err('Site mismatch, unable to unsubscribe.','unsubscribe');
  $default_error = false;
 }
}

if ($default_error) err('You have followed an invalid unsubscribe link. Please close this window when done.','unsubscribe');

hm_header();
?>
 <div id="loginbox">
  <div class="left">
<?php
if (errCnt('unsubscribe')) errOut('unsubscribe');
else {
?>
   <p><font color="#336666" size="3" face="Arial, Helvetica, sans-serif">You have followed an invalid unsubscribe link. Please close this window when done.</font> </p>
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