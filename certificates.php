<?php
include('fpdf/fpdf.php');

function pdfOut($in) {
 return iconv('UTF-8','windows-1252',html_entity_decode($in));
}

function get_certificate($report_id) {
 $report_id = intval($report_id);

 $q = hm_query("SELECT r.userid,r.courseid,r.datestarted,r.ceus,r.passorfail,r.report,r.certificate,r.subseries_id,r.certificate_override,u.`name`,c.certificate_type FROM results".SITE_SUFFIX." r LEFT JOIN users".SITE_SUFFIX." u ON (u.userid = r.userid) LEFT JOIN Courses c ON (r.courseid=c.c_id) WHERE idnum='$report_id' LIMIT 1;");
 $r = hm_fetch($q);

 if ($r['subseries_id'] < 1 && $r['certificate_type'] < 1) {
  if ($r['certificate_override'] < 1) return false;
 }
 if ($r['certificate'] == 'ignore') return false;

 if ($r['userid'] > 0) {

  $path = PROTECTED_ACCESS_FOLDER.'/'.SITE_ID.'/certificates/'.substr($r['userid'],0,1).'/';
  if ($r['certificate'] && file_exists($path.$r['certificate'])) return $path.$r['certificate'];

  if (file_exists($path) === false) {
   mkdir($path,0775,true);
  }

  if ($r['passorfail'] != 'F' && $r['passorfail'] != 'X' && $r['passorfail'] != 'R') {

   $sr_id = 0;
   $type = 0;
   $course_name = '';

   if ($r['subseries_id'] > 0) {
    $sr_id = intval($r['subseries_id']);
    if ($r['certificate_override'] > 0) $type = $r['certificate_override'];
    else $type = hm_result("SELECT sr_certificate FROM series_groups WHERE sr_id='$sr_id' LIMIT 1;",1);
   }

   if ($sr_id > 0 && $type > 0) {

    $incomplete = hm_result("SELECT count(*) FROM Assignments".SITE_SUFFIX." WHERE userid='".intval($r['userid'])."' and subseries_id='$sr_id' and active='Y';");
    if ($incomplete > 0) return false;

    $completed = hm_result("SELECT count(*) FROM Assignments".SITE_SUFFIX." WHERE userid='".intval($r['userid'])."' and subseries_id='$sr_id' and active='C';");
    if ($completed < 1) return false;

//    $completed = hm_query("SELECT  FROM Assignments".SITE_SUFFIX." WHERE userid='".intval($r['userid'])."' and subseries_id='$sr_id' and active='C' ORDER BY series_sort DESC LIMIT 1;");
    $name = $r['name'];
    $course_name = hm_result("SELECT sr_name FROM series_groups WHERE sr_id='$sr_id' LIMIT 1;");
    $ceus = hm_result("SELECT SUM(ceus) FROM results".SITE_SUFFIX." WHERE userid='".intval($r['userid'])."' and certificate='' and subseries_id='$sr_id' and passorfail != 'F' and passorfail != 'X' and passorfail != 'R';");


   } else {
    $sr_id = 0;
    $r['courseid'] = intval($r['courseid']);
    $name = $r['name'];
    $ceus = $r['ceus'];
    $type = ($r['certificate_override'] > 0 ? $r['certificate_override'] : $r['certificate_type']);
   }

   switch ($type) {
    case 2:
     $new = generate_certificate_ICC($r['userid'],$name,$r['datestarted'],$course_name);
     break;
    case 3:
     $new = generate_certificate_MedAide($r['userid'],$name,$r['datestarted'],5,$course_name);
     break;
    case 4:
     $new = generate_certificate_MedAide($r['userid'],$name,$r['datestarted'],10,$course_name);
     break;
    case 0:
     return false;
     break;
    default:
     $new = generate_certificate($r['userid'],$name,$r['datestarted'],$r['courseid'],$type,$ceus,$course_name);
   }

   if ($new) {
    $new2 = mysqlText($new);


    if ($sr_id) {
     $completed = max($completed,1);
     hm_query("UPDATE results".SITE_SUFFIX." SET certificate='$new2' WHERE idnum='$report_id' LIMIT 1;");
     hm_query("UPDATE results".SITE_SUFFIX." SET certificate='ignore' WHERE userid='".intval($r['userid'])."' and certificate='' and subseries_id='$sr_id' and passorfail != 'F' and passorfail != 'X' and passorfail != 'R' LIMIT $completed;");
     hm_query("UPDATE Assignments".SITE_SUFFIX." SET active='N' WHERE userid='".intval($r['userid'])."' and subseries_id='$sr_id' and active='C';");
    } else {
     hm_query("UPDATE results".SITE_SUFFIX." SET certificate='$new2' WHERE idnum='$report_id' LIMIT 1;");
    }

    return $path.$new;
   }
  }
 }

 return false;
}

function generate_certificate($user_id,$name,$date,$course_id,$type,$ceus = 1,$course_name = '') {

 $user_id = intval($user_id);
 $course_id = intval($course_id);
 $type = intval($type);
 if ($user_id < 1 || $course_id < 1) return false;
 if ($course_name) $coursename = $course_name;
 else $coursename = hm_cleanInput(hm_result("SELECT coursename FROM Courses WHERE c_id='$course_id' LIMIT 1;"),'z','15');
 $backer = hm_result("SELECT cert_image FROM certificates WHERE cert_id='$type' LIMIT 1;");
 if (strlen($backer) < 2) $backer = 'FA_Certificate_One_CEU.jpg';

 if ($date && strtotime($date) < 946702800) $date = (date('m/d/y'));
 else $date = date('m/d/y',strtotime($date));
 $pre = substr($user_id,0,1);
 $filename = 'Certificate_'.$coursename.'_'.date('Y-m-d',strtotime($date)).'_'.genCode(6).'.pdf';
 $credit = $ceus.' CEU Credit';

 $vars = array('backer' => $backer,'name' => $name,'coursename' => $coursename,'credit' => $credit,'date' => $date);

 $pdf = generate_certificate_pdf($vars);
 save_certificate($pdf,$pre,$filename);

 return $filename;
}

function generate_certificate_pdf($vars,$pdf = '') {

 $positioning = 0;
 $backer = (isset($vars['backer']) ? $vars['backer'] : false);
 $name = (isset($vars['name']) ? $vars['name'] : false);
 $coursename = (isset($vars['coursename']) ? $vars['coursename'] : false);
 $credit = (isset($vars['credit']) ? $vars['credit'] : false);
 $date = (isset($vars['date']) ? $vars['date'] : false);
 $length = strlen($name);

 if (!is_a($pdf,'FPDF')) $pdf = new FPDF('L','mm','Letter');

 $pdf->AddPage('L','Letter');
 $pdf->Image('certificates/'.$backer,0,0,279.4);
 $pdf->AddFont('LucidaCalligraphy-Italic','','LCALLIG.php');
 $pdf->SetFont('LucidaCalligraphy-Italic','',($length < 32 ? 32 : 24));
 $pdf->SetTextColor(0,0,0);

 $pdf->SetXY(64,90);
 $pdf->Cell(160,20,pdfOut($name),$positioning,0,'C');
 $pdf->SetXY(64,126);
 $pdf->Cell(160,20,pdfOut($coursename),$positioning,0,'C');

 $pdf->SetFont('LucidaCalligraphy-Italic','',16);
 $pdf->SetXY(201,147);
 $pdf->Cell(50,12,pdfOut($credit),$positioning,0,'C');
 $pdf->SetXY(201,169.5);
 $pdf->Cell(50,12,pdfOut($date),$positioning,0,'C');

 return $pdf;
}

function generate_certificate_MedAide($user_id,$name,$date,$hour = '5',$course_name = '') {

 $user_id = intval($user_id);
 if ($user_id < 1) return false;

 if ($date && strtotime($date) < 946702800) $date = (date('m/d/y'));
 else $date = date('m/d/y',strtotime($date));
 $pre = substr($user_id,0,1);
 $filename = 'MedAide_Certificate_'.date('Y-m-d',strtotime($date)).'_'.genCode(8).'.pdf';

 $vars = array('hour' => $hour,'name' => $name,'date' => $date);

 $pdf = generate_certificate_MedAide_pdf($vars);
 save_certificate($pdf,$pre,$filename);

 return $filename;
}

function generate_certificate_MedAide_pdf($vars,$pdf = '') {

 $positioning = 0;
 $hour = (isset($vars['hour']) ? $vars['hour'] : false);
 $name = (isset($vars['name']) ? $vars['name'] : false);
 $date = (isset($vars['date']) ? $vars['date'] : false);
 $length = strlen($name);

 if (!is_a($pdf,'FPDF')) $pdf = new FPDF('P','mm','Letter');

 $pdf = new FPDF('P','mm','Letter');
 $pdf->AddPage('P','Letter');
 $pdf->Image('certificates/MedAide_Certificate.png',0,0,215.9);
 $pdf->AddFont('Times','B');
 $pdf->AddFont('LucidaCalligraphy-Italic','','LCALLIG.php');
 $pdf->SetTextColor(0,0,0);

 $pdf->SetFont('Times','B',18);
 $pdf->SetXY(111.3,49.051);
 $pdf->Cell(25,12,pdfOut($hour.'-Hour'),$positioning,0,'R');

 $pdf->SetFont('LucidaCalligraphy-Italic','',($length < 32 ? 30 : 22));
 $pdf->SetXY(27.95,88);
 $pdf->Cell(160,20,pdfOut($name),$positioning,0,'C');

 $pdf->SetFont('LucidaCalligraphy-Italic','',24);
 $pdf->SetXY(85,168.2);
 $pdf->Cell(83,12,pdfOut($date),$positioning,0,'C');

 return $pdf;
}

function generate_certificate_ICC($user_id,$name,$date,$course_name = '') {

 $user_id = intval($user_id);
 if ($user_id < 1) return false;

 if ($date && strtotime($date) < 946702800) $date = (date('m/d/y'));
 else $date = date('m/d/y',strtotime($date));
 $pre = substr($user_id,0,1);
 $filename = 'ICC_Certificate_'.date('Y-m-d',strtotime($date)).'_'.genCode(8).'.pdf';

 $vars = array('name' => $name,'date' => $date);

 $pdf = generate_certificate_ICC_pdf($vars);
 save_certificate($pdf,$pre,$filename);

 return $filename;
}

function generate_certificate_ICC_pdf($vars,$pdf = '') {

 $positioning = 0;
 $name = (isset($vars['name']) ? $vars['name'] : false);
 $date = (isset($vars['date']) ? $vars['date'] : false);
 $length = strlen($name);

 if (!is_a($pdf,'FPDF')) $pdf = new FPDF('P','mm','Letter');

 $pdf->AddPage('P','Letter');
 $pdf->Image('certificates/Infection_Control_Certificate.png',0,0,215.9);
 $pdf->AddFont('LucidaCalligraphy-Italic','','LCALLIG.php');
 $pdf->SetFont('LucidaCalligraphy-Italic','',($length < 32 ? 32 : 24));
 $pdf->SetTextColor(0,0,0);

 $pdf->SetXY(27.95,67);
 $pdf->Cell(160,20,pdfOut($name),$positioning,0,'C');

 $pdf->SetFont('LucidaCalligraphy-Italic','',24);
 $pdf->SetXY(85,153);
 $pdf->Cell(83,12,pdfOut($date),$positioning,0,'C');

 return $pdf;
}

function save_certificate($pdf,$pre,$filename) {

 if (!is_a($pdf,'FPDF')) return false;

 if (file_exists(PROTECTED_ACCESS_FOLDER.'/'.SITE_ID.'/certificates/'.$pre.'/') === false) mkdir(PROTECTED_ACCESS_FOLDER.'/'.SITE_ID.'/certificates/'.$pre.'/',0777,1);
 $pdf->Output(PROTECTED_ACCESS_FOLDER.'/'.SITE_ID.'/certificates/'.$pre.'/'.$filename,'F');

 return true;
}

function display_certificate($pdf,$filename = 'certificate.pdf') {

 if (!is_a($pdf,'FPDF')) return false;

 $pdf->Output($filename,'I');

 return true;
}

function print_users_certificates($user_id,$date_from = '',$date_to = '') {
 $user_id = intval($user_id);
 $date_range = ($date_from && valid_date($date_from) ? " and r.datestarted >= '$date_from 00:00:00'" : '');
 $date_range .= ($date_to && valid_date($date_to) ? " and r.datestarted <= '$date_to 23:59:59'" : '');

 if ($user_id < 1) return false;

 $q = hm_query("SELECT r.userid,r.courseid,r.datestarted,r.ceus,r.passorfail,r.report,r.certificate,r.subseries_id,r.certificate_override,u.`name`,c.certificate_type,c.coursename FROM results".SITE_SUFFIX." r LEFT JOIN users".SITE_SUFFIX." u ON (u.userid = r.userid) LEFT JOIN Courses c ON (r.courseid=c.c_id) WHERE r.userid='$user_id'$date_range;");

 if (hm_cnt($q) < 1) return false;

 $pdf = null;

 while ($r = hm_fetch($q)) {

  if ($r['subseries_id'] < 1 && $r['certificate_type'] < 1 && $r['certificate_override'] < 1) continue;
  if ($r['certificate'] == 'ignore') continue;

  if ($r['passorfail'] != 'F' && $r['passorfail'] != 'X' && $r['passorfail'] != 'R') {

   $sr_id = 0;
   $type = 0;
   $name = $r['name'];
   $date = date('m/d/y',strtotime($r['datestarted']));
   $coursename = $r['coursename'];

   if ($r['subseries_id'] > 0) {
    $sr_id = intval($r['subseries_id']);
    if ($r['certificate_override'] > 0) $type = $r['certificate_override'];
    else $type = hm_result("SELECT sr_certificate FROM series_groups WHERE sr_id='$sr_id' LIMIT 1;",1);
   }

   if ($sr_id > 0 && $type > 0) {

    $incomplete = hm_result("SELECT count(*) FROM Assignments".SITE_SUFFIX." WHERE userid='".intval($r['userid'])."' and subseries_id='$sr_id' and active='Y';");
    if ($incomplete > 0) continue;

    $completed = hm_result("SELECT count(*) FROM Assignments".SITE_SUFFIX." WHERE userid='".intval($r['userid'])."' and subseries_id='$sr_id' and active='C';");
    if ($completed < 1) continue;

    //    $completed = hm_query("SELECT  FROM Assignments".SITE_SUFFIX." WHERE userid='".intval($r['userid'])."' and subseries_id='$sr_id' and active='C' ORDER BY series_sort DESC LIMIT 1;");
    $course_name = hm_result("SELECT sr_name FROM series_groups WHERE sr_id='$sr_id' LIMIT 1;");
    $ceus = hm_result("SELECT SUM(ceus) FROM results".SITE_SUFFIX." WHERE userid='".intval($r['userid'])."' and certificate='' and subseries_id='$sr_id' and passorfail != 'F' and passorfail != 'X' and passorfail != 'R';");


   } else {
    $sr_id = 0;
    $r['courseid'] = intval($r['courseid']);
    $ceus = $r['ceus'];
    $type = ($r['certificate_override'] > 0 ? $r['certificate_override'] : $r['certificate_type']);
   }


   switch ($type) {
    case 2:
     $vars = array('name' => $name,'date' => $date);
     $pdf = generate_certificate_ICC_pdf($vars,$pdf);
     break;
    case 3:
     $vars = array('hour' => 5,'name' => $name,'date' => $date);
     $pdf = generate_certificate_MedAide_pdf($vars,$pdf);
     break;
    case 4:
     $vars = array('hour' => 10,'name' => $name,'date' => $date);
     $pdf = generate_certificate_MedAide_pdf($vars,$pdf);
     break;
    case 0:
     return false;
     break;
    default:
     $backer = hm_result("SELECT cert_image FROM certificates WHERE cert_id='$type' LIMIT 1;");
     if (strlen($backer) < 2) $backer = 'FA_Certificate_One_CEU.jpg';
     $credit = $ceus.' CEU Credit';

     $vars = array('backer' => $backer,'name' => $name,'coursename' => $coursename,'credit' => $credit,'date' => $date);
     $pdf = generate_certificate_pdf($vars,$pdf);
   }
  }
 }

 $filename = 'Certificates-'.trim(preg_replace('/[_]+/','_',preg_replace('/[^a-zA-Z0-9]/', '_', $subject)),'_').'_'.date('y-m-d_H-i-s').'pdf';

 display_certificate($pdf,$filename);
 exit();

}