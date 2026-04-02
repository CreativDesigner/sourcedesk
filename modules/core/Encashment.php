<?php
// Abstract class for a encashment provider

abstract class Encashment
{
    protected $short;
    protected $name;
    protected $lang;
    protected $version;
    public $options;

    public function __construct()
    {
        global $CFG, $db;
        $this->options = new stdClass;
        $sql = $db->query("SELECT setting, value FROM encashment WHERE provider = '" . $this->short . "'");
        while ($row = $sql->fetch_object()) {
            $k = $row->setting;
            $this->options->$k = decrypt($row->value);
        }
        $name = $this->getLang("name");
        if (is_string($name)) {
            $this->name = $name;
        }
    }

    protected function getLang($str = "")
    {
        global $CFG, $adminInfo;
        $lang = $adminInfo->language ?: $CFG['LANG'];

        $str = strtoupper($str);

        if (!is_array($this->lang)) {
            if (file_exists($path = __DIR__ . "/../encashment/" . $this->short . "/language/" . basename($lang) . ".php")) {
                require $path;
            } else {
                return false;
            }
        }

        return !empty(trim($str)) && isset($this->lang[$str]) ? $this->lang[$str] : $this->lang;
    }

    abstract public function getSettings();
    abstract public function newClaim($debtor, $claim);
    abstract public function claimStatus($id);

    public function getName()
    {return $this->name;}
    public function getVersion()
    {return $this->version;}
    public function getShort()
    {return $this->short;}
}

class EncashmentHandler
{
    public static function getDrivers()
    {
        $arr = array();
        foreach (glob(__DIR__ . "/../encashment/*") as $p) {
            if (is_dir($p) && file_exists($p . "/" . basename($p) . ".php")) {
                require_once $p . "/" . basename($p) . ".php";
            }
        }

        foreach (get_declared_classes() as $class) {
            if (get_parent_class($class) != "Encashment") {
                continue;
            }

            $obj = new $class;
            $arr[$obj->getShort()] = $obj;
        }

        return $arr;
    }
}
