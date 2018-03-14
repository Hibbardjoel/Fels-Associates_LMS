<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

$order_fields = array('coursename','cert_name','`desc`','ceus','grade','exefield');

$orderby = intval($_GET['orderby']);
$sortby = ($_GET['sortby'] ? 1 : 0);

if ($orderby < 0 || $orderby > (count($order_fields) - 1)) $orderby = 0;
$orderbyfield = $order_fields[$orderby];

$certificate_types = get_certificate_types();

hm_header();

template('course_box');
?>
  <?php errOut('editcourses');?>
  <div class="banner">Editing Courses &bull;</div>
  <div id="view_course_table">
   <table width="100%">
    <tr>
     <th width="120"><?php echo sortHeader('Course Name',0,$sortby);?></th>
     <th><?php echo sortHeader('Description',2,$sortby);?></th>
     <th width="45"><?php echo sortHeader('CEUs',3,$sortby);?></th>
     <th width="55"><?php echo sortHeader('Grade',4,$sortby);?></th>
     <th width="100"><?php echo sortHeader('Path',5,$sortby);?></th>
     <th width="104"><?php echo sortHeader('Certificate Type',1,$sortby);?></th>
    </tr>
<?php
$q = hm_query("SELECT c_id,coursename,`desc`,ceus,grade,exefield,certificate_type FROM Courses LEFT JOIN certificates ON (cert_id = certificate_type) WHERE 1 ORDER BY $orderbyfield ".($sortby ? 'DESC' : 'ASC').";");

$cnt = hm_cnt($q);

if ($cnt > 0) {
 while ($r = hm_fetch($q)) {
?>
    <tr>
     <td><a href="editcourse.php?id=<?php echo $r['c_id'];?>"><?php echo cleanOutput($r['coursename']);?></a></td>
     <td><?php echo cleanOutput($r['desc']);?></td>
     <td><?php echo cleanOutput($r['ceus']);?></td>
     <td><?php echo cleanOutput($r['grade']);?></td>
     <td><?php echo cleanOutput($r['exefield']);?></td>
     <td><?php echo $certificate_types[$r['certificate_type']];?></td>
    </tr>
<?php
 }
} else {
?>
    <tr>
     <td colspan="2">No Courses Defined</td>
    </tr>
<?php
}
?>
   </table>
  </div>
<?php
hm_footer();
?>