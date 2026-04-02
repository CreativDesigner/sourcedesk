<?php
// File for initialize the base system for customer area
define("SOURCEDESK", true);

// Set production error reporting setting until line 46
ini_set("display_errors", 1);
error_reporting(E_ERROR);

// UTF8 encode
ini_set("default_charset", "UTF-8");

// Start session
if (empty($_POST['jtlrpc'])) {
    session_cache_expire(600);
    session_start();
}

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
        require __DIR__ . "/lib/UncaughtException.php";
    }
}

set_exception_handler("haseDESK_exception");

// Include first language file we can find
foreach (glob(__DIR__ . "/languages/*.php") as $f) {
    if (!is_file($f) || substr(basename($f), 0, 6) == "admin." || substr(basename($f), 0, -11) == ".custom.php") {
        continue;
    }

    require $f;
    $var['lang_active'] = explode(".", basename($f))[0];
    break;
}

if (empty($lang)) {
    die("No language found");
}

// Try to include config for database credentials or give fatal error
if (!include (__DIR__ . '/config.php')) {
    $path = "./";
    $ex = explode("/", $_SERVER['REQUEST_URI']);

    foreach ($ex as $e) {
        if (!empty($e)) {
            $path .= "../";
        }
    }

    header('Location: ' . $path . 'install/index.php');
    exit;
}

// Remove legacy files
foreach (glob(__DIR__ . "/modules/core/*.class.php") as $f) {
    @unlink($f);
}

// Connect to MySQL database with credentials specified in config.php
// Catch any connection error with fatal error
require_once __DIR__ . "/lib/Database.php";
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
    $file = file_get_contents(__DIR__ . "/install/req/config.dist.php");
    $file = str_replace("%host%", $CFG['DB']['HOST'], $file);
    $file = str_replace("%user%", $CFG['DB']['USER'], $file);
    $file = str_replace("%pw%", $CFG['DB']['PASSWORD'], $file);
    $file = str_replace("%db%", $CFG['DB']['DATABASE'], $file);
    $file = str_replace("%gen%", $CFG['HASH'], $file);

    // Try to copy config to root dir
    if (!file_put_contents(__DIR__ . "/config.php", $file)) {
        throw new Exception("Not able to put config.php");
    }

    throw new Exception("DB prefix removed, please reload page");
}

// Get all configuration variables from database and write them into @var CFG
if (!$cfg_sql = $db->query("SELECT * FROM settings")) {
    $path = "./";
    $ex = explode("/", $_SERVER['REQUEST_URI']);

    foreach ($ex as $e) {
        if (!empty($e)) {
            $path .= "../";
        }
    }

    header('Location: ' . $path . 'install/index.php');
    exit;
}
while ($c = $cfg_sql->fetch_object()) {
    $CFG[strtoupper($c->key)] = $c->value;
}

$raw_cfg = $CFG;

// License request
// CHANGES ARE NOT PERMITTED!
if (isset($_GET['sw_license_check']) && ctype_alnum($_GET['sw_license_check']) && file_get_contents("https://sourceway.de/de/license_check/" . $_GET['sw_license_check']) == "ok") {
    if (isset($_GET['sw_license_reset'])) {
        $db->query("UPDATE settings SET `value` = '' WHERE `key` LIKE 'LICENSE_ID'");
        die("-LRESOK-");
    }

    die("-LB-" . $CFG['LICENSE_KEY'] . "-LE-");
}
// CHANGES ARE NOT PERMITTED!

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
        if (php_sapi_name() != "cli") {
            exit;
        }

    }
}

// Check for HSTS
if ($CFG['HSTS']) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

require __DIR__ . "/lib/SiteActions.php";

// Write all available languages into array
$languages = array();
$supportedLanguages = array();
$isoLanguages = array();

function currentLang()
{
    global $var, $CFG;

    if (empty($var) || !array_key_exists("myLang", $var)) {
        return $CFG['LANG'];
    }

    return $var['myLang'] ?: $CFG['LANG'];
}

$oldLang = $lang;
foreach (Language::getClientLanguages() as $language) {
    require __DIR__ . "/languages/" . basename($language) . ".php";

    $languages[$language] = $lang['NAME'];
    $isoLanguages[$lang['ISOCODE']] = $language;
    $lngCodes = array();
    foreach ($lang['LANG_CODES'] as $code) {
        $lngCodes[$code] = $language;
    }

    if ($language == $CFG['LANG']) {
        $supportedLanguages = array_merge($lngCodes, $supportedLanguages);
    } else {
        $supportedLanguages = array_merge($supportedLanguages, $lngCodes);
    }
}
$lang = $oldLang;

// Sort languages
asort($languages);

// Set default timezone from @var CFG
date_default_timezone_set(unserialize($CFG['TIMEZONE'])[$CFG['LANG']]);

// Set default date/number format
$CFG['NUMBER_FORMAT'] = unserialize($raw_cfg['NUMBER_FORMAT'])[$CFG['LANG']];
$CFG['DATE_FORMAT'] = unserialize($raw_cfg['DATE_FORMAT'])[$CFG['LANG']];

$var['ca_disabled'] = $CFG['BLOCK_PROXY'] && IdentifyProxy::is();

// Define global salt
if (trim($CFG['GLOBAL_SALT']) == "") {
    $CFG['GLOBAL_SALT'] = $sec->generateSalt();
    $db->query("UPDATE settings SET value = '" . $db->real_escape_string(encrypt($CFG['GLOBAL_SALT'])) . "' WHERE `key` = 'global_salt' LIMIT 1");
}

// Check for newer system version if that was not done in the last 48 hours
if ($CFG['LAST_VERSION_UPDATE'] < time() - (60 * 60 * 24 * 2)) {
    Versioning::actualVersion();
}

// Delete used two factor codes after a specified period (10 minutes)
$db->query("DELETE FROM admin_tfa WHERE time < " . (time() - 600));
$db->query("DELETE FROM client_tfa WHERE time < " . (time() - 600));

// Get page for further actions
if (php_sapi_name() == "cli") {
    $_GET['p'] = $argv[1];
}

if (!isset($_GET['p'])) {
    $_GET['p'] = "";
}

$ex = explode("/", $_GET['p']);
$realPage = $_GET['p'];
if (isset($isoLanguages[$ex[0]])) {
    $CFG['LANG'] = $isoLanguages[$ex[0]];
    unset($ex[0]);
    $realPage = implode("/", $ex);
}

// Branding
if ($branding = Branding::get()) {
    if (!empty($branding->design) && is_dir(__DIR__ . "/themes/" . basename($branding->design)) && $branding->design != "order") {
        $CFG['THEME'] = $raw_cfg['THEME'] = $branding->design;
    }

    if (!empty($branding->pagename)) {
        $CFG['PAGENAME'] = $raw_cfg['PAGENAME'] = $branding->pagename;
    }

    if (!empty($branding->pageurl)) {
        $CFG['PAGEURL'] = $raw_cfg['PAGEURL'] = $branding->pageurl;
    }

    if (!empty($branding->pagemail) && filter_var($branding->pagemail, FILTER_VALIDATE_EMAIL)) {
        $CFG['PAGEMAIL'] = $raw_cfg['PAGEMAIL'] = $branding->pagemail;
    }
}

// Check if init.php is used from admin area or from client area
if (!defined("ADMIN_AREA") || !ADMIN_AREA) {
    // init.php is required from client area

    $cdn_count = -1;
    function cdnurl()
    {
        global $cdn_count, $CFG, $raw_cfg;
        if (empty($CFG['CDN_URLS'])) {
            return $raw_cfg['PAGEURL'];
        }

        if ($cdn_count == -1) {
            $cdn_count = 0;
            return rtrim($raw_cfg['PAGEURL'], "/") . "/";
        } else {
            $cdn_old = $cdn_count;
            $cdn_count++;
            if ($cdn_count >= count(explode(",", $CFG['CDN_URLS']))) {
                $cdn_count = -1;
            }

            return rtrim(explode(",", $CFG['CDN_URLS'])[$cdn_old], "/") . "/";
        }
    }

    // initialize a new object of the template engine
    $smarty = new SmartyEngine;

    // Register Smarty functions
    $smarty->register("dfo", array(&$dfo, "format_smarty"));
    $smarty->register("nfo", array(&$nfo, "format_smarty"));
    $smarty->register("infix", array(&$cur, "infix_smarty"));
    $smarty->register("conva", array(&$cur, "conva_smarty"));
    $smarty->register("product", array(&$smarty, "product_view"));
    $smarty->register("group", array(&$smarty, "group_view"));
    $smarty->register("cdnurl", "cdnurl");
    $smarty->register("ct", array("CSRF", "raw"));
    $smarty->register("cf", array("CSRF", "html"));

    // predefine a few template vars
    $var['tos'] = 0;
    $var['locked'] = 0;
    $var['is_admin'] = $session->get('admin') == 1;

    // Hide header or footer
    $var['hideHeader'] = !empty($_REQUEST['hide_header']);
    $var['hideFooter'] = !empty($_REQUEST['hide_footer']);

    // Client area branding
    $var['branding'] = ($CFG['BRANDING'] || $brandingRequired) ? '<a href="https://sourceway.de/de/sourcedesk" target="_blank">Powered by haseDESK</a>' : "";

    // Load addons
    class ModuleException extends Exception
    {}

    $moduleHandle = opendir(__DIR__ . '/modules/core/');
    while ($f = readdir($moduleHandle)) {
        if (is_file(__DIR__ . '/modules/core/' . $f) && substr($f, 0, 1) != ".") {
            require_once __DIR__ . '/modules/core/' . $f;
        }
    }

    closedir($moduleHandle);
    $var['addons'] = $addons;

    // if the client is logged in, define maintenance mode as false
    if ($session->get('admin') == 1) {
        $CFG['MAINTENANCE'] = false;
    }

    // Allowed pages from addons
    $allowedPages = ["ipn", "maintenance", "terms", "privacy", "withdrawal", "imprint", "cron", "email", "status", "link", "issue", "ssh_access"];

    $arr = $addons->runHook("MaintenanceAllowedPages");
    if (is_array($arr)) {
        foreach ($arr as $p) {
            if (is_array($p) && count($p) > 0) {
                $allowedPages = array_merge($allowedPages, $p);
            }
        }
    }

    // If the user is on maintenance page but maintenance mode is not active, redirect him to site
    if (!$CFG['MAINTENANCE'] && $realPage == "maintenance") {
        header('Location: ' . $CFG['PAGEURL']);
        if (php_sapi_name() != "cli") {
            exit;
        }
    }

    // Maintenance mode
    if ($CFG['MAINTENANCE'] && !in_array($realPage, $allowedPages)) {
        if (php_sapi_name() != "cli") {
            header('Location: ' . $CFG['PAGEURL'] . 'maintenance');
            exit;
        }
    } else if ($CFG['MAINTENANCE']) {
        $doNotRedirect = 1;
    }

    // Pass @var maintenance to layout template for enabling/disabled menu
    $var['maintenance'] = $CFG['MAINTENANCE'];

    // We select all currencies from database
    $currencySql = $db->query("SELECT * FROM currencies ORDER BY name ASC");
    $currencies = array();
    while ($row = $currencySql->fetch_assoc()) {
        $currencies[$row["currency_code"]] = $row;
    }

    $var['currencies'] = $currencies;

    // We can set a currency with GET parameter
    if (isset($_GET['currency']) && isset($currencies[$_GET['currency']])) {
        $var['myCurrency'] = $_GET['currency'];
        $session->set('currency', $_GET['currency']);
        setcookie("currency", $_GET['currency'], time() + 86400 * 365, "/", null, $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);

        // Save the choice to the logged in user
        $setCurrency = $_GET['currency'];
        // ...or get it from POST ...
    } else if (isset($_POST['currency']) && isset($currencies[$_POST['currency']])) {
        $var['myCurrency'] = $_POST['currency'];
        $session->set('currency', $_POST['currency']);
        setcookie("currency", $_POST['currency'], time() + 86400 * 365, "/", null, $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);

        // Save the choice to the logged in user
        $setCurrency = $_POST['currency'];
        // ...or get it from session ...
    } else if (is_string($session->get('currency')) && isset($currencies[$session->get('currency')])) {
        $var['myCurrency'] = $session->get('currency');
        $overwriteCurrency = true;
        // ...or from cookies ...
    } else if (isset($_COOKIE['currency']) && isset($currencies[$_COOKIE['currency']])) {
        $var['myCurrency'] = $_COOKIE['currency'];
        $overwriteCurrency = true;
        // We get the default currency if the user did not make any choose
    } else {
        $var['myCurrency'] = $cur->getBaseCurrency();
        $overwriteCurrency = true;
    }

    // If nobody is logged in, check if a cookie exists
    if ($session->get('mail') == null && isset($_COOKIE['auth']) && !$var['ca_disabled']) {
        // Get the cookie auth string from database
        $sql = $db->query("SELECT auth, user FROM client_cookie WHERE valid >= " . time() . " AND string = '" . $db->real_escape_string(hash("sha512", $_COOKIE['auth'])) . "' LIMIT 1");
        if ($sql->num_rows == 1) {
            // If the cookie is valid, get any information
            $info = $sql->fetch_object();
            $uid = $info->user;
            $auth = $info->auth;

            // Get information about the user from the database
            $usql = $db->query("SELECT mail, pwd, tfa FROM clients WHERE ID = $uid");

            // Check if user exists
            if ($usql->num_rows == 1) {
                $uinfo = $usql->fetch_object();

                // Explode the auth parameter (delimiter :)
                $ex = explode(":", $auth);

                // Check if password is correct (can be changed from another device)
                if ($ex[0] == $uinfo->pwd) {
                    // Setup the user session
                    $session->set('mail', $uinfo->mail);

                    $pwd = $uinfo->pwd;
                    if ($sec->hash($pwd, $uinfo->salt) == $pwd) {
                        $pwd = md5($pwd);
                    }

                    $session->set('pwd', $pwd);

                    // two factor authentication bridge (no request if it was entered before)
                    $session->set('tfa', false);
                    if (isset($ex[1])) {
                        if ($ex[1] == $uinfo->tfa) {
                            $session->set('tfa', true);
                        }
                    }

                    $user = new User($uinfo->mail);
                    if (isset($setCurrency)) {
                        $user->set(array("currency" => $setCurrency));
                    }

                    if (isset($overwriteCurrency) && trim($user->get()['currency']) != "" && isset($currencies[$user->get()['currency']])) {
                        $var['myCurrency'] = $user->get()['currency'];
                    }

                }
            }
        }
    }

    // Get user mail from session
    $mail = $session->get('mail');
    if ($var['ca_disabled']) {
        $mail = "";
    }

    if ($mail === null || trim($mail) == "") {
        // Set template @var logged_in to false
        $var['logged_in'] = 0;
        $cart = new VisitorCart;
        $var['cart'] = $cart->get();
        $var['cart_count'] = $cart->count();
    } else {
        // Requests an @object user from @class User and get password
        $user = new User($mail);
        $pwcheck = $user->get()['pwd'];
        if ($sec->hash($pwcheck, $user->get()['salt']) == $pwcheck) {
            $pwcheck = md5($pwcheck);
        }

        // Check if everything is okay
        if ($user->get() == null || !$session->get('pwd') || $pwcheck != $session->get('pwd') || ($user->get()['locked'] == "1" && !$session->get('admin_login')) || ($CFG['USER_CONFIRMATION'] == 1 && $user->get()['confirmed'] != "1")) {
            // User is not set, password is wrong or user is logged (and no admin is logged in)
            // Set template @var logged_in to false and clear session and @object user
            $var['logged_in'] = 0;
            $session->set('mail', '');
            $session->set('pwd', '');
            $session->set('admin_login', 0);
            $session->set('tfa', false);
            $user = null;
        } else {
            // User is okay, set template @var logged_in
            $var['logged_in'] = 1;

            if (isset($setCurrency)) {
                $user->set(array("currency" => $setCurrency));
            }

            if (isset($overwriteCurrency) && trim($user->get()['currency']) != "" && isset($currencies[$user->get()['currency']])) {
                $var['myCurrency'] = $user->get()['currency'];
            }

            // If the register time is not saved within the user profile, set it to current time
            if ($user->get()['registered'] == 0) {
                $user->set(array('registered' => time()));
            }

            // Empty the guest cart
            $session->set('cart', '0');
            $session->set('voucher', '0');
            setcookie("cart", "", time() - 3600, "/", null, $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);
            setcookie("voucher", "", time() - 3600, "/", null, $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);

            // Set some things to template variables, for example user and cart information
            $var['user'] = $user->get();
            $cart = new Cart($user->get()['ID']);
            $var['cart'] = $cart->get();
            $var['cart_count'] = $cart->count();

            // Get client IP address
            $ip = ip();

            // Get the last ip of the user
            // If the last ip is different to the current and its not an admin session, save the new IP into database
            $last = $user->get()['last_ip'];
            if (($last != $ip || empty($last)) && !$session->get('admin_login')) {
                $db->query("INSERT INTO ip_logs (time, user, ip) VALUES (" . time() . ", " . $user->get()['ID'] . ", '" . $db->real_escape_string($ip) . "')");
            }

            // If its not an admin session, save the last time active and the current IP to user profile
            if (!$session->get('admin_login')) {
                $user->set(array('last_active' => time()));
                $user->set(array('last_ip' => $ip));
            }

            // If the user have tfa, did not entered a code before and its not an admin session, redirect him to tfa page
            $var['tfa_open'] = 0;
            if ($user->get()['tfa'] != "none" && !$session->get('admin_login') && !$session->get('tfa')) {
                // Define pages the user can view without being authenticated full
                if ($realPage != "locked" && $realPage != "tfa" && $realPage != "logout" && $realPage != "imprint" && $realPage != "terms" && $realPage != "privacy" && $realPage != "withdrawal" && !isset($doNotRedirect)) {
                    header('Location: ' . $CFG['PAGEURL'] . 'tfa');
                    if (php_sapi_name() != "cli") {
                        exit;
                    }

                } else {
                    $doNotRedirect = true;
                }

                // Set template variable to display another logout button and no user information in menu
                $var['tfa_open'] = 1;
            }

            // Get the ID of the current terms
            $q = $db->query("SELECT * FROM `terms_of_service` ORDER BY `ID` DESC LIMIT 1");
            if ($q->num_rows == 0) {
                $aktTOS = 0;
            } else {
                $aktTOS = $q->fetch_object()->ID;
            }

            // Check if user has not accepted terms of service
            if ($user->get()['tos'] < $aktTOS && !$session->get('admin_login') && !isset($doNotRedirect)) {
                // Define pages the user can view without having accepted terms
                if (!isset($realPage) || ($realPage != "terms" && $realPage != "tfa" && $realPage != "imprint" && $realPage != "terms" && $realPage != "privacy" && $realPage != "withdrawal")) {
                    header('Location: ' . $CFG['PAGEURL'] . 'terms');
                    exit;
                } else {
                    $doNotRedirect = 1;
                }
            } else if ($user->get()['tos'] < $aktTOS) {
                // If its an admin session, only set a variable to template to display a warning in client area
                $var['tos'] = 1;
                // Redirect the user to privacy policy if not accepted yet (only when it is no admin session)
            } else if ($user->get()['privacy_policy'] != "1" && $realPage != "tfa" && $realPage != "logout" && $realPage != "imprint" && $realPage != "terms" && $realPage != "privacy" && $realPage != "withdrawal" && !$session->get('admin_login') && !isset($doNotRedirect)) {
                header('Location: ' . $CFG['PAGEURL'] . 'privacy');
                exit;
                // Redirect the user to profile if not all information is specified and he is not on profile already (only when it is no admin session)
            } else if ($realPage != "profile" && $realPage != "tfa" && $realPage != "logout" && $realPage != "imprint" && $realPage != "terms" && $realPage != "privacy" && $realPage != "withdrawal" && !$session->get('admin_login') && !isset($doNotRedirect)) {
                function is_duty_field($name)
                {
                    global $db, $CFG;
                    return (bool) $db->query("SELECT 1 FROM client_fields WHERE name = '" . $db->real_escape_string($name) . "' AND active = 1 AND duty = 1")->num_rows;
                }

                $arr = array(
                    "street" => "Straße",
                    "street_number" => "Hausnummer",
                    "postcode" => "Postleitzahl",
                    "city" => "Ort",
                    "country" => "Land",
                    "telephone" => "Telefonnummer",
                    "birthday" => "Geburtstag",
                    "vatid" => "USt-IdNr.",
                    "website" => "Webseite",
                );

                foreach ($arr as $db2 => $field) {
                    if (is_duty_field($field) && empty($user->get()[$db2])) {
                        header('Location: ' . $CFG['PAGEURL'] . 'profile');
                        exit;
                    }
                }

                if (empty($user->get()['firstname']) || empty($user->get()['lastname']) || empty($user->get()['mail'])) {
                    header('Location: ' . $CFG['PAGEURL'] . 'profile');
                    exit;
                }

                if (is_duty_field("Land") && $db->query("SELECT ID FROM client_countries WHERE active = 1 AND ID = '" . $db->real_escape_string($user->get()['country']) . "'")->num_rows != 1) {
                    header('Location: ' . $CFG['PAGEURL'] . 'profile');
                    exit;
                }

                $sql = $db->query("SELECT ID FROM client_fields WHERE `system` = 0 AND active = 1 AND duty = 1");
                while ($row = $sql->fetch_object()) {
                    if (empty($user->getField($row->ID))) {
                        header('Location: ' . $CFG['PAGEURL'] . 'profile');
                        exit;
                    }
                }
            }

            // Display a warning in some cases only for admin
            if ($session->get('admin_login')) {
                if ($user->get()['privacy_policy'] != "1") {
                    $var['privacy'] = 1;
                }

            }

            // Display a warning if user is locked (the session can only exist if its an admin session)
            if ($user->get()['locked'] == "1") {
                $var['locked'] = 1;
            }

            // Get the user notes which are purposed to be shown in frontend
            $sql = $db->query("SELECT * FROM client_notes WHERE user = " . $user->get()['ID'] . " AND display != '' AND display != 'none'");
            $userNotes = array();

            while ($note = $sql->fetch_array()) {
                $note['title'] = make_clickable($note['title']);
                $note['text'] = make_clickable($note['text']);
                array_push($userNotes, $note);
            }
            $var['userNotes'] = $userNotes;
        }
    }

    // if no page is passed, set it to default (index)
    $ex = explode("/", $_GET['p']);
    $langUrl = false;
    if (isset($isoLanguages[$ex[0]])) {
        $CFG['LANG'] = $isoLanguages[$ex[0]];
        $langUrl = true;
    }

    // Theme switcher for admins
    if (isset($_GET['theme']) && $session->get('admin_login') && is_dir(__DIR__ . "/themes/" . basename($_GET['theme'])) && $_GET['theme'] != "order") {
        $CFG['THEME'] = basename($_GET['theme']);
        $session->set('theme_overwrite', basename($_GET['theme']));
    }
    if ($session->get('theme_overwrite')) {
        $CFG['THEME'] = $session->get('theme_overwrite');
    }

    // We can set a language with GET parameter
    if (isset($_GET['lang']) && file_exists(__DIR__ . "/languages/" . basename($_GET['lang']) . ".php")) {
        $CFG['LANG'] = basename($_GET['lang']);
        $session->set('lang', basename($_GET['lang']));
        setcookie("lang", basename($_GET['lang']), time() + 86400 * 365, "/", null, $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);

        // Save the choice to the logged in user
        if ($var['logged_in']) {
            $user->set(array("language" => basename($_GET['lang'])));
        }

        // Otherwise we can take it from POST ...
    } else if (isset($_POST['new_language']) && file_exists(__DIR__ . "/languages/" . $_POST['new_language'] . ".php")) {
        $CFG['LANG'] = $_POST['new_language'];
        $session->set('lang', $_POST['new_language']);
        setcookie("lang", $_POST['new_language'], time() + 86400 * 365, "/", null, $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);

        // Save the choice to the logged in user
        if ($var['logged_in']) {
            $user->set(array("language" => $_POST['new_language']));
        }

        // ...or from session ...
    } else if (!$langUrl && is_string($session->get('lang')) && file_exists(__DIR__ . "/languages/" . $session->get('lang') . ".php")) {
        $CFG['LANG'] = $session->get('lang');
        // ...or from cookies ...
    } else if (!$langUrl && isset($_COOKIE['lang']) && file_exists(__DIR__ . "/languages/" . $_COOKIE['lang'] . ".php")) {
        $CFG['LANG'] = $_COOKIE['lang'];
        // ...or use the last choice of the current user
    } else if (!$langUrl && $var['logged_in'] && trim($user->get()['language']) != "" && file_exists(__DIR__ . "/languages/" . $user->get()['language'] . ".php")) {
        $CFG['LANG'] = $user->get()['language'];
        // We get the preferred user language if he did not make any choose
    } else if (!isset($_SESSION['lang']) && !isset($_COOKIE['lang'])) {
        $browserLanguage = $supportedLanguages[Language::getBrowser(array_keys($supportedLanguages), $_SERVER['HTTP_ACCEPT_LANGUAGE'])];
        if ($browserLanguage != $CFG['LANG'] && (!isset($_SERVER['HTTP_USER_AGENT']) || !preg_match('/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT']))) {
            require __DIR__ . "/languages/" . $browserLanguage . ".php";
            $var['browser_language'] = $browserLanguage;
            $var['browser_language_text'] = $lang['LANGUAGE_SWITCHER']['DETECTED'];
            $var['browser_language_title'] = $lang['LANGUAGE_SWITCHER']['TITLE'];
            $var['browser_language_yes'] = $lang['LANGUAGE_SWITCHER']['YES'];
            $var['browser_language_no'] = $lang['LANGUAGE_SWITCHER']['NO'];
        }
    }

    if (!in_array($CFG['LANG'], Language::getClientLanguages())) {
        $CFG['LANG'] = array_values(Language::getClientLanguages())[0];
    }

    if ($var['logged_in']) {
        $user = new User($mail);
    }

    // Overwrite the default language file if other language is set
    if (isset($CFG['LANG']) && $CFG['LANG'] != $var['lang_active'] && array_key_exists($CFG['LANG'], $languages)) {
        require __DIR__ . "/languages/" . $CFG['LANG'] . ".php";
        $var['lang_active'] = $CFG['LANG'];
    }

    if (!isset($_SESSION['lang']) || $_SESSION['lang'] != $CFG['LANG']) {
        $session->set('lang', $CFG['LANG']);
    }

    // Check page URL
    $ex = explode("/", $_GET['p']);

    $default = array_keys($isoLanguages)[0];
    if (count($isoLanguages) == 1 && $ex[0] != $default) {
        $ex = array_merge([$default], array_values($ex));
    }

    if ($ex[0] != $lang['ISOCODE'] && $ex[0] != "api" && $ex[0] != "jtlconnector") {
        if (isset($isoLanguages[$ex[0]])) {
            array_shift($ex);
        }

        $get = "?";
        foreach ($_GET as $k => $v) {
            if ($k != "p" && $k != "lang") {
                $get .= urlencode($k) . "=" . urlencode($v) . "&";
            }
        }

        header("Location: " . $CFG['PAGEURL'] . $lang['ISOCODE'] . "/" . rtrim(implode("/", $ex), "/") . rtrim($get, "?&"));
        if (php_sapi_name() != "cli") {
            exit;
        }
    }

    if ($ex[0] != "api" && $ex[0] != "jtlconnector" && php_sapi_name() != "cli") {
        array_shift($ex);
    }

    $_GET['p'] = rtrim(implode("/", $ex), "/");
    if (empty($_GET['p'])) {
        $_GET['p'] = "index";
    }

    if (count($isoLanguages) > 1) {
        $CFG['PAGEURL'] .= $lang['ISOCODE'] . "/";
    }

    // Set new timezone if necessary
    date_default_timezone_set(unserialize($CFG['TIMEZONE'])[$CFG['LANG']]);

    // Set new date/number format
    $CFG['NUMBER_FORMAT'] = unserialize($raw_cfg['NUMBER_FORMAT'])[$CFG['LANG']];
    $CFG['DATE_FORMAT'] = unserialize($raw_cfg['DATE_FORMAT'])[$CFG['LANG']];
    $nfo->__construct();
    $dfo->__construct();

    $var['pcomm'] = "";

    if (isset($_GET['add_voucher'])) {
        try {
            $sql = $db->query("SELECT * FROM vouchers WHERE code = '" . $db->real_escape_string($_GET['add_voucher']) . "'");
            if ($sql->num_rows != 1) {
                throw new Exception($lang['CART']['WRONG_VOUCHER']);
            }

            $info = $sql->fetch_object();

            if (($info->user != 0 && !$var['logged_in']) || ($info->ID == $CFG['BIRTHDAY_VOUCHER'] && !$var['logged_in'])) {
                throw new Exception($lang['CART']['VOUCHER_WRONG_USER_LOGIN']);
            }

            if (($info->user != 0 && $info->user != $user->get()['ID']) || $info->ID == $CFG['BIRTHDAY_VOUCHER']) {
                throw new Exception($lang['CART']['VOUCHER_WRONG_USER']);
            }

            if ($info->active != 1) {
                throw new Exception($lang['CART']['VOUCHER_NOT_ACTIVE']);
            }

            if ($info->valid_from > time() && $info->valid_from > 0) {
                throw new Exception($lang['CART']['VOUCHER_NOT_VALID_YET']);
            }

            if ($info->valid_to < time() && $info->valid_to > 0) {
                throw new Exception($lang['CART']['VOUCHER_NOT_VALID_ANYMORE']);
            }

            if ($info->uses > $info->max_uses && $info->max_uses >= 0) {
                throw new Exception($lang['CART']['VOUCHER_USED']);
            }

            if (!$cart->checkVoucherUsage($_POST['code'])) {
                throw new Exception($lang['CART']['VOUCHER_YOU_USED']);
            }

            $cart->addVoucher($info->ID);
            if ($var['logged_in']) {
                $cart->__construct($user->get()['ID']);
            }

            $cart->piwik();
        } catch (Exception $ex) {}
    }

    // Function to determine if product should be shown
    function should_show_product($id)
    {
        global $db, $CFG, $var, $user;

        $sql = $db->query("SELECT * FROM products WHERE ID = " . intval($id));
        if (!$sql->num_rows) {
            return false;
        }

        $info = $sql->fetch_object();

        if (!$info->hide) {
            return true;
        }

        if ($info->only_verified || $info->customer_groups) {
            if (!$var['logged_in']) {
                return false;
            }

            if ($info->only_verified && !$user->get()['verified']) {
                return false;
            }

            if ($info->customer_groups && !in_array($user->get()['cgroup'], explode(",", $info->customer_groups))) {
                return false;
            }
        }

        return true;
    }

    if (isset($_GET['add_service']) && $_GET['add_service'] > 0) { // Adds an service to cart
        $sql = $db->query("SELECT ID FROM products WHERE old_service = '" . $db->real_escape_string($_GET['add_service']) . "'");
        if ($sql->num_rows == 1 && !isset($_GET['add_product'])) {
            $_GET['add_product'] = $sql->fetch_object()->ID;
        }
    }

    if (isset($_GET['add_product'])) { // Adds an regularly product to cart
        $ex = explode(",", $_GET['add_product']);
        foreach ($ex as $pid) {
            $age->product($pid);

            $_GET['add_product'] = $pid;
            $sql = $db->query("SELECT * FROM products WHERE status = 1 AND ID = '" . $db->real_escape_string($_GET['add_product']) . "'");
            if ($sql->num_rows == 1 && should_show_product($_GET['add_product'])) {
                $info = $sql->fetch_object();

                $cont = true;
                if ($info->only_verified == 1) {
                    if (!$var['logged_in']) {
                        $var['global_info'] = $lang['CART']['ONLYVERIFIED_NLI'];
                        $cont = false;
                    } else if (!$user->get()['verified']) {
                        $var['global_info'] = $lang['CART']['ONLYVERIFIED'];
                        $cont = false;
                    }
                }

                if ($cont) {
                    $conf = false;
                    if ($info->domain_choose || $info->incldomains > 0 || $db->query("SELECT 1 FROM products_cf WHERE product = " . intval($_GET['add_product']))->num_rows > 0) {
                        $conf = true;
                    }

                    $variants = @unserialize($info->variants);
                    if (is_array($variants) && count($variants)) {
                        $conf = true;
                    }

                    if (trim($info->customer_groups) != "" && !$var['logged_in']) {
                        $var['global_info'] = $lang['CART']['ONLYVERIFIED_NLI'];
                    } else if (trim($info->customer_groups) != "" && $var['logged_in'] && !in_array($user->get()['cgroup'], explode(",", $info->customer_groups))) {
                        $var['global_info'] = $lang['CART']['NOTELIGIBLE'];
                    } else if ($info->available != 0) {
                        if ($conf) {
                            header('Location: ' . $CFG['PAGEURL'] . 'configure/' . $info->ID);
                            exit;
                        }

                        if ($cart->add($info->ID, "product", $info->type == "HOSTING" ? "h" : "e")) {
                            $var['global_alert'] = str_replace(array("%n", "%s", "%e"), array(unserialize($info->name)[$CFG['LANG']], '<a href="' . $CFG['PAGEURL'] . 'cart">', '</a>'), $lang['CART']['ADDED']);
                            if (empty($_GET['nm'])) {
                                $var['addedToCart'] = true;
                            }
                            $cart->__destruct();
                            $cart = $var['logged_in'] ? new Cart($user->get()['ID']) : new VisitorCart;
                            $var['cart'] = $cart->get();
                            $var['cart_count'] = $cart->count();
                            $cart->piwik();
                        } else {
                            $var['global_info'] = $lang['CART']['NOAUTH'];
                        }
                    } else {
                        if (!$conf && $info->preorder) {
                            $var['global_info'] = str_replace("%u", $CFG['PAGEURL'] . "preorder/" . $info->ID, $lang['CART']['OUTOFSTOCK']);
                        } else {
                            $var['global_info'] = $lang['CART']['OUTOFSTOCK_NP'];
                        }

                    }
                }
            }
        }
    } else if (isset($_GET['add_bundle']) && $_GET['add_bundle'] > 0) { // Adds an product bundle to cart
        $sql = $db->query("SELECT * FROM product_bundles WHERE ID = '" . $db->real_escape_string($_GET['add_bundle']) . "'");
        if ($sql->num_rows == 1) {
            $info = $sql->fetch_object();
            $cart->add($info->ID, 'bundle');
            $var['global_alert'] = str_replace(array("%n", "%s", "%e"), array(unserialize($info->name)[$CFG['LANG']], '<a href="' . $CFG['PAGEURL'] . 'cart">', '</a>'), $lang['CART']['ADDED_BUNDLE']);
            $var['cart'] = $cart->get();
            if (empty($_GET['nm'])) {
                $var['addedToCart'] = true;
            }
            $var['cart_count'] = $cart->count();
            $cart->piwik();
        }
    }

    // Set variables to template which specify a link for buying products
    $buy = array();
    $buy_short = array();
    $stock = array();
    $pricing = array();
    $pQ = $db->query("SELECT * FROM `products`");
    $factor = $var['logged_in'] ? $user->getRaw()['pricelevel'] / 100 : 1;
    while ($p = $pQ->fetch_object()) {
        if ($var['logged_in']) {
            $pcg = unserialize($p->price_cgroups);
            if (!is_array($pcg)) {
                $pcg = [];
            }
            $cg = $user->get()['cgroup'];
            if (array_key_exists($cg, $pcg)) {
                $p->price = $pcg[$cg][0];
                $p->setup = $pcg[$cg][1];
            }
        }

        $p->price = Product::getClientPrice($p->price, $p->tax);
        $p->setup = Product::getClientPrice($p->setup, $p->tax);

        // Monthly pricing breakdown
        $breakdown = [
            "quarterly" => "3",
            "semiannually" => "6",
            "annually" => "12",
            "biennially" => "24",
            "trinnially" => "36",
        ];

        if ($CFG['BREAKDOWN'] && array_key_exists($p->billing, $breakdown)) {
            $p->price = round($p->price / $breakdown[$p->billing], 2);
            $p->billing = "monthly";
        }

        // Long form
        $len = max(2, strlen(substr(strrchr(rtrim($p->price, "0"), "."), 1)));
        if ($p->price != 0) {
            if (empty($p->billing) || $p->billing == "onetime") {
                $buystring = str_replace("%p", $cur->infix($nfo->format($cur->convertAmount(null, $p->price, null, true, true) * $factor)), $lang['BUY']['LONG']);
            } else {
                $buystring = str_replace("%p", $cur->infix($nfo->format($cur->convertAmount(null, $p->price, null, true, true) * $factor, $len)), $lang['BUY'][strtoupper($p->billing)]);
            }
        } else {
            $buystring = $lang['BUY']['FREE'];
        }

        if ($p->type == "HOSTING") {
            $buystring = str_replace("%p", $cur->infix($nfo->format(round($cur->convertAmount(null, $p->price, null, false, true) * $factor, $len), $len)), $lang['BUY'][strtoupper($p->billing)]);

            if ($p->setup > 0) {
                $buystring .= " " . str_replace("%p", $cur->infix($nfo->format($cur->convertAmount(null, $p->setup, null, true, true) * $factor)), $lang['BUY']['SETUP']);
            }

            if ($p->setup < 0) {
                $buystring .= " " . str_replace("%p", $cur->infix($nfo->format($cur->convertAmount(null, abs($p->setup), null, true, true) * $factor)), $lang['BUY']['DISCOUNT']);
            }

            $buystring .= " " . $lang['BUY']['END'];
        }

        $buy[$p->ID] = '<a rel="nofollow" href="?add_product=' . $p->ID . '">' . $buystring . '</a>';

        // Short form
        if ($p->price == 0) {
            $buystring = $lang['BUY']['FREE'];
        } else {
            $buystring = $cur->infix($nfo->format($cur->convertAmount(null, $p->price, null, true, true) * $factor));
        }

        $buy_short[$p->ID] = '<a rel="nofollow" href="?add_product=' . $p->ID . '">' . $buystring . '</a>';

        $stock[$p->ID] = $p->status == 1 ? $p->available : 0;
        $pricing[$p->ID] = $cur->infix($nfo->format($cur->convertAmount(null, $p->price, null, true, true) * $factor));
    }

    // Set a few template variables
    $var['stock'] = $stock;
    $var['pricing'] = $pricing;
    $var['buy'] = $buy;
    $var['buy_short'] = $buy_short;
    $var['layout'] = strtolower($CFG['THEME']);

    $var['cfg'] = $CFG;
    $var['raw_cfg'] = $raw_cfg;
    $var['lang'] = $lang;
    $var['langs'] = $languages;
    $var['meta_tags'] = "";

    $var['paymentJS'] = "";

    $var['customercount'] = $nfo->format($db->query("SELECT COUNT(*) AS c FROM clients")->fetch_object()->c, 0);

    // Theme language
    $var['themelang'] = array();
    if (file_exists(__DIR__ . "/themes/{$CFG['THEME']}/lang/{$CFG['LANG']}.php")) {
        require __DIR__ . "/themes/{$CFG['THEME']}/lang/{$CFG['LANG']}.php";
        $var['themelang'] = $themelang;
    }

    // Get names of CMS pages
    $cms_pages = array();
    $sql = $db->query("SELECT slug, title FROM cms_pages");
    while ($row = $sql->fetch_object()) {
        $cms_pages[$row->slug] = unserialize($row->title) !== false ? unserialize($row->title)[$CFG['LANG']] : $row->title;
    }

    $var['cms_pages'] = $cms_pages;

    // Set all GET variables in a template variable (for language chooser)
    $getString = "";
    foreach ($_GET as $k => $v) {
        if ($k != "lang" && ($k != "p" || ($k == "p" && str_replace(array('?p=', '&p='), "", $_SERVER['REQUEST_URI']) != $_SERVER['REQUEST_URI']))) {
            $getString .= "&$k=$v";
        }
    }

    $var['get_string'] = $getString;

    // Display offers
    $var['offers'] = array();
    $sql = $db->query("SELECT * FROM offers WHERE start <= '" . date("Y-m-d") . "' AND end >= '" . date("Y-m-d") . "' AND status = 1 ORDER BY end ASC, ID ASC");
    while ($row = $sql->fetch_object()) {
        $var['offers'][] = array(
            "title" => unserialize($row->title)[$CFG['LANG']],
            "end" => $dfo->format($row->end, false),
            "url" => unserialize($row->url)[$CFG['LANG']],
            "price" => unserialize($row->price)[$CFG['LANG']],
        );
    }

    // Set ref cookie
    if (isset($_GET['ref']) && empty($_COOKIE['affiliate']) && User::getInstance(intval($_GET['ref']), "ID")) {
        setcookie("affiliate", intval($_GET['ref']), time() + 86400 * $CFG['AFFILIATE_DAYS'], "/", null, $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);
    }

}

// Ratings
$var['ratingValue'] = Testimonials::average();
$var['ratingCount'] = Testimonials::num();
$var['worstRating'] = Testimonials::worst();
$var['bestRating'] = Testimonials::best();
