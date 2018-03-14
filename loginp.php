<?php
require('inc/conf.php');

$uid = mysqlText(hm_cleanInput($_POST['uid'],'t',30));
$pwd = mysqlText($_POST['pwd']);

hm_query('DELETE FROM sess'.SITE_SUFFIX.' WHERE sess_time < (UNIX_TIMESTAMP(NOW())-'.SESSION_TIMEOUT.');');

$user_id = hm_result("SELECT userid FROM users".SITE_SUFFIX." WHERE username='$uid' AND password='$pwd' and status='1' LIMIT 1;",1);

if ($user_id > 0) {
 $level = hm_result("SELECT userlevel FROM users".SITE_SUFFIX." WHERE userid='$user_id' LIMIT 1;",1);

 if (isset($levels[$level])) {
  $match = 1;
  $safety = 0;
  $code = genCode();
  while ($match == 1) {
   if (hm_result("SELECT sess_user_id FROM sess".SITE_SUFFIX." WHERE sess_key='$code' LIMIT 1;") > 0) {
    $code = genCode();
    $safety++;
   } elseif ($safety > 30) {
    err('Error creating session, please log in again.','userlogin');
    hm_redirect('/login.php');
   } else $match = 0;
  }

  $_SESSION['siteuser']['userid'] = $user_id;
  setcookie('usersession',$code);
  hm_query("INSERT INTO sess".SITE_SUFFIX." (sess_user_id,sess_time,sess_key) VALUES ('$user_id',UNIX_TIMESTAMP(),'$code')");

  go_home($level);
 }
}

err('Unable to log in with the information provided. Please try again. If you keep getting this error message, please contact your supervisor.','userlogin');
hm_redirect('/login.php');
?>