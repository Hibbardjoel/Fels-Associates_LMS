<?php
require('inc/conf.php');
accessLevel(10);
require('inc/sess.php');

if (isset($_POST['filter_year'])) {
 $_SESSION['siteuser_2']['filter_year'] = intval($_POST['filter_year']);
 hm_redirect($_SERVER['REQUEST_URI']);
}

require('inc/certificates.php');

$order_fields = array('c.coursename','r.datestarted','r.score','r.passorfail','ROUNDED','datecomplied','ceus');

$orderby = intval($_GET['orderby']);
$sortby = ($_GET['sortby'] ? 1 : 0);

if ($orderby < 0 || $orderby > (count($order_fields) - 1)) $orderby = 1;
$orderbyfield = $order_fields[$orderby];

$order_fields2 = array('c.coursename','c.desc','a.datetakenby');

$orderby2 = intval($_GET['orderby2']);
$sortby2 = ($_GET['sortby2'] ? 1 : 0);

if ($orderby2 < 0 || $orderby2 > (count($order_fields2) - 1)) $orderby2 = 1;
$orderbyfield2 = $order_fields2[$orderby2];

hm_header();
?>
<script type="text/javascript">
var limit=30;
function beginRefresh() {
 setTimeout("refresh()",1000*limit);
}
function refresh() {
 window.location.reload(false);
}
function printWindow(){
 bV = parseInt(navigator.appVersion)
 if (bV >= 4) window.print()
}
window.onload=beginRefresh;
</script>

 <div id="user_table">
  <div id="welcomebox">Welcome, <?php echo cleanOutput($_SESSION['siteuser']['name']);?>, to your <?php echo MASTER_SITE_NAME_SHORT;?> Learning Library. The following learning modules will provide you with your approved CEU education and prepare you for delivering a higher level of quality of care and safety.</div>

  <div id="select_learning">
   <table width="100%">
    <tr>
     <th width="150"><?php echo sortHeader('Course',0,$sortby2,'',2);?></th>
     <th><?php echo sortHeader('Course Description',1,$sortby2,'',2);?></th>
     <th width="120"><?php echo sortHeader('Required By',2,$sortby2,'',2);?></th>
    </tr>
<?php
$previous_section = '';

$q = hm_query("SELECT a.id,a.datetakenby,a.active,a.series_sort,c.coursename,c.desc,c.exefield,cg_name FROM Assignments".SITE_SUFFIX." a INNER JOIN Courses c ON (a.course_id = c.c_id) LEFT JOIN course_groupings ON (cg_course_id = c_id) LEFT JOIN course_groups ON (cg_id = cg_group_id) WHERE a.series_sort = '0' and a.userid = '".mysqlText($_SESSION['siteuser']['userid'])."' and a.active = 'Y' ORDER BY ISNULL(cg_name),cg_name ".($sortby ? 'DESC' : 'ASC').",$orderbyfield2 ".($sortby2 ? 'DESC' : 'ASC').($orderby2 == 2 ? ', c.coursename ASC' : '').";");

while ($r = hm_fetch($q)) {
 list($ggg,$r['exefield']) = explode('/',$r['exefield'],2);

 if ($previous_section != $r['cg_name']) {
?>
    <tr class="section_header<?php echo ($previous_section ? '':' first');?>">
     <td colspan="5"><?php echo cleanOutput($r['cg_name'] ? $r['cg_name'] : SECTION_GENERAL);?></td>
    </tr>
<?php
 }

 $previous_section = $r['cg_name'];
?>
    <tr>
     <td><a href="<?php echo protectedURL().cleanOutput(rtrim($r['exefield'],'/')).'/?ai='.$r['id'];?>" target="_blank"><?php echo cleanOutput($r['coursename']);?></a></td>
     <td><?php echo cleanOutput($r['desc']); ?></td>
     <td><?php echo dateOut($r['datetakenby']); ?></td>
    </tr>
<?php
}



$show_only_one = false;

$q1 = hm_query("SELECT a.datetakenby,a.active,a.series_sort,series_id,g.sr_name FROM Assignments".SITE_SUFFIX." a LEFT JOIN series_groups g ON (g.sr_id=a.series_id) WHERE a.userid = '".mysqlText($_SESSION['siteuser']['userid'])."' and a.active = 'Y' and a.series_id > '0' GROUP BY g.sr_name ORDER BY g.sr_name ASC;");

while ($r1 = hm_fetch($q1)) {
	$series_id = intval($r1['series_id']);
	$previous_section = '';

 if ($r1['sr_name']) {
?>
    <tr class="section_header">
     <td colspan="5">PRSI: <?php echo cleanOutput($r1['sr_name']);?></td>
    </tr>
<?php
 }

 if ($series_id > 0) {

  $current_subseries = getSubseries($_SESSION['siteuser']['userid'],$series_id);
  $firstactive = false;

  $q = hm_query("SELECT a.id,a.course_id,a.subseries_id,a.dateassigned,a.datetakenby,a.series_sort,c.coursename,c.desc,c.exefield,s.sr_name,a.active FROM Assignments".SITE_SUFFIX." a INNER JOIN Courses c ON (a.course_id = c.c_id) LEFT JOIN series_groups s ON (a.subseries_id = s.sr_id) WHERE a.userid = '".mysqlText($_SESSION['siteuser']['userid'])."' and a.series_id='$series_id' and a.subseries_id='$current_subseries' and a.active != 'N' ORDER BY a.series_sort ASC,id DESC".($show_only_one ? ' LIMIT 1':'').";");

  while ($r = hm_fetch($q)) {
   list($ggg,$r['exefield']) = explode('/',$r['exefield'],2);

   if ($previous_section != $r['sr_name']) {
?>
    <tr class="section_header<?php echo ($previous_section ? '':' first');?>">
     <td colspan="5">Series: <?php echo cleanOutput($r['sr_name']);?></td>
    </tr>
<?php
   }

   $previous_section = $r['sr_name'];

   if ($firstactive === false && $r['active'] == 'Y') {
    $firstactive = true;
    $link = '<a href="'.protectedURL().cleanOutput(rtrim($r['exefield'],'/')).'/?ai='.$r['id'].'" target="_blank">'.cleanOutput($r['coursename']).'</a>';
   }
   else $link = cleanOutput($r['coursename']);
?>
    <tr<?php echo ($r['active'] != 'Y' ? ' class="completed"' : '');?>>
     <td><?php echo $link;?></td>
     <td><?php echo cleanOutput($r['desc']); ?></td>
     <td><?php echo dateOut($r['datetakenby']); ?></td>
    </tr>
<?php
  }
 }
}
?>
   </table>
  </div>

  <div id="learning_results">
   <div class="controls"><a href="javascript:printWindow()"><img src="<?php theme();?>/images/user_print.gif" width="139" height="46" border="0"></a><a href="javascript:refresh()"><img src="<?php theme();?>/images/user_refresh.gif" width="139" height="46" border="0"></a></div>
   <table width="100%">
    <tr>
     <th><?php echo sortHeader('Course',0,$sortby);?></th>
     <th width="90" class="c"><?php echo sortHeader('Instruction',1,$sortby);?></th>
     <th width="90" class="c"><?php echo sortHeader('Compliance',5,$sortby);?></th>
     <th width="65" class="c"><?php echo sortHeader('Results',2,$sortby);?></th>
     <th width="65" class="c"><?php echo sortHeader('Hours',6,$sortby);?></th>
     <th width="65" class="c"><?php echo sortHeader('Grade',3,$sortby);?></th>
     <th width="65" class="c"><?php echo sortHeader('Level',4,$sortby);?></th>
     <th width="65" class="c">Certs</th>
    </tr>
<?php
$previous_section = '';
$series_count = 0;


$filter_year = (isset($_SESSION['siteuser_2']['filter_year']) ? $_SESSION['siteuser_2']['filter_year'] : date('Y'));
$years = array();


// Get active years for filter drop down
$q = hm_query("SELECT r.idnum,r.userid,r.datestarted,r.courseid,r.ceus,r.passorfail,r.score,ROUND(r.duration/60,0) AS ROUNDED,r.datecomplied,c.coursename,c.certificate_type,cg_name FROM results".SITE_SUFFIX." r LEFT JOIN Courses c ON (r.courseid = c.c_id) LEFT JOIN course_groupings ON (cg_course_id = c_id) LEFT JOIN course_groups ON (cg_id = cg_group_id) WHERE r.userid = '".mysqlText($_SESSION['siteuser']['userid'])."' and r.subseries_id = 0 GROUP BY LEFT(r.datestarted,4);");
while ($r = hm_fetch($q)) {
 $year = intval(substr($r['datestarted'],0,4));
 if ($year > 2000 && $year <= intval(date('Y'))) $years[$year] = $year;
}
arsort($years);


$q = hm_query("SELECT r.idnum,r.userid,r.datestarted,r.courseid,r.ceus,r.passorfail,r.score,ROUND(r.duration/60,0) AS ROUNDED,r.datecomplied,c.coursename,c.certificate_type,r.subseries_id,sr_name as cg_name, (SELECT j.sr_name FROM series_groups j WHERE j.sr_id=sg.sr_parent_id LIMIT 1) AS series_name
FROM results r LEFT JOIN series_groups sg ON (r.subseries_id=sg.sr_id) LEFT JOIN Courses c ON (r.courseid = c.c_id)
WHERE r.userid = '".intval($_SESSION['siteuser']['userid'])."' and r.subseries_id > 0 and LEFT(r.datestarted,4) = '$filter_year'
ORDER BY sg.sr_parent_id ASC, sg.sr_sort ASC, cg_name ASC, r.datestarted ASC;");
while ($r = hm_fetch($q)) {
  if ($r['passorfail'] != 'F' && $r['passorfail'] != 'X' && $r['passorfail'] != 'R' && $r['passorfail'] != 'Pretest' && $r['certificate_type'] > 0) {
   $certificate = get_certificate($r['idnum']);
  }
  else $certificate = false;

  $series_name = ($r['series_name'] ? $r['series_name'] : 'Series').': ';

 if ($previous_section != $r['cg_name']) {
?>
    <tr class="section_header<?php echo ($previous_section ? '':' first');?>">
     <td colspan="5"><?php echo cleanOutput($series_name.($r['cg_name'] ? $r['cg_name'] : 'Unnamed'));?></td>
    </tr>
<?php
 }

 $previous_section = $r['cg_name'];
 $current = (strtotime($r['datecomplied']) < time() ? false : true);
?>
    <tr class="<?php echo ($r['passorfail'] == 'X' ? 'failed':'passed');?>">
     <td><?php echo courseName($r['coursename']); ?></td>
     <td class="c"><?php echo dateOut($r['datestarted']); ?></td>
     <td class="c"><?php echo dateOut($r['datecomplied']); ?></td>
     <td class="c"><?php echo cleanOutput($r['score']); ?></td>
     <td class="c"><?php echo cleanOutput($r['ceus']); ?></td>
     <td class="c"><?php echo gradeOut($r['passorfail']); ?></td>
     <td class="c"><?php echo cleanOutput($r['ROUNDED']); ?></td>
     <td class="certs"><?php if ($certificate) { ?><a href="<?php echo $certificate;?>" target="_blank"><img src="<?php theme();?>/images/pdf.png" alt=""></a><?php } ?></td>
    </tr>
<?php
 $series_count++;
}

if ($series_count > 0) $previous_section = 'ANYTHING HERE TO MAKE SURE IT DISPLAYS GAP PROPERLY ON FIRST';


$q = hm_query("SELECT r.idnum,r.userid,r.datestarted,r.courseid,r.ceus,r.passorfail,r.score,ROUND(r.duration/60,0) AS ROUNDED,r.datecomplied,c.coursename,c.certificate_type,cg_name FROM results".SITE_SUFFIX." r LEFT JOIN Courses c ON (r.courseid = c.c_id) LEFT JOIN course_groupings ON (cg_course_id = c_id) LEFT JOIN course_groups ON (cg_id = cg_group_id) WHERE r.userid = '".mysqlText($_SESSION['siteuser']['userid'])."' and r.subseries_id = 0 and LEFT(r.datestarted,4) = '$filter_year'
ORDER BY ISNULL(cg_name),cg_name ".($sortby ? 'DESC' : 'ASC').",$orderbyfield ".($sortby ? 'DESC' : 'ASC').";");
while ($r = hm_fetch($q)) {
  if ($r['passorfail'] != 'F' && $r['passorfail'] != 'X' && $r['passorfail'] != 'R' && $r['passorfail'] != 'Pretest' && $r['certificate_type'] > 0) {
   $certificate = get_certificate($r['idnum']);
  }
  else $certificate = false;

 if ($previous_section != $r['cg_name']) {
?>
    <tr class="section_header<?php echo ($previous_section ? '':' first');?>">
     <td colspan="7"><?php echo cleanOutput($r['cg_name'] ? $r['cg_name'] : SECTION_GENERAL);?></td>
    </tr>
<?php
 }

 $previous_section = $r['cg_name'];
 $current = (strtotime($r['datecomplied']) < time() ? false : true);
?>
    <tr class="<?php echo ($r['passorfail'] == 'X' ? 'failed':'passed');?>">
     <td><?php echo courseName($r['coursename']); ?></td>
     <td class="c"><?php echo dateOut($r['datestarted']); ?></td>
     <td class="c"><?php echo dateOut($r['datecomplied']); ?></td>
     <td class="c"><?php echo cleanOutput($r['score']); ?></td>
     <td class="c"><?php echo cleanOutput($r['ceus']); ?></td>
     <td class="c"><?php echo gradeOut($r['passorfail']); ?></td>
     <td class="c"><?php echo cleanOutput($r['ROUNDED']); ?></td>
     <td class="certs"><?php if ($certificate) { ?><a href="<?php echo $certificate;?>" target="_blank"><img src="<?php theme();?>/images/pdf.png" alt=""></a><?php } ?></td>
    </tr>
<?php
}


if (count($years) > 1) {
?>
    <tr>
     <td class="c" colspan="8"><form method="post"><select name="filter_year"><?php echo optOut($years,$filter_year);?></select> <input type="submit" value="Show Archive"></form></td>
    </tr>
<?php
}
?>
   </table>
  </div>
 </div>
<?php
hm_footer();