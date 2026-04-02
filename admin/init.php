<?php
// File for initialize the base system for admin area
define("SOURCEDESK", true);

// Set production error reporting setting until line 46
ini_set("display_errors", 1);
error_reporting(E_ERROR);

// Start session
session_cache_expire(600);
session_start();

// Error handler
function haseDESK_bug($data)
{
    global $CFG;

    if (function_exists("curl_init")) {
        $ch = curl_init("https://sourceway.de/de/sourcedesk_error/{$CFG['LICENSE_KEY']}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_exec($ch);
        curl_close($ch);
    }
}

function haseDESK_error($errCode, $errText, $errFile, $errLine)
{
    switch ($errCode) {
        case E_USER_ERROR:
            haseDESK_bug([
                "error" => $errText,
                "file" => $errFile,
                "line" => $errLine,
                "phpv" => PHP_VERSION,
                "os" => PHP_OS,
                "sapi" => PHP_SAPI,
            ]);
            break;
    }

    return false;
}

set_error_handler("haseDESK_error");

function haseDESK_exception($ex)
{
    global $CFG;

    haseDESK_bug([
        "exception" => $ex->getMessage(),
        "code" => $ex->getCode(),
        "file" => $ex->getFile(),
        "line" => $ex->getLine(),
        "trace" => $ex->getTraceAsString(),
        "phpv" => PHP_VERSION,
        "os" => PHP_OS,
        "sapi" => PHP_SAPI,
    ]);

    if (php_sapi_name() == "cli") {
        die("Uncaught exception: " . $ex->getMessage());
    } else {
        require __DIR__ . "/../lib/UncaughtException.php";
    }
}

set_exception_handler("haseDESK_exception");

// Define ADMIN_AREA
define("ADMIN_AREA", true);

// Include first language file we can find
foreach (glob(__DIR__ . "/../languages/admin.*.php") as $f) {
    if (!is_file($f) || substr($f, -11) == ".custom.php") {
        continue;
    }

    require $f;
    $var['lang_active'] = explode(".", basename($f))[1];
    break;
}

if (empty($lang)) {
    die("No admin language found");
}

// Try to include config for database credentials or give fatal error
if (!include (__DIR__ . '/../config.php')) {
    die($lang['GENERAL']['NOT_INSTALLED']);
}

// Remove legacy files
foreach (glob(__DIR__ . "/modules/core/*.class.php") as $f) {
    @unlink($f);
}

// Connect to MySQL database with credentials specified in config.php
// Catch any connection error with fatal error
require_once __DIR__ . "/../lib/Database.php";
$db = new DB($CFG['DB']['HOST'], $CFG['DB']['USER'], $CFG['DB']['PASSWORD'], $CFG['DB']['DATABASE']);
if ($db->connect_errno) {
    die($lang['GENERAL']['MYSQL_ERROR'] . ": " . $db->connect_error);
}

$db->set_charset("UTF8");

// Remove MySQL prefix
if ($CFG['DB']['PREFIX']) {
    $schema = $db->real_escape_string($CFG['DB']['DATABASE']);
    $prefix = $db->real_escape_string($CFG['DB']['PREFIX']);
    $sql = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema LIKE '$schema' AND table_name LIKE '$prefix%'");
    $tables = [];

    while ($row = $sql->fetch_object()) {
        $table = $db->real_escape_string($row->table_name);
        $prefixfree = $db->real_escape_string(substr($row->table_name, strlen($prefix)));
        if ($db->query("SELECT 1 FROM information_schema.tables WHERE table_schema LIKE '$schema' AND table_name LIKE '$prefixfree'")->num_rows) {
            throw new Exception("Table $prefixfree already exists");
        }
        $tables[] = $prefixfree;
    }

    foreach ($tables as $table) {
        $db->query("ALTER TABLE $prefix$table RENAME TO $table;");
    }

    // Replace all settings within config
    $file = file_get_contents(__DIR__ . "/../install/req/config.dist.php");
    $file = str_replace("%host%", $CFG['DB']['HOST'], $file);
    $file = str_replace("%user%", $CFG['DB']['USER'], $file);
    $file = str_replace("%pw%", $CFG['DB']['PASSWORD'], $file);
    $file = str_replace("%db%", $CFG['DB']['DATABASE'], $file);
    $file = str_replace("%gen%", $CFG['HASH'], $file);

    // Try to copy config to root dir
    if (!file_put_contents(__DIR__ . "/../config.php", $file)) {
        throw new Exception("Not able to put config.php");
    }

    throw new Exception("DB prefix removed, please reload page");
}

// Get all configuration variables from database and write them into @var CFG
if (!$db->query("SELECT 1 FROM settings")) {
    require_once __DIR__ . "/../lib/autoload.php";

    $ds = new DatabaseStructure();
    $ds->init();
    $ds->deploy($db);

    throw new Exception("Please reload page");
}

$cfg_sql = $db->query("SELECT * FROM settings");
if ($cfg_sql) {
    while ($c = $cfg_sql->fetch_object()) {
        $CFG[strtoupper($c->key)] = $c->value;
    }
}

$raw_cfg = $CFG;

if (count($CFG) < 10) {
    throw new Exception("Settings table empty");
}

// License system
// CHANGES ARE NOT PERMITTED!
try {
    function sd_licenseCheck($licenseKey = "", $cacheKey = "", $r = false)
    {
        global $db, $CFG;

        $secret = "2d3g88vj8gkqyxzxxfxaa92ca28gnqjv8ts45kvzadqqa976pmageqp4ucuasce2";
        $pid = [267, 268, 277, 278, 279];
        $url = "https://sourceway.de/";

        $dir = __DIR__;

        if (!empty($cacheKey)) {
            $ex = explode("|", $cacheKey);
            $signKey = implode("|", array_slice($ex, 0, 5));
            if (count($ex) > 6) {
                $signKey .= "|" . implode("|", array_slice($ex, 6));
            }

            if (
                count($ex) >= 6 &&
                in_array($ex[0], $pid) &&
                $ex[3] == $dir &&
                strtotime($ex[4]) >= strtotime(date("Y-m-d")) &&
                $ex[5] == hash("sha512", $secret . $signKey)
            ) {
                return array(true, $cacheKey);
            } else if ($r) {
                throw new Exception("Internal error");
            }

        }

        if (empty($licenseKey)) {
            throw new Exception("No license key");
        }

        $url = rtrim($url, "/") . "/api/license/info?key=" . urlencode($licenseKey) . "&dir=" . urlencode($dir);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            "data" => [
                "sourcedesk_version" => @file_get_contents(__DIR__ . "/../install/req/version.dist.txt"),
                "php_version" => PHP_VERSION,
                "operating_system" => PHP_OS,
                "clients" => $db->query("SELECT COUNT(*) c FROM clients")->fetch_object()->c,
                "products" => $db->query("SELECT COUNT(*) c FROM products")->fetch_object()->c,
                "contracts" => $db->query("SELECT COUNT(*) c FROM client_products")->fetch_object()->c,
                "domains" => $db->query("SELECT COUNT(*) c FROM domains")->fetch_object()->c,
            ],
        ]));
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res || !($res = json_decode($res))) {
            throw new Exception("License server connection failed");
        }

        if ($res->code != "100") {
            throw new Exception("License server: " . ($res->message ?? ""));
        }

        if (empty($res->data->cacheKey)) {
            throw new Exception("");
        }

        return sd_licenseCheck($licenseKey, $res->data->cacheKey, true);
    }

    if ($_SERVER['HTTP_HOST'] == "demo.sourcedesk.de") {
        $allowedCustomers = -1;
        $var['devLicense'] = $devLicense = false;
    } else {
        $res = sd_licenseCheck($CFG['LICENSE_KEY'], $CFG['LICENSE_ID']);

        if (!$res[0]) {
            if (isset($_GET['new_license_key'])) {
                $key = $_GET['new_license_key'];

                $res = sd_licenseCheck($key, "");

                if ($res[0]) {
                    $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($key) . "' WHERE `key` = 'license_key'");
                    $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($res[1]) . "' WHERE `key` = 'license_id'");

                    header('Location: ./');
                    exit;
                }
            }

            header('Location: ./license.php');
            exit;
        } else {
            $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($res[1]) . "' WHERE `key` = 'license_id'");
            $CFG['LICENSE_ID'] = $res[1];
        }

        $allowedCustomers = 0;
        $ex = explode("|", $CFG['LICENSE_ID']);

        switch ($ex[0]) {
            case "267":
            case "268":
            case "279":
                $allowedCustomers = -1;
                break;

            case "277":
                $allowedCustomers = 50;
                break;

            case "278":
                $allowedCustomers = 100;
                break;
        }

        $var['devLicense'] = $devLicense = $ex[0] == "279";

        if (count($ex) == 9) {
            $allowedCustomers = intval($ex[6]);
            $noBranding = boolval($ex[7]);
            $var['devLicense'] = $devLicense = boolval($ex[8]);
        }

        if ($allowedCustomers >= 0) {
            $is = $db->query("SELECT COUNT(*) AS c FROM clients")->fetch_object()->c;
            if ($is > $allowedCustomers) {
                throw new Exception("Customer limit exceeded (limit: $allowedCustomers, is: $is)");
            }
        }
    }
} catch (Exception $ex) {
    try {
        if (!empty($_GET['new_license_key'])) {
            $key = $_GET['new_license_key'];

            $res = sd_licenseCheck($key, "");

            if ($res[0]) {
                $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($key) . "' WHERE `key` = 'license_key'");
                $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($res[1]) . "' WHERE `key` = 'license_id'");

                header('Location: ./');
                exit;
            }
        }
    } catch (Exception $ex) {
        header('Location: ./license.php?reason=' . urlencode($ex->getMessage()));
        exit;
    }

    header('Location: ./license.php?reason=' . urlencode($ex->getMessage()));
    exit;
}
// CHANGES ARE NOT PERMITTED!

// Handle single auth request
if (isset($_GET['sa_pagename'])) {
    die("PN:" . $raw_cfg['PAGENAME']);
}

if (isset($_GET['sa_check'])) {
    $sql = $db->query("SELECT 1 FROM admins WHERE username = '" . $db->real_escape_string($_GET['sa_check']) . "' AND sat != '' AND sat = '" . $db->real_escape_string($_GET['sa_token']) . "'");
    if ($sql->num_rows == 1) {
        echo "ok";
    } else {
        echo "nok";
    }

    $db->query("UPDATE admins SET sat = '' WHERE username = '" . $db->real_escape_string($_GET['sa_check']) . "'");
    exit;
}

// Secure session
$currentSessionParams = session_get_cookie_params();
session_set_cookie_params($currentSessionParams['lifetime'], $currentSessionParams['path'], $currentSessionParams['domain'], $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);

// Get error reporting setting from @var CFG and set it for runtime
if ($CFG['DISPLAY_ERRORS'] == "errors_warnings") {
    error_reporting(E_ERROR | E_WARNING);
} else if ($CFG['DISPLAY_ERRORS'] == "errors") {
    error_reporting(E_ERROR);
} else if ($CFG['DISPLAY_ERRORS'] == "errors_warnings_notices") {
    error_reporting(E_ERROR | E_WARNING);
} else {
    error_reporting(0);
}

// Check if explicit SSL is defined by admin
if ($CFG['EXPLICIT_SSL']) {
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "" || $_SERVER['HTTPS'] == "off") {
        // If user is not using https redirect him
        $redirect = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header("Location: $redirect");
        exit;
    }
}

// Check for HSTS
if ($CFG['HSTS']) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

require __DIR__ . "/../lib/SiteActions.php";

if ($CFG['BLOCK_PROXY'] && IdentifyProxy::is()) {
    header('Location: ../');
    exit;
}

// Write all available languages into array
$adminLanguages = array();
$languages = array();
$defaultLanguages = array();
$isoCodes = array();

function currentLang()
{
    global $adminInfo, $CFG;

    if (empty($adminInfo)) {
        return $CFG['LANG'];
    }

    return $adminInfo->language ?: $CFG['LANG'];
}

foreach (Language::getLanguageFiles() as $language) {
    require __DIR__ . "/../languages/" . basename($language) . ".php";

    $languages[$language] = $lang['NAME'];
    if (is_array($lang['LANG_CODES']) && count($lang['LANG_CODES']) > 0) {
        $defaultLanguages[$language] = $lang['NAME'];
    }
    $isoCodes[$language] = $lang['ISOCODE'];
}

foreach (Language::getAdminLanguages() as $language) {
    require __DIR__ . "/../languages/admin." . basename($language) . ".php";
    $adminLanguages[$language] = $lang['NAME'];
}

asort($adminLanguages);
asort($languages);

// Require language
$nL = basename($CFG['LANG']);
if (file_exists(__DIR__ . "/../languages/admin.$nL.php")) {
    require __DIR__ . "/../languages/admin.$nL.php";
}

// Set default timezone from @var CFG
date_default_timezone_set(unserialize($CFG['TIMEZONE'])[$CFG['LANG']]);

// Set default date/number format
$CFG['NUMBER_FORMAT'] = unserialize($raw_cfg['NUMBER_FORMAT'])[$CFG['LANG']];
$CFG['DATE_FORMAT'] = unserialize($raw_cfg['DATE_FORMAT'])[$CFG['LANG']];

$ari = new AdminRights;
$adminRights = $ari->get();

// Build redirection parameter/s
$redirection_parameters = array();
if (isset($_GET['p']) && $_GET['p'] != "index") {
    $redirection_parameters['p'] = $_GET['p'];
}

$notAllowed = array("p", "language", "incorrect", "tfa", "usr", "c");
foreach ($_GET as $k => $v) {
    if (!in_array($k, $notAllowed)) {
        $redirection_parameters[$k] = $v;
    }
}

if (!$ari->accessAllowed() && strpos($_SERVER['PHP_SELF'], "whitelist.php") === false) {
    header('Location: ./whitelist.php' . rtrim("?" . http_build_query($redirection_parameters), "?"));
    exit;
}

// Define global salt
if (trim($CFG['GLOBAL_SALT']) == "") {
    $CFG['GLOBAL_SALT'] = $sec->generateSalt();
    $db->query("UPDATE settings SET value = '" . $db->real_escape_string(encrypt($CFG['GLOBAL_SALT'])) . "' WHERE `key` = 'global_salt' LIMIT 1");
    alog("general", "global_salt_generated");
}

// Require modules
class ModuleException extends Exception
{}

$moduleHandle = opendir(__DIR__ . '/../modules/core/');
while ($f = readdir($moduleHandle)) {
    if (is_file(__DIR__ . '/../modules/core/' . $f) && substr($f, 0, 1) != ".") {
        require_once __DIR__ . '/../modules/core/' . $f;
    }
}

closedir($moduleHandle);

if (!defined('BYPASS_AUTH')) {
    // Select all administrators from database
    $admins = array();
    $admin_salts = array();
    $q = $db->query("SELECT * FROM admins");
    while ($a = $q->fetch_object()) {
        $admins[$a->username] = $a->password;
        $admin_salts[$a->username] = $a->salt;
    }

    // Handle a login
    if (isset($_POST['login'])) {
        $usr = $_POST['user'];
        $pwd = isset($_POST['password']) ? $_POST['password'] : "";

        // Get client IP address
        $ip = ip();

        try {
            if (!isset($admins[$usr])) {
                alog("login", "not_found", $usr);
                // Send admin notification/s
                if (($ntf = AdminNotification::getInstance("Fehlgeschlagener Admin-Login")) !== false) {
                    $ntf->set("usr", $usr);
                    $ntf->set("ip", $ip);
                    $ntf->set("staff", "");
                    $ntf->send();
                }
                throw new Exception();
            }

            $adminInfo = $db->query("SELECT * FROM admins WHERE username = '" . $db->real_escape_string($usr) . "' LIMIT 1")->fetch_object();
            $adminId = $adminInfo->ID;

            if ($admins[$usr] != $sec->adminHash($pwd, $admin_salts[$usr]) && (!isset($_POST['hashed']) || $CFG['CLIENTSIDE_HASHING_ADMIN'] != 1 || $sec->adminHash($_POST['hashed'], $admin_salts[$usr], true) != $admins[$usr])) {
                if (!empty($pwd) && $pwd == $admins[$usr]) {
                    $slt = $sec->generateSalt();
                    $pwd = $sec->adminHash($pwd, $slt, false);
                    $db->query("UPDATE admins SET password = '" . $db->real_escape_string($pwd) . "', salt = '" . $db->real_escape_string($slt) . "' WHERE ID = $adminId LIMIT 1");
                } else if (!empty($_POST['hashed']) && $CFG['CLIENTSIDE_HASHING_ADMIN'] == 1 && $sec->adminHashClient($admins[$usr]) == $_POST['hashed']) {
                    $slt = $sec->generateSalt();
                    $pwd = $sec->adminHash($_POST['hashed'], $slt, true);
                    $db->query("UPDATE admins SET password = '" . $db->real_escape_string($pwd) . "', salt = '" . $db->real_escape_string($slt) . "' WHERE ID = $adminId LIMIT 1");
                } else {
                    alog("login", "wrong_pw", $usr);
                    // Send admin notification/s
                    if (($ntf = AdminNotification::getInstance("Fehlgeschlagener Admin-Login")) !== false) {
                        $ntf->set("usr", $usr);
                        $ntf->set("ip", $ip);
                        $ntf->set("staff", " ({$adminInfo->name})");
                        $ntf->send();
                    }
                    throw new Exception();
                }
            }

            if (!$ari->check(1, $adminInfo->ID)) {
                alog("login", "insufficient_rights", $usr);
                throw new Exception();
            }

            $tfaCdt = "";
            if ($adminInfo->tfa != "none" && $adminInfo->tfa != "") {
                if (isset($_POST['ajax']) && empty($_POST['2fa'])) {
                    sleep(1.5);
                    die("tfa");
                }

                $otp = $_POST['2fa'];

                if ($db->query("SELECT ID FROM admin_tfa WHERE user = $adminId AND code = '" . $db->real_escape_string($_POST['2fa']) . "'")->num_rows > 0) {
                    alog("login", "code_used", $usr);
                    // Send admin notification/s
                    if (($ntf = AdminNotification::getInstance("Fehlgeschlagener Admin-Login")) !== false) {
                        $ntf->set("usr", $usr);
                        $ntf->set("ip", $ip);
                        $ntf->set("staff", " ({$adminInfo->name})");
                        $ntf->send();
                    }
                    throw new Exception();
                }

                $tfaDone = false;
                if ($adminInfo->tfa_second != "" && $adminInfo->tfa_valid >= time() && hash("sha512", $otp . $CFG['SALT']) == $adminInfo->tfa_second) {
                    $tfaDone = true;
                    $db->query("UPDATE admins SET tfa_second = '' WHERE ID = " . $adminInfo->ID . " LIMIT 1");
                }

                if (!$tfaDone && !$tfa->verifyCode($adminInfo->tfa, $otp, 2)) {
                    alog("login", "wrong_code", $usr);
                    // Send admin notification/s
                    if (($ntf = AdminNotification::getInstance("Fehlgeschlagener Admin-Login")) !== false) {
                        $ntf->set("usr", $usr);
                        $ntf->set("ip", $ip);
                        $ntf->set("staff", " ({$adminInfo->name})");
                        $ntf->send();
                    }
                    throw new Exception();
                }

                // If the code is correct and was not used before, the login is successful
                $tfa = true;
                $tfaCdt = ":" . $adminInfo->tfa;
                $db->query("INSERT INTO admin_tfa (`user`, `code`, `time`) VALUES ($adminId, '" . $db->real_escape_string($_POST['2fa']) . "', " . time() . ")");
            }

            // Save session (username, password and - if set - two-factor secret)
            $session->set("credentials", $usr . ":" . $adminInfo->password . $tfaCdt);
            $session->set("admin", "1");
            $session->set("admin_login", "1");

            if (isset($_POST['cookie'])) {
                // If the user wants to set a cookie, generate a random key and insert it with the authentication information into the database
                $key = "";
                for ($i = 0; $i < 5; $i++) {
                    if (function_exists("random_bytes")) {
                        $key .= bin2hex(random_bytes(22));
                    } else {
                        $key .= bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
                    }
                }

                $valid = time() + 60 * 60 * 24 * 30;

                $db->query("INSERT INTO admin_cookie (`string`, `valid`, `user`, `auth`) VALUES ('" . hash("sha512", $key) . "', $valid, " . $adminId . ", '" . $adminInfo->password . $tfaCdt . "')");

                // Save key on the clients local computer
                setcookie("admin_auth", $key, $valid, null, null, $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);
            }

            // Overwrite language if other was set explicit on login screen
            if (isset($adminLanguages[$session->get('admin_language')])) {
                require __DIR__ . "/../languages/admin." . basename($session->get('admin_language')) . ".php";
                $db->query("UPDATE admins SET language = '" . $db->real_escape_string(basename($session->get('admin_language'))) . "' WHERE ID = " . $adminInfo->ID . " LIMIT 1");
                $adminInfo->language = basename($session->get('admin_language'));
            }

            $addons->runHook("AdminLogin", [
                "admin" => $adminInfo,
            ]);

            alog("login", "ok");

            if (isset($_POST['ajax'])) {
                die("ok");
            }
        } catch (Exception $ex) {
            // If anything gone wrong, clear the session and redirect admin back to login
            $session->set("credentials", "");
            $session->set("admin", "");
            $session->set("admin_login", "");
            setcookie('admin_auth', '', time() - 86400, null, null, $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);
            $c = isset($_POST['cookie']) ? '&c=1' : '';
            $f2b->failedLogin();

            if (isset($_POST['ajax'])) {
                sleep(1.5);
                die("fail");
            }

            header('Location: ./login.php?incorrect=1&usr=' . $usr . $c . rtrim("&" . http_build_query($redirection_parameters), "&"));
            exit;
        }
    }

    // If no admin is logged in, check if there is any cookie on the clients local computer
    if (($session->get('credentials') == null || $session->get('credentials') == "") && isset($_COOKIE['admin_auth'])) {
        try {
            // Select cookie key from database
            $sql = $db->query("SELECT auth, user FROM admin_cookie WHERE valid >= " . time() . " AND string = '" . $db->real_escape_string(hash("sha512", $_COOKIE['admin_auth'])) . "' LIMIT 1");
            if ($sql->num_rows != 1) {
                alog("login", "wrong_cookie_found");
                throw new Exception();
            }

            // Get information from database
            $info = $sql->fetch_object();
            $aid = $info->user;
            $auth = $info->auth;

            // Select user information from database
            $usql = $db->query("SELECT ID, username, password, tfa FROM admins WHERE ID = $aid");
            if ($usql->num_rows != 1) {
                alog("login", "wrong_cookie_user");
                throw new Exception();
            }
            $uinfo = $adminInfo = $usql->fetch_object();

            // Explode auth parameters (delimiter :)
            $ex = explode(":", $auth);

            // Check password
            if ($ex[0] != $uinfo->password) {
                alog("login", "wrong_cookie_pw");
                throw new Exception();
            }

            // Get information about TFA
            $tfaCdt = "";
            if (isset($ex[1]) && $ex[1] == $uinfo->tfa) {
                $tfaCdt = ":" . $uinfo->tfa;
            }

            // Set session
            $session->set('credentials', $uinfo->username . ":" . $uinfo->password . $tfaCdt);
            $session->set("admin_login", "1");
            $session->set("admin", "1");
        } catch (Exception $ex) {
            // If anything goes wrong, clear the cookie
            setcookie("admin_auth", "", time() - 86400, null, null, $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);
        }
    }

    // Get admin information from session and database
    $ex = explode(":", $session->get("credentials"));
    $adminUsername = $ex[0];
    $adminInfo = $db->query("SELECT * FROM admins WHERE username = '" . $adminUsername . "' LIMIT 1")->fetch_object();

    // If no session exists, the admin does not exists, the password is wrong or the admin has no rights to enter admin area, clear the session and redirect him to login page
    try {
        if (!$session->get("credentials") || !$ex || count($ex) < 2) {
            throw new Exception();
        }

        if (!isset($admins[$ex[0]])) {
            alog("login", "wrong_user_session");
            throw new Exception();
        }

        if ($admins[$ex[0]] != $ex[1]) {
            alog("login", "wrong_password_session");
            throw new Exception();
        }

        if (!$ari->check(1, $adminInfo->ID)) {
            alog("login", "insufficient_session");
            throw new Exception();
        }

        if ($adminInfo->tfa != "none" && $ex[2] != $adminInfo->tfa) {
            // If the two-factor authentication is not bypassed, clear the session and redirect the user to login page
            alog("login", "tfa_wrong_session");
            throw new Exception();
        }
    } catch (Exception $ex) {
        // Handle single auth
        try {
            if (empty($_GET['sa_user']) || empty($_GET['sa_user2']) || empty($_GET['sa_from']) || empty($_GET['sa_token'])) {
                throw new Exception();
            }

            $c = file_get_contents($_GET['sa_from'] . "/admin/?sa_check=" . urlencode($_GET['sa_user2']) . "&sa_token=" . urlencode($_GET['sa_token']));
            if ($c != "ok") {
                throw new Exception();
            }

            $sql = $db->query("SELECT sa FROM admins WHERE username = '" . $db->real_escape_string($_GET['sa_user']) . "'");
            if ($sql->num_rows != 1) {
                throw new Exception();
            }

            $sa = unserialize($sql->fetch_object()->sa) ?: [];

            $found = false;
            $from = rtrim($_GET['sa_from'], "/");
            $user = $_GET['sa_user2'];
            foreach ($sa as $s) {
                $s[0] = rtrim($s[0], "/");
                if ($s[1] != $user) {
                    continue;
                }

                if ($s[0] != $from) {
                    continue;
                }

                if (!($s[3] ?? false)) {
                    continue;
                }

                $found = true;
                break;
            }

            if (!$found) {
                throw new Exception();
            }

            $adminInfo = $db->query("SELECT * FROM admins WHERE username = '" . $db->real_escape_string($_GET['sa_user']) . "' LIMIT 1")->fetch_object();
            $adminId = $adminInfo->ID;

            $tfaCdt = "";
            if (!empty($adminInfo->tfa) && $adminInfo->tfa != "none") {
                $tfaCdt = ":" . $adminInfo->tfa;
            }

            $session->set("credentials", $adminInfo->username . ":" . $adminInfo->password . $tfaCdt);
            $session->set("admin", "1");
            $session->set("admin_login", "1");

            $key = "";
            for ($i = 0; $i < 5; $i++) {
                if (function_exists("random_bytes")) {
                    $key .= bin2hex(random_bytes(22));
                } else {
                    $key .= bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
                }
            }

            $valid = time() + 60 * 60 * 24 * 30;

            $db->query("INSERT INTO admin_cookie (`string`, `valid`, `user`, `auth`) VALUES ('" . hash("sha512", $key) . "', $valid, " . $adminId . ", '" . $adminInfo->password . $tfaCdt . "')");

            setcookie("admin_auth", $key, $valid, null, null, $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);

            header('Location: ./');
            exit;
        } catch (Exception $ex) {
            $session->set("credentials", "");
            $session->set("admin", "");
            $session->set("admin_login", "");
            setcookie('admin_auth', '', time() - 86400, null, null, $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);

            if (!empty($_POST['ajax_req'])) {
                die("no_session");
            }

            header('Location: ./login.php' . rtrim("?" . http_build_query($redirection_parameters), "?"));
            exit;
        }
    }

    // Set admin session - for example to bypass maintenance mode
    $session->set('admin', 1);

    // Use admins language
    if (isset($adminInfo->language) && file_exists(__DIR__ . "/../languages/admin." . $adminInfo->language . ".php") && $adminInfo->lang != $var['lang_active']) {
        require __DIR__ . "/../languages/admin." . $adminInfo->language . ".php";
        $var['lang_action'] = $adminInfo->language;
    }

    // Check if admin wants to change the language
    if (isset($_POST['change_language'])) {
        $new_language = $db->real_escape_string(basename($_POST['new_language']));
        if (file_exists(__DIR__ . "/../languages/admin.$new_language.php")) {
            require __DIR__ . "/../languages/admin.$new_language.php";
            $db->query("UPDATE admins SET language = '$new_language' WHERE ID = " . $adminInfo->ID . " LIMIT 1");
            alog("general", "language_changed", $adminInfo->language, $new_language);
            $adminInfo->language = $new_language;
        }
    }

    // Set new timezone if necessary
    date_default_timezone_set(unserialize($CFG['TIMEZONE'])[$adminInfo->language]);

    // Set admin date/number format
    $CFG['NUMBER_FORMAT'] = unserialize($raw_cfg['NUMBER_FORMAT'])[$adminInfo->language];
    $CFG['DATE_FORMAT'] = unserialize($raw_cfg['DATE_FORMAT'])[$adminInfo->language];
    $nfo->__construct();
    $dfo->__construct();

    // Check if the administrator wants to logout
    if (isset($_GET['a']) && $_GET['a'] == "logout") {
        alog("login", "logout");

        $addons->runHook("AdminLogout", [
            "admin" => $adminInfo,
        ]);

        $session->set("credentials", "");
        $session->set("admin_otp", "");
        $session->set("admin", "");
        $session->set("admin_login", "");
        setcookie('admin_auth', '', time() - 86400, null, null, $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);
        header('Location: ./');
        exit;
    }
}

$addons->construct();

function alog()
{
    global $CFG, $db, $adminInfo;

    $ip = ip();

    $id = is_object($adminInfo) ? intval($adminInfo->ID) : 0;

    $last = $db->query("SELECT `action` FROM `admin_log` WHERE `admin` = $id AND `ip` = '" . $db->real_escape_string($ip) . "' ORDER BY `ID` DESC LIMIT 1");
    if ($last->num_rows) {
        $action = $last->fetch_object()->action;
        if ($action == serialize(func_get_args())) {
            return;
        }
    }

    $db->query("INSERT INTO `admin_log` (`time`, `admin`, `action`, `ip`) VALUES (" . time() . ", $id, '" . $db->real_escape_string(serialize(func_get_args())) . "', '" . $db->real_escape_string($ip) . "')");
}

$var['adminInfo'] = (array) $adminInfo;

if (SepaDirectDebit::active() && $ari->check(7)) {
    $var['sepa'] = $db->query("SELECT 1 FROM client_transactions WHERE sepa_done = 0")->num_rows;
}

if (!empty($adminInfo)) {
    $adminInfo->can_call = false;
    $ex = explode("|", $adminInfo->call_info);
    if (!empty($ex[0]) && array_key_exists($ex[0], $telephone->get())) {
        $adminInfo->can_call = true;
    }
}

// This is new modal
$ignored = explode(",", $CFG['TIN_MODAL']);
if ($CFG['TIN_MODAL'] == $CFG['VERSION']) {
    $ignored = [];
}

if (isset($_POST['hide_tin_modal'])) {
    array_push($ignored, $adminInfo->ID);
    $new = trim($db->real_escape_string(implode(",", $ignored)), ",");
    $db->query("UPDATE settings SET `value` = '$new' WHERE `key` = 'tin_modal'");
    die("ok");
}

$var['tin'] = "";
if (!in_array($adminInfo->ID, $ignored) && file_exists(__DIR__ . "/res/tin.php")) {
    ob_start();
    require __DIR__ . "/res/tin.php";
    $var['tin'] = ob_get_contents();
    ob_end_clean();
}

$sid = $db->real_escape_string(substr(hash("sha512", session_id()), 0, 64));
$db->query("UPDATE admins SET last_sid = '$sid' WHERE ID = " . $adminInfo->ID . " LIMIT 1");
