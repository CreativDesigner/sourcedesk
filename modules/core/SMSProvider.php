<?php
// Abstract class for a sms provider

abstract class SMSProvider
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
        $sql = $db->query("SELECT setting, value FROM sms_providers WHERE provider = '" . $this->short . "'");
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
            if (file_exists($path = __DIR__ . "/../sms/" . $this->short . "/language/" . basename($lang) . ".php")) {
                require $path;
            } else {
                return false;
            }
        }

        return !empty(trim($str)) && isset($this->lang[$str]) ? $this->lang[$str] : $this->lang;
    }

    abstract public function getSettings();
    abstract public function sendMessage($recipient, $message, $type = 0);
    abstract public function getTypes();

    public function getName()
    {return $this->name;}
    public function getVersion()
    {return $this->version;}
    public function getShort()
    {return $this->short;}
}

class SMSHandler
{
    public static function getDrivers()
    {
        $arr = array();
        foreach (glob(__DIR__ . "/../sms/*") as $p) {
            if (is_dir($p) && file_exists($p . "/" . basename($p) . ".php")) {
                require_once $p . "/" . basename($p) . ".php";
            }
        }

        foreach (get_declared_classes() as $class) {
            if (get_parent_class($class) != "SMSProvider") {
                continue;
            }

            $obj = new $class;
            $arr[$obj->getShort()] = $obj;
        }

        return $arr;
    }

    public static function getDriver()
    {
        global $CFG;

        return self::getDrivers()[$CFG['SMS_PROVIDER']] ?: false;
    }
}
