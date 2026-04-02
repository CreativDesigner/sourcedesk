<?php

namespace Fibu;

class Entry {
	public function __construct($value, $key = "ID") {
		global $db, $CFG;

        $key = $db->real_escape_string($key);
        $value = $db->real_escape_string($value);

        $sql = $db->query("SELECT * FROM fibu_journal WHERE `$key` = '$value'");
        if ($sql->num_rows == 1) {
            $this->info = $sql->fetch_object();
        }
	}
	
	public function getAmount() {
		return $this->info->amount;
	}

	public function getTax() {
		return $this->info->tax;
	}

	public static function getByAccount(Account $account, $limit = -1, $offset = -1) {
		global $db, $CFG;

		$limit = $limit > 0 ? " LIMIT $limit" . ($offset > 0 ? " OFFSET $offset" : "") : "";

		$arr = Array();
		$sql = $db->query("SELECT ID FROM fibu_journal WHERE account = {$account->getId()} OR account2 = {$account->getId()} OR taxacct = {$account->getId()} ORDER BY year DESC, month DESC, day DESC, ID DESC$limit");
		while($row = $sql->fetch_object())
			$arr[$row->ID] = new Entry($row->ID);
		return $arr;
	}

	public static function countByAccount(Account $account) {
		global $db, $CFG;

		$arr = Array();
		$sql = $db->query("SELECT COUNT(*) AS c FROM fibu_journal WHERE account = {$account->getId()}");
		return $sql->fetch_object()->c;
	}

	public function getSollAcct() {
		return Account::getInstance($this->info->account, true);
	}

	public function getHabenAcct() {
		return Account::getInstance($this->info->account2, true);
	}

	public function getTaxAcct() {
		return Account::getInstance($this->info->tax, true);
	}
}