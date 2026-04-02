<?php
// Global some variables for security reasons
global $session, $user, $db, $CFG, $var, $addons;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();

// Log into user log
$user->log("Hat sich ausgeloggt");

$addons->runHook("CustomerLogout", [
    "user" => $user,
]);

// Clear all necessary session details
$session->set('mail', '');
$session->set('pwd', '');
$session->set('admin_login', 0);
$session->set('tfa', false);
$session->set("card", array());
$var['logged_in'] = 0;

// Clear the auth cookie
setcookie("auth", "", time() - 86400, "/", null, $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);

// Redirect user to last page or start page
$additional = array();
foreach ($_GET as $k => $v) {
    if ($k != "p" && $k != "redirect_to" && $k != "add_product" && $k != "add_service") {
        $additional[$k] = $v;
    }
}

header('Location: ' . $CFG['PAGEURL'] . (isset($_GET['redirect_to']) ? $_GET['redirect_to'] : "") . rtrim("?" . http_build_query($additional), "?"));
exit;
