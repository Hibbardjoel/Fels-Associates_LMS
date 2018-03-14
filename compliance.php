<?php
require('inc/conf.php');
accessLevel(6);
require('inc/sess.php');

$sortby = 'ASC';
$newsort = 'DESC';
$date = date('Y-m-d');
$course_id = 0;

if ($_GET['id']) $course_id = intval($_GET['id']);

if (isset($_POST['filter_year'])) {
 $_SESSION['adminuser_2']['filter_year'] = intval($_POST['filter_year']);
 hm_redirect($_SERVER['REQUEST_URI']);
}

$order_fields = array('u.username','r.datestarted','r.datecomplied','r.score','c.coursename');

$orderby = intval($_GET['orderby']);
$sortby = ($_GET['sortby'] ? 1 : 0);

if ($orderby < 0 || $orderby > (count($order_fields) - 1)) $orderby = 0;
$orderbyfield = $order_fields[$orderby];

hm_header();

template('compliance_box',array('manager' => 1));
?>
  <div class="banner">Which Compliance Report &bull;</div>
  <div id="compliance_reports">
   <table>
    <tr class="unnamed2">
     <th width="119"><?php echo sortHeader('Employee',0,$sortby,"id=$course_id");?></th>
     <th width="158"><?php echo sortHeader('1 CEU per Course',4,$sortby,"id=$course_id");?></th>
     <th width="110"><?php echo sortHeader('Instruction',1,$sortby,"id=$course_id");?></th>
     <th width="139"><?php echo sortHeader('Compliance To',2,$sortby,"id=$course_id");?></th>
     <th width="57"><?php echo sortHeader('Score',3,$sortby,"id=$course_id");?></th>
    </tr>
<?php
$filter_year = (isset($_SESSION['adminuser_2']['filter_year']) ? intval($_SESSION['adminuser_2']['filter_year']) : 0);
$filter_year_addin = ($filter_year ? '' : " and LEFT(r.datestarted,4) >= '".date('Y')."'");
$years = array('Current Results','Previous Results');

$q = hm_query("SELECT u.name, u.username, c.coursename, r.passorfail, r.courseid, r.datecomplied, r.datestarted, r.score,ROUND(r.duration/60,0) AS rounded
FROM results".SITE_SUFFIX." r
LEFT JOIN users".SITE_SUFFIX." u ON (u.userid = r.userid)
LEFT JOIN Courses c ON (c.c_id = r.courseid)
WHERE r.courseid = '$course_id'$filter_year_addin ORDER BY $orderbyfield ".($sortby ? 'DESC' : 'ASC').";");

while ($r = hm_fetch($q)) {
 $current = (strtotime($r['datecomplied']) < time() ? false : true);
?>
    <tr class="<?php echo ($r['passorfail'] == 'X' ? 'failed':'passed');?>">
     <td><?php echo cleanOutput($r['username']); ?></td>
     <td><?php echo cleanOutput($r['coursename']); ?></td>
     <td><?php echo dateOut($r['datestarted']); ?></td>
     <td><?php echo dateOut($r['datecomplied']); ?></td>
     <td><?php echo cleanOutput($r['score']); ?></td>
    </tr>
<?php } ?>
    <tr>
     <td class="c" colspan="8"><form method="post"><select name="filter_year" onchange="this.form.submit();"><?php echo optOut($years,$filter_year);?></select> <input type="submit" value="Update"></form></td>
    </tr>
   </table>
  </div>
<?php
hm_footer();