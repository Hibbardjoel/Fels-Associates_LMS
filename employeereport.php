<?php
require('inc/conf.php');
accessLevel(6);
require('inc/sess.php');
require('inc/certificates.php');

$user_id = intval($_GET['id']);
if ($user_id < 1) {
 err('Invalid Employee ID: Unable to view report.','employeereports');
 hm_redirect('employeereports.php');
}

if (isset($_POST['filter_year'])) {
 $_SESSION['adminuser_2']['filter_year'] = intval($_POST['filter_year']);
 hm_redirect($_SERVER['REQUEST_URI']);
}

$name = hm_result("SELECT `name` FROM users".SITE_SUFFIX." WHERE userid='$user_id' and userlevel='10' LIMIT 1;");

$order_fields = array('c.coursename','r.datestarted','r.score','r.passorfail','ROUNDED','r.datecomplied','ceus');

$orderby = intval($_GET['orderby']);
$sortby = ($_GET['sortby'] ? 1 : 0);

if ($orderby < 0 || $orderby > (count($order_fields) - 1)) $orderby = 0;
$orderbyfield = $order_fields[$orderby];

hm_header();

template('compliance_box',array('manager' => 1));
?>
<script type="text/javascript">
function printWindow(){
 bV = parseInt(navigator.appVersion)
 if (bV >= 4) window.print()
}
</script>
<div id="employee_report">
<?php errOut('employeereport');?>
  <table width="715">
    <tr>
      <td valign="top">
        <table width="100%" height="56" border="0" cellpadding="0" cellspacing="0" background="<?php theme();?>/images/user_04.gif" style="margin:0 0 10px;">
          <tr>
            <td width="480" height="56"></td>
            <td width="139" style="padding:5px 0 0;"><a href="javascript:printWindow()"><img src="<?php theme();?>/images/user_print.gif" width="139" height="46" border="0"></a></td>
            <td width="60"></td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td><h2><?php echo $name;?></h2></td>
    </tr>
    <tr>
      <td valign="top"><table width="100%" class="results">
          <tr>
            <td width="55">&nbsp;</td>
            <td valign="top"><?php echo sortHeader('Course',0,$sortby,'&id='.$user_id);?></td>
            <td width="90" valign="top" class="center"><?php echo sortHeader('Instruction',1,$sortby,'&id='.$user_id);?></td>
            <td width="90" valign="top" class="center"><?php echo sortHeader('Compliance',5,$sortby,'&id='.$user_id);?></td>
            <td width="55" valign="top" class="center"><?php echo sortHeader('Results',2,$sortby,'&id='.$user_id);?></td>
            <td width="55" valign="top" class="center"><?php echo sortHeader('CEU',6,$sortby,'&id='.$user_id);?></td>
            <td width="55" valign="top" class="center"><?php echo sortHeader('Grade',3,$sortby,'&id='.$user_id);?></td>
            <td width="55" valign="top" class="center"><?php echo sortHeader('Level',4,$sortby,'&id='.$user_id);?></td>
            <td width="55" valign="top" class="center">Certs</td>
          </tr>
<?php
$previous_section = '';
$total_count = 0;$series_count = 0;


$filter_year = (isset($_SESSION['adminuser_2']['filter_year']) ? intval($_SESSION['adminuser_2']['filter_year']) : 0);
$filter_year_addin = ($filter_year ? '' : " and LEFT(r.datestarted,4) = '".date('Y')."'");
$years = array('Current Results','Previous Results');

$q = hm_query("SELECT r.idnum,r.userid,r.datestarted,r.courseid,r.ceus,r.passorfail,r.score,ROUND(r.duration/60,0) AS ROUNDED,r.datecomplied,c.coursename,c.certificate_type,r.subseries_id,sr_name as cg_name, (SELECT j.sr_name FROM series_groups j WHERE j.sr_id=sg.sr_parent_id LIMIT 1) AS series_name
FROM results".SITE_SUFFIX." r LEFT JOIN series_groups sg ON (r.subseries_id=sg.sr_id) LEFT JOIN Courses c ON (r.courseid = c.c_id)
WHERE r.userid = '$user_id' and r.subseries_id > 0$filter_year_addin
ORDER BY sg.sr_parent_id ASC, sg.sr_sort ASC, cg_name ASC, r.datestarted ASC;");

if (hm_cnt($q) > 0) {

 while ($r = hm_fetch($q)) {
  $total_count++;
  if ($r['passorfail'] != 'F' && $r['passorfail'] != 'X' && $r['passorfail'] != 'R' && $r['passorfail'] != 'Pretest' && $r['certificate_type'] > 0) {
   $certificate = get_certificate($r['idnum']);
  }
  else $certificate = false;

  $series_name = ($r['series_name'] ? $r['series_name'] : 'Series').': ';

 if ($previous_section != $r['cg_name']) {
?>
    <tr class="section_header<?php echo ($previous_section ? '':' first');?>">
     <td>&nbsp;</td>
     <td colspan="7"><?php echo cleanOutput($series_name.($r['cg_name'] ? $r['cg_name'] : 'Unnamed'));?></td>
    </tr>
<?php
 }

 $previous_section = $r['cg_name'];
 $current = (strtotime($r['datecomplied']) < time() ? false : true);
?>
          <tr class="<?php echo ($r['passorfail'] == 'X' ? 'failed':'passed');?>">
            <td>&nbsp;</td>
            <td valign="top"><?php echo courseName($r['coursename']); ?></td>
            <td valign="top" class="center"><?php echo dateOut($r['datestarted']); ?></td>
            <td valign="top" class="center"><?php echo dateOut($r['datecomplied']); ?></td>
            <td valign="top" class="center"><?php echo cleanOutput($r['score']); ?></td>
            <td valign="top" class="center"><?php echo cleanOutput($r['ceus']); ?></td>
            <td valign="top" class="center"><?php echo gradeOut($r['passorfail']); ?></td>
            <td valign="top" class="center"><?php echo cleanOutput($r['ROUNDED']); ?></td>
            <td valign="top" class="certs"><?php if ($certificate) { ?><a href="<?php echo $certificate;?>" target="_blank"><img src="<?php theme();?>/images/pdf.png" alt=""></a><?php } ?></td>
			       </tr>
<?php
 }
}

$q = hm_query("SELECT r.idnum,r.userid,r.datestarted,r.courseid,r.ceus,r.passorfail,r.score,ROUND(r.duration/60,0) AS ROUNDED,r.datecomplied,c.coursename,c.certificate_type,cg_name FROM results".SITE_SUFFIX." r LEFT JOIN Courses c ON (r.courseid = c.c_id) LEFT JOIN course_groupings ON (cg_course_id = c_id) LEFT JOIN course_groups ON (cg_id = cg_group_id) WHERE r.userid = '$user_id' and r.subseries_id = 0$filter_year_addin ORDER BY ISNULL(cg_name),cg_name ".($sortby ? 'DESC' : 'ASC').",$orderbyfield ".($sortby ? 'DESC' : 'ASC').";");

if (hm_cnt($q) === 0 && $total_count === 0) {
?>
    <tr>
     <td>&nbsp;</td>
     <td colspan="6"><p>This employee has not completed any reports.</p></td>
    </tr>
<?php
} else {

 while ($r = hm_fetch($q)) {
  if ($r['passorfail'] != 'F' && $r['passorfail'] != 'X' && $r['passorfail'] != 'R' && $r['passorfail'] != 'Pretest' && $r['certificate_type'] > 0) {
   $certificate = get_certificate($r['idnum']);
  }
  else $certificate = false;

 if ($previous_section != $r['cg_name']) {
?>
    <tr class="section_header<?php echo ($previous_section ? '':' first');?>">
     <td>&nbsp;</td>
     <td colspan="7"><?php echo cleanOutput($r['cg_name'] ? $r['cg_name'] : SECTION_GENERAL);?></td>
    </tr>
<?php
 }

 $previous_section = $r['cg_name'];
 $current = (strtotime($r['datecomplied']) < time() ? false : true);
?>
          <tr class="<?php echo ($r['passorfail'] == 'X' ? 'failed':'passed');?>">
            <td>&nbsp;</td>
            <td valign="top"><?php echo courseName($r['coursename']); ?></td>
            <td valign="top" class="center"><?php echo dateOut($r['datestarted']); ?></td>
            <td valign="top" class="center"><?php echo dateOut($r['datecomplied']); ?></td>
            <td valign="top" class="center"><?php echo cleanOutput($r['score']); ?></td>
            <td valign="top" class="center"><?php echo cleanOutput($r['ceus']); ?></td>
            <td valign="top" class="center"><?php echo gradeOut($r['passorfail']); ?></td>
            <td valign="top" class="center"><?php echo cleanOutput($r['ROUNDED']); ?></td>
            <td valign="top" class="certs"><?php if ($certificate) { ?><a href="<?php echo $certificate;?>" target="_blank"><img src="<?php theme();?>/images/pdf.png" alt=""></a><?php } ?></td>
			       </tr>
<?php
 }
}

?>
    <tr>
     <td class="c" colspan="8"><form method="post"><select name="filter_year" onchange="this.form.submit();"><?php echo optOut($years,$filter_year);?></select> <input type="submit" value="Update"></form></td>
    </tr>
      </table></td>
    </tr>
  </table>
</div>
<?php
hm_footer();