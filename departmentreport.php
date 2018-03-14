<?php
require('inc/conf.php');
accessLevel(6);
require('inc/sess.php');

if (isset($_POST['filter_year'])) {
 $_SESSION['adminuser_2']['filter_year'] = intval($_POST['filter_year']);
 hm_redirect($_SERVER['REQUEST_URI']);
}

$orderby = 'datestarted';
$sortby = 'ASC';
$newsort = 'DESC';

if ($_GET['sortby']) {
 $sortby = ($_GET['sortby'] == 'ASC' ? 'ASC':'DESC');
 $newsort = ($sortby == 'ASC' ? 'DESC' : 'ASC');
}
if ($_GET['orderby']) {
 $orderby = $_GET['orderby'];
 if ($orderby == 'name') {
  $orderby = 'u.`name`';
 }
 elseif (mysqlFieldExists('results'.SITE_SUFFIX,$orderby) !== true) {
  $orderby = 'datestarted';
 }
}

if ($orderby != 'u.`name`') {
 $orderby = 'u.`name` ASC,'.$orderby;
}

$group = intval($_POST['selectgroup']);

$groups = get_user_groups();

hm_header();

template('compliance_box',array('manager' => 1));
?>
<script type="text/javascript">
function printWindow(){
 bV = parseInt(navigator.appVersion)
 if (bV >= 4) window.print()
}
</script>
  <table>
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
      <td><form method="post"><div class="controls reportcontrol">View Results by Department: <select name="selectgroup" id="selectgroup" onchange="this.form.submit();" style="width:115px;"><?php echo optOut($groups,$group,'Show All');?></select> <input type="submit" name="loadgroup" value="Update"></div></form></td>
    </tr>
  </table>
<div id="compliance_reports">
<?php errOut('employeereport');?>
  <table>
          <tr class="reporttitles">
            <th valign="top"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=name&sortby=$newsort";?>">Employee</a></th>
            <th valign="top" class="center"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=c.coursename&sortby=$newsort";?>">1 CEU per Course</a></th>
            <th valign="top" class="center"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=r.datestarted&sortby=$newsort";?>">Instruction</a></th>
            <th width="130" valign="top" class="center"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=r.datecomplied&sortby=$newsort";?>">Compliance To</a></th>
            <th valign="top" class="center"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=r.score&sortby=$newsort";?>">Score</a></td>
          </tr>
<?php

$filter_year = (isset($_SESSION['adminuser_2']['filter_year']) ? intval($_SESSION['adminuser_2']['filter_year']) : 0);
$filter_year_addin = ($filter_year ? '' : " and LEFT(r.datestarted,4) = '".date('Y')."'");
$years = array('Current Results','Previous Results');

if ($group > 0) $where = " and gu_group_id='$group'";
else $where = '';
$prev = '';

$q = hm_query("SELECT u.name,r.userid,r.datestarted,r.datecomplied,r.courseid,r.score,r.passorfail,c.coursename FROM results".SITE_SUFFIX." r LEFT JOIN Courses c ON (r.courseid = c.c_id) LEFT JOIN group_users".SITE_SUFFIX." g ON (r.userid = g.gu_user_id) LEFT JOIN users".SITE_SUFFIX." u ON (u.userid = r.userid) WHERE 1$where$filter_year_addin ORDER BY ".mysqlText($orderby)." $sortby;");

if (hm_cnt($q) === 0) {
?>
    <tr>
     <td colspan="6"><p>There are no employees report<?php echo ($group > 0 ? ' in this department' : '');?>.</p></td>
    </tr>
<?php
} else {

 while ($r = hm_fetch($q)) {
  $current = (strtotime($r['datecomplied']) < time() ? false : true);
  if ($prev && $prev != $r['name']) {
?>
          <tr class="reporttitles">
            <th valign="top"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=name&sortby=$newsort";?>">Employee</a></th>
            <th valign="top" class="center"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=c.coursename&sortby=$newsort";?>">1 CEU per Course</a></th>
            <th valign="top" class="center"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=r.datestarted&sortby=$newsort";?>">Instruction</a> </th>
            <th width="130" valign="top" class="center"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=r.datecomplied&sortby=$newsort";?>">Compliance To</a></th>
            <th valign="top" class="center"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=r.score&sortby=$newsort";?>">Score</a></th>
          </tr>
<?php
  }
?>
          <tr class="<?php echo ($r['passorfail'] == 'X' ? 'failed':'passed');?>">
            <td width="180" valign="top" class="font whiteout<?php echo ($r['name'] == $prev ? ' noborder' : '');?>"><?php echo ($r['name'] != $prev ? cleanOutput($r['name'],0,1) : '&nbsp;'); ?></td>
            <td width="180" valign="top" class="center"><?php echo courseName($r['coursename']); ?></td>
            <td width="163" valign="top" class="center"><?php echo dateOut($r['datestarted']); ?></td>
            <td width="130" valign="top" class="center"><?php echo dateOut($r['datecomplied']); ?></td>
            <td width="126" valign="top" class="center"><?php echo cleanOutput($r['score']); ?></td>
			       </tr>
<?php
  $prev = $r['name'];
 }
}
?>
    <tr>
     <td class="c" colspan="8"><form method="post"><select name="filter_year" onchange="this.form.submit();"><?php echo optOut($years,$filter_year);?></select> <input type="submit" value="Update"></form></td>
    </tr>
  </table>
</div>
<?php
hm_footer();