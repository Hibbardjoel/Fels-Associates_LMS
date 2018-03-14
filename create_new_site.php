<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

$site_host = $username = $password = '';

if (intval($_POST['create_site']) === 1) {
 $username = strtolower($_POST['site_admin']);
 $password = $_POST['site_pass'];
 $musername = strtolower($_POST['site_madmin']);
 $mpassword = $_POST['site_mpass'];
 $site_host = strtolower($_POST['site_host']);

 if (strlen($username) < 4) err('You must enter an admin username at least 4 characters long.','create_new_site');
 if (strlen($password) < 4) err('You must enter an admin password at least 4 characters long.','create_new_site');
 if (strlen($musername) < 4) err('You must enter an manager username at least 4 characters long.','create_new_site');
 if (strlen($mpassword) < 4) err('You must enter an manager password at least 4 characters long.','create_new_site');

 if (errCnt('create_new_site') === 0) {
  $site_id = createSite($_POST['site_host']);

  if ($site_id > 0) {
   err('Created new site successfully. New Site ID: '.$site_id,'view_sites',1);
   $user_id = createUser($site_id,$username,$password,3);

   if ($user_id > 0) {
    err('Created new site administrator on Site ID: '.$site_id,'view_sites',1);
   }
   $user_id = createUser($site_id,$musername,$mpassword,6);

   if ($user_id > 0) {
    err('Created new site manager on Site ID: '.$site_id,'view_sites',1);
   }

   hm_redirect('view_sites.php');
  } else {
   switch ($site_id) {
    case -1:
     err('Failed to create new site. Site Hostname must contain only a-z or 0-9.','create_new_site');
     break;
    case -2:
     err('Failed to create new site. Site Hostname must be 2 to 32 characters long.','create_new_site');
     break;
    case -3:
     err('Failed to create new site. Database insert failed.','create_new_site');
     break;
    case -4:
     err('Failed to create new site. Unable to find new Site ID. Please try again or check the database.','create_new_site');
     break;
    case -5:
     err('Failed to create new site. Site Hostname already exists.','create_new_site');
     break;
    default:
     err('Failed to create new site. Unknown error.','create_new_site');
   }
  }
 }
}

hm_header();

template('sites_box');
?>
 <div id="main">
  <?php errOut('create_new_site');?>
  <div class="banner">Creating New Site &bull;</div>
  <div id="regularbox">
   <form method="post">
   <input type="hidden" name="create_site" value="1">
   <div>
    <label for="site_host">Site Hostname:</label> http://<input type="text" name="site_host" id="site_host" value="<?php echo cleanOutput($site_host);?>">.<?php echo $_SERVER['HTTP_HOST'];?>
   </div>
   <div>
    <label for="site_admin">Site Admin Username:</label> <input type="text" name="site_admin" id="site_admin" value="<?php echo ($username ? cleanOutput($username) : 'siteadmin');?>">
   </div>
   <div>
   <label for="site_pass">Site Admin Password:</label> <input type="text" name="site_pass" id="site_pass" value="<?php echo ($password ? cleanOutput($password) : 'siteadmin');?>">
   </div>
   <div>
    <label for="site_admin">Site Manager Username:</label> <input type="text" name="site_madmin" id="site_madmin" value="<?php echo ($musername ? cleanOutput($musername) : 'manager');?>">
   </div>
   <div>
   <label for="site_pass">Site Manager Password:</label> <input type="text" name="site_mpass" id="site_mpass" value="<?php echo ($mpassword ?cleanOutput($mpassword) : 'manager');?>">
   </div>
   <div>
    <input type="submit" value="Create New Site">
   </div>
   </form>
  </div>
 </div>
<?php
hm_footer();
?>