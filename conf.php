<?php
session_start();
date_default_timezone_set('America/New_York');
define('SUPPORT_EMAIL','support@felsandassociates.com');
define('SENDER_EMAIL','support@felsandassociates.com');
define('SESSION_TIMEOUT',3600);
define('CHARACTER_ENC','UTF-8');
define('MASTER_SITE','bmslearning.info');
define('MASTER_SITE_NAME','Building Maintenance Service');
define('MASTER_SITE_NAME_SHORT','BMS');
define('MASTER_URL','http://'.MASTER_SITE);
define('ENCRYPTION_KEY', '772293E22024178FDB4F642B44506593');
define('PROTECTED_FOLDER',dirname(dirname(__DIR__)).'/Training');
define('PROTECTED_ACCESS_FOLDER','m');
define('DEBUG_MODE',true);
define('SECTION_GENERAL','Special Emphasis Education');
define('THUMBNAIL_WIDTH',120);
define('THUMBNAIL_HEIGHT',100);
define('ICON_WIDTH',40);
define('ICON_HEIGHT',33);

mb_internal_encoding(CHARACTER_ENC);
mb_http_output(CHARACTER_ENC);

include('funcs.php');


/* Variables and Set-up */

hm_connect('localhost','bms','OgFxiU%9TKU%bTwbPS!H*8e9OX7&c7Mu','bms_manager');

define('SITE_ID',getSiteID($cronjob_mode));
define('SITE_SUFFIX',siteSuffix());


if (!hm_table_exists('config')) include('core_upgrade.php');
// Config Loader - HM-configloader v1.1
$q = hm_query("SELECT config_key, config_value FROM config WHERE config_group='0';");
while ($r = hm_fetch($q)) {
 define(strtoupper($r['config_key']),$r['config_value']);
}

$homes = array(1 => '/admin.php',3 => '/siteadmin.php',6 => '/manager.php',10 => '/user.php');
$levels = array(1 => 'Super Admin',3 => 'Site Admin',6 => 'Manager',10 => 'User');
$level_slugs = array(1 => 'admin',3 => 'siteadmin',6 => 'manager',10 => 'user');

$departments = get_user_groups();

$statuses = array(
'1' => 'Active',
'2' => 'In-Active',
'3' => 'On-Leave',
'4' => 'Terminated'
);

$statuses_options = array(
 '1' => 'Active',
 '2' => 'In-Active',
 '3' => 'On-Leave',
 '4' => 'Terminated'
);
if (get_level() > 3) unset($statuses_options[2]);

$compliance_periods = array(
'0.25' => '1 Week',
'0.5' => '2 Weeks',
'0.75' => '3 Weeks',
'1' => '1 Month',
'2' => '2 Months',
'3' => '3 Months',
'4' => '4 Months',
'5' => '5 Months',
'6' => '6 Months',
'7' => '7 Months',
'8' => '8 Months',
'9' => '9 Months',
'10' => '10 Months',
'11' => '11 Months',
'12' => '12 Months'
);

define('EMAIL_FAILED_COMPLIANCE',
'<p>Report Date: '.date('m/d/Y').'</p>

<table style="border:1px solid #999;">
<tr>
<th>Name</th><th>Username</th><th>Course ID</th><th>Course Name</th><th>Compliance Date</th>
</tr>
[[ROWS]]
</table>

<p>To stop receiving these reports, <a href="[[UNSUB]]">click this link to unsubscribe</a>.</p>
'
);

define('EMAIL_FAILED_COMPLIANCE_ROW',
'<tr style="[[STYLE]]">
<td style="[[STYLE2]]">[[NAME]]</td><td style="[[STYLE2]]">[[USERNAME]]</td><td style="[[STYLE2]]">[[COURSEID]]</td><td style="[[STYLE2]]">[[COURSE]]</td><td style="[[STYLE2]]">[[DATE]]</td>
</tr>'
);

if (SITE_ID < 0) {
 hm_redirect(MASTER_URL);
}

if (get_level() == 1 && floatval(CORE_VERSION) < 5.4) include('core_upgrade.php');

if (SITE_ID > 0) checkSite(SITE_ID);
?>