<?php
// Global some variables for security reasons
global $var, $db, $_GET, $user, $lang, $CFG, $pars;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

User::status();

$_GET['download'] = isset($pars[0]) ? intval($pars[0]) : 0;

// Download a file
if (isset($_GET['download'])) {
	$sql = $db->query("SELECT filename, filepath FROM client_files WHERE ID = '" . $db->real_escape_string($_GET['download']) . "' AND user_access = 1 AND user = " . $user->get()['ID']);
	if ($sql->num_rows == 1) {
		$fileInfo = $sql->fetch_object();

		if (file_exists(__DIR__ . "/../files/customers/" . basename($fileInfo->filepath))) {
			// Get the file from download directory
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"" . $fileInfo->filename . "\"");
			readfile(__DIR__ . "/../files/customers/" . basename($fileInfo->filepath));

			// Log action
			$user->log("Datei " . $fileInfo->filename . " heruntergeladen");

			// Exit the script to prevent Smarty exception
			exit;
		}
	}
}

$tpl = "file";
$title = $lang['NAV']['FILES'];

// Select files from database
$files = Array();
$sql = $db->query("SELECT filename, ID FROM client_files WHERE user_access = 1 AND user = " . $user->get()['ID'] . " ORDER BY filename ASC");
if ($sql->num_rows > 0) {
	while ($file = $sql->fetch_object()) {
		$files[$file->filename] = $file->ID;
	}
}

$var['files'] = $files;

?>