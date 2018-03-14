<?php
require('inc/conf.php');
accessLevel(3);
require('inc/sess.php');

$course_ids_raw = (array)$_POST['course_id'];
$course_ids = array();

$series_id = max(0,intval($_POST['series_id']));

$award_date = $_POST['award_date'];
if (!valid_date($award_date)) $award_date = date('Y-m-d');

foreach ($course_ids_raw as $course_id) {
 $course_id = max(0,intval($course_id));
	if ($course_id > 0) $course_ids[] = $course_id;
}

$course_ids = array_unique($course_ids);
$total_courses = count($course_ids);

if (count($course_ids) === 0 && $series_id === 0) {
 err('Please select a series or a course to assign.','grouptraining');
 hm_redirect('grouptraining.php');
}

$certificate_types = get_certificate_types();

$certificate_type = intval($_POST['certificate_type']);
if ($certificate_type < 0 || key_exists($certificate_type,$certificate_types) === false) $certificate_type = '';

$display_form = true;
$single_mode = ($series_id > 0 ? false:true);

$default_compliance_period = get_default_compliance($course_ids,12);
$compliance_period = $default_compliance_period;
$department = '';

if ($_POST['options']) {
 $compliance_period = strval(floatval($_POST['compliance_period']));
 $department = intval($_POST['department']);
}

if ($_POST['submit']) {

 $change_cnt = $deleted_count = 0;
 $safe_ids = array();
 $compliance_period = strval(floatval($_POST['compliance_period']));
 if (!isset($compliance_periods[$compliance_period])) {
  $compliance_period = $default_compliance_period;
 }
 $date = $award_date;

 if ($compliance_period > 0) {
  if ($compliance_period < 1) {
   $addon = ' +'.($compliance_period * 4).' week';
  } else {
   $addon = ' +'.$compliance_period.' month';
  }

  $comp_date = date('Y-m-d', strtotime($date.$addon));
  $comp_date_nice = date('F jS, Y', strtotime($comp_date));

  $post_users = array();
  if (is_array($_POST['users'])) $post_users = $_POST['users'];

   /* Series Assignments */

   if ($series_id > 0) {

    $series_courses = array();
    $total_series_users = 0;
    $all_users = array();

    $q = hm_query("SELECT sr_id,sr_sort,sr_name,sr_parent_id FROM series_groups WHERE sr_parent_id='$series_id' ORDER BY sr_sort;");

    while ($r = hm_fetch($q)) {
     $series_id2 = intval($r['sr_id']);

     if ($series_id2 > 0) {

      $q2 = hm_query("SELECT sr_course_id,sr_grouping_sort,c_id,coursename,`desc` FROM series_groupings LEFT JOIN Courses ON (sr_course_id=c_id) WHERE sr_group_id='$series_id2' ORDER BY sr_grouping_sort ASC;");

      while ($r2 = hm_fetch($q2)) {
       $series_courses[] = array('id' => $r2['sr_course_id'],'subseries_id' => $series_id2);
      }
     }
    }

    if (count($series_courses))
    foreach ($post_users as $user_id) {

     $user_id = max(0,intval($user_id));

     if ($user_id > 0) {
      $all_users[] = $user_id;

      $current_subseries = getSubseries($user_id,$series_id);

      /* If already assigned, delete and reassign any unfinished assignments in untouched subseries,
      and delete and reassign untouched courses within current subseries

      Basically, any still active='Y' should be deleted, and assign all from untouched subseries
      and untouched within current subseries
      */

      if ($current_subseries) {

       sync_series($user_id,$series_id,$series_courses,$date,$comp_date,true);


       /* Otherwise, just assign all as no active assignments exist for the series and user */

      } else {

       $sort = 0;
       $insert_buffer = array();

       foreach ($series_courses as $course) {
        $course_id = intval($course['id']);
        $subseries = intval($course['subseries_id']);
        $sort++;
        $change_cnt++;

        $insert_buffer[] = "(NULL,'$user_id','$course_id','".mysqlText($date)."','".mysqlText($comp_date)."', 'N','$series_id','$subseries','$sort')";
//        hm_query("INSERT INTO Assignments".SITE_SUFFIX." (id,userid,course_id,dateassigned,datetakenby,active,series_id,subseries_id,series_sort) VALUES (NULL,'$user_id','$course_id','$date','$comp_date', 'Y','$series_id','$subseries','$sort')");
       }


       if (count($insert_buffer)) {
        hm_query("INSERT INTO Assignments".SITE_SUFFIX." (id,userid,course_id,dateassigned,datetakenby,active,series_id,subseries_id,series_sort) VALUES ".implode(',',$insert_buffer));
       }
      }

      foreach ($series_courses as $course) {
       award_course($user_id,$course['id'],$date,$comp_date,$certificate_type,$series_id);
      }

      $total_series_users++;
     }
    }

    $all_ids = explode(',',$_POST['all_ids']);
    $clean_ids = array();

    foreach ($all_ids as $id) {
     if (is_numeric($id)) {
      $id = intval($id);
      if (in_array($id,$all_users)) continue;
      $clean_ids[] = $id;
     }
    }

    $delete_count = count($clean_ids);
    if ($delete_count > 0) {
     $uids = implode(',',$clean_ids);
     hm_query("DELETE FROM Assignments WHERE userid IN ($uids) and series_id='$series_id' and (active='Y' or active='C');");
    }


    if ($total_series_users > 0) {
     err('Awarded series users to '.$total_series_users.' users, with a compliance period of '.$compliance_periods[$compliance_period].' ('.$comp_date_nice.').',get_home_slug(),1);
     go_home();
    } elseif ($delete_count > 0) {
     err('Deleted series assignments for '.$delete_count.' users.',get_home_slug(),1);
     go_home();
    }
   }

   /* Single Course Assignments */

   else if (count($course_ids) > 0) {

    $single_mode = true;
    $users_changed = array();

    foreach ($course_ids as $course_id) {
     foreach ($post_users as $user_id) {
      $user_id = intval($user_id);

      if ($user_id > 0) {
       $safe_ids[] = $user_id;

       $users_changed[$user_id] = 1;
       $change_cnt++;
       hm_query("INSERT INTO Assignments".SITE_SUFFIX." (id,userid,course_id,dateassigned,datetakenby,active) VALUES (NULL,'$user_id','$course_id','$date','$comp_date', 'N')");

       award_course($user_id,$course_id,$date,$comp_date,$certificate_type);

      }
     }
    }

    $safe_ids = array_unique($safe_ids);


    if ($total_courses === 1) {
     if (count($safe_ids) > 0) $safelist = implode(',',$safe_ids);
     if (strlen($safelist) === 0) $safelist = 0;

     hm_query("DELETE FROM Assignments".SITE_SUFFIX." WHERE course_id='$course_id' and series_sort='0' and active='Y' and userid NOT IN ($safelist);");
     $deleted_count = hm_affected();

     if (errCnt('groupawardusertocourse') === 0) {
      if ($change_cnt > 0 || $deleted_count > 0) err('Awarded '.$change_cnt.' users with a compliance period of '.$compliance_periods[$compliance_period].' ('.$comp_date_nice.') and removed '.$deleted_count.' users.',get_home_slug(),1);
      else err('No users were awarded passing results.',get_home_slug(),2);
      go_home();
     }
    }

    if (errCnt('groupawardusertocourse') === 0) {
     $total_users = (is_array($users_changed) ? count($users_changed):0);
     if ($change_cnt > 0) err('Awarded '.$total_users.' users to '.$total_courses.' courses, with a compliance period of '.$compliance_periods[$compliance_period].' ('.$comp_date_nice.').',get_home_slug(),1);
     else err('No users were awarded passing results.',get_home_slug(),2);
     go_home();
    }


   }
  }
 }


hm_header();

template('course_box');
?>
  <div class="banner">Award Course to Who? &bull;</div>
  <div id="view_course_table" class="assignto">
<?php

errOut('groupawardusertocourse');
$first = true;


$coursenames = array();

if (get_level() < 4) {
 $departments = get_user_group_parents() + array('' => '---------') + $departments;
}
?>
<form method="post">
<input type="hidden" name="options" value="1">
<input type="hidden" name="award_date" value="<?php echo $award_date;?>">
<input type="hidden" name="certificate_type" value="<?php echo $certificate_type;?>">
<?php

if ($series_id > 0) {

 $course_ids = array();
 $coursenames[] = hm_result("SELECT sr_name FROM series_groups WHERE sr_id='$series_id' LIMIT 1;");

 $q = hm_query("SELECT sr_id,sr_sort,sr_name,sr_parent_id FROM series_groups WHERE sr_parent_id='$series_id' ORDER BY sr_sort;");

 while ($r = hm_fetch($q)) {
  $subseries_id = intval($r['sr_id']);

  if ($subseries_id > 0) {

   $q2 = hm_query("SELECT sr_course_id,sr_grouping_sort,c_id,coursename,`desc` FROM series_groupings LEFT JOIN Courses ON (sr_course_id=c_id) WHERE sr_group_id='$subseries_id' ORDER BY sr_grouping_sort ASC;");

   while ($r2 = hm_fetch($q2)) {
    $course_ids[] = $r2['c_id'];
   }
  }
 }
?>
   <input type="hidden" name="series_id" value="<?php echo $series_id;?>">
<?php

} elseif (is_array($course_ids) && count($course_ids) > 0) {
 foreach ($course_ids as $id => $course_id) {
  $name = hm_result("SELECT coursename FROM Courses WHERE c_id='$course_id' LIMIT 1;");
  if ($name) $coursenames[] = $name;
?>
   <input type="hidden" name="course_id[]" value="<?php echo $course_id;?>">
<?php
 }
}

$course_id_tally = implode(',',$course_ids);
if ($course_id_tally == '') $course_id_tally = 0;
$total_courses = count($course_ids);

?>
   <div class="controls">Select Department <select name="department" onchange="this.form.submit();"><?php echo optOut($departments,$department,'Show All');?></select> &nbsp; Compliance Period <select name="compliance_period" onchange="this.form.submit();"><?php echo optOut($compliance_periods,$compliance_period);?></select> &nbsp; <input type="submit" value="Update"></div>
</form>

<form method="post">
<input type="hidden" name="department" value="<?php echo $department;?>">
<input type="hidden" name="compliance_period" value="<?php echo $compliance_period;?>">
<input type="hidden" name="award_date" value="<?php echo $award_date;?>">
<input type="hidden" name="certificate_type" value="<?php echo $certificate_type;?>">
<?php
if ($series_id > 0) {
?>
   <input type="hidden" name="series_id" value="<?php echo $series_id;?>">
<?php
} else {

 foreach ($course_ids as $cid) {
?>
   <input type="hidden" name="course_id[]" value="<?php echo $cid;?>">
<?php
 }
}
?>
   <div class="controls"><button type="button" onClick="selectAll(this);">Select all</button> <button type="button" onClick="deselectAll(this);">Deselect all</button></div>

   <h2 class="first"><?php echo implode(', ',$coursenames);?></h2>
<?php
if (get_level() < 4) {
 if ($department && is_group_parent($department)) {
  $children = get_user_group_children($department,1);
  if (count($children) > 0) {
   $children = implode(',',$children);
   $dept = " and gu_group_id IN ($children)";
  } else {
   $dept = " and false";
  }

 } else {
  $dept = ($department ? " and gu_group_id='$department'" : '');
 }

} else {
 $dept = ($department ? " and gu_group_id='$department'" : '');
}
$q = hm_query("SELECT userid,`name`,userlevel FROM users".SITE_SUFFIX." LEFT JOIN group_users".SITE_SUFFIX." ON (gu_user_id=userid) WHERE status='1' and userlevel='10'$dept ORDER BY userlevel DESC,name ASC;");

if (hm_cnt($q) > 0) {
 $user_tally = array();

 while ($r = hm_fetch($q)) {
  $user_tally[] = $r['userid'];
  $checked = false;

 ?>
   <div class="field <?php echo $level_slugs[$r['userlevel']];?>"><input type="checkbox" name="users[]" id="user_<?php echo $r['userid'];?>" value="<?php echo cleanOutput($r['userid']);?>" <?php echo ($checked ? ' checked':'');?>> <label for="user_<?php echo $r['userid'];?>"><?php echo cleanOutput($r['name'],'',1);?></label></div>
<?php
 }

 if (count($user_tally) > 0) {
?>
<input type="hidden" name="all_ids" value="<?php echo implode(',',$user_tally);?>">
<?php
 }
?>
   <div class="clear"></div>
   <div class="buttons"><input type="submit" name="submit" value="submit"></div>
<?php
} else {
?>
<div class="noresult">No users found in the <?php echo $departments[$department];?> department.</div>
<?php
}
?>
  </form>
  </div>

<script type="text/javascript">
function selectAll(x) {
 for(var i=0,l=x.form.length; i<l; i++)
 if (x.form[i].type == 'checkbox')
 x.form[i].checked = true
}
function deselectAll(x) {
 for(var i=0,l=x.form.length; i<l; i++)
 if (x.form[i].type == 'checkbox')
 x.form[i].checked = false
}
</script>
<?php
hm_footer();