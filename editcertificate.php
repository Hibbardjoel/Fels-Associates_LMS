<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

if ($_POST['cert_id']) $cert_id = intval($_POST['cert_id']);
else $cert_id = intval($_GET['id']);
$active_level = get_level();

$q = hm_query("SELECT cert_id,cert_name,cert_image FROM certificates WHERE cert_id='$cert_id' LIMIT 1;");
$r = hm_fetch($q);

if ($r['cert_id'] < 1) {
 err('Invalid ID: Unable to edit.','editcertificates');
 hm_redirect('editcertificates.php');
}

$name = $r['cert_name'];
$certimage = $r['cert_image'];

if ($_POST) {
 $name = hm_cleanInput($_POST['certname'],'x',25);

 $upload = hm_upFile('certimage','certificates',array('jpeg','jpg','png','gif'),'','','editcertificate');

 if ($upload[0]) {
  $new_image = $upload[1];
  $new_image_res = resizeimg(1100,850,'certificates/'.$new_image,100,'editcertificate');
  if ($new_image_res) $new_image = $new_image_res;
 }

 if (strlen($name) < 2) err('You must enter a Certificate Name at least 2 characters long.','editcertificate');

 if (errCnt('editcertificate') === 0) {
  $name = mysqlText($name);
  if ($new_image) $addin = ",cert_image='".mysqlText($new_image)."'";

  hm_query("UPDATE certificates SET cert_name='$name'$addin WHERE cert_id='$cert_id' LIMIT 1;");

  err('Changes to certificate have been updated successfully.','editcertificates',1);
  errClear('editcertificate');
  hm_redirect('editcertificates.php');
 }
}

hm_header();

$allowed_levels = $levels;
if ($active_level > 1) unset($allowed_levels[1]);
krsort($allowed_levels);

template('compliance_box');

$thumb = getThumbnail('certificates/'.$certimage);
?>
  <div class="banner">Editing Certificate Information &bull;</div>
  <div id="edit_user_box">
<?php errOut('editcertificate');?>
  <form action="editcertificate.php" method="post" enctype="multipart/form-data">
  <input type="hidden" name="cert_id" value="<?php echo $cert_id;?>">
   <div class="field">
    <label>Certificate Name:</label>
    <input name="certname" type="text" id="certname" value="<?php echo $name;?>">
   </div>
   <div class="field double">
    <label>Certificate Image:</label>
    <input name="certimage" type="file" id="certimage">
   </div>
   <div class="field">
    <div class="thumb"><?php echo ($thumb ? '<img src="'.$thumb.'" width="'.THUMBNAIL_WIDTH.'" alt="">':'No Image');?><br><em><?php echo $certimage;?></em></div>
   </div>
   <div class="clear"></div>
   <div class="field">
    <input type="submit" name="submit" value="Submit">
   </div>
   <div class="clear"></div>
  </form>
<?php
$count = hm_result("SELECT count(*) FROM Courses WHERE certificate_type='$cert_id';",1);
if ($count === 0) {
?>
  <div class="deletebox">Delete this certificate? <a href="deletecertificate.php?id=<?php echo $cert_id;?>">DELETE</a></div>
<?php
}
?>
  </div>
<?php
hm_footer();
?>