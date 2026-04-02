<?php
namespace Vautron;

if(!class_exists("httpResponse")){
	class httpResponse {
	    private $response;

	    function __construct($response){
	        $this->response = $response;
	    }

	    public function body() {
	        return simplexml_load_string(trim($this->response));
	    }
	}
}