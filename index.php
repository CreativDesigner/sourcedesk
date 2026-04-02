<?php
// File for handling all client area requests

// Require init.php
require_once 'init.php';

// Set get parameter p to local @var page
// No isset check required because this is already processed in init.php
$page = str_replace("..", ".", $_GET['p']);
if (php_sapi_name() == "cli") {
    $page = $argv[1];
}

// Check if there is an overwrite
$ur = @unserialize($CFG['URL_REWRITE']);
if (is_array($ur)) {
    $page = trim($page, "/");

    foreach ($ur as $r) {
        $new = @trim($r["new"], "/");
        $old = @trim($r["old"], "/");

        if (empty($new) || empty($old)) {
            continue;
        }

        if ($new == $page) {
            $page = $old;
        } elseif ($old == $page && $r["force"]) {
            if (substr($new, 0, 8) == "https://" || substr($new, 0, 7) == "http://") {
                header('Location: ' . $new);
                exit;
            } else {
                $page = $new;
            }
        }
    }
}

// Route the page with the parameters in URL
$ex = explode("/", $page);
if (count($ex) > 1) {
    $page = array_shift($ex);
    $pars = $ex;
}

// Check if there is open abuse
if ($var['logged_in'] && $db->query("SELECT 1 FROM abuse WHERE user = " . intval($user->get()['ID']) . " AND status = 'open'")->num_rows) {
    $var['global_error'] = $lang['ABUSE']['WARNING'] . "<ul>";
    $sql = $db->query("SELECT ID, subject FROM abuse WHERE user = " . intval($user->get()['ID']) . " AND status = 'open'");
    while ($row = $sql->fetch_object()) {
        $var['global_error'] .= "<li><a href=\"" . $CFG['PAGEURL'] . "abuse/" . $row->ID . "\">" . htmlentities($row->subject) . "</a></li>";
    }
    $var['global_error'] .= "</ul>";
}

// If the requested controller does not exist, route the request to the content management system controller
if (!file_exists(__DIR__ . "/controller/$page.php") && !$addons->routePage($page, "client")) {
    $page = "cms";
}

// Get top message
if (!empty($CFG['TOP_ALERT_MSG']) && unserialize($CFG['TOP_ALERT_MSG']) !== false) {
    $var['top_alert'] = unserialize($CFG['TOP_ALERT_MSG'])[$CFG['LANG']];
}

// Frontend footer by hook
$var['frontendFooter'] = "";
$res = $addons->runHook("FrontendFooter");
if (is_array($res)) {
    $var['frontendFooter'] = implode("\n", $res);
}

// Frontend variables by hook
$res = $addons->runHook("ClientTemplateVars");
foreach ($res as $r) {
    if (is_array($r)) {
        foreach ($r as $k => $v) {
            $var[$k] = $v;
        }
    }
}

// CSRF hook
$res = $addons->runHook("SkipCSRF", [
    "page" => $page,
    "pars" => $pars,
]);
$skipCsrf = false;
foreach ($res as $r) {
    if ($r) {
        $skipCsrf = true;
    }
}

// Activate CSRF protection
if (!$skipCsrf && !in_array($page, [
    "ipn",
    "api",
    "cli",
    "cron",
    "psc_gate",
    "websocket",
])) {
    CSRF::validate();
}

// Analytics
$ip = $db->real_escape_string(ip());

$ua = new UserAgent;
$os = $ua->getOperatingSystem();
$browser = $ua->getBrowserName();

$start = date("Y-m-d H:i:s", strtotime("-2 hours"));
$now = date("Y-m-d H:i:s");

$anPage = $page;
if (count($pars)) {
    $anPage .= "/" . implode(",", $pars);
}

$anPage = $db->real_escape_string($anPage);

$sql = $db->query("SELECT ID FROM visits WHERE ip = '$ip' AND os = '$os' AND browser = '$browser' AND `time` >= '" . date("Y-m-d") . " 00:00:00' AND `time` >= '$start'");
if ($sql->num_rows) {
    $id = intval($sql->fetch_object()->ID);
    $db->query("UPDATE visits SET `pages` = `pages` + 1, `last_action` = '$now', `end_page` = '$anPage' WHERE ID = $id");
} else {
    $db->query("INSERT INTO visits (`time`, `ip`, `os`, `browser`, `start_page`, `last_action`, `end_page`) VALUES ('$now', '$ip', '$os', '$browser', '$anPage', '$now', '$anPage')");
}

// Minimum age
$age->req(null, "https://google.com/");

// Reseller
if ($var['logged_in'] && $user->get()['reseller']) {
    if (!empty($var['global_info'])) {
        $var['global_info'] .= '</div><div class="alert alert-info">';
    }

    $var['global_info'] .= $lang['RESELLER']['INFO'] . "<br />[ <a href=\"{$CFG['PAGEURL']}reseller/customers\">{$lang['RESELLER']['CUSTOMERS']}</a> ] &nbsp; [ <a href=\"{$CFG['PAGEURL']}reseller/contracts\">{$lang['RESELLER']['CONTRACTS']}</a> ] &nbsp; [ <a href=\"{$CFG['PAGEURL']}reseller/config\">{$lang['RESELLER']['CONFIG']}</a> ] &nbsp; [ <a href=\"{$raw_cfg['PAGEURL']}res/?resid={$user->get()['ID']}\" target=\"_blank\">{$lang['RESELLER']['WI']}</a> ]";
}

// Impersonate
if ($var['logged_in'] && ($im = $user->impersonate())) {
    if (!empty($var['global_info'])) {
        $var['global_info'] .= '</div><div class="alert alert-info">';
    }

    $var['global_info'] .= $lang['IMPERSONATE']['INFO'] . "<ul>";

    foreach ($im as $uid) {
        $imSql = $db->query("SELECT firstname, lastname, company FROM clients WHERE ID = $uid");
        if ($imSql->num_rows) {
            $imInfo = $imSql->fetch_object();

            $name = $CFG['CNR_PREFIX'] . "$uid &nbsp; " . htmlentities($imInfo->firstname) . " " . htmlentities($imInfo->lastname);
            if ($imInfo->company) {
                $name .= " (" . htmlentities($imInfo->company) . ")";
            }

            $var['global_info'] .= "<li><a href='" . $CFG['PAGEURL'] . "impersonate/" . $uid . "'>$name</a></li>";
        }
    }

    $var['global_info'] .= "</ul>";
}

// Show the requested page within the template system
$smarty->show($page);
