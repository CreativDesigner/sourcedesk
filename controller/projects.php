<?php
// Global some variables for security reasons
global $db, $user, $CFG, $nfo, $var, $lang, $dfo, $pars;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

User::status();

// File download
if (isset($pars[0]) && isset($pars[1])) {
	$sql = $db->query("SELECT files, show_details FROM projects WHERE user = '" . $user->get()['ID'] . "' AND ID = '" . $db->real_escape_string($pars[0]) . "' LIMIT 1");
	if ($sql->num_rows == 1) {
		$info = $sql->fetch_object();
		$files = unserialize($info->files) !== false ? unserialize($info->files) : Array();
		if (in_array($pars[1], $files) && $info->show_details != "0" && file_exists(__DIR__ . '/../files/projects/' . basename($pars[1]))) {
			// Get the file from download directory
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"" . substr($pars[1], 9) . "\"");
			readfile(__DIR__ . '/../files/projects/' . basename($pars[1]));

			// Log action
			$user->log("Projekt-Datei " . substr($pars[1], 9) . " heruntergeladen");

			// Exit the script to prevent Smarty exception
			exit;
		}
	}
}

$title = $lang['NAV']['PROJECTS'];
$tpl = "projects";
$projects = Array();
$var['show'] = 0;
$var["show_files"] = 0;

// Iterate users projects from database
$sql = $db->query("SELECT * FROM projects WHERE user = '" . $user->get()['ID'] . "' ORDER BY status ASC, due DESC");
while ($r = $sql->fetch_object()) {
	$arr = Array("ID" => $r->ID, "overdue" => strtotime($r->due) < strtotime(date("Y-m-d")) ? true : false, "due" => $dfo->format(strtotime($r->due), 0), "name" => $r->name, "status" => $r->status, "description" => $r->description);

	// Get tasks and files if user should see them
	if ($r->show_details != "0") {
		$tasks = Array();
		$tasksSql = $db->query("SELECT * FROM project_tasks WHERE project = " . $r->ID . " ORDER BY status ASC, name ASC, ID ASC");
		while ($task = $tasksSql->fetch_object()) {
			$tasks[] = Array("status" => $task->status, "name" => $task->name);
		}

		$arr["tasks"] = $tasks;
		$var['show'] = 1;

		$files = Array();
		$uns = unserialize($r->files);
		if ($uns !== false && is_array($uns) && count($uns) > 0) {
			foreach ($uns as $file) {
				array_push($files, $file);
			}
		}

		$arr["files"] = $files;
		if (count($files) > 0) {
			$var["show_files"] = 1;
		}

	}

	$projects[] = $arr;
}

$var['projects'] = $projects;