<?php
function mysqlText($text) { // mysqlText v2.0
 global $db;
 if (get_magic_quotes_gpc()) $text = stripslashes($text);
 return mysqli_real_escape_string($db,$text);
}
function hm_connect($host, $user, $pass, $dbname = '') { // hm_connect v1.0
 global $db;
 $db = mysqli_connect($host,$user,$pass,$dbname) or hm_error('',0,'MySQL Connection Error');
 mysqli_set_charset($db,CHARACTER_ENC);
}
function hm_error($q, $errno, $error) { // hm_error v1.0
 die("<b>$errno - $error<br><br>$q</b>");
}
function hm_query($q) { // hm_query v2.0
 global $db;

 // if (strpos($q,' users ') !== false) echo $q;
 // if (strpos($q,' sess ') !== false) echo $q;
 // if (strpos($q,' Assignments ') !== false) echo $q;
 // if (strpos($q,' reports ') !== false) echo $q;
 $r = mysqli_query($db,$q) or hm_error($q, mysqli_errno($db), mysqli_error($db));
 return $r;
}
function hm_fetch($q, $numeric = false) { // hm_fetch v2.0
 if ($numeric) return mysqli_fetch_array($q,MYSQLI_NUM);
 return mysqli_fetch_assoc($q);
}
function hm_result($q, $int='0') { // hm_result v2.0
 $out = '';
 $r = hm_query($q);
 if ($r) {
  $s = hm_fetch($r,true);
  $out = $s[0];
  if ($int) $out = intval($out);
  hm_free($r);
 }
 return $out;
}
function hm_cnt($q) { // hm_cnt v2.0
 $r = mysqli_num_rows($q);
 if ($r > 0) mysqli_data_seek($q,0);
 return intval($r);
}
function hm_insert_id() { // hm_insert_id v1.0
 global $db;
 return mysqli_insert_id($db);
}
function hm_free($q) { // hm_free v1.0
 mysqli_free_result($q);
}
function hm_affected() { // hm_affected v1.0
 global $db;
 return mysqli_affected_rows($db);
}
function mysqlFieldExists($table,$field) { // hm_mysqlFieldExists v1.0
 $table = mysqlText($table);
 $field = mysqlText($field);
 $check = hm_query("SHOW FIELDS FROM $table WHERE field = '$field';");
 if (hm_cnt($check) > 0) return true;
 return false;
}
function hm_table_exists($table) { // hm_table_exists v1.0
 $q = hm_query("SHOW TABLES LIKE '".mysqlText($table)."';");
 if (hm_cnt($q) > 0) return true;
 return false;
}

/**
 * Cleans various types of input depending on the type specified.
 *
 * A type must be selected:
 * null - no character filtering
 * a - Alphanumeric [AZaz09]
 * d - Date [09-]
 * e - Email Address
 * f - Float [-1.23]
 * h - Hex [0F]
 * i - Integer [-123]
 * n - Numeric [09]
 * p - Phone [123-4567]
 * s - Server File [A-Za-z0-9!#'+-/^_`~.," ]
 * t - Text with Multiline option*
 * v - Vehicle Make/Model with Multiline option*
 * w - Website [A-Za-z0-9#%&+-=?^_~.]
 * z - ZIP/Postal [AZaz09- ]
 *
 * *Set multiline to true to prevent stripping line endings and tabs.
 *
 * @param mixed $in
 * @param char[null|a|d|e|f|h|n|p|s|t|w] $type
 * @param int $length
 * @param bool[t only] $multiline
 * @return mixed
 * @version 1.43
 * @copyright 2009-2015 H.O.W.D.Y. Media modded
 */
function hm_cleanInput($in,$type,$length = '',$multiline = '') {
 $in = stripslashes(trim($in));
 switch ($type) {
  case '': $filter = '//'; break;
  case 'a': $filter = '/[^A-Za-z0-9]/'; break;
  case 'd':
   $in = trim(preg_replace('/[^0-9]/','-',$in),'-');
   $filter = '//';
   break;
  case 'e': $filter = '/[^A-Za-z0-9\!\#\$\%\&\\\'\*\+\-\/\=\?\^\_\`\{\|\}\~\.\@\(\)]/'; break;
  case 'f': $filter = '/[^0-9\.]/'; if (strpos($in,'-') === 0) $neg = 1; break;
  case 'h': $filter = '/[^0-9a-fA-F]/'; break;
  case 'i': $filter = '/[^0-9]/'; if (strpos($in,'-') === 0) $neg = 1; break;
  case 'n': $filter = '/[^0-9]/'; break;
  case 'p': break;
  case 's': $filter = '/[^A-Za-z0-9\!\#\'\+\-\/\^\_\`\~\.\,\"\s]/'; break;
  case 't':
   if ($multiline == false) $in = str_replace(array("\r","\n","\t"),' ',$in);
   $filter = '/[^\p{L}\p{N}\~\`\!\@\#\$\%\^\&\*\(\)\_\-\+\=\{\}\[\]\\\|\:\;\"\'\?\/\<\>\.\,\s]/u';
   break;
  case 'v':
   if ($multiline == false) $in = str_replace(array("\r","\n","\t"),' ',$in);
   $filter = '/[^\p{L}\p{N}\-\+\"\'\.\,\s]/u';
   break;
  case 'w': $filter = '/[^A-Za-z0-9\#\%\&\+\-\=\?\^\_\~\.]/'; break;
  case 'z': $filter = '/[^A-Za-z0-9\-\s]/'; break;
  default:
   $filter = '//';
 }

 if ($type == 'p') {
  if (strpos($in,'+') === 0) $plus = 1;
  $in = preg_replace('/[^0-9]/','-',$in);
  $in = trim(preg_replace('/[\-]+/','-',$in),'-');
  if ($plus) $in = '+'.$in;
 }
 elseif ($type == 'd') {}
 else $in = preg_replace($filter,'',$in);
 if (isset($neg)) $in = '-'.$in;
 if ($length > 0) $in = mb_substr($in,0,$length);
 return $in;
}

function err($error,$section = 'GLOBALERROR',$good = 0) { // HM-err v1.6
 $good = intval($good);
 if ($good < 0 || $good > 2) $good = 0;
 $_SESSION['errorbuffer'][$section][] = array($error,$good,time());
}
function errOut($section = 'GLOBALERROR') { // HM-errOut v1.6
 if (is_array($_SESSION['errorbuffer'][$section]) && count($_SESSION['errorbuffer'][$section]) > 0) {
  $out = '<div class="hm_errors '.strtolower($section).'">';
  $tally = array();
  foreach ($_SESSION['errorbuffer'][$section] as $e) {
   if ((time() - $e[2]) < 600) {
    $hash = md5($e[0].$e[1]);
    if (array_search($hash,$tally) !== false) continue;
    $tally[] = $hash;
    $out .= '<div class="'.($e[1] == 1 ? 'hmgood' : ($e[1] == 2 ? 'hmwarn' : 'hmerror')).'">'.$e[0].'</div>
';
   }
  }
  unset ($_SESSION['errorbuffer'][$section]);
  echo $out.'</div>';
 }
}
function errCnt($section = 'GLOBALERROR',$all_messages = false) { // HM-errCnt v1.6
 $ecnt = 0;
 if (is_array($_SESSION['errorbuffer'][$section])) {
  if ($all_messages) return count($_SESSION['errorbuffer'][$section]);
  foreach ($_SESSION['errorbuffer'][$section] as $e) {
   if ($e[1] < 1) $ecnt++;
  }
 }
 return $ecnt;
}
function errClear($section = 'GLOBALERROR') { // HM-errClear v1.0
 unset ($_SESSION['errorbuffer'][$section]);
}
function hm_redirect($path) { // HM_redirect v1.0
 header('Location: '.$path);
 exit();
}
function prepareInput($in) {
 $in = str_replace('&nbsp;',' ',$in);
 $in = preg_replace('/[\s]+/',' ',$in);
 $in = trim($in);
 return $in;
}
function cleanOutput($in,$nl2br = false,$blank_fill = '') { // HM-cleanOutput v1.0 modded
 if ($nl2br) $in = nl2br($in);
 if (strlen($in) === 0) $in = ($blank_fill === 1 || $blank_fill === true ? '<undefined>' : $blank_fill);
 return htmlentities($in,ENT_COMPAT,'ISO-8859-1');
}
function genCode($length = 64,$salt = null) { // HM-genCode v1.0
 if (strlen($salt) < 2) $salt = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
 $saltlen = strlen($salt)-1;
 for ($i = 0;$i < $length;$i++) {
  $out .= substr($salt,rand(0,$saltlen),1);
 }
 return $out;
}
function optOut($array,$set='',$none='',$match_value=''){ // HM-optOut v2.3
 if (is_array($array)){
  if (is_array($set)) {} else $set = array($set);
  if ($none) $out = '<option value=""'.(count($set) ? '':' selected').'>'.$none.'</option>';
  foreach ($array as $id => $val){
   $match = ($match_value ? $val:$id);
   $out .= '<option value="'.$match.'"'.(count($set) && in_array($match,$set) ? ' selected':'').'>'.$val.'</option>';
  }
  return $out;
 }
}
function hm_checker($value,$set_array,$default = false) { // HM-checker v1.1
 $set_array = (array)$set_array;
 if (in_array($value,$set_array) !== false || ($value == '' && $default)) echo ' checked="checked"';
}

function smail($to,$title,$content,$from = '',$headers_array = ''){ // HM-smail v1.3
 $to = trim($to);
 $title = trim($title);
 $from = trim($from);

 if (is_array($to) !== true) $to = array($to);
 if (count($to) > 0 && $title && $content) {
  $headfrom = false;
  if (is_array($headers_array) && count($headers_array) > 0) {
   foreach ($headers_array as $h) {
    if (strtolower(substr($h,0,5)) == 'from:') $headfrom = true;
   }
  } else {
   $headers_array = array('X-Mailer: '.$_SERVER['HTTP_HOST'].' Mailer','MIME-Version: 1.0','Content-Type: text/html; charset=utf-8','Content-Transfer-Encoding: 8bit');
  }
  if ($headfrom === false) {
   if (strlen($from) === 0) $from = 'NoReply@'.$_SERVER['HTTP_HOST'];
   $headers_array = array_merge(array('From: '.$from),$headers_array);
  }
  $headers = implode("\r\n",$headers_array)."\r\n\r\n";

  foreach ($to as $t) {
   mail($t, $title, $content, $headers);
  }
  return true;
 }
 return false;
}
function validEmail($email) {
 $isValid = true;
 $atIndex = strrpos($email, "@");
 if (is_bool($atIndex) && !$atIndex) { $isValid = false; }
 else {
  $domain = substr($email, $atIndex+1);
  $local = substr($email, 0, $atIndex);
  $localLen = strlen($local);
  $domainLen = strlen($domain);
  if ($localLen < 1 || $localLen > 64) { $isValid = false; }
  else if ($domainLen < 1 || $domainLen > 255) { $isValid = false; }
  else if ($local[0] == '.' || $local[$localLen-1] == '.') { $isValid = false; }
  else if (preg_match('/\\.\\./', $local)) { $isValid = false; }
  else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) { $isValid = false; }
  else if (preg_match('/\\.\\./', $domain)) { $isValid = false; }
  else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',str_replace("\\\\","",$local))) {
   if (!preg_match('/^"(\\\\"|[^"])+"$/',str_replace("\\\\","",$local))) { $isValid = false; }
  }
  if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) { $isValid = false; }
 }
 return $isValid;
}
function hm_encrypt($data,$key = ENCRYPTION_KEY) {
 $key = hash('SHA256',$key,true);
 srand();
 $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
 $iv_base64 = rtrim(base64_encode($iv),'=');
 if (strlen($iv_base64) != 22) return false;
 return $iv_base64.base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128,$key,$data,MCRYPT_MODE_CBC,$iv));
}

function hm_decrypt($data,$key = ENCRYPTION_KEY) {
 $key = hash('SHA256',$key,true);
 $iv = base64_decode(substr($data, 0, 22).'==');
 $data = base64_decode(substr($data, 22));
 return @mcrypt_decrypt(MCRYPT_RIJNDAEL_128,$key,$data,MCRYPT_MODE_CBC,$iv);
}
function hm_out($message,$active = false) {
 if (DEBUG_MODE !== true && $active === false) return;
 if (is_array($message)) {
  echo '<pre style="width:95%">';
  print_r($message);
  echo '</pre>';
 }
 else echo '<p>'.$message.'</p>';
}
function rmdir_recursive($dir) { // HM-rmdir_recursive v1.0
 if (is_dir($dir)) {
  $files = scandir($dir);
  foreach ($files as $file) {
   if ($file != '.' && $file != '..') {
    if (filetype($dir.'/'.$file) == 'dir') rmdir_recursive($dir.'/'.$file);
    else unlink($dir.'/'.$file);
   }
  }
  reset($files);
  rmdir($dir);
 }
}
function hm_upFile($upfile,$folder = 'uploads',$allowed = array('jpg','jpeg','gif','png'),$prefix = '',$filename = '',$error_handler = 'uploader') { // HM-upFile v2.3
 if ($_FILES[$upfile]['name']) {
  if (!is_array($allowed)) $allowed = array($allowed);
  $ext = strtolower(explodeEnd('.',$_FILES[$upfile]['name']));
  if (array_search($ext,$allowed) === false) {
   err('Upload Failed: Unsupported File Type.',$error_handler);
   return array(0,'',0);
  }
  if ($_FILES[$upfile]['error'] > 0) {
   switch ($_FILES[$upfile]['error']) {
    case 1: err('Upload Failed: File is too big.',$error_handler); break;
    case 2: err('Upload Failed: File is too big.',$error_handler); break;
    case 3: err('Upload Failed: File only partially uploaded.',$error_handler); break;
    case 4: err('Upload Failed: No file was uploaded.',$error_handler); break;
    case 6: err('Upload Failed: Unable to allocate temporary space.',$error_handler); break;
    case 7: err('Upload Failed: Unable to write to temporary space.',$error_handler); break;
    case 8: err('Upload Failed: File extension is not permitted.',$error_handler); break;
    default: err('Upload Failed: Unknown Error.',$error_handler);
   }
   return array(0,'',0);
  } else {
   $result = false;
   if ($_FILES[$upfile]['size'] > 0) {
    if ($filename) $file = $filename;
    else $file = $prefix.basename($_FILES[$upfile]['name']);
    $path = hm_uniqueFilename($folder.'/'.$file);
    if (move_uploaded_file($_FILES[$upfile]['tmp_name'], $path)) {
     $filesize = $_FILES[$upfile]['size'];
     $result = true;
     $file = basename($path);
    } else err('Upload Failed: Couldn\'t move file.',$error_handler);
   } else err('Upload Failed: Empty file uploaded.',$error_handler);
   return array($result,$file,$filesize);
  }
 }
 return array(0,'',0);
}
function resizeimg($maxwidth, $maxheight, $image, $quality='',$error_handler = 'GLOBALERROR',$allow_transparent = false) { // HM-resizeimg v1.5
 if ($maxwidth < 1) $maxwidth=9999;
 elseif ($maxheight < 1) $maxheight=9999;
 $img = null;
 $dot = strrpos($image,'.');
 $file = substr(substr($image,0,$dot),0,24).'-'.$maxwidth.'x'.$maxheight;
 $ext = substr($image,$dot+1);
 if ($ext == 'jpeg') $ext = 'jpg';
 if ($ext == 'jpg') $img = @imagecreatefromjpeg($image);
 elseif($ext == 'gif') $img = @imagecreatefromgif($image);
 elseif($ext == 'png') $img = @imagecreatefrompng($image);
 else err("Invalid Image Type - Can't resize",$error_handler);

 if ($img) {
  $width = imagesx($img);
  $height = imagesy($img);
  $scale = min($maxwidth/$width, $maxheight/$height);
  if ($scale < 1) {
   $new_width = floor($scale*$width);
   $new_height = floor($scale*$height);
   $new_img = @imagecreatetruecolor($new_width, $new_height);
   if ($ext != 'jpg') {
    if ($allow_transparent) $color = imagecolortransparent($new_img, imagecolorallocatealpha($new_img, 0, 0, 0, 127));
    else $color = imagecolortransparent($new_img, imagecolorallocate($new_img, 255, 255, 255));
    imagefill($new_img, 0, 0, $color);
    imagesavealpha($new_img, true);
   }
   imagecopyresampled($new_img,$img,0,0,0,0,$new_width,$new_height,$width,$height);
   imagedestroy($img);
   $img = $new_img;
  } else {
   return basename($image);
  }
  if ($quality < 1 || $quality > 100) $quality = 50;
  switch ($ext) {
   case $ext === 'jpg':
    imagejpeg($img,$file.'.'.$ext,$quality);
    err("Resized from $width x $height to $new_width x $new_height successfully.",$error_handler,1);
    break;
   case $ext === 'gif':
    imagegif($img,$file.'.'.$ext);
    err("Resized from $width x $height to $new_width x $new_height successfully.",$error_handler,1);
    break;
   case $ext === 'png':
    imagepng($img,$file.'.'.$ext);
    err("Resized from $width x $height to $new_width x $new_height successfully.",$error_handler,1);
    break;
  }
  return basename($file.'.'.$ext);
 }
 return false;
}
function explodeEnd ($separator,$str) { // HM-explodeEnd v1.0
 return strrev(current(explode($separator,strrev($str),2)));
}
function hm_uniqueFilename($file,$inc = 0) { // HM-uniqueFilename v1.0
 if ($inc > 200) return date("Y-m-d_H-i-s").'_'.substr(md5(time()),0,6).'_'.$file;
 if (file_exists($file)) {
  list($ext,$file) = explode('.',strrev($file),2);
  $ext = strrev($ext);
  if (strpos($file,'_') !== false) {
   list($inc2,$file2) = explode('_',$file,2);
   if (is_numeric($inc2) && preg_match('/[^0-9]+/',$inc2) === 0) {
    $inc = strrev($inc2);
    $file = $file2;
   }
  }
  $file = strrev($file);
  $inc++;
  return hm_uniqueFilename($file.'_'.$inc.'.'.$ext);
 }
 return $file;
}

/**
 * Stores in a session variable POST data. Also optionally cleans various types of input depending on the type specified.
 *
 * A type must be selected:
 * null - no character filtering
 * a - Alphanumeric [AZaz09]
 * d - Date [09-]
 * e - Email Address
 * f - Float [-1.23]
 * h - Hex [0F]
 * i - Integer [-123]
 * n - Numeric [09]
 * p - Phone [123-4567]
 * s - Server File [A-Za-z0-9!#'+-/^_`~.," ]
 * t - Text with Multiline option*
 * w - Website [A-Za-z0-9#%&+-=?^_~.]
 * z - ZIP/Postal [AZaz09- ]
 *
 * *Set multiline to true to prevent stripping line endings and tabs.
 *
 * @param mixed $field
 * @param string $section
 * @param char[null|a|d|e|f|h|n|p|s|t|w] $type
 * @param int $length
 * @param bool[t only] $multiline
 * @return string
 * @version 1.0
 * @copyright 2016 H.O.W.D.Y. Media
 */
function persistent_field($field,$section = 'GLOBAL',$type = '', $length = '', $multiline = false) {
 if (isset($_POST[$field])) {
  if ($type == 'd') $_SESSION['forms'][$section][$field] = cleanDate($_POST[$field]);
  else $_SESSION['forms'][$section][$field] = hm_cleanInput($_POST[$field],$type,$length,$multiline);
 }
 if (isset($_SESSION['forms'][$section][$field])) return $_SESSION['forms'][$section][$field];
 return '';
}

function update_persistent_field($field,$section = 'GLOBAL',$value) {
 $_SESSION['forms'][$section][$field] = $value;
}

function destroy_persistent_field($field,$section = 'GLOBAL') {
 if (isset($_SESSION['forms'][$section][$field])) unset($_SESSION['forms'][$section][$field]);
}

function destroy_persistent_section($section = 'GLOBAL') {
 if (isset($_SESSION['forms'][$section])) unset($_SESSION['forms'][$section]);
}

function cleanOutputDate($in,$time = false) { // HM_cleanOutputDate v1.0
 if ($in == '' || $in == '0000-00-00 00:00:00') return '';
 return date('m/d/Y'.($time ? ' h:i:s a' : ''),strtotime($in));
}

function cleanDate($in) {
 if ($in == 'mm/dd/yyyy' || $in == '') return '';
 return date('Y-m-d',strtotime($in));
}

/* Custom Functions */

function publicPage() {
 define('PUBLIC_PAGE',1);
}
function accessLevel($level) {
 global $levels;

 if (!isset($levels[$level])) $level = 0;
 if ($level == 1 && SITE_ID != 0) hm_redirect(MASTER_URL);
 define('ACCESS_LEVEL',$level);
}
function is_logged_in() {
 if ($_SESSION['siteuser']['userid'] > 0) return true;
 return false;
}
function getSiteID($cronjob_mode = false) {
 if ($cronjob_mode === true) return 0;
 $current = $_SERVER['HTTP_HOST'];
 if (substr($current,0,4) == 'www.') $current = substr($current,4);
 if ($current == MASTER_SITE) return 0;
 list($host,$ggg) = explode('.',$current,2);
 $host = mysqlText($host);
 $id = hm_result("SELECT site_id FROM sites WHERE site_host='$host' LIMIT 1;",1);
 if ($id > 0) return $id;
 return -1;
}
function getSiteSlug($site_id = 0) {
 $site_id = intval($site_id);
 if ($site_id < 1) $site_id = SITE_ID;
 if ($site_id < 1) return '';
 return strtolower(hm_result("SELECT site_host FROM sites WHERE site_id='$site_id' LIMIT 1;"));
}
function deleteSite($site_id) {
 $site_id = intval($site_id);
 $check = hm_result("SELECT site_id FROM sites WHERE site_id='$site_id' LIMIT 1;",1);
 if ($check > 0) {
  hm_query("DROP TABLE IF EXISTS compliance_$site_id;");
  hm_query("DROP TABLE IF EXISTS groups_$site_id;");
  hm_query("DROP TABLE IF EXISTS group_users_$site_id;");
  hm_query("DROP TABLE IF EXISTS sess_$site_id;");
  hm_query("DROP TABLE IF EXISTS users_$site_id;");
  hm_query("DROP TABLE IF EXISTS results_$site_id;");
  hm_query("DROP TABLE IF EXISTS Assignments_$site_id;");
  hm_query("DELETE FROM sites WHERE site_id='$site_id' LIMIT 1;");

  rmdir_recursive(PROTECTED_ACCESS_FOLDER.'/'.$site_id);
 }
 return -1;
}
function createSite($site_host) {
 global $createSite_loop_protection;
 $createSite_loop_protection++;
 if ($createSite_loop_protection > 20) return -4;

 $site_host_cleaned = hm_cleanInput($site_host,'a',32);
 if ($site_host != $site_host_cleaned) return -1;
 if (strlen($site_host_cleaned) < 2 || strlen($site_host_cleaned) > 32) return -2;

 $check = hm_result("SELECT site_id FROM sites WHERE site_host='$site_host_cleaned' LIMIT 1;");
 if ($check > 0) return -5;

 hm_query("INSERT INTO sites (site_id,site_host) VALUES (NULL,'$site_host_cleaned');");
 $site_id = hm_insert_id();
 if ($site_id < 1) return -3;

 $check = hm_query("SHOW TABLES LIKE '%_$site_id';");
 if (hm_cnt($check) > 0) {
  hm_query("DELETE FROM sites WHERE site_id='$site_id' LIMIT 1;");
  return createSite($site_host_cleaned);
 }

 hm_query("CREATE TABLE Assignments_$site_id LIKE Assignments;");
 hm_query("CREATE TABLE results_$site_id LIKE results;");
 hm_query("CREATE TABLE sess_$site_id LIKE sess;");
 hm_query("CREATE TABLE users_$site_id LIKE users;");
 hm_query("CREATE TABLE groups_$site_id LIKE groups;");
 hm_query("CREATE TABLE group_users_$site_id LIKE group_users;");
 hm_query("CREATE TABLE compliance_$site_id LIKE compliance;");
 hm_query("INSERT INTO groups_$site_id (SELECT * FROM groups);");

 recurse_copy('theme-base','themes/'.$site_host_cleaned);

 createResourceFolders($site_id);

 return $site_id;
}
function checkSite($site_id) {
 $site_id = intval($site_id);
 $check = hm_result("SELECT site_host FROM sites WHERE site_id='$site_id' LIMIT 1;");
 if ($check) {
  if (file_exists('themes/'.$check) === false || file_exists(PROTECTED_ACCESS_FOLDER.'/'.$site_id) === false ||
  file_exists(PROTECTED_ACCESS_FOLDER.'/'.$site_id.'/a') === false) fixSite($site_id,1);
 }
 return false;
}
function fixSite($site_id,$site_id_valid) {
 $site_id = intval($site_id);
 $check = ($site_id_valid ? $site_id : hm_result("SELECT site_id FROM sites WHERE site_id='$site_id' LIMIT 1;",1));
 if ($check > 0) {
//  hm_out('Healed site');
  $site_host = hm_result("SELECT site_host FROM sites WHERE site_id='$site_id' LIMIT 1;");
  if (!hm_table_exists('Assignments_'.$site_id)) hm_query("CREATE TABLE Assignments_$site_id LIKE Assignments;");
  if (!hm_table_exists('results_'.$site_id)) hm_query("CREATE TABLE results_$site_id LIKE results;");
  if (!hm_table_exists('sess_'.$site_id)) hm_query("CREATE TABLE sess_$site_id LIKE sess;");
  if (!hm_table_exists('users_'.$site_id)) hm_query("CREATE TABLE users_$site_id LIKE users;");
  if (!hm_table_exists('groups_'.$site_id)) {
   hm_query("CREATE TABLE groups_$site_id LIKE groups;");
   hm_query("INSERT INTO groups_$site_id (SELECT * FROM groups);");
  }
  if (!hm_table_exists('group_users_'.$site_id)) hm_query("CREATE TABLE group_users_$site_id LIKE group_users;");
  if (!hm_table_exists('compliance_'.$site_id)) hm_query("CREATE TABLE compliance_$site_id LIKE compliance;");

  recurse_copy('theme-base','themes/'.$site_host);

  createResourceFolders($site_id);

  $q = hm_query("SELECT userid,hash FROM users_$site_id WHERE (status=1 or status=2) and userlevel=10;");

  while ($r = hm_fetch($q)) {
  createUserAccess($r['userid'],$site_id);
 }

  return true;
 }
 return false;
}
function createUser($site_id,$username,$password,$level) {
 global $levels;

 $suffix = siteSuffix($site_id);
 $username = hm_cleanInput($username,'u',30);
 $password = hm_cleanInput($password,'x',41);
 $level = intval($level);
 if (!isset($levels[$level])) $level = 10;

 $check = hm_result("SELECT userid FROM users$suffix WHERE username='$username' LIMIT 1;");
 if ($check > 0) return false;

 hm_query("INSERT INTO users$suffix (userid,name,department,username,password,employeeid,status,userlevel,email) VALUES (NULL,'Manager','','$username','$password','','1','$level','');");
 $site_id = hm_insert_id();

 createUserAccess($user_id,$site_id);

 return $site_id;
}
function siteSuffix($site_id = 0) {
 global $suffix;
 if ($site_id > 0) return '_'.$site_id;
 if (SITE_ID > 0) return '_'.SITE_ID;
 return '';
}
function hm_header($page_title = '',$header_addin = '') {
 $slug = getSiteSlug();
 if ($slug) {
  $slug = 's/'.$slug;
  if (!file_exists('theme'.$slug.'/header.php')) $slug = '';
 }
 if ($header_addin) {
  if ($header_addin == 'jquery' || $header_addin == 'jqueryui') {
   if ($header_addin == 'jqueryui') $ui = true;
   $header_addin = '<script src="/scripts/jquery.js" type="text/javascript"></script>
';
   if ($ui = true) {
    $header_addin .= '<script src="/scripts/jquery-ui.custom.min.js" type="text/javascript"></script>
<link href="/css/ui-lightness/jquery-ui.custom.min.css" rel="stylesheet" type="text/css">
<script type="text/javascript">
$(function() {$(".datefield").datepicker();});
</script>
';
   }
  }
 }
 include('theme'.$slug.'/header.php');
}
function hm_footer() {
 $slug = getSiteSlug();
 if ($slug) {
  $slug = 's/'.$slug;
  if (!file_exists('theme'.$slug.'/footer.php')) $slug = '';
 }
 include('theme'.$slug.'/footer.php');
}
function hm_scriptName() {
 return trim(str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['SCRIPT_FILENAME']),'/');
}
function go_home($level = 0) {
 global $homes;

 $level = intval($level);
 if ($level < 1) $level = $_SESSION['siteuser']['userlevel'];

 if ($homes[$level]) hm_redirect($homes[$level]);

 unset($_SESSION['siteuser']['userid']);
 err('User level invalid. Please log in again.','userlogin');
 hm_redirect('/login.php');
}
function recurse_copy($src,$dst) {
 $dir = opendir($src);
 @mkdir($dst);
 while (false !== ($file = readdir($dir))) {
  if ($file != '.' && $file != '..') {
   if (is_dir($src.'/'.$file)) {
    recurse_copy($src.'/'.$file,$dst.'/'.$file);
   } else {
    copy($src.'/'.$file,$dst.'/'.$file);
   }
  }
 }
 closedir($dir);
}
function get_home_slug() {
 global $level_slugs;
 return $level_slugs[$_SESSION['siteuser']['userlevel']];
}
function get_level() {
 return intval($_SESSION['siteuser']['userlevel']);
}
function get_superadmin_count($user_id = 0) {
 return get_exclusive_count(1,$user_id);
}
function get_siteadmin_count($user_id = 0) {
 return get_exclusive_count(3,$user_id);
}
function get_manager_count($user_id = 0) {
 return get_exclusive_count(6,$user_id);
}
function get_user_count() {
 return hm_result("SELECT count(*) FROM users".SITE_SUFFIX." WHERE userlevel='10';",1);
}
function get_exclusive_count($level,$user_id = 0) {
 $level = intval($level);
 $user_id = intval($user_id);
 if ($user_id > 0) $addin = " and userid !='$user_id'";
 return hm_result("SELECT count(*) FROM users".SITE_SUFFIX." WHERE userlevel='$level'$addin;",1);
}
function checkUsername($username,$user_id = 0) {
 $user_id = intval($user_id);
 if ($user_id > 0) $addin = " and userid !='$user_id'";
 $username = mysqlText($username);
 return hm_result("SELECT count(*) FROM users".SITE_SUFFIX." WHERE username='$username'$addin;",1);
}
function get_user_groups($parents = false) {
 $groups = array();
 $q = hm_query("SELECT g_id".($parents ? ',g_parent_id':'').",g_name FROM groups".SITE_SUFFIX." WHERE ".($parents ? '1':'g_parent_id > 0')." ORDER BY g_name ASC;");
 if ($parents) {
 	$groups[$r['g_id']] = array('name' => $r['g_name'],'parent' => $r['g_parent_id']);
  return $groups;
 }

 while ($r = hm_fetch($q)) {
 	$groups[$r['g_id']] = $r['g_name'];
 }
 return $groups;
}
function get_user_group_parents() {
 $groups = array();
 $q = hm_query("SELECT g_id,g_name FROM groups".SITE_SUFFIX." WHERE g_parent_id=0 ORDER BY g_name ASC;");
 while ($r = hm_fetch($q)) {
 	$groups[$r['g_id']] = $r['g_name'];
 }
 return $groups;
}
function get_user_group_children($parent_id,$ids_only = false) {
 $parent_id = intval($parent_id);
 $groups = array();
 $q = hm_query("SELECT g_id,g_name FROM groups".SITE_SUFFIX." WHERE g_parent_id='$parent_id' ORDER BY g_name ASC;");
 while ($r = hm_fetch($q)) {
 	$groups[$r['g_id']] = $r['g_name'];
 	$keys[] = $r['g_id'];
 }
 return ($ids_only ? $keys : $groups);
}
function is_group_parent($id) {
 $id = intval($id);
  return (hm_result("SELECT g_parent_id FROM groups".SITE_SUFFIX." WHERE g_id='$id' LIMIT 1;") > 0 ? false : true);
}
function get_course_groups() {
 $groups = array();
 $q = hm_query("SELECT cg_id,cg_name FROM course_groups WHERE 1 ORDER BY cg_name ASC;");
 while ($r = hm_fetch($q)) {
 	$groups[$r['cg_id']] = $r['cg_name'];
 }
 return $groups;
}
function get_course_groupings($group_id,$course_field = 2) {
 $group_id = intval($group_id);
 $course_field = max(min(intval($course_field),2),0);
 $groups = array();
 $fields = array('cg_course_id','courseid','coursename');

 $q = hm_query("SELECT cg_course_id,coursename FROM course_groupings LEFT JOIN Courses ON (cg_course_id=c_id) WHERE cg_group_id='$group_id' ORDER BY cg_course_id ASC;");
 while ($r = hm_fetch($q)) {
 	$groups[$r['cg_course_id']] = $r[$fields[$course_field]];
 }
 return $groups;
}
function get_series_groups($parent_id = '') {
 $groups = array();
 if ($parent_id = '') $addin = '1';
 else {
  $parent_id = intval($parent_id);
  $addin = "sr_parent_id='$parent_id'";
 }
 $q = hm_query("SELECT sr_id,sr_parent_id,sr_name FROM series_groups WHERE $addin ORDER BY sr_name ASC;");
 while ($r = hm_fetch($q)) {
 	$groups[$r['sr_id']] = $r['sr_name'];
 }
 return $groups;
}
function get_series_groupings($group_id,$series_field = 2) {
 $group_id = intval($group_id);
 $series_field = max(min(intval($series_field),2),0);
 $groups = array();
 $fields = array('sr_course_id','courseid','coursename');

 $q = hm_query("SELECT sr_course_id,coursename FROM series_groupings LEFT JOIN Courses ON (sr_course_id=c_id) WHERE sr_group_id='$group_id' ORDER BY sr_grouping_sort ASC;");
 while ($r = hm_fetch($q)) {
 	$groups[$r['sr_course_id']] = $r[$fields[$series_field]];
 }
 return $groups;
}
function get_users_groups($user_id) {
 $user_id = intval($user_id);
 $groups = array();
 $q = hm_query("SELECT g_id,g_name FROM group_users".SITE_SUFFIX." LEFT JOIN groups".SITE_SUFFIX." ON (g_id = gu_group_id) WHERE gu_user_id='$user_id' ORDER BY g_name ASC;");
 while ($r = hm_fetch($q)) {
 	$groups[$r['g_id']] = $r['g_name'];
 }
 return $groups;
}
function set_users_groups($group_ids,$user_id) {
 $user_id = intval($user_id);
 $group_ids = (array)$group_ids;
 $set_ids = get_users_groups($user_id);
 $save_ids = array();

 foreach ($group_ids as $id => $gid) {
 	$group_ids[$id] = intval($group_ids[$id]);
 	if ($group_ids[$id] < 1) unset($group_ids[$id]);
 	elseif (in_array($group_ids[$id],array_keys($set_ids))) {
 	 $save_ids[] = $group_ids[$id];
 	 unset($group_ids[$id]);
 	}
 }

 $group_ids = array_unique($group_ids);
 $ids = '';
 if (count($save_ids) > 0) $ids = ' and gu_group_id NOT IN ('.mysqlText(implode(',',$save_ids)).')';

 hm_query("DELETE FROM group_users".SITE_SUFFIX." WHERE gu_user_id='$user_id'$ids;");

 if (count($group_ids) > 0) {
  foreach ($group_ids as $gid) {
   hm_query("INSERT INTO group_users".SITE_SUFFIX." (gu_group_id,gu_user_id) VALUES ('$gid','$user_id');");
  }
 }
 return true;
}
function delete_user($user_id,$site_id) {
 $user_id = intval($user_id);
 if ($user_id < 1) return false;

 $site_suffix = siteSuffix($site_id);

 hm_query("DELETE FROM sess$site_suffix WHERE sess_user_id='$user_id';");
 removeUserAccess($user_id,$site_id);
 hm_query("DELETE FROM users$site_suffix WHERE userid='$user_id';");
 hm_query("DELETE FROM group_users$site_suffix WHERE gu_user_id='$user_id';");
 hm_query("DELETE FROM results$site_suffix WHERE userid='$user_id';");
 hm_query("DELETE FROM Assignments$site_suffix WHERE userid='$user_id';");

 return true;
}
function set_mass_users_groups($group_id,$user_ids) {
 $group_ids = intval($group_ids);
 $user_ids = (array)$user_ids;

 foreach ($user_ids as $id => $uid) {
 	$user_ids[$id] = intval($user_ids[$id]);
 	if ($user_ids[$id] < 1) unset($user_ids[$id]);
 }

 $user_ids = array_unique($user_ids);
 $ids = mysqlText(implode(',',$user_ids));
 if (strlen($ids) < 1) $ids = 0;

 hm_query("DELETE FROM group_users".SITE_SUFFIX." WHERE gu_group_id='$group_id' and gu_user_id NOT IN ($ids);");

 if (count($user_ids) > 0) {
  foreach ($user_ids as $uid) {
  	hm_query("REPLACE INTO group_users".SITE_SUFFIX." (gu_group_id,gu_user_id) VALUES ('$group_id','$uid');");
  }
 }
 return true;
}
function dateOut($date) {
 return date('m/d/Y',strtotime($date));
}
function gradeOut($grade) {
 if ($grade == 'X') $grade = 'F';
 return cleanOutput($grade);
}
function courseName($in) {
 return ($in ? cleanOutput($in) : 'Pretest');
}
function getSiteIDs() {
 $out = array(0);
 $q = hm_query("SELECT site_id FROM sites;");
 while ($r = hm_fetch($q)) {
 	$out[] = $r['site_id'];
 }
 return $out;
}
function getSiteURL($site_id = 0) {
 $site_id = intval($site_id);
 if ($site_id < 1) return MASTER_URL.'/';
 return 'http://'.getSiteSlug($site_id).'.'.MASTER_SITE.'/';
}
function generateUnsubURL($user_id,$site_id = 0) {
 $user_id = intval($user_id);
 $site_id = intval($site_id);
 if ($site_id < 1 && SITE_ID > 0) $site_id = SITE_ID;
 if ($user_id < 1) return false;
 $encrypted = safeUrlEncode(hm_encrypt(json_encode(array('u' => $user_id, 's' => $site_id))));
 return getSiteURL($site_id).'unsubscribe.php?id='.$encrypted;
}
function decryptUnsubURL($in) {
 $out = json_decode(utf8_encode(trim(hm_decrypt(safeUrlDecode($in)))),true);
 if ($out === false || $out['u'] < 1) return false;
 return array('user_id' => $out['u'],'site_id' => $out['s']);
}
function safeUrlEncode($in) {
 return urlencode(str_replace(array('=','+','/'),array('-','_','.'),$in));
}
function safeUrlDecode($in) {
 return str_replace(array('-','_','.'),array('=','+','/'),urldecode($in));
}
function protectedURL() {
 return PROTECTED_ACCESS_FOLDER.'/'.SITE_ID.'/'.substr($_SESSION['siteuser']['hash'],0,1).'/'.$_SESSION['siteuser']['hash'].'/';
}
function createResourceFolders($site_id) {
 $char_list = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
 $chars = str_split($char_list);
 if (!file_exists(PROTECTED_ACCESS_FOLDER)) mkdir(PROTECTED_ACCESS_FOLDER);
 if (!file_exists(PROTECTED_ACCESS_FOLDER.'/'.$site_id)) mkdir(PROTECTED_ACCESS_FOLDER.'/'.$site_id);
 foreach ($chars as $char) {
  if (!file_exists(PROTECTED_ACCESS_FOLDER.'/'.$site_id.'/'.$char)) mkdir(PROTECTED_ACCESS_FOLDER.'/'.$site_id.'/'.$char);
 }
}
function createUserAccess($user_id,$site_id) {
 $user_id = intval($user_id);
 if ($user_id < 1) return false;

 $site_suffix = siteSuffix($site_id);
 $hash = genCode(24);
 $safety = 0;
 while (hm_result("SELECT count(*) FROM users$site_suffix WHERE hash='$hash' LIMIT 1;") > 0) {
  $safety++;
  if ($safety > 10) {
   $hash = '';
   hm_out('Failed to add hash for userid '.$r['userid']);
   break;
  }
  $hash = genCode(24);
 }
 if ($hash) hm_query("UPDATE users$site_suffix SET hash='$hash' WHERE userid='$user_id' LIMIT 1;");

 symlink(PROTECTED_FOLDER,PROTECTED_ACCESS_FOLDER.'/'.$site_id.'/'.substr($hash,0,1).'/'.$hash);
}
function removeUserAccess($user_id,$site_id) {
 $user_id = intval($user_id);
 if ($user_id < 1) return false;

 $site_suffix = siteSuffix($site_id);
 $hash = hm_result("SELECT hash FROM users$site_suffix WHERE userid='$user_id' LIMIT 1;");
 if ($hash) {
  $file = PROTECTED_ACCESS_FOLDER.'/'.$site_id.'/'.substr($hash,0,1).'/'.$hash;
  if (file_exists($file)) unlink($file);
 }

 hm_query("UPDATE users$site_suffix SET hash='' WHERE userid='$user_id' LIMIT 1;");
}
function sortHeader($title,$order_id,$sort_by,$query_addon = '',$order_set = '') {
 if ($query_addon && substr($query_addon,0,1) != '&') $query_addon = '&'.$query_addon;
 return '<a href="'.$_SERVER['PHP_SELF'].'?orderby'.$order_set.'='.$order_id.'&sortby'.$order_set.'='.(!$sort_by).$query_addon.'">'.$title.'</a>';
}
function get_default_compliance($course_ids,$default = 12) {
 $out = 0;
 $default = intval($default);
 if (is_array($course_ids)) {
  foreach ($course_ids as $cid) {
  	$out = max(hm_result("SELECT compliance_period FROM compliance".SITE_SUFFIX." WHERE course_id='$cid' LIMIT 1;"),$out);
  }
 }
 if ($out > 0) return $out;
 return ($default ? $default : 12);
}
function get_certificate_types($none = false) {
 $out = array();
 if ($none) $out[0] = 'None';
 $q = hm_query("SELECT cert_id,cert_name FROM certificates WHERE 1;");
 while ($r = hm_fetch($q)) {
  $out[$r['cert_id']] = $r['cert_name'];
 }
 return $out;
}
function getThumbnail($image_file,$error_handler = 'GLOBALERROR') {
 $dot = strrpos($image_file,'.');
 $file = substr(substr($image_file,0,$dot),0,24).'-'.THUMBNAIL_WIDTH.'x'.THUMBNAIL_HEIGHT;
 $ext = substr($image_file,$dot+1);
 $thumb = $file.'.'.$ext;
 $folder = dirname($image_file).'/';
 if (file_exists($thumb)) return $thumb;
 if (file_exists($image_file)) return $folder.resizeimg(THUMBNAIL_WIDTH,THUMBNAIL_HEIGHT,$image_file,100,$error_handler);
 return false;
}
function getIcon($image_file,$error_handler = 'GLOBALERROR') {
 $dot = strrpos($image_file,'.');
 $file = substr(substr($image_file,0,$dot),0,24).'-'.ICON_WIDTH.'x'.ICON_HEIGHT;
 $ext = substr($image_file,$dot+1);
 $thumb = $file.'.'.$ext;
 $folder = dirname($image_file).'/';
 if (file_exists($thumb)) return $thumb;
 if (file_exists($image_file)) return $folder.resizeimg(ICON_WIDTH,ICON_HEIGHT,$image_file,100,$error_handler);
 return false;
}
function safefloatval($float) {
 return intval($float * 10000)/10000;
}
function getNextCourse($user_id,$series_id) {
 $user_id = intval($user_id);
 $series_id = intval($series_id);
 if ($user_id > 0 && $series_id > 0) $id = hm_result("SELECT id FROM Assignments".SITE_SUFFIX." WHERE userid='$user_id' and series_id='$series_id' and active='Y' ORDER BY series_sort ASC LIMIT 1;",1);
 else return false;
 return ($id ? $id : false);
}
function getSubseries($user_id,$series_id) {
 $user_id = intval($user_id);
 $series_id = intval($series_id);
 if ($user_id > 0 && $series_id > 0) {
  $id = hm_result("SELECT subseries_id FROM Assignments".SITE_SUFFIX." WHERE userid='$user_id' and series_id='$series_id' and active='Y' ORDER BY series_sort ASC LIMIT 1;",1);
 }
 else return false;
 return ($id ? $id : false);
}
function get_subseries($series_id) {
 $series_id = intval($series_id);
 $o = array();
 if ($series_id > 0) {
  $q = hm_query("SELECT sr_id,sr_name FROM series_groups WHERE sr_parent_id='$series_id' ORDER BY sr_sort ASC;");
  while ($r = hm_fetch($q)) {
  	$o[$r['sr_id']] = $r['sr_name'];
  }
 }
 return $o;
}
function getSeriesCourses($series_id) {
 $series_id = intval($series_id);
 $series_courses = array();
 if ($series_id > 0) {
  $q = hm_query("SELECT sr_id FROM series_groups WHERE sr_parent_id='$series_id' ORDER BY sr_sort;");

  while ($r = hm_fetch($q)) {
   $series_id2 = intval($r['sr_id']);

   if ($series_id2 > 0) {

    $q2 = hm_query("SELECT sr_course_id,sr_grouping_sort FROM series_groupings LEFT JOIN Courses ON (sr_course_id=c_id) WHERE sr_group_id='$series_id2' ORDER BY sr_grouping_sort ASC;");

    while ($r2 = hm_fetch($q2)) {
     $series_courses[] = array('id' => $r2['sr_course_id'],'subseries_id' => $series_id2);
    }
   }
  }
 }
 return $series_courses;
}
function sync_series($user_id,$series_id,$series_courses,$date,$comp_date,$award = false) {
 $user_id = intval($user_id);
 $series_id = intval($series_id);
 if ($user_id > 0 && $series_id > 0) {
  $insert_buffer = array();

  $active = ($award ? 'N' : 'Y');

  if (!is_array($series_courses) || count($series_courses) === 0) {
   $series_courses = getSeriesCourses($series_id);
  }

  $sort = hm_result("SELECT series_sort FROM Assignments".SITE_SUFFIX." WHERE userid='$user_id' and series_id='$series_id' and active='C' ORDER BY series_sort DESC;",1);
  $q = hm_query("SELECT course_id,subseries_id,active FROM Assignments".SITE_SUFFIX." WHERE userid='$user_id' and series_id='$series_id' ORDER BY series_sort DESC;");
  $yes = $no = $series_id_previous = 0;
  $first = true;
  $completed_series = array();
  $partial_series = array();
  $untouched_series = array();
  $cull_list = array();

  $total_yes = $total_no = 0;

  while ($r = hm_fetch($q)) {

   if ($first === false && $series_id_previous != $r['subseries_id']) {
    if ($yes === 0) $completed_series[] = $series_id_previous;
    elseif ($no === 0) $untouched_series[] = $series_id_previous;
    else $partial_series[$series_id_previous] = $cull_list;
    $yes = $no = 0;
    $cull_list = array();
   }

   if ($r['active'] == 'Y') {
    $total_yes++;
    $yes++;
   } else {
    $cull_list[] = $r['course_id'];
    $total_no++;
    $no++;
   }

  	$series_id_previous = $r['subseries_id'];
  	$first = false;
  }

  if ($series_id_previous > 0) {
    if ($yes === 0) $completed_series[] = $series_id_previous;
    elseif ($no === 0) $untouched_series[] = $series_id_previous;
    else $partial_series[$series_id_previous] = $cull_list;
  }

  /* Partially completed Subseries */

  if ($series_id_previous > 0 && $yes > 0) {
/*   echo 'Partial:<br>$completed_series = ';
   print_r($completed_series);
   echo '<br>$untouched_series = ';
   print_r($untouched_series);
   echo '<br>$partial_series = ';
   print_r($partial_series);*/

   foreach ($series_courses as $id => $course) {
   	if (in_array($course['subseries_id'],$completed_series)) unset($series_courses[$id]);
   	if (is_array($partial_series[$course['subseries_id']])) {
   	 if (in_array($course['id'],$partial_series[$course['subseries_id']])) unset($series_courses[$id]);
   	}
   }

/*   echo '<br>final:';
   print_r($series_courses);*/
  }

//  if ($total_no === 0) {
   hm_query("DELETE FROM Assignments".SITE_SUFFIX." WHERE userid='$user_id' and series_id='$series_id' and active='Y';");
   $total_yes = 0;
//  }

  $total_assignments = $total_yes + $total_no;

  $date = mysqlText($date);
  $comp_date = mysqlText($comp_date);

  if ($total_assignments > 0) {

   foreach ($series_courses as $course) {
    $course_id = intval($course['id']);
    $subseries = intval($course['subseries_id']);
    $sort++;

    $insert_buffer[] = "(NULL,'$user_id','$course_id','$date','$comp_date', $active,'$series_id','$subseries','$sort')";
    if ($award) award_course($user_id,$course_id,$series_id);
   }

  } else {

   foreach ($series_courses as $course) {
    $course_id = intval($course['id']);
    $subseries = intval($course['subseries_id']);
    $sort++;

    $insert_buffer[] = "(NULL,'$user_id','$course_id','$date','$comp_date', $active,'$series_id','$subseries','$sort')";
    if ($award) award_course($user_id,$course_id,$series_id);
   }
  }

  if (count($insert_buffer)) {
   hm_query("INSERT INTO Assignments".SITE_SUFFIX." (id,userid,course_id,dateassigned,datetakenby,active,series_id,subseries_id,series_sort) VALUES ".implode(',',$insert_buffer));
  }
 }
}
function set_assignment_id() {
 $ai = intval($_GET['ai']);
 $r = hm_result("SELECT subseries_id FROM Assignments".SITE_SUFFIX." WHERE id='$ai' LIMIT 1;",1);
 $_SESSION['active_assignment_id'] = $ai;
 $_SESSION['active_subseries_id'] = $r;
}
function get_assignment_id() {
 return intval($_SESSION['active_assignment_id']);
}
function get_subseries_id() {
 return intval($_SESSION['active_subseries_id']);
}
function reset_assignment_id() {
 unset($_SESSION['active_assignment_id']);
}
function reset_subseries_id() {
 unset($_SESSION['active_subseries_id']);
}
function award_course($user_id,$course_id,$award_date,$complied_date,$certificate_type,$subseries_id = 0) {
 $user_id = intval($user_id);
 $course_id = intval($course_id);
 $subseries_id = intval($subseries_id);
 $certificate_type = intval($certificate_type);

 if ($user_id > 0 && $course_id > 0) {
  if (!valid_date($award_date)) $award_date = date('Y-m-d H:i:s');
  if (!valid_date($complied_date)) $complied_date = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 year'));

  $g = get_course_ceus_grade($course_id);
  $ceus = intval($g['ceus']);
  $grade = $g['grade'];

  hm_query("INSERT INTO results".SITE_SUFFIX." (userid, score, courseid, duration, ceus, passorfail, datestarted, datecomplied, subseries_id,certificate_override) VALUES ('$user_id', '100', '$course_id', '0', '$ceus', '".mysqlText($grade)."', '$award_date','$complied_date', '$subseries_id','$certificate_type');");
  return hm_insert_id();
 }

 return false;
}
function get_course_ceus_grade($course_id) {
 $course_id = intval($course_id);

 if ($course_id > 0) {
  $cq = hm_query("SELECT ceus,grade FROM Courses WHERE c_id='$course_id' LIMIT 1;");
  $cr = hm_fetch($cq);
  if ($cr['grade'] == 'Pretest') $cr['ceus'] = 0;
  return $cr;
 }

 return false;
}
function valid_date($date) {
 $ts = strtotime($date);
 return checkdate(date('m',$ts),date('d',$ts),date('Y',$ts));
}

/* Site echo functions */

function home() {
 global $homes;
 echo $homes[$_SESSION['siteuser']['userlevel']];
}
function theme() {
 if (SITE_ID) echo '/themes/'.getSiteSlug();
 else echo '/theme';
}
function level() {
 global $levels;
 echo $levels[$_SESSION['siteuser']['userlevel']];
}
function siteURL($site_id = 0) {
 $site_id = intval($site_id);
 if ($site_id < 1) $site_id = SITE_ID;

 $site = getSiteSlug($site_id);
 echo 'http://'.($site ? $site.'.' : '').MASTER_SITE;
}
function template($name,$data = '') {
 $name = hm_cleanInput($name,'u',32);
 if (file_exists('templates/'.$name.'.php')) include('templates/'.$name.'.php');
 else echo 'Failed to load template: '.$name;
}
function applyTemplate($template,$array) {
 $array = (array)$array;
 foreach ($array as $key => $replacement) {
  $template = str_replace($key,$replacement,$template);
 }
 return $template;
}