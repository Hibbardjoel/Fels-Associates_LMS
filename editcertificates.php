<?php
require('inc/conf.php');
accessLevel(1);
require('inc/sess.php');

$order_fields = array('cert_name','cert_image');

$orderby = intval($_GET['orderby']);
$sortby = ($_GET['sortby'] ? 1 : 0);

if ($orderby < 0 || $orderby > (count($order_fields) - 1)) $orderby = 0;
$orderbyfield = $order_fields[$orderby];

hm_header();

template('compliance_box');
?>
<?php errOut('editcertificates');?>
  <div class="banner">Editing Certificates &bull;</div>
  <div id="view_course_table">
<div class="controls"><a class="button" href="addcertificate.php">Add New Certificate</a></div>
   <table width="100%">
    <tr>
     <th width="200"><?php echo sortHeader('Certificate Name',0,$sortby);?></th>
     <th colspan="2"><?php echo sortHeader('Certificate Image',1,$sortby);?></th>
    </tr>
<?php
$q = hm_query("SELECT cert_id,cert_name,cert_image FROM certificates WHERE 1 ORDER BY $orderbyfield ".($sortby ? 'DESC' : 'ASC').";");

$cnt = hm_cnt($q);

if ($cnt > 0) {
 while ($r = hm_fetch($q)) {
  $icon = getIcon('certificates/'.$r['cert_image']);
?>
    <tr>
     <td><a href="editcertificate.php?id=<?php echo $r['cert_id'];?>"><?php echo cleanOutput($r['cert_name']);?></a></td>
     <td><?php echo cleanOutput($r['cert_image']);?></td>
     <td class="r"><?php echo ($icon ? '<img src="'.$icon.'" width="'.ICON_WIDTH.'" alt="">':'');?></td>
    </tr>
<?php
 }
} else {
?>
    <tr>
     <td colspan="2">No Certificates Defined</td>
    </tr>
<?php
}
?>
   </table>
  </div>
<?php
hm_footer();
?>