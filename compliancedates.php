<?php
require('inc/conf.php');
accessLevel(6);
require('inc/sess.php');

$order_fields = array('coursename','`desc`','compliance_period');

$orderby = intval($_GET['orderby']);
$sortby = ($_GET['sortby'] ? 1 : 0);

if ($orderby < 0 || $orderby > (count($order_fields) - 1)) $orderby = 0;
$orderbyfield = $order_fields[$orderby];

if ($_POST) {
 $cnt = 0;
 $compliance_period = strval(floatval($_POST['compliance_period']));
 if (is_array($_POST['course_id']) && $compliance_period > 0) {
  echo 'sdfsfgbds'.$compliance_period;
  foreach ($_POST['course_id'] as $cid) {
   $cid = intval($cid);
   echo ("REPLACE INTO compliance".SITE_SUFFIX." SET course_id='$cid',compliance_period='$compliance_period';");
  	if ($cid > 0) hm_query("REPLACE INTO compliance".SITE_SUFFIX." SET course_id='$cid',compliance_period='$compliance_period';");
  	$cnt++;
  }

  if ($cnt > 0) {
   err('Updated the default compliance period for '.$cnt.' courses.','complaincedate',1);
  }
 }
}

hm_header('Assign Course');

template('course_box');
?>
  <div class="banner">Set Default Compliance Period &bull;</div>
  <div id="view_course_table" class="assign">
  <form action="" method="post">
   <div class="controls">Choose Compliance Period <select name="compliance_period"><?php echo optOut($compliance_periods,$compliance_period);?></select> &nbsp; <input type="submit" value="Update"></div>
  <?php errOut('complaincedate');?>
   <table>
    <tr>
     <td colspan="4" style="border: 0;"><div class="controls"><button type="button" onClick="selectAll(this);">Select all</button> <button type="button" onClick="deselectAll(this);">Deselect all</button></div></td>
    </tr>
    <tr>
     <th width="225"><?php echo sortHeader('Course',0,$sortby);?></th>
     <th><?php echo sortHeader('Description',1,$sortby);?></th>
     <th width="75"><?php echo sortHeader('Period',2,$sortby);?></th>
    </tr>
<?php
$previous_section = '';

$q = hm_query("SELECT c_id,coursename,`desc`,compliance_period,cg_name FROM Courses LEFT JOIN compliance".SITE_SUFFIX." ON (course_id=c_id) LEFT JOIN course_groupings ON (cg_course_id = c_id) LEFT JOIN course_groups ON (cg_id = cg_group_id) ORDER BY ISNULL(cg_name),cg_name ".($sortby ? 'DESC' : 'ASC').",$orderbyfield ".($sortby ? 'DESC' : 'ASC').";");

while ($r = hm_fetch($q)) {
 $course_id = intval($r['c_id']);
 $period = ($r['compliance_period'] ? $r['compliance_period'] : 12);

 if ($previous_section != $r['cg_name']) {
?>
    <tr class="section_header">
     <td colspan="5"><?php echo cleanOutput($r['cg_name'] ? $r['cg_name'] : SECTION_GENERAL);?></td>
    </tr>
<?php
 }

 $previous_section = $r['cg_name'];
?>
    <tr>
     <td><input type="checkbox" name="course_id[]" id="ci_<?php echo $course_id;?>" value="<?php echo $course_id;?>"><label for="ci_<?php echo $course_id;?>"><?php echo cleanOutput($r['coursename']);?></label></td>
     <td><?php echo cleanOutput($r['desc']);?></td>
     <td><?php echo $compliance_periods[$period];?></td>
    </tr>
<?php
}
?>
   </table>
  </form>
  </div>

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