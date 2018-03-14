<?php
require('inc/conf.php');
accessLevel(6);
require('inc/sess.php');

if ($_POST['user_id']) $user_id = intval($_POST['user_id']);
else $user_id = intval($_GET['id']);
$active_level = get_level();

$q = hm_query("SELECT userid,username,`name`,status,email,password,userlevel,unsubscribed,hash FROM users".SITE_SUFFIX." WHERE userid='$user_id' LIMIT 1;");
$r = hm_fetch($q);

$name = $r['name'];
$department = get_users_groups($r['userid']);
$username = $r['username'];
$password = $r['password'];
$status = $r['status'];
$email = $r['email'];
$userlevel = $r['userlevel'];
$unsubscribed = $r['unsubscribed'];
$hash = $r['hash'];

$username_current = $username;
$userlevel_current = $userlevel;

if ($r['userid'] < 1) {
 err('Invalid ID: Unable to edit.','editusers');
 hm_redirect('editusers.php');
}
if ($r['userlevel'] < get_level()) {
 err('You do not have permission to edit the user selected.','editusers');
 hm_redirect('editusers.php');
}

if ($_POST) {
 $name = $_POST['name'];
 $department = (array)$_POST['department'];
 $username = $_POST['username'];
 $password = $_POST['password'];
 $empid = $_POST['empid'];
 $status = intval($_POST['status']);
 $userlevel = intval($_POST['level']);
 $email = $_POST['email'];
 $unsubscribed = intval(!$_POST['subscribed']);

 if (strlen($name) < 2) err('You must enter a Name at least 2 characters long.','edituser');
 if (count($department) < 1) err('You must select at least 1 Department.','edituser');
 if (strlen($username) < 3) err('You must enter a Username at least 3 characters long.','edituser');
 elseif ($username != $username_current && checkUsername($username,$user_id)) err('A user already exists with the username you selected. Please choose another.','edituser');
 if (strlen($password) < 4) err('You must enter a Password at least 4 characters long.','edituser');
 if (!isset($levels[$userlevel])) err('You must select a user level.','newuser');
 elseif ($userlevel < $active_level) err('You do not have permission to assign a user level higher than yours.','edituser');
 elseif (SITE_ID > 0 && $userlevel == 1) err('You can only create a Super Admin on the master account.','edituser');
 elseif ($userlevel > 6 && $userlevel > $userlevel_current && get_manager_count($user_id) === 0) err('At least one user must have a level of Manager or Site Admin. Please create another manager before this user\'s level.','edituser');
 elseif ($active_level == 1 && $userlevel > $userlevel_current && $userlevel > 1 && get_superadmin_count($user_id) === 0) err('There must be at least one Super Admin. Please create another super admin before this user\'s level.','edituser');

 if (errCnt('edituser') === 0) {
  $name = mysqlText($name);
  $username = mysqlText($username);
  $password = mysqlText($password);
  $email = mysqlText($email);

  hm_query("UPDATE users".SITE_SUFFIX." SET name='$name',username='$username',password='$password',status='$status',userlevel='$userlevel',email='$email',unsubscribed='$unsubscribed' WHERE userid='$user_id' LIMIT 1;");
  set_users_groups($department,$user_id);
  if ($status != 1 || $userlevel < 10) removeUserAccess($user_id,SITE_ID);
  elseif ($userlevel == 10 && strlen($hash) != 24) createUserAccess($user_id,SITE_ID);

  err('Changes to user have been updated successfully.',get_home_slug(),1);
  go_home();
 }
}

hm_header();

$allowed_levels = $levels;
if ($active_level > 1) unset($allowed_levels[1]);
krsort($allowed_levels);

template('user_box');
?>
  <div class="banner">Editing User Information &bull;</div>
  <div id="edit_user_box">
<?php errOut('edituser');?>
  <form action="edituser.php" method="post">
  <input type="hidden" name="user_id" value="<?php echo $user_id;?>">
   <div class="field">
    <label>User Name:</label>
    <input name="username" type="text" id="username" value="<?php echo $username;?>">
   </div>
   <div class="field">
    <label>Employee Name:</label>
    <input name="name" type="text" id="name" value="<?php echo $name;?>">
   </div>
   <div class="field">
    <label>Level:</label>
    <select name="level" id="level"><?php echo optOut($allowed_levels,$userlevel);?></select>
   </div>
   <div class="field">
    <label>Status:</label>
    <select name="status" id="status"><?php echo optOut($statuses,$status);?></select>
   </div>
   <div class="clear"></div>
   <div class="field">
    <label>Department:</label>
    <select name="department[]" id="department" multiple><?php echo optOut($departments,array_keys($department));?></select>
   </div>
   <div class="field">
    <label>Email:</label>
    <input name="email" type="text" id="email" value="<?php echo $email;?>">
   </div>
   <div class="field">
    <label>Password:</label>
    <input name="password" type="text" id="password4" size="19" value="<?php echo $password;?>">
   </div>
<?php if ($userlevel < 7) { ?>
   <div class="field">
    <label>Email Reports:</label>
    <input name="subscribed" type="checkbox" id="subscribed" value="1"<?php echo hm_checker(1,!$unsubscribed);?>>
   </div>
<?php } ?>
   <div class="clear"></div>
   <div class="field">
    <input type="submit" name="submit" value="Submit">
   </div>
   <div class="clear"></div>
  </form>
<?php if (get_level() < 4) { ?>
  <div class="deletebox">Delete this user? <a href="deleteuser.php?id=<?php echo $user_id;?>">DELETE</a></div>
<?php } ?>
  </div>
<?php
hm_footer();
?>