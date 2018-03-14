<?php
require('inc/conf.php');
accessLevel(6);
require('inc/sess.php');

$order_fields = array('username','name','status','g_name','password','email');

$orderby = intval($_GET['orderby']);
$sortby = ($_GET['sortby'] ? 1 : 0);

if ($orderby < 0 || $orderby > (count($order_fields) - 1)) $orderby = 0;
$orderbyfield = $order_fields[$orderby];

$group = intval($_POST['selectgroup']);

$groups = get_user_groups();

hm_header();

template('user_box');
?>
<form method="post">
<div class="controls" style="padding: 0 0 0 110px;"><select name="selectgroup" id="selectgroup" onchange="this.form.submit();" style="width:115px;"><?php echo optOut($groups,$group,'Show All');?></select> <input type="submit" name="loadgroup" value="Update"></div>
  <div class="banner">Viewing User Information &bull;</div>
  <div id="view_user_table">
   <table>
    <tr>
     <th width="100"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=0&sortby=".(!$sortby);?>">USERNAME</a></th>
     <th width="150"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=1&sortby=".(!$sortby);?>">EMPLOYEE NAME</a></th>
     <th class="c" width="75"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=2&sortby=".(!$sortby);?>">STATUS</a></th>
     <th class="c" width="110"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=3&sortby=".(!$sortby);?>">DEPARTMENT</a></th>
     <th class="c" width="110"><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=4&sortby=".(!$sortby);?>">PASSWORD</a></th>
     <th><a href="<?php echo $_SERVER['PHP_SELF']."?orderby=5&sortby=".(!$sortby);?>">EMAIL</a></th>
    </tr>
<?php
if ($group > 0) $where = " and gu_group_id='$group'";
else $where = '';

$q = hm_query("SELECT userid,username,`name`,status,email,userlevel,password FROM users".SITE_SUFFIX." LEFT JOIN group_users".SITE_SUFFIX." ON (userid = gu_user_id) LEFT JOIN groups".SITE_SUFFIX." ON (g_id = gu_group_id) WHERE userlevel >= ".get_level()."$where GROUP BY userid ORDER BY userlevel DESC,$orderbyfield ".($sortby ? 'DESC' : 'ASC').";");

if (hm_cnt($q) > 0) {

 while ($r = hm_fetch($q)) {
  $users_groups = get_users_groups($r['userid']);
?>
    <tr class="<?php echo $level_slugs[$r['userlevel']];?>">
     <td><?php echo cleanOutput($r['username'],0,1); ?></td>
     <td><?php echo cleanOutput($r['name']); ?></td>
     <td class="c"><?php echo cleanOutput($statuses[$r['status']]); ?></td>
     <td class="c"><?php echo cleanOutput(implode(', ',$users_groups)); ?></td>
     <td class="c"><?php echo cleanOutput($r['password']); ?></td>
     <td><?php echo cleanOutput($r['email']); ?></td>
    </tr>
<?php
 }
} else {
 ?>
    <tr>
     <td colspan="6"><p>No users found.</p></td>
    </tr>
<?php
}
?>
   </table>
  </div>
</form>
<?php
hm_footer();