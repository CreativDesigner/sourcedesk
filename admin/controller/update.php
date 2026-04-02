<?php
global $ari, $lang, $CFG, $db, $var;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($lang['SYSTEMUPDATE']['TITLE']);
menu("settings");

// Check admin rights
if ($ari->check(34)) {
    $tpl = "update";

    if (isset($_POST['actual_version'])) {
        Versioning::actualVersion();
        alog("systemupdate", "checked");
        header('Location: ./?p=update');
        exit;
    }

    if (Versioning::newerVersion()) {
        if (isset($_GET['download'])) {
            Update::manualDownload();
        }

        $var['update_hint'] = '<div id="update_hint" class="alert alert-warning">' . $lang['SYSTEMUPDATE']['UPDATE_NOW'] . '</div>';
        $var['stage'] = 0;
    } else if (strcmp($CFG['VERSION'], $CFG['ACTUAL_VERSION']) > 0) {
        $var['update_hint'] = '<div id="update_hint" class="alert alert-warning">' . $lang['SYSTEMUPDATE']['NEWER_VERSION'] . '</div>';
        $var['stage'] = 2;
    } else {
        $var['update_hint'] = '<div id="update_hint" class="alert alert-success">' . $lang['SYSTEMUPDATE']['UP_TO_DATE'] . '</div>';
        $var['stage'] = 1;
    }

    if (isset($_POST['mpstep'])) {
        switch (intval($_POST['mpstep'])) {
            case 1:
                die($CFG['PATCHLEVEL']);
                break;

            case 2:
                die(strval(Micropatch::avail()));
                break;

            case 3:
                die(Micropatch::apply() ? "ok" : "fail");
                break;

            default:
                die("fail");
        }
    }

    if ($var['stage'] == 0) {
        if (isset($_POST['step'])) {
            switch (intval($_POST['step'])) {
                case 1:
                    $func = "getUpdate";
                    break;

                case 2:
                    $func = "verifySig";
                    break;

                case 3:
                    $func = "unzipArchive";
                    break;

                case 4:
                    $func = "permCheck";
                    break;

                case 5:
                    $func = "autoUpdate";
                    break;

                default:
                    die("fail");
            }

            die(Update::$func(!empty($_GET['manual'])) ? "ok" : "fail");
        }
    }
} else {
    alog("general", "insufficient_page_rights", "update");
    $tpl = "error";
}
