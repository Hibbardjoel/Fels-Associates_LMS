<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

if ($_POST['cert_id']) $cert_id = intval($_POST['cert_id']);
else $cert_id = intval($_GET['id']);
$active_level = get_level();

$name = $certimage = $new_image = '';

if ($_POST) {
 $name = hm_cleanInput($_POST['certname'],'x',25);

 $upload = hm_upFile('certimage','certificates',array('jpeg','jpg','png','gif'),'','','addcertificate');

 if ($upload[0]) {
  $new_image = $upload[1];
  $new_image_res = resizeimg(1100,850,'certificates/'.$new_image,100,'editcertificate');
  if ($new_image_res) $new_image = $new_image_res;
 }

 if (strlen($name) < 2) err('You must enter a Certificate Name at least 2 characters long.','addcertificate');

 if (errCnt('addcertificate') === 0) {
  $name = mysqlText($name);
  $new_image = mysqlText($new_image);

  hm_query("INSERT INTO certificates (cert_name,cert_image) VALUES ('$name','$new_image');");

  err('Added certificate successfully.','editcertificates',1);
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
  <div class="banner">Adding Certificate Information &bull;</div>
  <div id="edit_user_box">
<?php errOut('addcertificate');?>
  <form action="addcertificate.php" method="post" enctype="multipart/form-data">
   <div class="field">
    <label>Certificate Name:</label>
    <input name="certname" type="text" id="certname" value="<?php echo $name;?>">
   </div>
   <div class="field double">
    <label>Certificate Image:</label>
    <input name="certimage" type="file" id="certimage">
   </div>
   <div class="clear"></div>
   <div class="field">
    <input type="submit" name="submit" value="Submit">
   </div>
   <div class="clear"></div>
  </form>
<!--  <div class="deletebox">Delete this certificate? <a href="deletecertificate.php?id=<?php echo $cert_id;?>">DELETE</a></div>-->
  </div>
<?php
hm_footer();
?>