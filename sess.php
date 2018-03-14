<?php
hm_query("DELETE FROM sess".SITE_SUFFIX." WHERE sess_time < (UNIX_TIMESTAMP(NOW())-".SESSION_TIMEOUT.")");

if ($_COOKIE['usersession']) {
 $sessid = hm_fetch(hm_query("SELECT sess_user_id,sess_time FROM sess".SITE_SUFFIX." WHERE sess_key='".hm_cleanInput($_COOKIE['usersession'],'a',64)."' LIMIT 1;"));
} else {
 unset($_SESSION['siteuser']['userid']);
 $sessid = array();
}

if (defined('PUBLIC_PAGE') && PUBLIC_PAGE == '1') {} else {
 if (time()-SESSION_TIMEOUT > $sessid['sess_time'] || $sessid['sess_user_id'] < 1) {
  err('You must be logged in to view the page you requested. Please log in.','userlogin');
  unset($_SESSION['siteuser']['userid']);
  hm_redirect('/login.php');
 }

 if ($sessid['sess_user_id'] > 0) {

  if (hm_result("SELECT userid FROM users".SITE_SUFFIX." WHERE userid='".$sessid['sess_user_id']."' and (status='1' or status='2') and userlevel>'0' LIMIT 1;",1) < 1) {
   err('The account you are trying to access has been disabled.','userlogin');
   unset($_SESSION['siteuser']['userid']);
   setcookie('usersession', '', 0, '/', '', 0);
   hm_redirect('/login.php');
  }

  if ($logout == 'Perform a log out.') {
   hm_query("UPDATE sess".SITE_SUFFIX." SET sess_time='0' WHERE sess_key='".hm_cleanInput($_COOKIE['usersession'],'a',64)."' LIMIT 1;");
   err('You have successfully logged out.','userlogin',1);
   unset($_SESSION['siteuser']['userid']);
   setcookie('usersession', '', 0, '/', '', 0);
   hm_redirect('/login.php');
  }

  $_SESSION['siteuser'] = hm_fetch(hm_query("SELECT userid,name,status,userlevel,department,username,employeeid,email,hash FROM users".SITE_SUFFIX." WHERE userid='".$sessid['sess_user_id']."' LIMIT 1;"));

  if (defined('ACCESS_LEVEL')) {
   if (intval(ACCESS_LEVEL) < intval($_SESSION['siteuser']['userlevel'])) {

    if (isset($level_slugs[$_SESSION['siteuser']['userlevel']])) $err = $level_slugs[$_SESSION['siteuser']['userlevel']];
    else $err = 'userlogin';

    err('You do not have the proper permissions to view the page '.trim($_SERVER['PHP_SELF'],'/').' and have been redirected here.',$err);
    go_home();
   }
  }
 }
}

if ($sessid['sess_user_id'] > 0) {
 hm_query("UPDATE sess".SITE_SUFFIX." SET sess_time='".time()."' WHERE sess_user_id='".$sessid['sess_user_id']."' LIMIT 1;");
}
?>