<?php
require('inc/conf.php');
accessLevel(6);
require('inc/sess.php');

$active_level = get_level();

$name = $department = $username = $password = $status = $email = $userlevel = '';

if ($_POST) {
 $name = $_POST['name'];
 $department = (array)$_POST['department'];
 $username = $_POST['username'];
 $password = $_POST['password'];
 $status = intval($_POST['status']);
 $userlevel = intval($_POST['level']);
 $email = $_POST['email'];

 if (strlen($name) < 2) err('You must enter a Name at least 2 characters long.','newuser');
 if (count($department) < 1) err('You must select at least 1 Department.','newuser');
 if (strlen($username) < 3) err('You must enter a Username at least 3 characters long.','newuser');
 elseif ($username != $username_current && checkUsername($username,$user_id)) err('A user already exists with the username you selected. Please choose another.','newuser');
 if (strlen($password) < 4) err('You must enter a Password at least 4 characters long.','newuser');
 if (!isset($levels[$userlevel])) err('You must select a user level.','newuser');
 elseif ($userlevel < $active_level) err('You do not have permission to assign a user level higher than yours.','newuser');
 elseif (SITE_ID > 0 && $userlevel === 1) err('You can only create a Super Admin on the master account.','newuser');

 if (errCnt('newuser') === 0) {
  $name = mysqlText($name);
  $username = mysqlText($username);
  $password = mysqlText($password);
  $email = mysqlText($email);

  hm_query("INSERT INTO users".SITE_SUFFIX." SET userid=NULL,name='$name',department='$department',username='$username',password='$password',status='$status',userlevel='$userlevel',email='$email';");
  $new_id = hm_insert_id();

  if ($new_id > 0) {
   set_users_groups($department,$new_id);
   if ($userlevel == 10) createUserAccess($new_id,SITE_ID);
  }

  err('New user has been successfully added.',get_home_slug(),1);
  go_home();
 }
}

hm_header();

$allowed_levels = $levels;
if ($active_level > 1) unset($allowed_levels[1]);
krsort($allowed_levels);

template('user_box');
?>
  <div class="banner">Adding New User Information &bull;</div>
  <div id="edit_user_box" class="add">
  <?php errOut('newuser');?>
  <form action="newuser.php" method="post">
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
    <select name="department[]" id="department" multiple><?php echo optOut($departments,array_keys((array)$department));?></select>
   </div>
   <div class="field">
    <label>Email:</label>
    <input name="email" type="text" id="email" value="<?php echo $email;?>">
   </div>
   <div class="field">
    <label>Password:</label>
    <input name="password" type="text" id="password4" size="19" value="<?php echo $password;?>">
   </div>
   <div class="clear"></div>
   <div class="field">
    <input type="submit" name="submit" value="Submit">
   </div>
   <div class="clear"></div>
  </form>
  </div>
<?php
hm_footer();
?>