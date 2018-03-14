<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

if ($_POST['group_id']) $group_id = intval($_POST['group_id']);
else $group_id = intval($_GET['id']);

$q = hm_query("SELECT sr_id,sr_name,sr_parent_id FROM series_groups WHERE sr_id='$group_id' and sr_parent_id > 0 LIMIT 1;");
$r = hm_fetch($q);

$group_id = $r['sr_id'];
$group_name = $r['sr_name'];
$group_parent_id = $r['sr_parent_id'];

if ($group_id < 1) {
 err('Invalid ID: Unable to edit.','seriessections');
 hm_redirect('series.php');
}

$order_fields = array('coursename','`desc`');

$orderby = intval($_GET['orderby']);
$sortby = ($_GET['sortby'] ? 1 : 0);

if ($orderby < 0 || $orderby > (count($order_fields) - 1)) $orderby = 0;
$orderbyfield = $order_fields[$orderby];

$selected = get_series_groupings($group_id,0);

if ($_POST) {

 $updated = false;
 $new_group_name = hm_cleanInput($_POST['groupname'],'t',128);
 $course_ids_raw = (array)$_POST['course_id'];
 $course_ids = array();
 foreach ($course_ids_raw as $course_id) {
  $course_id = intval($course_id);
  if ($course_id > 0) $course_ids[] = $course_id;
 }

 $course_ids = array_unique($course_ids);
 $total_courses = count($course_ids);
 $deleted_count = 0;
 $add_count = 0;

 if (count($course_ids) === 0) {
  if (count($selected) > 0) {
   hm_query("DELETE FROM series_groupings WHERE sr_group_id='$group_id';");
   $deleted_count = hm_affected();
  }

 } else {

  $existing_ids = array();
  $to_add_ids = array();

  $level = hm_result("SELECT MAX(sr_grouping_sort) FROM series_groupings WHERE sr_group_id='$group_id';",1);

  foreach ($course_ids as $c) {
   if (in_array($c,$selected)) $existing_ids[] = $c;
   else $to_add_ids[] = $c;
  }

  if (count($existing_ids) > 0) {
   $safelist = implode(',',$existing_ids);
   hm_query("DELETE FROM series_groupings WHERE sr_group_id='$group_id' and sr_course_id NOT IN ($safelist);");
   $deleted_count = hm_affected();
  }

  if (count($to_add_ids) > 0) {

   foreach ($to_add_ids as $course_id) {
    if (in_array($course_id,$selected) === false) {
     $level++;
     hm_query("INSERT INTO series_groupings SET sr_group_id='$group_id',sr_course_id='$course_id',sr_grouping_sort='$level';");
     $add_count++;
    }
   }
  }

  if (is_array($_POST['sort'])) {
   foreach ($_POST['sort'] as $id => $sort) {
    $id = intval($id);
    $sort = intval($sort);
   	if ($sort > 0) {
   	 hm_query("UPDATE series_groupings SET sr_grouping_sort='$sort' WHERE sr_group_id='$group_id' and sr_course_id='$id' LIMIT 1;");
   	 $sort_total += hm_affected();
   	}
   }
   err("updated $sort_total sorts",'editseriescourses',2);
  }

  $sorted_list = array();
  $q = hm_query("SELECT sr_course_id FROM series_groupings WHERE sr_group_id='$group_id' ORDER BY sr_grouping_sort ASC;");
  while ($g = hm_fetch($q)) {
  	$sorted_list[] = $g['sr_course_id'];
  }

  if (count($sorted_list) > 0) {
   $sort = 0;
   foreach ($sorted_list as $id) {
    $sort++;
    hm_query("UPDATE series_groupings SET sr_grouping_sort='$sort' WHERE sr_group_id='$group_id' and sr_course_id='$id' LIMIT 1;");
   }
  }

 }

 if ($new_group_name != $group_name) {
  $new_group_name2 = mysqlText($new_group_name);
  hm_query("UPDATE series_groups SET sr_name='$new_group_name2' WHERE sr_id='$group_id' LIMIT 1;");
  $updated = true;
  $group_name = $new_group_name;
 }

 if (($add_count + $deleted_count) < 1 && $updated) err('Successfully updated Series Group name.','editseriescourses',1);
 elseif ($add_count > 0 || $deleted_count > 0) {
  err('Successfully updated course selections for Series Group.','editseriescourses',1);
 }
 else err('No changes made.','editseriescourses',2);

 $selected = get_series_groupings($group_id,0);
}

hm_header();

template('course_box');
?>
  <form method="post">
  <?php errOut('editseriescourses');?>
  <div class="banner">Edit Course Section &bull;</div>
  <div id="view_course_table">
  <input type="hidden" name="group_id" value="<?php echo $group_id;?>">
   <div>
    <label>Course Group Name:</label>
    <input name="groupname" type="text" id="groupname" value="<?php echo $group_name;?>" style="width:350px;">
   </div>
   <div class="clear"></div>
   <table>
    <tr>
     <td colspan="4" style="border: 0;"><div class="controls"><button type="button" onClick="selectAll(this);">Select all</button> <button type="button" onClick="deselectAll(this);">Deselect all</button> <button type="submit">Update</button> <a class="button" href="editseries.php?id=<?php echo $group_parent_id;?>">Cancel/Back</a></div></td>
    </tr>
    <tr>
     <th width="225"><?php echo sortHeader('Course',0,$sortby);?></th>
     <th><?php echo sortHeader('Description',1,$sortby);?></th>
    </tr>
    <tr class="section_header">
     <td colspan="5"><?php echo cleanOutput($group_name);?></td>
    </tr>
<?php

$q = hm_query("SELECT c_id,coursename,`desc`,sr_grouping_sort FROM Courses LEFT JOIN series_groupings ON (sr_course_id = c_id) WHERE sr_group_id='$group_id' and sr_course_id=c_id ORDER BY sr_grouping_sort ASC;");

$selected = array();
while ($r = hm_fetch($q)) {
 $course_id = intval($r['c_id']);
 $selected[] = $course_id;
?>
    <tr>
     <td><input type="checkbox" name="course_id[]" id="ci_<?php echo cleanOutput($r['c_id']);?>" value="<?php echo cleanOutput($r['c_id']);?>"<?php hm_checker($r['c_id'],$selected);?>><label for="ci_<?php echo cleanOutput($r['c_id']);?>"><?php echo cleanOutput($r['coursename']);?></label></td>
     <td><?php echo cleanOutput($r['desc']);?></td>
     <td><input type="number" style="width:40px" name="sort[<?php echo cleanOutput($r['c_id']);?>]" value="<?php echo cleanOutput($r['sr_grouping_sort']);?>"></td>
    </tr>
<?php
}

$selected_list = (count($selected) ? implode(',',$selected) : 0);
?>
    <tr class="section_header">
     <td colspan="5">Unassigned</td>
    </tr>
<?php
$previous_section = '';

$q = hm_query("SELECT c_id,coursename,`desc`,cg_name,cg_id FROM Courses LEFT JOIN course_groupings ON (cg_course_id = c_id) LEFT JOIN course_groups ON (cg_id = cg_group_id) WHERE c_id NOT IN ($selected_list) ORDER BY ISNULL(cg_name),cg_name ".($sortby ? 'DESC' : 'ASC').",$orderbyfield ".($sortby ? 'DESC' : 'ASC').";");

//$q = hm_query("SELECT c_id,coursename,`desc` FROM Courses WHERE (SELECT count(*) FROM series_groupings WHERE sr_course_id = c_id) = 0 ORDER BY coursename ASC;");

while ($r = hm_fetch($q)) {
 $course_id = intval($r['c_id']);
 $count = hm_result("SELECT count(*) FROM Assignments".SITE_SUFFIX." a LEFT JOIN users".SITE_SUFFIX." u ON (a.userid=u.userid) WHERE a.course_id='$course_id' and a.active='Y' and u.userlevel='10';",1);

 if ($previous_section != $r['cg_name']) {
?>
    <tr class="section_header">
     <td colspan="5"><?php echo cleanOutput($r['cg_name'] ? $r['cg_name'] : SECTION_GENERAL);?> <button class="small" onclick="selectAllGroup(<?php echo intval($r['cg_id']);?>);return false;">Select All</button> <button class="small" onclick="deselectAllGroup(<?php echo intval($r['cg_id']);?>);return false;">Deselect All</button></td>
    </tr>
<?php
 }

 $previous_section = $r['cg_name'];
?>
    <tr>
     <td><input type="checkbox" data-parent="<?php echo intval($r['cg_id']);?>" name="course_id[]" id="ci_<?php echo cleanOutput($r['c_id']);?>" value="<?php echo cleanOutput($r['c_id']);?>"<?php hm_checker($r['c_id'],$selected);?>><label for="ci_<?php echo cleanOutput($r['c_id']);?>"><?php echo cleanOutput($r['coursename']);?></label></td>
     <td><?php echo cleanOutput($r['desc']);?></td>
    </tr>
<?php
}

if (false)

while ($r = hm_fetch($q)) {
 $course_id = intval($r['c_id']);
?>
    <tr>
     <td><input type="checkbox" name="course_id[]" id="ci_<?php echo cleanOutput($r['c_id']);?>" value="<?php echo cleanOutput($r['c_id']);?>"<?php hm_checker($r['c_id'],$selected);?>><label for="ci_<?php echo cleanOutput($r['c_id']);?>"><?php echo cleanOutput($r['coursename']);?></label></td>
     <td><?php echo cleanOutput($r['desc']);?></td>
    </tr>
<?php
}
?>
   </table>
  </div>
  </form>

<script type="text/javascript">
function selectAllGroup(x) {
 var items = document.querySelectorAll('[data-parent]');
 for(var i=0,l=items.length; i<l; i++) {
  var c = items[i].getAttributeNode("data-parent").value;
  if (x == c) {
  if (items[i].type == 'checkbox') {
   items[i].checked = true
  }
  }
 }
 return false;
}
function deselectAllGroup(x) {
 var items = document.querySelectorAll('[data-parent]');
 for(var i=0,l=items.length; i<l; i++) {
  var c = items[i].getAttributeNode("data-parent").value;
  if (x == c) {
  if (items[i].type == 'checkbox') {
   items[i].checked = false
  }
  }
 }
 return false;
}
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
?>