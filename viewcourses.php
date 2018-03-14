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

template('course_box');
?>
  <div class="banner">Viewing Course Information &bull;</div>
  <div id="view_course_table">
   <table>
    <tr>
     <th width="175"><?php echo sortHeader('Course',0,$sortby);?></th>
     <th><?php echo sortHeader('Description',1,$sortby);?></th>
     <th>Assignees</th>
    </tr>
<?php
$previous_section = '';
$total_users = get_user_count();

$q = hm_query("SELECT c_id,coursename,`desc`,cg_name FROM Courses LEFT JOIN course_groupings ON (cg_course_id = c_id) LEFT JOIN course_groups ON (cg_id = cg_group_id) ORDER BY ISNULL(cg_name),cg_name ".($sortby ? 'DESC' : 'ASC').",$orderbyfield ".($sortby ? 'DESC' : 'ASC').";");

while ($r = hm_fetch($q)) {
 $course_id = intval($r['c_id']);
 $count = hm_result("SELECT count(*) FROM Assignments".SITE_SUFFIX." a LEFT JOIN users".SITE_SUFFIX." u ON (a.userid=u.userid) WHERE a.course_id='$course_id' and a.active='Y' and u.userlevel='10';",1);

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
     <td><?php echo cleanOutput($r['coursename']);?></td>
     <td><?php echo cleanOutput($r['desc']);?></td>
     <td><?php echo $count.' of '.$total_users;?></td>
    </tr>
<?php } ?>
   </table>
  </div>
<?php
hm_footer();
?>