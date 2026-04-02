<?php
// Controller for Adminer database management
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

// Global some variables for security reasons
global $adminInfo, $db, $CFG, $_POST, $ari, $dfo, $lang, $session, $addons, $nfo, $cur;

if (!$ari->check(38)) {
    alog("general", "insufficient_page_rights", "adminer");
    exit;
}

if (strpos($_SERVER['REQUEST_URI'], "adminer.php") === false) {
    header('Location: ./adminer.php');
    exit;
}

function adminer_object()
{
    class AdminerSoftware extends Adminer
    {
        function name()
        {
            return 'haseDESK DB';
        }

        function credentials()
        {
            global $CFG;
            return array($CFG['DB']['HOST'], $CFG['DB']['USER'], $CFG['DB']['PASSWORD']);
        }

        function database()
        {
            global $CFG;
            return $CFG['DB']['DATABASE'];
        }

        function login()
        {
            return true;
        }
    }

    return new AdminerSoftware;
}

$_GET["username"] = $CFG['DB']['USER'];

ini_set("display_errors", "0");
require __DIR__ . "/../../lib/Adminer/adminer.php";
exit;
