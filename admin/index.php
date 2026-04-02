<?php
// Require init.php
require 'init.php';
$var['customJSFiles'] = array("global");

// Export handling
if (isset($_POST['export_fields'])) {
    ob_start();
}

// Get controller
if (!isset($_GET['p']) || trim($_GET['p']) == "") {
    $pageController = "index";
} else {
    $pageController = $_GET['p'];
}

alog("general", "page_requested", $pageController);

// Init smarty
$tpl = new SmartyAdminEngine;

// Register Smarty functions
$tpl->register("dfo", array(&$dfo, "format_smarty"));
$tpl->register("dfop", array(&$dfo, "placeholder"));
$tpl->register("nfo", array(&$nfo, "format_smarty"));
$tpl->register("nfop", array(&$nfo, "placeholder"));
$tpl->register("infix", array(&$cur, "infix_smarty"));
$tpl->register("ct", array("CSRF", "raw"));
$tpl->register("cf", array("CSRF", "html"));

// CSRF hook
$res = $addons->runHook("SkipCSRF", [
    "page" => $pageController,
]);
$skipCsrf = false;
foreach ($res as $r) {
    if ($r) {
        $skipCsrf = true;
    }
}

// Activate CSRF protection
if (!$skipCsrf) {
    CSRF::validate();
}

// Menu to open function
$menuToOpen = "";
function menu($m)
{
    global $menuToOpen;
    $menuToOpen = $m;
}

// Title function
$currentPageTitle = "";
function title($t)
{
    global $currentPageTitle;
    $currentPageTitle = $t;
}

// If no controller exists, we use the old system
if (!file_exists(__DIR__ . "/controller/$pageController.php") && !$addons->routePage($pageController)) {
    if (file_exists(__DIR__ . '/pages/' . $pageController . '.php')) {
        ob_start();
        require __DIR__ . '/pages/' . $pageController . '.php';
        $var['content'] = ob_get_contents();
        CSRF::auto($var['content']);
        ob_end_clean();
    } else {
        alog("error", "not_found", $pageController);
        $pageController = "error";
    }
}

// We assign a few variables to template
$var['bugs'] = 0;
if ($ari->check(34)) {
    $var['crit'] = ($CFG['MAINTENANCE'] ? 1 : 0) + ($CFG['DISPLAY_ERRORS'] == 'errors_warnings_notices' ? 1 : 0) + $db->query("SELECT * FROM `system_status`")->num_rows;
} else {
    $var['crit'] = 0;
}

$var['open_reminders'] = $db->query("SELECT * FROM admin_reminders WHERE user = " . $adminInfo->ID . " AND time <= " . time())->num_rows;
$var['open_projects'] = $db->query("SELECT * FROM projects WHERE status = 0 AND due < '" . date("Y-m-d") . "'")->num_rows;
if ($db->query("SELECT ID FROM client_countries WHERE active = 1 AND ID = " . $CFG['DEFAULT_COUNTRY'])->num_rows <= 0) {
    $var['crit2'] = 1;
} else {
    $var['crit2'] = 0;
}

if ($db->query("SELECT ID FROM currencies")->num_rows <= 0) {
    $var['crit3'] = 1;
} else {
    $var['crit3'] = 0;
}

$var['abuse'] = 0;
if ($ari->check(68)) {
    $var['abuse'] = $db->query("SELECT 1 FROM abuse WHERE status = 'open' AND deadline < '" . date("Y-m-d H:i:s") . "'")->num_rows;
}

$var['crit4'] = (int) Versioning::newerVersion();
$var['payments'] = $db->query("SELECT COUNT(ID) as count FROM csv_import WHERE done = 0")->fetch_object()->count;
$var['wire_active'] = $gateways->get()['transfer']->isActive();

$var['crit5'] = $db->query("SELECT COUNT(ID) as count FROM offers WHERE status = 0 AND start <= '" . date("Y-m-d") . "' AND end >= '" . date("Y-m-d") . "'")->fetch_object()->count + $db->query("SELECT COUNT(ID) as count FROM offers WHERE status = 1 AND (start > '" . date("Y-m-d") . "' OR end < '" . date("Y-m-d") . "')")->fetch_object()->count;

$var['admin_info'] = (array) $adminInfo;
$var['admin_rights'] = (array) $ari->getArray();
$var['cfg'] = $CFG;
$var['lang'] = $lang;
$var['admin_languages'] = $adminLanguages;
$var['hideSidebar'] = $adminInfo->hide_sidebar;
$var['lack_wishes'] = $db->query("SELECT 1 FROM wishlist WHERE ack = 0")->num_rows + $db->query("SELECT 1 FROM wishlist_comments WHERE ack = 0")->num_rows;
$var['domain_wishes'] = $db->query("SELECT 1 FROM domains WHERE customer_wish > 0 AND customer_when <= '" . date("Y-m-d H:i:s", strtotime("-24 hours")) . "' AND status != 'TRANSIT' AND status != 'EXPIRED' AND status != 'DELETED'")->num_rows;
$var['cronhang'] = 0;

foreach (glob(__DIR__ . "/../controller/crons/*.lock") as $f) {
    $c = file_get_contents($f);
    $ex = explode("\n", $c);
    $l = $ex[0];
    $d = substr($l, 1, 19);
    if (time() - strtotime($d) > 1200) {
        $var['cronhang']++;
    }
}

$var['cronhang'] += $db->query("SELECT 1 FROM cronjobs WHERE active = 1 AND last_call < " . time() . " - 10*`intervall` LIMIT 1")->num_rows;

if (isset($additionalJS)) {
    $var['additionalJS'] = $additionalJS;
}

foreach ($addons->runHook("AdminAreaAdditionalJS", []) as $res) {
    $var['additionalJS'] .= $res;
}

$var['topMenu'] = "";
foreach ($addons->runHook("AdminAreaTopMenu", []) as $res) {
    $var['topMenu'] .= $res;
}

$var['adminFooter'] = "";
foreach ($addons->runHook("AdminAreaFooter", []) as $res) {
    $var['adminFooter'] .= $res;
}

if ($ari->check(43)) {
    $oGateways = $gateways->get();
    foreach ($oGateways as $k => $v) {
        if (!$v->isActive() || !$v->haveLog()) {
            unset($oGateways[$k]);
        }
    }

    $var['show_logs'] = count($oGateways) >= 1;
}

$var['addon_menu'] = $addons->getAdminMenu();
$var['addonlabel'] = 0;

foreach ($var['addon_menu'] as $name => $page) {
    if (is_array($page)) {
        $al = $page[1];
        if (intval($al) == $al) {
            $var['addonlabel'] += $al;
        } else {
            $var['addonlabel'] = "!";
            break;
        }
    }
}

// Page color handling
$color = "#428bca";
if (preg_match('/^#[a-f0-9]{6}$/i', $CFG['ADMIN_COLOR'])) {
    $color = $CFG['ADMIN_COLOR'];
} elseif (preg_match('/^[a-f0-9]{6}$/i', $CFG['ADMIN_COLOR'])) {
    $color = '#' . $CFG['ADMIN_COLOR'];
}

$var['admin_color'] = $color != "#428bca" ? $color : "";

preg_match('/^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $color, $parts);

$out = "";
for ($i = 1; $i <= 3; $i++) {
    $parts[$i] = hexdec($parts[$i]);
    $parts[$i] = round($parts[$i] * 80 / 100);
    $out .= str_pad(dechex($parts[$i]), 2, '0', STR_PAD_LEFT);
}
$var['admin_color'] = $color != "#428bca" ? $color : "";
$var['admin_color2'] = "#" . $out;

// Get due projects
$dueProjects = array();
$projSql = $db->query("SELECT * FROM projects WHERE status = 0 ORDER BY star DESC, due ASC LIMIT 3");
while ($r = $projSql->fetch_object()) {
    $tasks = $db->query("SELECT ID FROM project_tasks WHERE project = " . $r->ID)->num_rows;
    $done = $db->query("SELECT ID FROM project_tasks WHERE project = " . $r->ID . " AND status = 1")->num_rows;
    $percent = floor($done / ($tasks ?: 1) * 100);
    $dueProjects[$r->ID] = array("name" => $r->name, "percent" => $percent, "star" => $r->star);
}
$var['due_projects'] = $dueProjects;

// Get working project
if ($task = Project::working()) {
    if ($task < 0) {
        $taskInfo = new stdClass;
        $taskInfo->ID = $task;
        $taskInfo->project = intval($task) / -1;
    } else {
        $taskInfo = $db->query("SELECT * FROM project_tasks WHERE ID = " . Project::working())->fetch_object();
    }

    $times = time() - strtotime($db->query("SELECT start FROM project_times WHERE end = '0000-00-00 00:00:00' AND admin = {$adminInfo->ID}")->fetch_object()->start);

    $hours = floor($times / 3600);
    $time = $times - 3600 * $hours;
    $minutes = floor($time / 60);
    $secs = $time - 60 * $minutes;

    if (strlen($hours) != 2) {
        $hours = "0" . $hours;
    }

    if (strlen($minutes) != 2) {
        $minutes = "0" . $minutes;
    }

    if (strlen($secs) != 2) {
        $secs = "0" . $secs;
    }

    $var['working_project'] = array(
        "ID" => $taskInfo->ID,
        "project" => $taskInfo->project,
        "project_name" => $db->query("SELECT name FROM projects WHERE ID = " . $taskInfo->project)->fetch_object()->name,
        "project_time" => $times,
        "project_timef" => $hours . ":" . $minutes . ":" . $secs, //einstellig
    );

    if ($pageController != "view_project") {
        array_push($var['customJSFiles'], "projects.header");
    }
}

$parameters = $_GET;
ksort($parameters);
$parameters = http_build_query($parameters);
$sql = $db->query("SELECT ID FROM admin_shortcut WHERE url = '" . $db->real_escape_string($parameters) . "' AND admin = " . $adminInfo->ID . " LIMIT 1");
$var['shortcut'] = false;
if ($sql->num_rows > 0) {
    $var['shortcut'] = $sql->fetch_object()->ID;
}

$var['shortcuts'] = array();
$sql = $db->query("SELECT * FROM admin_shortcut WHERE admin = " . $adminInfo->ID);
while ($row = $sql->fetch_assoc()) {
    array_push($var['shortcuts'], $row);
}

$var['online_status'] = $adminInfo->online;
$var['user_session'] = "";

if (!empty($session->get('mail'))) {
    $user_session = new User($session->get('mail'));
    $var['user_session'] = "<a href=\"?p=customers&edit={$user_session->get()['ID']}\">" . htmlentities($user_session->get()['name']) . (!empty($c = $user_session->get()['company']) ? " (" . htmlentities($c) . ")" : "") . "</a>";
}

// Top bar
$var['topbar'] = "";
foreach ($addons->runHook("AdminTopBar") as $l) {
    if (!empty($l)) {
        $var['topbar'] .= $l;
    }
}

// SSO
$var['sso'] = [];
foreach ($addons->runHook("AdminSSO") as $v1) {
    foreach ($v1 as $k => $v) {
        $var['sso'][$k] = $v;
    }
}

// Support
$var['supportLink'] = "";
foreach ($addons->runHook("AdminSidebarSupportLink") as $l) {
    if (!empty($l)) {
        $var['supportLink'] = $l;
    }
}

if (empty($var['supportLink'])) {
    $statusIn = $CFG['WTIP'] ? "(0,1)" : "(0)";

    $var['support'] = 0;
    $var['support'] += $var['support_my'] = $db->query("SELECT COUNT(*) AS c FROM support_tickets WHERE dept = " . ($adminInfo->ID / -1) . " AND status IN $statusIn AND priority != 5")->fetch_object()->c;

    $var['support_depts'] = array();
    $sql = $db->query("SELECT dept FROM support_department_staff WHERE staff = " . intval($adminInfo->ID));
    while ($row = $sql->fetch_object()) {
        $ds = $db->query("SELECT ID, name FROM support_departments WHERE ID = " . $row->dept);
        while ($sd = $ds->fetch_object()) {
            $count = $db->query("SELECT COUNT(*) AS c FROM support_tickets WHERE dept = " . ($sd->ID) . " AND status IN $statusIn AND priority != 5")->fetch_object()->c;
            $var['support'] += $count;
            $var['support_depts'][$sd->ID] = array($sd->name, $count);
        }
    }
    $support_depts = $var['support_depts'];
}

// Select other admins
$var['otherAdmins'] = array();
if ($ari->check(63) || $session->get('admin_session_switch_allowed') == "1") {
    $sql = $db->query("SELECT ID, name, online FROM admins WHERE ID != " . $adminInfo->ID);
    while ($row = $sql->fetch_object()) {
        if ($ari->check(1, $row->ID)) {
            $var['otherAdmins'][$row->ID] = array($row->name, $row->online);
        }
    }

}

// Avatar
$var['ownAvatar'] = "";
if (!empty($adminInfo->avatar) && file_exists(__DIR__ . "/../files/avatars/" . basename($adminInfo->avatar))) {
    $var['ownAvatar'] = basename($adminInfo->avatar);
}

// Single auth
$var['sa'] = [];
$sa = $sa_old = unserialize($adminInfo->sa) ?: [];
foreach ($sa as &$s) {
    if (empty($s[2])) {
        $c = file_get_contents($s[0] . "/admin/?sa_pagename=1");
        if (substr($c, 0, 3) != "PN:") {
            continue;
        }

        $s[2] = trim(substr($c, 3));
    }
    $var['sa'][] = [$s[0], $s[1], $s[2]];
}

if ($sa_old != $sa) {
    $db->query("UPDATE admins SET sa = '" . $db->real_escape_string(serialize($sa)) . "' WHERE ID = " . $adminInfo->ID);
}

// Monitoring
$var['monitoring'] = $db->query("SELECT 1 FROM monitoring_services WHERE active = 1 AND last_result != '1' AND last_called > 0 GROUP BY server")->num_rows;

// Run AdminPage hook
$res = $addons->runHook("AdminPage", [
    "page" => $pageController,
]);

// Output page and do logic
$tpl->show($pageController);
