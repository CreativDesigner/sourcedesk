<?php
// Global some variables for security reasons
global $db, $session, $var, $_POST, $user, $CFG, $lang, $dfo;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$title = $lang['PRIVACY']['TITLE'];
$tpl = "privacy";

// Get the privacy policy
$var['terms'] = unserialize($CFG['PRIVACY_POLICY'])[$CFG['LANG']];

// Check if the privacy policy was not accepted by the current user yet
$var['new_tos'] = false;
if ($var['logged_in'] && $user->get()['privacy_policy'] != 1) {
	$var['new_tos'] = true;
}

// If the user wants to accept the terms
if (isset($_POST['accept'])) {
	// Create log entries and redirect to start page
	$user->set(Array("privacy_policy" => "1"));
	$user->log("Datenschutzbestimmungen bestätigt");
	header('Location: ' . $CFG['PAGEURL']);
	exit;
}