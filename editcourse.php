<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

if ($_POST['course_id']) $course_id = intval($_POST['course_id']);
else $course_id = intval($_GET['id']);
$active_level = get_level();

$q = hm_query("SELECT c_id,coursename,`desc`,ceus,grade,exefield,certificate_type FROM Courses WHERE c_id='$course_id' LIMIT 1;");
$r = hm_fetch($q);

if ($r['c_id'] < 1) {
 err('Invalid ID: Unable to edit.','editcourses');
 hm_redirect('editcourses.php');
}

$name = $r['coursename'];
$desc = $r['desc'];
$ceus = $r['ceus'];
$grade = $r['grade'];
$exefield = $r['exefield'];
$certificate_type = $r['certificate_type'];

$certificate_types = get_certificate_types(1);

if ($_POST) {
 $name = hm_cleanInput($_POST['coursename'],'x',25);
 $desc = hm_cleanInput($_POST['desc'],'x',255);
 $ceus = intval($_POST['ceus']);
 $grade = hm_cleanInput($_POST['grade'],'t',8);
 $exefield = hm_cleanInput($_POST['exefield'],'t',255);
 $certificate_type = intval($_POST['certificate_type']);

 if (strlen($name) < 2) err('You must enter a Course Name at least 2 characters long.','editcourse');
 if (strlen($desc) < 2) err('You must enter a Description at least 2 characters long.','editcourse');
 if (strlen($grade) < 1) err('You must enter a Grade.','editcourse');
 if (strlen($exefield) < 2) err('You must enter a proper path for Exefield.','editcourse');
 if ($certificate_type < 0 || key_exists($certificate_type,$certificate_types) === false) err('You must select a Certificate Type.','editcourse');

 if (errCnt('editcourse') === 0) {
  $name = mysqlText($name);
  $desc = mysqlText($desc);
  $grade = mysqlText($grade);
  $exefield = mysqlText($exefield);

  hm_query("UPDATE Courses SET coursename='$name',`desc`='$desc',grade='$grade',exefield='$exefield',ceus='$ceus',certificate_type='$certificate_type' WHERE c_id='$course_id' LIMIT 1;");

  err('Changes to course have been updated successfully.','editcourses',1);
  hm_redirect('editcourses.php');
 }
}

hm_header();

$allowed_levels = $levels;
if ($active_level > 1) unset($allowed_levels[1]);
krsort($allowed_levels);

template('user_box');
?>
  <div class="banner">Editing Course Information &bull;</div>
  <div id="edit_user_box">
<?php errOut('editcourse');?>
  <form action="editcourse.php" method="post">
  <input type="hidden" name="course_id" value="<?php echo $course_id;?>">
   <div class="field">
    <label>Course Name:</label>
    <input name="coursename" type="text" id="coursename" value="<?php echo $name;?>">
   </div>
   <div class="field double">
    <label>Description:</label>
    <input name="desc" type="text" id="desc" value="<?php echo $desc;?>">
   </div>
   <div class="field half">
    <label>CEUs:</label>
    <input name="ceus" type="text" id="ceus" value="<?php echo $ceus;?>">
   </div>
   <div class="field half">
    <label>Grade:</label>
    <input name="grade" type="text" id="grade" value="<?php echo $grade;?>">
   </div>
   <div class="field double">
    <label>Training Path:</label>
    <input name="exefield" type="text" id="exefield" value="<?php echo $exefield;?>">
   </div>
   <div class="field">
    <label>Certificate Type:</label>
    <select name="certificate_type" id="certificate_type"><?php echo optOut($certificate_types,$certificate_type);?></select>
   </div>
   <div class="clear"></div>
   <div class="field">
    <input type="submit" name="submit" value="Submit">
   </div>
   <div class="clear"></div>
  </form>
<!--  <div class="deletebox">Delete this course? <a href="deletecourse.php?id=<?php echo $course_id;?>">DELETE</a></div>-->
  </div>
<?php
hm_footer();
?>
