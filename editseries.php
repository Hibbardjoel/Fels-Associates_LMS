<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

if ($_POST['group_id']) $group_id = intval($_POST['group_id']);
else $group_id = intval($_GET['id']);

$q = hm_query("SELECT sr_id,sr_name FROM series_groups WHERE sr_id='$group_id' and sr_parent_id='0' LIMIT 1;");
$r = hm_fetch($q);

$group_id = $r['sr_id'];
$group_name = $r['sr_name'];
$new_group = $new_level = '';
unset($section);
unset($level);

if ($group_id < 1) {
 err('Invalid ID: Unable to edit.','seriessections');
 hm_redirect('series.php');
}

$certificate_types = get_certificate_types(1);

if ($_POST['groupname']) {

 $update_sorts = false;

 if ($group_id > 0) {
  $group_name_new = hm_cleanInput($_POST['groupname'],'t',128);
  if ($group_name != $group_name_new) {
   $group_name_new = mysqlText($group_name_new);
   hm_query("UPDATE series_groups SET sr_name='$group_name_new' WHERE sr_id='$group_id' and sr_parent_id='0' LIMIT 1;");
  }
 }

} elseif (isset($_POST['delgroup'])) {

 if ($group_id > 0) {
  $del_group = intval($_POST['delgroup']);
  $check = hm_result("SELECT sr_id FROM series_groups WHERE sr_id='$del_group' LIMIT 1;",1);

  if ($del_group < 1 || $check < 1) err('You must select a Individual Series to delete.','editseries');

  if (errCnt('editseries') === 0) {
   hm_query("DELETE FROM series_groupings WHERE sr_group_id='$del_group';");
   hm_query("DELETE FROM series_groups WHERE sr_id='$del_group' LIMIT 1;");
   if (hm_affected() == 1) {
    err('Successfully deleted Individual Series.','editseries',1);
    $update_sorts = true;
   }
  }
 }

} elseif ($_POST['newgroup']) {

 if ($group_id > 0) {
  $new_group = hm_cleanInput($_POST['newgroup'],'t',128);
  $new_level = intval($_POST['newlevel']);
  $group_name_new = mysqlText($new_group);
  $certificate_type = intval($_POST['certificate_type']);
  $certificate_mode = intval($_POST['certificate_mode']);
  if ($$certificate_mode == 1) $certificate_type = 0;

  if (strlen($new_group) == 0) err('You must enter a name for the new Series.','editseries');
  if ($certificate_mode != 1 && ($certificate_type < 0 || key_exists($certificate_type,$certificate_types) === false)) err('You must select a Certificate Type.','editseries');

  $check = hm_result("SELECT sr_id FROM series_groups WHERE sr_name='$group_name_new' LIMIT 1;",1);
  if ($check < 1) {
   if (errCnt('editseries') === 0) {
    $group_name_new = mysqlText($group_name_new);
    hm_query("INSERT INTO series_groups (sr_id,sr_parent_id,sr_sort,sr_name,sr_certificate) VALUES (NULL,'$group_id','$new_level','$group_name_new','$certificate_type');");
    if (hm_affected() == 1) {
     $new_group = $new_level = '';
     err('Successfully added new Individual Series.','editseries',1);
     $update_sorts = true;
    }
   }
  } else {
   err('You must enter a name for the new Series that is not already being used.','editseries');
  }
 }

} elseif ($_POST['run'] == 1) {

 if (is_array($_POST['series_name']) && count($_POST['series_name'])) {
  $success = 0;

  foreach ($_POST['series_name'] as $snid => $snval) {
   $snid = intval($snid);
   if ($snid > 0) {
    $section[$snid] = hm_cleanInput($snval,'t',128);
    $level[$snid] = max(0,intval($_POST['series_level'][$snid]));
    $certificate_type = intval($_POST['certificate_type'][$snid]);
    $certificate_mode = intval($_POST['certificate_mode'][$snid]);
    if ($$certificate_mode == 1) $certificate_type = 0;

    if (strlen($section[$snid]) == 0) err('You must enter a name for all Independent Series.','editseries');
    if (strlen($level[$snid]) == 0) err('You must enter a level for all Independent Series.','editseries');
    if ($certificate_mode != 1 && ($certificate_type < 0 || key_exists($certificate_type,$certificate_types) === false)) err('You must select a Certificate Type.','editseries');


    if (errCnt('editseries') === 0) {
     $section2 = mysqlText($section[$snid]);
     hm_query("UPDATE series_groups SET sr_name='$section2',sr_sort='{$level[$snid]}',sr_certificate='$certificate_type' WHERE sr_id='$snid' LIMIT 1;");
     if (hm_affected() == 1) $success++;
    } else {
     break;
    }
   }
  }

  if ($success) err('Updated '.$success.' total Individual Series.','editseries',1);
  $update_sorts = true;
 }

}

if ($update_sorts) {
 $sorted_list = array();
 $q = hm_query("SELECT sr_id FROM series_groups WHERE sr_parent_id='$group_id' ORDER BY sr_sort ASC;");
 while ($g = hm_fetch($q)) {
  $sorted_list[] = $g['sr_id'];
 }

 if (count($sorted_list) > 0) {
  $sort = 0;
  foreach ($sorted_list as $id) {
   $sort++;
   hm_query("UPDATE series_groups SET sr_sort='$sort' WHERE sr_parent_id='$group_id' and sr_id='$id' LIMIT 1;");
  }
 }

 hm_redirect('editseries.php?id='.$group_id);
}

$subseries = get_subseries($group_id);

$order_fields = array('coursename','`desc`');

$orderby = intval($_GET['orderby']);
$sortby = ($_GET['sortby'] ? 1 : 0);

if ($orderby < 0 || $orderby > (count($order_fields) - 1)) $orderby = 0;
$orderbyfield = $order_fields[$orderby];

hm_header('Editing Prerequisite Series Instruction');

template('course_box');
?>
  <div class="banner">Editing Prerequisite Series Instruction &bull;</div>
  <div id="view_course_table" class="assign">
  <?php errOut('editseries');?>
  <form action="editseries.php" method="post">
  <input type="hidden" name="group_id" value="<?php echo $group_id;?>">
   <div class="fieldboxrow">
    <label>Series Group:</label>
    <input name="groupname" type="text" id="groupname" value="<?php echo cleanOutput($group_name);?>">
    &nbsp; &nbsp; <input type="submit" value="Update Series Group">
   </div>
   <div class="clear"></div>
  </form>
  <hr>
  <form action="editseries.php" method="post">
  <input type="hidden" name="group_id" value="<?php echo $group_id;?>">
   <div class="fieldboxrow">
    <label>Add Individual Series:</label>
    <input name="newgroup" type="text" id="newgroup" value="<?php echo $new_group;?>">
    &nbsp; <label>Level:</label> <input type="number" style="width:40px" name="newlevel" id="newlevel" value="<?php echo $new_level;?>">
   </div>
   <div class="fieldboxrow">
    <label>Certificate:</label> <select name="certificate_type" id="certificate_type"><?php echo optOut($certificate_types,$certificate_type);?></select> &nbsp; &nbsp; <input type="checkbox" id="certs_idv" name="certificate_mode" value="1" onchange="document.getElementById('certificate_type').value=0;"> <label for="certs_idv_<?php echo $series_id;?>">Use default certificates shown below</label>
    <br><input type="submit" value="Add Individual Series">
   </div>
   <div class="clear"></div>
  </form>
  <hr>
  <form action="editseries.php" method="post">
  <input type="hidden" name="group_id" value="<?php echo $group_id;?>">
   <div class="fieldboxrow">
    <label>Delete Individual Series:</label>
    <select name="delgroup" id="delgroup"><?php echo optOut($subseries,'',' ');?></select> &nbsp; &nbsp; <input type="submit" value="Delete Individual Series">
   </div>
   <div class="clear"></div>
  </form>
  <hr>
  <form action="editseries.php" method="post">
  <input type="hidden" name="group_id" value="<?php echo $group_id;?>">
  <input type="hidden" name="run" value="1">
   <div class="controls" style="text-align:center;padding:20px 0 0;margin:0;">
    <input type="submit" value="Update Individual Series" class="button">
   </div>
   <table>
<?php
$previous_section = '';
$total_users = get_user_count();

$q = hm_query("SELECT sr_id,sr_sort,sr_name,sr_parent_id,sr_certificate FROM series_groups WHERE sr_parent_id='$group_id' ORDER BY sr_sort;");

while ($r = hm_fetch($q)) {
 $series_id = intval($r['sr_id']);
?>
    <tr class="section_header nobold smaller">
     <td colspan="5">Individual Series: <input type="text" name="series_name[<?php echo $series_id;?>]" value="<?php echo cleanOutput($section[$series_id] ? $section[$series_id] : ($r['sr_name'] ? $r['sr_name'] : 'Unnamed'));?>"> &nbsp;Level: <input type="number" style="width:40px" name="series_level[<?php echo $series_id;?>]" value="<?php echo ($level[$series_id] ? $level[$series_id] : $r['sr_sort']);?>"><br>Indv. Series Certificate: <select name="certificate_type[<?php echo $series_id;?>]" id="certificate_type_<?php echo $series_id;?>"><?php echo optOut($certificate_types,$r['sr_certificate']);?></select> &nbsp; &ndash; or &ndash; &nbsp; <input type="checkbox" id="certs_idv_<?php echo $series_id;?>" name="certificate_mode[<?php echo $series_id;?>]" value="1" onchange="document.getElementById('certificate_type_<?php echo $series_id;?>').value=0;" <?php echo ($r['sr_certificate'] == 0 ? ' checked':'');?>> <label for="certs_idv_<?php echo $series_id;?>">Use default certificates shown below</label></td>
    </tr>
<?php
 $q2 = hm_query("SELECT sr_course_id,sr_grouping_sort,c_id,coursename,`desc`,certificate_type FROM series_groupings LEFT JOIN Courses ON (sr_course_id=c_id) WHERE sr_group_id='$series_id' ORDER BY sr_grouping_sort ASC;");

 while ($r2 = hm_fetch($q2)) {
?>
    <tr>
     <td><?php echo cleanOutput($r2['coursename']);?></td>
     <td><?php echo cleanOutput($r2['desc']);?></td>
     <td class="cert"><?php echo $certificate_types[$r2['certificate_type']];?></td>
    </tr>
<?php
 }
?>
    <tr>
     <td colspan="2" class="addbutton"><a href="editseriescourses.php?id=<?php echo $series_id;?>" type="submit" class="button small">Add/Edit Courses</a></td>
    </tr>
<?php
}
?>
   </table>
   <div class="controls" style="text-align:center;padding:20px 0;">
    <input type="submit" value="Update Individual Series" class="button">
   </div>
  </form>
  </div>
<?php
hm_footer();
?>