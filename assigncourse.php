<?php
require('inc/conf.php');
accessLevel(6);
require('inc/sess.php');

$order_fields = array('coursename','`desc`');

$orderby = intval($_GET['orderby']);
$sortby = ($_GET['sortby'] ? 1 : 0);

if ($orderby < 0 || $orderby > (count($order_fields) - 1)) $orderby = 0;
$orderbyfield = $order_fields[$orderby];

hm_header('Assign Course');

template('course_box');
?>
  <div class="banner">Assign Which Course &bull;</div>
  <div id="view_course_table" class="assign">
  <form action="assignusertocourse.php" method="post">
  <?php errOut('assigncourse');?>
   <table>
    <tr>
     <td colspan="2" style="border: 0;"><div class="controls"><button type="button" onClick="selectAll(this);">Select all</button> <button type="button" onClick="deselectAll(this);">Deselect all</button> <button type="submit">Assign</button></div></td>
    </tr>
   </table>
   <table id="seriesbox">
    <tr>
     <th>Series Group</th>
     <th width="65">Courses</th>
    </tr>
<?php
$q = hm_query("SELECT sr_id,sr_name,(SELECT count(*) FROM `series_groupings` cs LEFT JOIN `series_groups` cg ON (cg.sr_id=cs.sr_group_id) WHERE cg.sr_parent_id=g.sr_id) as course_count FROM series_groups g LEFT JOIN series_groupings c ON (c.sr_group_id = g.sr_id) WHERE g.sr_parent_id='0' and (SELECT count(*) FROM `series_groupings` cs LEFT JOIN `series_groups` cg ON (cg.sr_id=cs.sr_group_id) WHERE cg.sr_parent_id=g.sr_id) > 0 ORDER BY g.sr_name ASC");

if (hm_cnt($q)) {
?>
    <tr class="section_header">
     <td colspan="2">Prerequisite Series Instruction</td>
    </tr>
<?php
 while ($r = hm_fetch($q)) {
?>
    <tr>
     <td><input type="radio" data-parent="series" name="series_id" id="series_<?php echo cleanOutput($r['sr_id']);?>" value="<?php echo cleanOutput($r['sr_id']);?>" onclick="deselectAll(this);this.checked=true;"><label for="series_<?php echo cleanOutput($r['sr_id']);?>"><?php echo cleanOutput($r['sr_name']);?></label></td>
     <td><?php echo $r['course_count'];?></td>
    </tr>
<?php
 }
}
?>
   </table>
<br><br>
   <table id="coursesbox">
    <tr>
     <th width="225"><?php echo sortHeader('Course',0,$sortby);?></th>
     <th><?php echo sortHeader('Description',1,$sortby);?></th>
     <th>Assignees</th>
    </tr>
<?php

$previous_section = '';
$total_users = get_user_count();

$q = hm_query("SELECT c_id,coursename,`desc`,cg_name,cg_id FROM Courses LEFT JOIN course_groupings ON (cg_course_id = c_id) LEFT JOIN course_groups ON (cg_id = cg_group_id) ORDER BY ISNULL(cg_name),cg_name ".($sortby ? 'DESC' : 'ASC').",$orderbyfield ".($sortby ? 'DESC' : 'ASC').";");

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
     <td><input type="checkbox" data-parent="<?php echo intval($r['cg_id']);?>" name="course_id[]" id="ci_<?php echo cleanOutput($r['c_id']);?>" value="<?php echo cleanOutput($r['c_id']);?>" onclick="deselectAllGroup('series');"><label for="ci_<?php echo cleanOutput($r['c_id']);?>"><?php echo cleanOutput($r['coursename']);?></label></td>
     <td><?php echo cleanOutput($r['desc']);?></td>
     <td><?php echo $count.' of '.$total_users;?></td>
    </tr>
<?php
}
?>
   </table>
  </form>
  </div>

<script type="text/javascript">
function selectAllGroup(x) {
 deselectAllGroup('series');
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
  if (items[i].type == 'checkbox' || items[i].type == 'radio') {
   items[i].checked = false
  }
  }
 }
 return false;
}
function selectAll(x) {
 deselectAllGroup('series');
 for(var i=0,l=x.form.length; i<l; i++)
 if (x.form[i].type == 'checkbox')
 x.form[i].checked = true
}
function deselectAll(x) {
 for(var i=0,l=x.form.length; i<l; i++)
 if (x.form[i].type == 'checkbox' || x.form[i].type == 'radio')
 x.form[i].checked = false
}
</script>
<?php
hm_footer();
?>