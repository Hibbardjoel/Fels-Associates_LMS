<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

//if (intval($_GET['id']) > 0) {
// $result = deleteSite($_GET['id']);
//
// if ($result) {
//  err('Deleted site successfully.','delete_new_site',1);
// } else {
//  switch ($result) {
//   default:
//    err('Failed to delete site. Unknown error.','delete_new_site');
//  }
// }
// hm_redirect('view_sites.php');
//}

hm_header();

template('sites_box');
?>
 <div id="main">
  <?php errOut('delete_new_site');?>
  <div class="banner">Viewing Available Sites &bull;</div>
  <div id="regularbox">
   <form method="post">
   <div id="view_user_table" class="noleft"><table>
    <tr>
     <th width="130">SITE NAME</th>
     <th width="80">USERS</th>
     <th>WEBSITE</th>
     <th width="100">ACTIONS</th>
    </tr>
<?php
$q = hm_query("SELECT site_id,site_host FROM sites ORDER BY site_host;");

if (hm_cnt($q) > 0) {
 while ($r = hm_fetch($q)) {
  $user_count = hm_result("SELECT count(*) FROM users".siteSuffix($r['site_id'])." WHERE userlevel='10';",1);
?>
    <tr>
     <td><?php echo cleanOutput($r['site_host'],'',1);?></td>
     <td class="c"><?php echo intval($user_count); ?></td>
     <td><a href="<?php echo getSiteURL($r['site_id']); ?>" target="_blank"><?php echo getSiteURL($r['site_id']); ?></a></td>
     <td><a href="deletesite.php?id=<?php echo $r['site_id'];?>">DELETE</a></td>
    </tr>
<?php
 }
} else {
?>
    <tr>
     <td colspan="3">No sites available.</td>
    </tr>
<?php
}
?>
   </table></div>
  </form>
  </div>
 </div>
<?php
hm_footer();
?>