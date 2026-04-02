<?php

class PlacetelCallthrough extends TelephoneModule {
	protected $name = "Placetel";

	public function call($number, $info) {
		$ex = explode("|", $info);

		$data = Array(
			"api_key" => $ex[0],
			"sipuid" => $ex[1],
			"target" => $number,
		);

		$ch = curl_init("https://api.placetel.de/api/initiateCall.json");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		$res = json_decode(curl_exec($ch));
		curl_close($ch);

		return $res->result == 1;
	}
}