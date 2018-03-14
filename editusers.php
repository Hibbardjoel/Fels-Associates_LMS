<?php
require('inc/conf.php');
accessLevel(6);
require('inc/sess.php');

$massedit_mode = false;

$order_fields = array('username','name','status','g_name','password','email');

$orderby = intval($_GET['orderby']);
$sortby = ($_GET['sortby'] ? 1 : 0);

if ($orderby < 0 || $orderby > (count($order_fields) - 1)) $orderby = 0;
$orderbyfield = $order_fields[$orderby];

$groups = get_user_groups();
$group = key($groups);

if ($_POST) {

 if ($_POST['deletegroup'] && strlen($_POST['delgroup']) > 0) {

  $delete_id = intval($_POST['deletegroup']);
  if ($delete_id > 0) {
   $count = hm_result("SELECT count(*) FROM group_users".SITE_SUFFIX." WHERE gu_group_id='$delete_id';");

   if ($count > 0) {
    err('You must unassign all of the users within the department before deleting.','editusers');
    $group = $delete_id;
   } else {
    hm_query("DELETE FROM group_users".SITE_SUFFIX." WHERE gu_group_id='$delete_id';");
    hm_query("DELETE FROM groups".SITE_SUFFIX." WHERE g_id='$delete_id' LIMIT 1;");
    err('Deleted department successfully.','editusers',1);
   }
  }
 } else {

  $group = intval($_POST['selectgroup']);
  $newgroup = hm_cleanInput($_POST['newgroup'],'t',32);
  $addgroup = (strlen($_POST['addgroup']) > 0 ? true : false);
  $savegroup = (strlen($_POST['savegroup']) > 0 ? true : false);
  $loadgroup = (strlen($_POST['loadgroup']) > 0 ? true : false);
  if ($loadgroup) $savegroup = false;

  if ($addgroup && $newgroup) {
   $newgroup = mysqlText($newgroup);
   $check = hm_result("SELECT g_id FROM groups".SITE_SUFFIX." WHERE g_name='$newgroup' LIMIT 1;",1);
   if ($check === 0) {
    hm_query("INSERT INTO groups".SITE_SUFFIX." (g_id,g_name) VALUES (null,'$newgroup');");
    $group = hm_insert_id();
    err('Added department successfully.','editusers',1);
   } else {
    $group = $check;
   }
  }

  if ($loadgroup === false && $addgroup === false) {
   set_mass_users_groups($group,$_POST['user_id']);
  }
 }

 $groups = get_user_groups();
}

hm_header();

template('user_box');
?>
  <form method="post">
  <?php errOut('editusers');?>
<div class="controls" style="text-align:center;"><label for="newgroup">Add Department: </label><input type="text" id="newgroup" name="newgroup" value=""> <input type="submit" name="addgroup" value="Add"> &nbsp; <label for="deletegroup">Delete Department: </label><select name="deletegroup" id="deletegroup" style="width:115px;"><?php echo optOut($groups,'',' ');?></select> <input type="submit" name="delgroup" value="Delete"></div>
  <div class="banner">Edit User Information &bull;</div>
  <div id="view_user_table">
<?php if ($massedit_mode) { ?>
<div class="controls"><label for="selectgroup">Edit Users Assigned to Department: </label><select name="selectgroup" id="selectgroup"><?php echo optOut($groups,$group);?></select> <input type="submit" name="loadgroup" value="Select"></div>
<?php } ?>
<?php if ($massedit_mode) { ?>
<div class="controls buttons"><button type="button" onClick="selectAll(this);">Select all</button> <button type="button" onClick="deselectAll(this);">Deselect all</button></div>
<?php } ?>
   <table>
    <tr>
     <th width="130"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=0&sortby=".(!$sortby);?>">USERNAME</a></th>
     <th width="150"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=1&sortby=".(!$sortby);?>">EMPLOYEE NAME</a></th>
     <th class="c" width="75"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=2&sortby=".(!$sortby);?>">STATUS</a></th>
     <th class="c" width="110"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=3&sortby=".(!$sortby);?>">DEPARTMENT</a></th>
     <th class="c" width="110"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=4&sortby=".(!$sortby);?>">PASSWORD</a></th>
     <th><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=5&sortby=".(!$sortby);?>">EMAIL</a></th>
    </tr>
<?php
$q = hm_query("SELECT userid,username,`name`,status,email,userlevel,password FROM users".SITE_SUFFIX." LEFT JOIN group_users".SITE_SUFFIX." ON (userid = gu_user_id) LEFT JOIN groups".SITE_SUFFIX." ON (g_id = gu_group_id) WHERE userlevel >= ".get_level()." GROUP BY userid ORDER BY userlevel DESC,$orderbyfield ".($sortby ? 'DESC' : 'ASC').";");
while ($r = hm_fetch($q)) {
 $users_groups = get_users_groups($r['userid']);
?>
    <tr class="<?php echo $level_slugs[$r['userlevel']];?>">
     <td><?php if ($massedit_mode) { ?><input type="checkbox" name="user_id[]" id="ui_<?php echo $r['userid'];?>" value="<?php echo $r['userid'];?>" <?php hm_checker($group,array_keys($users_groups));?>><label for="ui_<?php echo $r['userid'];?>"><?php } ?><a href="edituser.php?id=<?php echo $r['userid'];?>"><?php echo cleanOutput($r['username'],'',1);?></a><?php if ($massedit_mode) { ?></label><?php } ?></td>
     <td><?php echo cleanOutput($r['name']); ?></td>
     <td class="c"><?php echo cleanOutput($statuses[$r['status']]); ?></td>
     <td class="c"><?php echo cleanOutput(implode(', ',$users_groups)); ?></td>
     <td class="c"><?php echo cleanOutput($r['password']); ?></td>
     <td><?php echo cleanOutput($r['email']); ?></td>
    </tr>
<?php } ?>
   </table>
<?php if ($massedit_mode) { ?>
<div class="controls buttons"><button type="submit" name="addusers">Update Users in Department <?php echo $groups[$group];?></button></div>
<?php } ?>
  </div>
  </form>

<script type="text/javascript">
function selectAll(x) {
 for(var i=0,l=x.form.length; i<l; i++)
 if (x.form[i].type == 'checkbox')
 x.form[i].checked = true
}
function deselectAll(x) {
 for(var i=0,l=x.form.length; i<l; i++)
 if (x.form[i].type == 'checkbox')
 x.form[i].checked = false
}
</script>
<?php
hm_footer();