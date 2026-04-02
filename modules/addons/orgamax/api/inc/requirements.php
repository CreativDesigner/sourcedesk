<?php

$checkText = "Bitte &uuml;berpr&uuml;fen Sie, ob diese im Verzeichnis \"inc\" der Webshop-Anbindung
    vorhanden ist.";

// Required files and settings for the shop connector
try
{
    // php.ini settings
    error_reporting(0);
    ini_set("error_reporting", 0);
    ini_set("display_errors", 0);
    ini_set("display_start_up_errors", 0);

    $dir = dirname(__FILE__);

    // check files
    if (!file_exists("$dir/const.php"))
    {
        die("Die Datei <b>const.php</b> wurde nicht gefunden. <br><br>$checkText");
    }
    elseif (!file_exists("$dir/functions.php"))
    {
        die("Die Datei <b>functions.php</b> wurde nicht gefunden. <br><br>$checkText");
    }
    elseif (!file_exists("$dir/resources.php"))
    {
        die("Die Datei <b>resources.php</b> wurde nicht gefunden. <br><br>$checkText");
    }
    elseif (!file_exists("$dir/config.php"))
    {
        die("Die Datei <b>config.php</b> wurde nicht gefunden. <br><br>$checkText");
    }
    elseif (!file_exists("$dir/shops.config.php"))
    {
        die("Die Datei <b>shops.config.php</b> wurde nicht gefunden. <br><br>$checkText");
    }
    elseif (!file_exists("$dir/extensions.config.php"))
    {
        die("Die Datei <b>extensions.config.php</b> wurde nicht gefunden. <br><br>$checkText");
    }

    // include required files
    include_once("$dir/const.php");
    include_once("$dir/config.php");
    include_once("$dir/shops.config.php");
    include_once("$dir/extensions.config.php");
    include_once("$dir/functions.php");
    include_once("$dir/resources.php");
    //dumpImportantIniValues();
}
catch(Exception $e)
{
    die($e->getMessage());
}

?>