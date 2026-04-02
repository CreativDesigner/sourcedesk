<?php
define("BYPASS_AUTH", true);
require __DIR__ . "/init.php";

try {
	$headers = getallheaders();
	if (!array_key_exists("Authorization", $headers)) {
		throw new Exception("Authentication failed.");
	}

	$ex = explode(" ", $headers["Authorization"]);
	if (count($ex) != 2 || $ex[0] != "Bearer" || strlen($ex[1]) != 64) {
		throw new Exception("Authentication failed.");
	}

	$sql = $db->query("SELECT * FROM admins WHERE api_key = '" . $db->real_escape_string($ex[1]) . "' AND api_key != ''");
	if ($sql->num_rows != 1) {
		throw new Exception("Authentication failed.");
	}

	$admin = $adminInfo = $sql->fetch_object();

	$path = ltrim($_SERVER['PATH_INFO'], "/");
	if (empty($path)) {
		throw new Exception("No action submitted.");
	}

	$method = strtolower($_SERVER['REQUEST_METHOD']);

	$ex = explode("/", $path);

	$pars = [];
	if (count($ex) > 1) {
		$pars = array_slice($ex, 1);
	}

	$file = __DIR__ . "/controller/api/" . strtolower(basename($ex[0])) . ".$method.php";
	
	if (!file_exists($file)) {
		throw new Exception("Action not found.");
	}

	alog("api", "requested", $path);
	require $file;
} catch (Exception $ex) {
	die(json_encode([
		"error" => $ex->getMessage(),
	]));
}