<?php
// Global some variables for security reasons
global $db, $session, $var, $_POST, $user, $aktTOS, $CFG, $lang, $dfo;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$title = $lang['TOS']['SHORT'];
$tpl = "terms";

// Select the last terms of service from database
$q = $db->query("SELECT * FROM `terms_of_service` ORDER BY `ID` DESC LIMIT 1");
if ($q->num_rows == 0) {
    // Show a default sentence
    $var['terms'] = $lang['TOS']['DEFAULT'];
} else {
    // Show the last terms
    $i = $q->fetch_object();
    $var['terms'] = unserialize($i->text)[$CFG['LANG']] . ($CFG['TERMS_DATE'] ? "<br /><br /><b>" . $lang['TOS']['DATE'] . ": " . $dfo->format($i->time, false) . "</b>" : "");
    $var['tid'] = $i->ID;

    // Get the history
    $historySql = $db->query("SELECT * FROM `terms_of_service` ORDER BY `ID` DESC");
    if ($historySql->num_rows > 1) {
        $var['historyTerms'] = array();
        $x = 0;
        while ($r = $historySql->fetch_object()) {
            if ($x > 0) {
                $var['historyTerms'][$r->ID] = array("terms" => unserialize($r->text)[$CFG['LANG']], "time" => $dfo->format($r->time, false));
            }

            $x++;
        }
    }
}

// Check if the current terms was not accepted by the current user yet
$var['new_tos'] = false;
if ($var['logged_in'] && $user->get()['tos'] < $aktTOS) {
    $var['new_tos'] = true;
}

// If the user wants to accept the (new) terms
if (isset($_POST['tid']) && is_numeric($_POST['tid'])) {
    if ($_POST['tid'] > $user->get()['tos'] && $_POST['tid'] == $i->ID) {
        // Token correct, set new TOS ID, create log entries and redirect to start page
        $user->set(array("tos" => $_POST['tid']));
        $user->log("AGB bestätigt");
        header('Location: ' . $CFG['PAGEURL']);
        exit;
    }
}
