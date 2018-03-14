<?php
require('inc/conf.php');
accessLevel(6);
require('inc/sess.php');

$order_fields = array('coursename','`desc`');

$orderby = intval($_GET['orderby']);
$sortby = ($_GET['sortby'] ? 1 : 0);

if ($orderby < 0 || $orderby > (count($order_fields) - 1)) $orderby = 0;
$orderbyfield = $order_fields[$orderby];

hm_header();

template('compliance_box',array('manager' => 1));
?>
  <div class="banner">Which Compliance Report &bull;</div>
  <div id="compliance_reports">
   <table width="670"  border="0">
    <tr>
     <th width="167"><?php echo sortHeader('Course Name',0,$sortby);?></td>
     <th><?php echo sortHeader('Course Description',1,$sortby);?></th>
     <th width="100">Assigned</th>
     <th colspan="2">Compliant</th>
    </tr>
<?php
$previous_section = '';
$q = hm_query("SELECT c_id,coursename,`desc`,cg_name FROM Courses LEFT JOIN course_groupings ON (cg_course_id = c_id) LEFT JOIN course_groups ON (cg_id = cg_group_id) ORDER BY ISNULL(cg_name),cg_name ".($sortby ? 'DESC' : 'ASC').",$orderbyfield ".($sortby ? 'DESC' : 'ASC').";");

while ($r = hm_fetch($q)) {
 $course_id = intval($r['c_id']);
 $total_assigned = hm_result("SELECT count(*) FROM Assignments".SITE_SUFFIX." a LEFT JOIN users".SITE_SUFFIX." u ON (a.userid = u.userid) WHERE a.course_id='$course_id' and LEFT(a.dateassigned,4)='".date('Y')."' and u.userlevel='10' and u.status != '4';",1);
 $total_compliant = hm_result("SELECT count(*) FROM results".SITE_SUFFIX." r LEFT JOIN users".SITE_SUFFIX." u ON (r.userid = u.userid) WHERE r.courseid='$course_id' and r.passorfail != 'F' and r.passorfail != 'X' and u.userlevel='10' and u.status != '4' and LEFT(r.datestarted,4)='".date('Y')."';");
 $percentage = ($total_assigned > 0 ? round($total_compliant / $total_assigned * 100) : 0);

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
     <td><a href="compliance.php?id=<?php echo $course_id;?>"><?php echo cleanOutput($r['coursename']);?></a></td>
     <td><?php echo cleanOutput($r['desc']); ?></td>
     <td><?php echo $total_assigned; ?></td>
     <td width="30"><?php echo $total_compliant; ?></td>
     <td width="30"><?php echo ($percentage).'%'; ?></td>
    </tr>
<?php } ?>
   </table>
  </div>
<?php
hm_footer();