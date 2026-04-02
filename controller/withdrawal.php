<?php
// Global some variables for security reasons
global $db, $session, $var, $_POST, $user, $CFG, $lang, $dfo;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$title = $lang['WITHDRAWAL']['TITLE'];
$tpl = "withdrawal";

// Get the privacy policy
$var['terms'] = unserialize($CFG['WITHDRAWAL_RULES'])[$CFG['LANG']];

// Check if the privacy policy was not accepted by the current user yet
$var['new_tos'] = false;
if ($var['logged_in'] && $user->get()['withdrawal_rules'] != 1) {
	$var['new_tos'] = true;
}

// If the user wants to accept the terms
if (isset($_POST['accept']) && $var['logged_in'] && $user->get()['withdrawal_rules'] != 1) {
	// Create log entries and redirect to start page
	$user->set(Array("withdrawal_rules" => "1"));
	$user->log("Widerrufsbestimmungen bestätigt");
	header('Location: ' . $CFG['PAGEURL']);
	exit;
}