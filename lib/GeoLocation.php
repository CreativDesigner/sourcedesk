<?php
class GeoLocation {
	static private $url = "https://nominatim.openstreetmap.org/search";

	static public function getLocation($address) {
		global $CFG;

		$data = [
			"q" => $address,
			"format" => "json",
		];

		$url = self::$url . "?" . http_build_query($data);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "sourceDESK");
		$resp = curl_exec($ch);
		curl_close($ch);
		
		$resp = json_decode($resp);

		if (!is_array($resp) || !isset($resp[0])) {
			return false;
		}
		
		$resp = $resp[0];
		
		if (empty($resp->place_id)) {
			return false;
		}

		return [
			"lat" => $resp->lat,
			"lng" => $resp->lon,
		];
	}
}