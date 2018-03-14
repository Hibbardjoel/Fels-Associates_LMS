<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

function cleanAssignments($id = '',$delete = false) {
 $id = intval($id);
 $site_suffix = siteSuffix($id);
 $cnt = 0;

 if ($delete) {
  $q = hm_query("SELECT id FROM Assignments$site_suffix a WHERE (SELECT count(*) FROM results$site_suffix r WHERE a.active!='N' and r.userid = a.userid and r.courseid = a.course_id and (r.passorfail = 'P' or r.passorfail = 'C' or r.passorfail = 'IS')) > 0;");
  while ($r = hm_fetch($q)) {
   $aid = intval($r['id']);
   hm_query("DELETE FROM Assignments$site_suffix WHERE id='$aid' LIMIT 1;");
   $cnt++;
  }

 } else {
  $q = hm_query("SELECT id FROM Assignments$site_suffix a WHERE (SELECT count(*) FROM results$site_suffix r WHERE a.active!='N' and r.userid = a.userid and r.courseid = a.course_id and (r.passorfail = 'P' or r.passorfail = 'C' or r.passorfail = 'IS')) > 0;");
  while ($r = hm_fetch($q)) {
   $aid = intval($r['id']);
   hm_query("UPDATE Assignments$site_suffix SET active='N' WHERE id='$aid' LIMIT 1;");
   $cnt++;
  }
 }

 return $cnt;
}

$id = intval($_GET['id']);
$sites = getSiteIDs();

if ($id > 0) {
 $total = cleanAssignments($id);
 err('Cleaned '.$total.' assignments from site ID: '.$id.' - '.getSiteSlug($id),'clean_assignments',1);
 hm_redirect('cleansites.php');

} else {
 if ($_GET['id'] == 'MASTER') {
  $total = cleanAssignments();
  err('Cleaned '.$total.' assignments from Master Site','clean_assignments',1);
  hm_redirect('cleansites.php');

 } elseif ($_GET['id'] == 'ALL') {
  if (is_array($sites) && count($sites) > 0) {
   $cnt = 0;
   $total = cleanAssignments();

   foreach ($sites as $id) {
    $total += cleanAssignments($id);
    $cnt++;
   }

   err('Cleaned '.$total.' assignments from '.$cnt.' sites','clean_assignments',1);
   hm_redirect('cleansites.php');
  }
 }
}


hm_header();

template('sites_box');
?>
 <div id="main">
  <?php errOut('clean_assignments');?>
  <div class="banner">Sites Available for Cleaning &bull;</div>
  <div id="regularbox">
   <form method="post">
   <div id="view_user_table" class="noleft"><table>
    <tr>
     <th width="130">SITE NAME</th>
     <th width="80">USERS</th>
     <th>ACTIONS</th>
    </tr>
    <tr>
     <th colspan="3" style="text-align:right;"><a href="cleansites.php?id=ALL">Clean ALL Sites</a></th>
    </tr>
    <tr>
     <td>Master Site</td>
     <td class="c"></td>
     <td><a href="cleansites.php?id=MASTER">Clean Assignments</a></td>
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
     <td><a href="cleansites.php?id=<?php echo $r['site_id'];?>">Clean Assignments</a></td>
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