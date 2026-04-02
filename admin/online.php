<?php
if (empty($_GET['id']) || empty($_GET['pw'])) {
	exit;
}

define("BYPASS_AUTH", true);
require __DIR__ . "/init.php";

if (!isset($_GET['status'])) {
	$sql = $db->query("SELECT online FROM admins WHERE ID = " . intval($_GET['id']) . " AND `password` = '" . $db->real_escape_string($_GET['pw']) . "' LIMIT 1");
	if ($sql->num_rows != 1) {
		exit;
	}

	$status = (String) $sql->fetch_object()->online;
	$project = "";

	if (($task = Project::working($_GET['id'])) > 0) {
		$taskInfo = $db->query("SELECT project FROM project_tasks WHERE ID = " . $task)->fetch_object();
		$project = $db->query("SELECT name FROM projects WHERE ID = " . $taskInfo->project)->fetch_object()->name;
	}

	alog("general", "status_retrieved");
	echo json_encode(["status" => $status, "project" => $project]);

	exit;
} else if ($_GET['status'] == "-1") {
	$task = Project::working($_GET['id']);
	if ($db->query("SELECT ID FROM project_tasks WHERE ID = " . intval($task))->num_rows != 1) {
		exit;
	}

	if ($db->query("SELECT ID FROM admins WHERE ID = " . intval($_GET['id']))->num_rows != 1) {
		exit;
	}

	$search = "0000-00-00 00:00:00";
	$end = date("Y-m-d H:i:s");
	alog("general", "project_ended");
	if (!$db->query("UPDATE project_times SET `end` = '$end' WHERE `end` = '$search' AND `admin` = " . intval($_GET['id']) . " AND `task` = " . intval($task) . " LIMIT 1") || $db->affected_rows != 1) {
		exit;
	}

	exit;
}

$s = $_GET['status'] == "1" ? "1" : ($_GET['status'] == "2" ? "2" : "0");
$db->query("UPDATE admins SET online = " . $s . " WHERE ID = " . intval($_GET['id']) . " AND `password` = '" . $db->real_escape_string($_GET['pw']) . "' LIMIT 1");

if ($db->affected_rows) {
	$db->query("UPDATE admin_times SET `end` = '" . date("Y-m-d H:i:s") . "' WHERE `end` = '0000-00-00 00:00:00' AND `admin` = " . intval($_GET['id']));
	if ($_GET['status'] == "1") {
		$db->query("INSERT INTO admin_times (`admin`, `start`) VALUES (" . intval($_GET['id']) . ", '" . date("Y-m-d H:i:s") . "')");
	}

	alog("general", "status_changed", $s);
	$addons->runHook("AdminOnlineStatusChanged");
}

exit;
