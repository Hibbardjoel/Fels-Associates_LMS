<?php
require('inc/conf.php');
accessLevel(10);
require('inc/sess.php');

/*ob_start();
print_r($_GET);
print_r($_POST);
print_r($_SERVER);
$utt = ob_get_contents();
ob_end_clean();*/

$action = $_GET['action'];
$time = intval($_GET['time']);

$coursename = mysqlText($_POST['coursename']);
$course_id = intval($_POST['id_num']);
$score = intval($_POST['score']);

$username = $_SESSION['siteuser']['name'];


if ($action == 'add') {
 $now = date('Y-m-d H:i:s');
 $nextyear = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 year'));
 $result_id = intval($_POST['idnum']);
 $pretest = intval($_GET['pretest']);
 $subseries_id = get_subseries_id();

 if ($course_id < 1) {
  $course_id = hm_result("SELECT c_id FROM Courses WHERE coursename='$coursename' LIMIT 1;",1);
 }

 $cq = hm_query("SELECT ceus,grade FROM Courses WHERE c_id='$course_id' LIMIT 1;");
 $cr = hm_fetch($cq);
 $grade = $cr['grade'];

 if ($grade == 'Pretest' && $pretest === 1) $grade_start = 'Pretest';
 else $grade_start = 'F';

/* if ($grade == 'Pretest' && $result_id > 0 && $pretest === 0) {
  hm_query("UPDATE results".SITE_SUFFIX." SET passorfail='C' WHERE idnum='$result_id' and userid='".intval($_SESSION['siteuser']['userid'])."' LIMIT 1;");
 }*/

/* mail('sdfasf@howdymedia.com',$action.'dsfasdfas',$utt."
ci: $course_id
ri: $result_id
pt: $pretest
gd: $grade
gs: $grade_start
un: $username
");*/

 hm_query("INSERT INTO results".SITE_SUFFIX." (userid, score, courseid, duration, ceus, passorfail, datestarted, datecomplied, subseries_id) VALUES ('".intval($_SESSION['siteuser']['userid'])."', '0', '$course_id', '0', '0', '$grade_start', '$now','$nextyear', '$subseries_id');");
 $result_id = hm_insert_id();

 echo "&username=$username&idnum=$result_id"; //send the id value to flash so it can update in the future

 if ($grade == 'Pretest') {
  if ($pretest === 1) echo '&pretest=1';
  else echo '&pretest=0';
 }

} elseif ($action == 'update') {

 $result_id = intval($_POST['idnum']);
 $pretest = intval($_POST['pretest']);
 $course_id = hm_result("SELECT courseid FROM results".SITE_SUFFIX." WHERE idnum='$result_id' and userid='".intval($_SESSION['siteuser']['userid'])."' LIMIT 1;",1);
 $subseries_id = hm_result("SELECT subseries_id FROM results".SITE_SUFFIX." WHERE idnum='$result_id' and userid='".intval($_SESSION['siteuser']['userid'])."' LIMIT 1;",1);

 $cq = hm_query("SELECT ceus,grade FROM Courses WHERE c_id='$course_id' LIMIT 1;");
 $cr = hm_fetch($cq);

 $ceus = $cr['ceus'];
 $grade = $cr['grade'];
 $assignment_id = intval(get_assignment_id());

 if ($score >= 80) {

  if ($grade == 'R' || ($grade == 'Pretest' && $pretest === 1)) {
   if ($grade == 'Pretest' && $pretest === 1) $ceus = 0;
  }
  elseif ($course_id > 0) {

   $active = ($subseries_id > 0 ? 'C':'N');

   if ($grade == 'Pretest' && $pretest === 0) $grade = 'P';
   hm_query("UPDATE Assignments".SITE_SUFFIX." SET Active='$active' WHERE id='$assignment_id' and userid='".intval($_SESSION['siteuser']['userid'])."' and course_id='$course_id'".($subseries_id > 0 ? " and subseries_id='$subseries_id'" : '')." and Active='Y' ORDER BY id DESC LIMIT 1;");
  }

 } else {

  if ($grade == 'Pretest' && $pretest === 1) {
  }
  else $grade = 'F';
  $ceus = 0;
 }

/* mail('sdfasf@howdymedia.com',$action.'dsfasdfas',$utt."
ci: $course_id
ri: $result_id
pt: $pretest
gd: $grade
sc: $score
");*/

 $grade = mysqlText($grade);
 hm_query("UPDATE results".SITE_SUFFIX." SET score='$score', duration='$time', ceus='$ceus', passorfail='$grade' WHERE idnum='$result_id' and userid='".intval($_SESSION['siteuser']['userid'])."' LIMIT 1;");

}
?>