<?php

namespace Fibu;

class Account
{
    protected $info;

    public function __construct($value, $key = "ID", $type = 0)
    {
        global $db, $CFG;

        $key = $db->real_escape_string($key);
        $value = $db->real_escape_string($value);
        $type = intval($type);

        $sql = $db->query("SELECT * FROM fibu_accounts WHERE `$key` = '$value' AND `type` = $type");
        if ($sql->num_rows == 1) {
            $this->info = $sql->fetch_object();
        }

    }

    public function found()
    {
        return is_object($this->info);
    }

    public function getName()
    {
        return $this->info->description;
    }

    public function setName($name)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT 1 FROM fibu_accounts WHERE `description` = '" . $db->real_escape_string($name) . "' AND ID != {$this->info->ID}");
        if ($sql->num_rows > 0) {
            return false;
        }

        $this->info->description = $name;
        $db->query("UPDATE fibu_accounts SET `description` = '" . $db->real_escape_string($name) . "' WHERE ID = {$this->info->ID} LIMIT 1");
    }

    public function getId()
    {
        return $this->info->ID;
    }

    public function getEntries($limit = -1, $offset = -1)
    {
        return Entry::getByAccount($this, $limit, $offset);
    }

    public function countEntries()
    {
        return Entry::countByAccount($this);
    }

    public function getSaldo()
    {
        $entries = $this->getEntries();
        $saldo = 0;

        foreach ($entries as $e) {
            if ($e->getSollAcct()->getId() == $this->getId()) {
                $saldo -= $e->getAmount();
            } else if ($e->getHabenAcct()->getId() == $this->getId()) {
                $saldo += $e->getAmount();
            } else {
                $saldo += $e->getTax();
            }
        }

        return $saldo;
    }

    public function delete()
    {
        global $db, $CFG;

        if ($this->countEntries() > 0) {
            return false;
        }

        return $db->query("DELETE FROM fibu_accounts WHERE ID = {$this->info->ID}");
    }

    public static function getInstance($value, $alsoLedger = false, $key = "ID")
    {
        $obj = new Account($value, $key);
        return $obj->found() ? $obj : Ledger::getInstance($value, $key);
    }

    public static function create($name, $type = 0, $id = 0)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT 1 FROM fibu_accounts WHERE `description` = '" . $db->real_escape_string($name) . "'");
        if ($sql->num_rows > 0) {
            return false;
        }

        $type = intval($type);

        $id1 = $id2 = "";
        if ($id) {
            $id1 = "`ID`, ";
            $id2 = "$id, ";
        }

        $db->query("INSERT INTO fibu_accounts ($id1`description`, `type`) VALUES ($id2'" . $db->real_escape_string($name) . "', $type)");

        if ($type == 0) {
            $obj = new self($db->insert_id);
        } else {
            $obj = new Ledger($db->insert_id);
        }

        return $obj->found() ? $obj : false;
    }

    public static function getAll($order = "name", $class = "Fibu\Account", $type = 0)
    {
        global $db, $CFG;

        $arr = array();
        $sql = $db->query("SELECT ID FROM fibu_accounts WHERE type = $type ORDER BY '" . $db->real_escape_string($order) . "'");
        while ($row = $sql->fetch_object()) {
            $arr[$row->ID] = new $class($row->ID);
        }

        return $arr;
    }

    public static function import($file)
    {
        if (!file_exists(__DIR__ . "/" . basename($file) . ".json")) {
            return false;
        }

        $skr = @json_decode(file_get_contents(__DIR__ . "/" . basename($file) . ".json"), true);
        if (!$skr) {
            return false;
        }

        foreach ($skr as $acct) {
            if ($acct["code"] < 0 || !$acct["leaf"]) {
                continue;
            }

            self::create($acct["name"], in_array($acct["type"], ["Bank"]) ? 0 : 1, $acct["code"]);
        }

        return true;
    }
}
