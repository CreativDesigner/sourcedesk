<?php

function sd_licenseCheck($licenseKey = "", $cacheKey = "", $r = false){
	/* Please insert the secret shown in the product configuration in haseDESK here */
	$secret = "SECRET_HERE";
	/* Please insert the number of directory should be removed from the path end */
	$rmdir = 0;
	/* Please insert the ID of the product */
	$pid = PID_HERE;
	/* Please insert the URL to haseDESK */
	$url = "URL_HERE";
	/* DO NOT CHANGE ANYTHING BELOW THIS LINE */

	$host = $_SERVER['SERVER_NAME'];
	$ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
	$dir = $rmdir > 0 ? implode("/", array_splice(explode("/", __DIR__), 0, $rmdir / -1)) : __DIR__;

	if(!empty($cacheKey)){
		$ex = explode("|", $cacheKey);
		if(
			count($ex) == 6 &&
			$pid == $ex[0] && 
			($ex[1] == "all" || in_array($host, explode(",", $ex[1]))) && 
			($ex[2] == "all" || in_array($ip, explode(",", $ex[2]))) && 
			($ex[3] == "all" || in_array($dir, explode(",", $ex[3]))) && 
			strtotime($ex[4]) >= strtotime(date("Y-m-d")) && 
			$ex[5] == hash("sha512", $secret . implode("|", array_splice($ex, 0, 5)))
		) return Array(true, $cacheKey);
		else if($r) return Array(false, "");
	}

	if(empty($licenseKey)) return Array(false, "");
	$url = rtrim($url, "/") . "/api/license/info?key=" . urlencode($licenseKey) . "&dir=" . urlencode($dir);
	$res = @file_get_contents($url);
	if(!$res || !($res = json_decode($res)) || empty($res->cacheKey)) return Array(false, "");

	return sd_licenseCheck($licenseKey, $res->data->cacheKey, true);
}

?>