<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

$order_fields = array('cg_name','(SELECT count(*) FROM course_groupings WHERE cg_group_id=cg_id)');

$orderby = intval($_GET['orderby']);
$sortby = ($_GET['sortby'] ? 1 : 0);

if ($orderby < 0 || $orderby > (count($order_fields) - 1)) $orderby = 0;
$orderbyfield = $order_fields[$orderby];

$groups = get_course_groups();

if ($_POST) {

 if ($_POST['deletegroup'] && strlen($_POST['delgroup']) > 0) {

  $delete_id = intval($_POST['deletegroup']);
  if ($delete_id > 0) {
   hm_query("DELETE FROM course_groupings WHERE cg_group_id='$delete_id';");
   hm_query("DELETE FROM course_groups WHERE cg_id='$delete_id' LIMIT 1;");
   err('Deleted course section successfully.','coursesections',1);
  }
 } else {

  $newgroup = hm_cleanInput($_POST['newgroup'],'t',64);
  $addgroup = (strlen($_POST['addgroup']) > 0 ? true : false);

  if ($addgroup && $newgroup) {
   $newgroup = mysqlText($newgroup);
   $check = hm_result("SELECT cg_id FROM course_groups WHERE cg_name='$newgroup' LIMIT 1;",1);
   if ($check === 0) {
    hm_query("INSERT INTO course_groups (cg_id,cg_name) VALUES (null,'$newgroup');");
    err('Added course section successfully.','coursesections',1);
   } else {
    err('Add failed, course section already exists.','coursesections');
   }
  }
 }

 $groups = get_course_groups();
}


hm_header();

template('course_box');
?>
  <form method="post">
  <?php errOut('coursesections');?>
  <div class="controls" style="text-align:center;"><label for="newgroup">Add Section: </label><input type="text" id="newgroup" name="newgroup" value=""> <input type="submit" name="addgroup" value="Add"> &nbsp; <label for="deletegroup">Delete Section: </label><select name="deletegroup" id="deletegroup" style="width:115px;"><?php echo optOut($groups,'',' ');?></select> <input type="submit" name="delgroup" value="Delete"></div>
  </form>
  <div class="banner">Viewing Course Sections &bull;</div>
  <div id="view_course_table">
   <table width="100%">
    <tr>
     <th><?php echo sortHeader('Course Section Name',0,$sortby);?></th>
     <th width="100"><?php echo sortHeader('Courses',1,$sortby);?></th>
    </tr>
<?php
$q = hm_query("SELECT cg_id,cg_name,(SELECT count(*) FROM course_groupings WHERE cg_group_id=cg_id) as count FROM course_groups ORDER BY $orderbyfield ".($sortby ? 'DESC' : 'ASC').";");

$cnt = hm_cnt($q);

if ($cnt > 0) {
 while ($r = hm_fetch($q)) {
?>
    <tr>
     <td><a href="editcoursesection.php?id=<?php echo $r['cg_id'];?>"><?php echo cleanOutput($r['cg_name']);?></a></td>
     <td><?php echo $r['count'];?></td>
    </tr>
<?php
 }
} else {
?>
    <tr>
     <td colspan="2">No Course Sections Defined</td>
    </tr>
<?php
}
?>
   </table>
  </div>
<?php
hm_footer();
?>