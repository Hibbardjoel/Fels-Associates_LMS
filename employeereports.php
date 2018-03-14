<?php
require('inc/conf.php');
accessLevel(6);
require('inc/sess.php');

$order_fields = array('username','name','status','g_name','email');

$orderby = intval($_GET['orderby']);
$sortby = ($_GET['sortby'] ? 1 : 0);

if ($orderby < 0 || $orderby > (count($order_fields) - 1)) $orderby = 0;
$orderbyfield = $order_fields[$orderby];

hm_header();

template('compliance_box',array('manager' => 1));
?>
  <div class="banner">Which Compliance Report &bull;</div>
  <div id="compliance_reports">
  <form method="post">
  <?php errOut('employeereports');?>
   <table>
    <tr>
     <th width="130"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=0&sortby=".(!$sortby);?>">USERNAME</a></th>
     <th width="150"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=1&sortby=".(!$sortby);?>">EMPLOYEE NAME</a></th>
     <th class="c" width="75"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=2&sortby=".(!$sortby);?>">STATUS</a></th>
     <th width="110"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=3&sortby=".(!$sortby);?>">DEPARTMENT</a></th>
     <th><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=4&sortby=".(!$sortby);?>">EMAIL</a></th>
     <th width="70" class="c">CERTS</th>
    </tr>
<?php
$q = hm_query("SELECT userid,username,`name`,status,email,userlevel FROM users".SITE_SUFFIX." LEFT JOIN group_users".SITE_SUFFIX." ON (userid = gu_user_id) LEFT JOIN groups".SITE_SUFFIX." ON (g_id = gu_group_id) WHERE userlevel = '10' ORDER BY $orderbyfield ".($sortby ? 'DESC' : 'ASC').";");
if (hm_cnt($q) === 0) {
?>
    <tr>
     <td colspan="6"><p>No employees found.</p></td>
    </tr>
<?php
} else {
 while ($r = hm_fetch($q)) {
 $users_groups = get_users_groups($r['userid']);
 $certificates = hm_result("SELECT count(*) FROM results".SITE_SUFFIX." r LEFT JOIN Courses c ON (r.courseid=c.c_id) WHERE r.userid='".intval($r['userid'])."' and r.passorfail != 'F' and r.passorfail != 'X' and r.passorfail != 'R' and r.passorfail != 'Pretest' and c.certificate_type > 0;");
?>
    <tr class="<?php echo $level_slugs[$r['userlevel']];?>">
     <td><a href="employeereport.php?id=<?php echo $r['userid'];?>"><?php echo cleanOutput($r['username'],'',1);?></a></td>
     <td><?php echo cleanOutput($r['name']); ?></td>
     <td class="c"><?php echo cleanOutput($statuses[$r['status']]); ?></td>
     <td><?php echo cleanOutput(implode(', ',$users_groups)); ?></td>
     <td><?php echo cleanOutput($r['email']); ?></td>
     <td class="certs"><?php if ($certificates > 0) { ?><a href="employeereport.php?id=<?php echo $r['userid'];?>"><img src="<?php theme();?>/images/pdf.png" alt=""></a><?php } ?></td>
    </tr>
<?php
 }
}
?>
   </table>
  </form>
  </div>
<?php
hm_footer();