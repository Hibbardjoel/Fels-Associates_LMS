<?php
require('inc/conf.php');
accessLevel(3);
require('inc/sess.php');

$sortby = ($_GET['sortby'] ? 1 : 0);
$sortby2 = ($_GET['sortby2'] ? 1 : 0);

$groups = get_user_groups();

if ($_POST) {

 if ($_POST['updateparents'] == 1) {

  if (is_array($_POST['children']) && count($_POST['children']) > 0) {
   $cnt = 0;

   foreach ($_POST['children'] as $id => $ch) {
    $id = intval($id);
    $ch = intval($ch);
    hm_query("UPDATE groups".SITE_SUFFIX." SET g_parent_id='$ch' WHERE g_id='$id' and g_parent_id != '$ch' LIMIT 1;");
    if (hm_affected()) $cnt++;
   }

   err('Updated '.$cnt.' department'.($cnt == 1 ? '' : 's'),'departments',1);
  }

 } else if ($_POST['deletegroup'] && strlen($_POST['delgroup']) > 0) {

  $delete_id = intval($_POST['deletegroup']);
  if ($delete_id > 0) {
   $count = hm_result("SELECT count(*) FROM groups".SITE_SUFFIX." WHERE g_parent_id='$delete_id';");

   if ($count > 0) {
    err('You must reassign all the children to another parent department before deleting.','departments');
   } else {
    hm_query("DELETE FROM groups".SITE_SUFFIX." WHERE g_parent_id=0 and g_id='$delete_id' LIMIT 1;");
    err('Deleted parent department successfully.','departments',1);
   }
  }

 } else {

  $newgroup = hm_cleanInput($_POST['newgroup'],'t',32);
  $addgroup = (strlen($_POST['addgroup']) > 0 ? true : false);

  if ($addgroup && $newgroup) {
   $newgroup = mysqlText($newgroup);
   $check = hm_result("SELECT g_id FROM groups".SITE_SUFFIX." WHERE g_name='$newgroup' LIMIT 1;",1);
   if ($check === 0) {
    hm_query("INSERT INTO groups".SITE_SUFFIX." (g_id,g_parent_id,g_name) VALUES (null,0,'$newgroup');");
    $group = hm_insert_id();
    err('Added parent department successfully.','departments',1);
   }
  }
 }

 $groups = get_user_groups();
}

$parents = get_user_group_parents();

hm_header();

template('user_box');
?>
  <?php errOut('departments');?>
<form method="post">
<div class="controls" style="line-height:1.8;padding:0 0 0 130px;"><label for="newgroup">Add Parent Department: </label><input type="text" id="newgroup" name="newgroup" value=""> <input type="submit" name="addgroup" value="Add"><br>
<label for="deletegroup">Delete Parent Department: </label><select name="deletegroup" id="deletegroup" style="width:115px;"><?php echo optOut($parents,'',' ');?></select> <input type="submit" name="delgroup" value="Delete"></div>
  </form>
  <form method="post"><input type="hidden" name="updateparents" value="1">
  <div class="banner">Edit Department Parents &bull;</div>
  <div id="view_user_table">
   <table>
    <tr>
     <th width="150"><a href="<?php echo $_SERVER['PHP_SELF']."?sortby=".(!$sortby);?>">DEPARTMENT</a></th>
     <th><a href="<?php echo $_SERVER['PHP_SELF']."?sortby2=".(!$sortby2);?>">PARENT</a></th>
    </tr>
<?php
$q = hm_query("SELECT g_id,g_parent_id,g_name FROM groups".SITE_SUFFIX." WHERE g_parent_id=0 ORDER BY g_name ".($sortby ? 'DESC' : 'ASC').";");
while ($r = hm_fetch($q)) {
 $parent_id = intval($r['g_id']);
?>
    <tr class="parent">
     <td><?php echo cleanOutput($r['g_name'],'',1);?></td>
     <td></td>
    </tr>
<?php
 $q2 = hm_query("SELECT g_id,g_name FROM groups".SITE_SUFFIX." WHERE g_parent_id > 0 and g_parent_id='$parent_id' ORDER BY g_name ".($sortby2 ? 'DESC' : 'ASC').";");
 while ($r2 = hm_fetch($q2)) {
?>
    <tr class="child">
     <td><?php echo cleanOutput($r2['g_name'],'',1);?></td>
     <td><select name="children[<?php echo $r2['g_id'];?>]"><?php echo optOut($parents,$parent_id);?></select></td>
    </tr>
<?php
 }
}
?>
   </table>
<div class="controls buttons"><button type="submit" name="update">Update Department Parents</button></div>
  </div>
  </form>
<?php
hm_footer();