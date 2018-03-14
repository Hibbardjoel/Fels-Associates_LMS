<?php
require('inc/conf.php');
accessLevel(3);
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
 err('Invalid ID: Unable to delete.','editusers');
 hm_redirect('editusers.php');
}
if ($r['userlevel'] < get_level()) {
 err('You do not have permission to delete the user selected.','editusers');
 hm_redirect('editusers.php');
}

if ($_POST) {
 if ($_POST['confirm'] != 1) err('You must confirm deletion.','deleteuser');

 if (errCnt('deleteuser') === 0) {
  delete_user($user_id,SITE_ID);
  err('User has been deleted successfully.',get_home_slug(),1);
  go_home();
 }
}

hm_header();

$allowed_levels = $levels;
if ($active_level > 1) unset($allowed_levels[1]);
krsort($allowed_levels);

template('user_box');
?>
  <div class="banner">Deleting User Information &bull;</div>
  <div id="edit_user_box" class="delete">
   <div id="view_user_table"><table>
    <tr>
     <th width="130">USERNAME</th>
     <th width="150">EMPLOYEE NAME</th>
     <th class="c" width="75">STATUS</th>
     <th class="c" width="110">DEPARTMENT</th>
     <th class="c" width="110">USER LEVEL</th>
     <th>EMAIL</th>
    </tr>
    <tr class="<?php echo $level_slugs[$userlevel];?>">
     <td><?php echo cleanOutput($username,'',1);?></td>
     <td><?php echo cleanOutput($name); ?></td>
     <td class="c"><?php echo cleanOutput($statuses[$status]); ?></td>
     <td class="c"><?php echo cleanOutput(implode(', ',$department)); ?></td>
     <td class="c"><?php echo cleanOutput($levels[$userlevel]); ?></td>
     <td><?php echo cleanOutput($email); ?></td>
    </tr>
   </table></div>
  <p>Are you sure you want to delete this user? All data will be permanently removed, including reports. There is no undoing this operation.</p>
<?php errOut('deleteuser');?>
  <form action="deleteuser.php" method="post">
  <input type="hidden" name="user_id" value="<?php echo $user_id;?>">
   <div class="field">
    <label>Confirm:</label>
    <input name="confirm" type="checkbox" id="confirm" value="1"> Yes, delete this user.
   </div>
   <div class="clear"></div>
   <div class="confirm">
    <input type="submit" name="submit" value="DELETE" class="button delete"> <a href="/editusers.php" class="button right">Cancel Deletion</a>
   </div>
   <div class="clear"></div>
  </form>
  </div>
<?php
hm_footer();
?>