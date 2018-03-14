<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

if ($_POST['site_id']) $site_id = intval($_POST['site_id']);
else $site_id = intval($_GET['id']);

$q = hm_query("SELECT site_id,site_host FROM sites WHERE site_id='$site_id' LIMIT 1;");
$r = hm_fetch($q);

if ($r['site_id'] < 1) {
 err('Invalid Site ID: Unable to delete.','view_sites');
 hm_redirect('view_sites.php');
}

if ($_POST) {
 if ($_POST['confirm'] != 1) err('You must confirm deletion.','deletesite');

 if (errCnt('deletesite') === 0) {
  deleteSite($site_id);
  err('Site has been deleted successfully.',get_home_slug(),1);
  go_home();
 }
}

$user_count = hm_result("SELECT count(*) FROM users".siteSuffix($r['site_id'])." WHERE userlevel='10';",1);

hm_header();

template('sites_box');
?>
  <div class="banner">Deleting Entire Site &bull;</div>
  <div id="edit_user_box" class="delete">
   <div id="view_user_table" class="noleft"><table>
    <tr>
     <th width="130">SITE NAME</th>
     <th width="80">USERS</th>
     <th>WEBSITE</th>
    </tr>
    <tr>
     <td><?php echo cleanOutput($r['site_host'],'',1);?></td>
     <td class="c"><?php echo intval($user_count); ?></td>
     <td><a href="<?php echo getSiteURL($site_id); ?>"><?php echo getSiteURL($site_id); ?></a></td>
    </tr>
   </table></div>
  <p>Are you sure you want to delete this site? All data will be permanently removed, including ALL site users and their reports. There is no undoing this operation. Be absolutely sure before continuing.</p>
<?php errOut('deletesite');?>
  <form action="deletesite.php" method="post">
  <input type="hidden" name="site_id" value="<?php echo $site_id;?>">
   <div class="field">
    <label>Confirm:</label>
    <input name="confirm" type="checkbox" id="confirm" value="1"> Yes, delete this site. I understand there is no undoing this.
   </div>
   <div class="clear"></div>
   <div class="confirm">
    <input type="submit" name="submit" value="DELETE" class="button delete"> <a href="/view_sites.php" class="button right">Cancel Deletion</a>
   </div>
   <div class="clear"></div>
  </form>
  </div>
<?php
hm_footer();
?>