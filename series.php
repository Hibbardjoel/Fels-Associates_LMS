<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

$order_fields = array('s.sr_name','(SELECT count(*) FROM series_groups c WHERE c.sr_parent_id=s.sr_id)');

$orderby = intval($_GET['orderby']);
$sortby = ($_GET['sortby'] ? 1 : 0);

if ($orderby < 0 || $orderby > (count($order_fields) - 1)) $orderby = 0;
$orderbyfield = $order_fields[$orderby];

$groups = get_series_groups();

if ($_POST) {

 if ($_POST['deletegroup'] && strlen($_POST['delgroup']) > 0) {

  $delete_id = intval($_POST['deletegroup']);
  if ($delete_id > 0) {
   hm_query("DELETE FROM series_groupings WHERE sr_group_id='$delete_id';");
   hm_query("DELETE FROM series_groups WHERE sr_id='$delete_id' LIMIT 1;");
   err('Deleted series group successfully.','seriessections',1);
  }
 } else {

  $newgroup = hm_cleanInput($_POST['newgroup'],'t',64);
  $addgroup = (strlen($_POST['addgroup']) > 0 ? true : false);

  if ($addgroup && $newgroup) {
   $newgroup = mysqlText($newgroup);
   $check = hm_result("SELECT sr_id FROM series_groups WHERE sr_name='$newgroup' LIMIT 1;",1);
   if ($check === 0) {
    hm_query("INSERT INTO series_groups (sr_id,sr_parent_id,sr_name) VALUES (null,0,'$newgroup');");
    err('Added series group successfully.','seriessections',1);
   } else {
    err('Add failed, series group already exists.','seriessections');
   }
  }
 }

 $groups = get_series_groups();
}


hm_header('Prerequisite Series Instruction');

template('course_box');
?>
  <form method="post">
  <?php errOut('seriessections');?>
  <div class="controls" style="text-align:center;"><label for="newgroup">Add Series: </label><input type="text" id="newgroup" name="newgroup" value=""> <input type="submit" name="addgroup" value="Add"> &nbsp; <label for="deletegroup">Delete Series: </label><select name="deletegroup" id="deletegroup" style="width:115px;"><?php echo optOut($groups,'',' ');?></select> <input type="submit" name="delgroup" value="Delete"></div>
  </form>
  <div class="banner">Viewing Prerequisite Series Instruction &bull;</div>
  <div id="view_course_table">
   <table width="100%">
    <tr>
     <th><?php echo sortHeader('Series Group Name',0,$sortby);?></th>
     <th width="100"><?php echo sortHeader('Series',1,$sortby);?></th>
    </tr>
<?php
$q = hm_query("SELECT s.sr_id,s.sr_name,(SELECT count(*) FROM series_groups c WHERE c.sr_parent_id=s.sr_id) as count FROM series_groups s WHERE s.sr_parent_id='0' ORDER BY $orderbyfield ".($sortby ? 'DESC' : 'ASC').";");

$cnt = hm_cnt($q);

if ($cnt > 0) {
 while ($r = hm_fetch($q)) {
?>
    <tr>
     <td><a href="editseries.php?id=<?php echo $r['sr_id'];?>"><?php echo cleanOutput($r['sr_name']);?></a></td>
     <td><?php echo $r['count'];?></td>
    </tr>
<?php
 }
} else {
?>
    <tr>
     <td colspan="2">No Series Groups Defined</td>
    </tr>
<?php
}
?>
   </table>
  </div>
<?php
hm_footer();
?>