<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

if ($_POST['group_id']) $group_id = intval($_POST['group_id']);
else $group_id = intval($_GET['id']);

$q = hm_query("SELECT cg_id,cg_name FROM course_groups WHERE cg_id='$group_id' LIMIT 1;");
$r = hm_fetch($q);

$group_id = $r['cg_id'];
$group_name = $r['cg_name'];

if ($group_id < 1) {
 err('Invalid ID: Unable to edit.','coursesections');
 hm_redirect('coursesections.php');
}

$selected = get_course_groupings($group_id,0);

if ($_POST) {

 $updated = false;
 $new_group_name = hm_cleanInput($_POST['newgroup'],'t',64);
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
   hm_query("DELETE FROM course_groupings WHERE cg_group_id='$group_id';");
   $deleted_count = hm_affected();
  }

 } else {

  $existing_ids = array();
  $to_add_ids = array();

  foreach ($course_ids as $c) {
   if (in_array($c,$selected)) $existing_ids[] = $c;
   else $to_add_ids[] = $c;
  }

  if (count($existing_ids) > 0) {
   $safelist = implode(',',$existing_ids);
   hm_query("DELETE FROM course_groupings WHERE cg_group_id='$group_id' and cg_course_id NOT IN ($safelist);");
   $deleted_count = hm_affected();
  }

  if (count($to_add_ids) > 0) {
   foreach ($to_add_ids as $course_id) {
    if (in_array($course_id,$selected) === false) {
     hm_query("INSERT INTO course_groupings SET cg_group_id='$group_id',cg_course_id='$course_id';");
     $add_count++;
    }
   }
  }
 }

 if ($new_group_name != $group_name) {
  $new_group_name2 = mysqlText($new_group_name);
  hm_query("UPDATE course_groups SET cg_name='$new_group_name2' WHERE cg_id='$group_id' LIMIT 1;");
  $updated = true;
  $group_name = $new_group_name;
 }

 if (($add_count + $deleted_count) < 1 && $updated) err('Successfully updated course section name.','editcoursesection',1);
 elseif ($add_count > 0 || $deleted_count > 0) {
  err('Successfully updated course selections for course section.','editcoursesection',1);
 }
 else err('No changes made.','editcoursesection',2);

 $selected = get_course_groupings($group_id,0);
}

hm_header();

template('course_box');
?>
  <form method="post">
  <?php errOut('editcoursesection');?>
  <div class="banner">Edit Course Section &bull;</div>
  <div id="view_course_table">
  <input type="hidden" name="group_id" value="<?php echo $group_id;?>">
   <div>
    <label>Course Group Name:</label>
    <input name="newgroup" type="text" id="newgroup" value="<?php echo $group_name;?>">
   </div>
   <div class="clear"></div>
   <table>
    <tr>
     <td colspan="4" style="border: 0;"><div class="controls"><button type="button" onClick="selectAll(this);">Select all</button> <button type="button" onClick="deselectAll(this);">Deselect all</button> <button type="submit">Assign</button></div></td>
    </tr>
    <tr>
     <th width="225"><?php echo sortHeader('Course',0,$sortby);?></th>
     <th><?php echo sortHeader('Description',1,$sortby);?></th>
    </tr>
    <tr class="section_header">
     <td colspan="5"><?php echo cleanOutput($group_name);?></td>
    </tr>
<?php

$q = hm_query("SELECT c_id,coursename,`desc` FROM Courses LEFT JOIN course_groupings ON (cg_course_id = c_id) WHERE cg_group_id = '$group_id' and cg_course_id = c_id ORDER BY coursename ASC;");

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
    <tr class="section_header">
     <td colspan="5"><?php echo 'Unassigned';?></td>
    </tr>
<?php

$q = hm_query("SELECT c_id,coursename,`desc` FROM Courses WHERE (SELECT count(*) FROM course_groupings WHERE cg_course_id = c_id) = 0 ORDER BY coursename ASC;");

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