<?php
namespace Vautron;

if(!class_exists("httpRequest")){
	class httpRequest {
		private $url;

		function __construct($url){
			$this->url = $url;
		}

		public function get($path){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->build_url($path));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain')); 
			$res = curl_exec($ch);
			curl_close($ch);

			return $res;
		}

		public function post($path, $body, $type = "POST"){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->build_url($path));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			if($type == "POST") curl_setopt($ch, CURLOPT_POST, 1);
			else curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain')); 
			$res = curl_exec($ch);
			curl_close($ch);

			return $res;
		}

		public function put($path, $body){
			return $this->post($path, $body, "PUT");
		}

		public function delete($path, $body){
			return $this->post($path, $body, "DELETE");
		}

		private function build_url($path){
			return rtrim($this->url, "/") . "/" . ltrim($path, "/");
		}
	}
}