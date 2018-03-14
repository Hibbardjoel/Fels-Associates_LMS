<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

if ($_POST['cert_id']) $cert_id = intval($_POST['cert_id']);
else $cert_id = intval($_GET['id']);

$q = hm_query("SELECT cert_id,cert_name FROM certificates WHERE cert_id='$cert_id' LIMIT 1;");
$r = hm_fetch($q);

if ($r['cert_id'] < 1) {
 err('Invalid Site ID: Unable to delete.','editcertificates');
 hm_redirect('editcertificates.php');
}

if ($_POST) {
 if ($_POST['confirm'] != 1) err('You must confirm deletion.','deletecertificate');

 if (errCnt('deletecertificate') === 0) {
  hm_query("DELETE FROM certificates WHERE cert_id='$cert_id' LIMIT 1;");
  err('Certificate has been deleted successfully.','editcertificates',1);
  hm_redirect('editcertificates.php');
 }
}

hm_header();

template('compliance_box');
?>
  <div class="banner">Deleting Certificate &bull;</div>
  <div id="edit_user_box" class="delete">
  <p>Are you sure you want to delete this certificate? All data will be permanently removed. There is no undoing this operation. Be absolutely sure before continuing.</p>
<?php errOut('deletecertificate');?>
  <form action="deletecertificate.php" method="post">
  <input type="hidden" name="cert_id" value="<?php echo $cert_id;?>">
   <div class="field">
    <label>Confirm:</label>
    <input name="confirm" type="checkbox" id="confirm" value="1"> Yes, delete this site. I understand there is no undoing this.
   </div>
   <div class="clear"></div>
   <div class="confirm">
    <input type="submit" name="submit" value="DELETE" class="button delete"> <a href="/editcertificates.php" class="button right">Cancel Deletion</a>
   </div>
   <div class="clear"></div>
  </form>
  </div>
<?php
hm_footer();
?>