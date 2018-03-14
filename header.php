<?php
if (!function_exists('hm_header')) exit;
?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo ($page_title ? $page_title:'Fels &amp; Associates, Inc. - Training &amp; Marketing Group');?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="/theme/global.css">
<link rel="stylesheet" type="text/css" href="<?php theme();?>/custom.css">
<?php echo $header_addin;?>
</head>
<body>
<div id="container">
<?php
if (is_logged_in()) {
?>
 <div id="header">
  <div id="headnav">
   <a href="/logout.php"></a>
   <div class="clear"></div>
  </div>
 </div>
<?php
} else {
?>
 <div id="header" class="noaccess">
 </div>
<?php
}