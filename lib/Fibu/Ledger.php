<?php

namespace Fibu;

class Ledger extends Account {
	public function __construct($key, $field = "ID", $type = null){
		parent::__construct($key, $field, 1);
	}

	public function getSaldo($factor = null){
		return parent::getSaldo(-1);
	}

	public static function create($name, $type = null) {
		return parent::create($name, 1);
	}

	public static function getInstance($value, $key = "ID") {
		$obj = new Ledger($value, $key);
		return $obj->found() ? $obj : false;
	}

	public static function getAll($order = "name", $class = null, $type = null) {
		return parent::getAll($order, "Fibu\Ledger", 1);
	}
}

?>