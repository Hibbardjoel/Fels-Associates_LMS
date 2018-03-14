<?php
// H.O.W.D.Y. Media - Dump Database 1.42
//
// Mimics the command mysqldump for exporting databases to a .sql file
//
// Copyright 2008-2016 H.O.W.D.Y. Media
// H.O.W.D.Y. Media
// 4701 Wrightsville Ave #2203
// Wilmington, NC 28403 US
// www.howdymedia.com
// 315-469-8414
//
// This script is solely owned by H.O.W.D.Y. Media and may not be sold, modified, repackaged, or distributed with out express permission.
//

date_default_timezone_set('America/New_York');

// Wordpress Mode:
if (file_exists('wp-config.php')) {
 $wp = file_get_contents('wp-config.php');
 list($ggg,$databaseserver) = explode("'DB_HOST'",$wp,2);
 list($ggg,$databaseserver) = explode("'",$databaseserver,3);
 list($ggg,$databaseuser) = explode("'DB_USER'",$wp,2);
 list($ggg,$databaseuser) = explode("'",$databaseuser,3);
 list($ggg,$databasepass) = explode("'DB_PASSWORD'",$wp,2);
 list($ggg,$databasepass) = explode("'",$databasepass,3);
 list($ggg,$databasename) = explode("'DB_NAME'",$wp,2);
 list($ggg,$databasename) = explode("'",$databasename,3);
} else {
 $databaseserver = 'localhost';
 $databaseuser = 'bms';
 $databasepass = 'gshi0100';
 $databasename = 'bms_manager';
}

hm_connect($databaseserver,$databaseuser,$databasepass,$databasename);
if (!$db) {
 echo 'Failed to connect.';
 echo $db;
 exit;
}
mb_internal_encoding('UTF-8');

header("Content-type: text/plain;charset=UTF-8");
header("Content-Disposition: attachment;filename=database-".date('Y-m-d_h-i-s').".sql");

function mysqlText($text) { // mysqlText v2.0
 global $db;
 if (get_magic_quotes_gpc()) $text = stripslashes($text);
 return mysqli_real_escape_string($db,$text);
}
function hm_connect($host, $user, $pass, $dbname = '') { // hm_connect v1.0
 global $db;
 $db = mysqli_connect($host,$user,$pass,$dbname) or hm_error('',0,'MySQL Connection Error');
 mysqli_set_charset($db,'utf8');
 return $db;
}
function hm_error($q, $errno, $error) { // hm_error v1.0
 die("<b>$errno - $error<br><br>$q</b>");
}
function hm_query($q) { // hm_query v2.0
 global $db;
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

if (!function_exists('hex2bin')) {
 function hex2bin($data) {
  $bin = "";
  $i = 0;
  do {
   $bin .= chr(hexdec($data{$i}.$data{($i + 1)}));
   $i += 2;
  } while ($i < strlen($data));
  return $bin;
 }
}


$q = hm_query("SHOW TABLES;");
while($r = hm_fetch($q,1)) {
 $tables[] = $r[0];
}

foreach ($tables as $table) {
 $q = hm_query("SHOW CREATE TABLE `$table`;");
 $r = hm_fetch($q);
 echo $r['Create Table'].";\n\n";

 $q = hm_query("SHOW COLUMNS FROM `$table`;");
 $fields = array();
 while($r = hm_fetch($q)) {
  $fields[] = $r['Field'];
  if (strpos($r['Type'],'blob') !== false) $types[] = 1;
  else $types[] = 0;
 }
 $fieldlist = '`'.implode('`,`',$fields).'`';
 $q = hm_query("SELECT * FROM `$table`;");

 while($r = hm_fetch($q)) {
  $data = array();
  $cnt = 0;
  foreach ($r as $key => $value) {
   if ($types[$cnt]) $data[$cnt] = '0x'.bin2hex($value);
   else $data[$cnt] = "'".mysqlText($value)."'";
   $cnt++;
  }
  $datalist = implode(', ',$data);
  echo "INSERT INTO `$table` ($fieldlist) VALUES ($datalist);\n";
 }
 echo "\n\n";
}