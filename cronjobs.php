<?php
chdir(dirname(__FILE__));
$cronjob_mode = true;
require('inc/conf.php');

$sites = getSiteIDs();

$verbose = true;



hm_out('Beginning Cron Script...',$verbose);



/* Notifications */

hm_out('Preparing to find expired compliances...',$verbose);

foreach ($sites as $site_id) {
 $site_suffix = siteSuffix($site_id);

 // Add failed reports for expired assignments.
 $q = hm_query("SELECT id,userid,course_id,datetakenby FROM Assignments$site_suffix WHERE active='Y' and datetakenby < NOW();");
 while ($r = hm_fetch($q)) {
  $assignment_id = intval($r['id']);
  $user_id = intval($r['userid']);
  $course_id = intval($r['course_id']);
  $datetakenby = mysqlText($r['datetakenby']);
  hm_query("INSERT INTO results$site_suffix (userid, score, courseid, duration, ceus, passorfail, datestarted, datecomplied) VALUES ('$user_id', '0', '$course_id', '0', '0', 'X', NOW(),'$datetakenby');");
  $inserted = hm_insert_id();
  if ($inserted > 0) hm_query("UPDATE Assignments$site_suffix SET active='N' WHERE id='$assignment_id';");
 }

// hm_query("UPDATE Assignments$site_suffix SET active='N' WHERE active='Y' and datetakenby < NOW();");

 $q = hm_query("SELECT userid,name,email FROM users$site_suffix WHERE userlevel < 7 and status = '1' and unsubscribed = '0';");
 while ($r = hm_fetch($q)) {
  if (validEmail($r['email'])) $recipients[$r['userid']] = array('id' => $r['userid'],'name' => $r['name'],'email' => $r['email']);
 }

 if (count($recipients) < 1) continue;

 hm_out('<hr>',$verbose);
 hm_out('Found '.count($recipients).' valid manager/admin email addresses for site: '.$site_id);

 $q = hm_query("SELECT a.id,u.name,u.userid,a.dateassigned, u.username, c.coursename, a.course_id, a.datetakenby
FROM Assignments$site_suffix a LEFT JOIN users$site_suffix u ON (u.userid = a.userid) LEFT JOIN Courses c ON (c.c_id = a.course_id)
WHERE a.active != 'Y' and a.notified = '0' and a.datetakenby < NOW() ORDER BY c.coursename ASC,u.name ASC;");

 $cnt = hm_cnt($q);
 hm_out('Found '.$cnt.' expired assignments',$verbose);

 if ($cnt < 1) continue;

 $template_rows = '';
 $i = 1;
 $ids_to_update = array();

 while ($r = hm_fetch($q)) {
  $i++;
  $style = 'background-color:#'.($i%2 ? 'f3f3f3' : 'e6e6e6');
  $style2 = 'border-bottom:1px solid #ccc;';
  $vars = array('[[NAME]]' => $r['name'],'[[USERNAME]]' => $r['username'],'[[COURSEID]]' => $r['coursename'],'[[COURSE]]' => $r['coursename'],'[[DATE]]' => dateOut($r['datetakenby']),'[[STYLE]]' => $style,'[[STYLE2]]' => $style2);
  $template_rows .= applyTemplate(EMAIL_FAILED_COMPLIANCE_ROW,$vars);
  $ids_to_update[] = intval($r['id']);
 }

 hm_out('Sending emails to managers/admins',$verbose);

 if (DEBUG_MODE === true) {
  $recipients[] = array('id' => 0,'name' => 'Super Admin [Debug Mode On]','email' => SUPPORT_EMAIL);
 }

 foreach ($recipients as $admin) {
  $url = generateUnsubURL($admin['id'],$site_id);
  hm_out("Url : $url");
  $vars = array('[[ROWS]]' => $template_rows,'[[UNSUB]]' => $url);
  $template = applyTemplate(EMAIL_FAILED_COMPLIANCE,$vars);
  $email = $admin['name'].' <'.$admin['email'].'>';
  smail($email,'Failed Compliance Report for '.getSiteURL($site_id),$template,SENDER_EMAIL);
 }

 hm_out('Setting notified bits',$verbose);
 foreach ($ids_to_update as $id) {
 	hm_query("UPDATE Assignments$site_suffix SET notified='1' WHERE id='$id' LIMIT 1;");
 }

}

hm_out('Cron script finished',$verbose);