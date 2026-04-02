<?php
// Abstract class for a domain registrar

abstract class DomainRegistrar
{
    protected $short;
    protected $name;
    protected $version;
    protected $udd;
    protected $lang;
    public $delayDns = false;
    public $options;

    public function __construct()
    {
        global $CFG, $db;
        $this->options = new stdClass;
        $sql = $db->query("SELECT `setting`, `value` FROM domain_registrars WHERE registrar = '" . $this->short . "'");
        if (!$sql) {
            return;
        }

        while ($row = $sql->fetch_object()) {
            $k = $row->setting;
            $this->options->$k = decrypt($row->value);
        }

        $name = $this->getLang("name");
        if (is_string($name)) {
            $this->name = $name;
        }
    }

    public function hasAvailibilityStatus()
    {
        return method_exists($this, "availibilityStatus");
    }

    protected function getLang($str = "")
    {
        global $CFG, $adminInfo;
        $lang = $adminInfo->language ?: $CFG['LANG'];

        $str = strtoupper($str);

        if (!is_array($this->lang)) {
            if (file_exists($path = __DIR__ . "/../domain/" . $this->short . "/language/" . basename($lang) . ".php")) {
                require $path;
            } else {
                return false;
            }
        }

        return !empty(trim($str)) && isset($this->lang[$str]) ? $this->lang[$str] : $this->lang;
    }

    abstract public function registerDomain($domain, $owner, $admin, $tech, $zone, $ns, $privacy = false);
    abstract public function transferDomain($domain, $owner, $admin, $tech, $zone, $authCode, $ns, $privacy = false);
    abstract public function changeContact($domain, $owner, $admin, $tech, $zone);
    abstract public function changeValues($domain, $status = false, $renew = true, $privacy = false);
    abstract public function syncDomain($domain, $kkSync = false);
    abstract public function getSettings();

    public function getName()
    {return $this->name;}
    public function getVersion()
    {return $this->version;}
    public function isActive()
    {return $this->options->active == "1";}
    public function getShort()
    {return $this->short;}

    public function setUDD(array $data)
    {
        $this->udd = $data;
    }

    public function setUser(User $user)
    {
        $this->udd = [];

        $settings = @unserialize($user->get()['registrar_settings']);
        if (is_array($settings)) {
            if (array_key_exists($this->short, $settings) && is_array($settings[$this->short])) {
                $this->udd = $settings[$this->short];
            }
        }

        return true;
    }

    public function trade($domain, $owner, $admin, $tech, $zone)
    {
        return $this->changeContact($domain, $owner, $admin, $tech, $zone);
    }

    public function changeAll($domain, $owner, $admin, $tech, $zone, $ns, $status = false, $renew = true, $privacy = false)
    {
        if (method_exists($this, "changeContact")) {
            $this->changeContact($domain, $owner, $admin, $tech, $zone);
        }

        if (method_exists($this, "changeNameserver")) {
            $this->changeNameserver($domain, $ns);
        }

        if (method_exists($this, "changeValues")) {
            $this->changeValues($domain, $status, $renew, $privacy);
        }
    }

    public function logRequest($url, $request, $response, $domain = "")
    {
        global $db, $CFG;
        if (empty($domain) || !$CFG['DOMAIN_LOG']) {
            return;
        }

        $db->query("INSERT INTO domain_log (`time`, domain, registrar, url, request, response) VALUES (
			" . time() . ",
			'" . $db->real_escape_string($domain) . "',
			'" . $db->real_escape_string($this->short) . "',
			'" . $db->real_escape_string($url) . "',
			'" . $db->real_escape_string($request) . "',
			'" . $db->real_escape_string($response) . "'
        )");

        // Only keep last 2500 records
        $iid = $db->insert_id;
        $db->query("DELETE FROM domain_log WHERE ID < " . ($iid - 2500));
    }
}

class DomainHandler
{
    public static function getRegistrars()
    {
        $arr = array();

        foreach (glob(__DIR__ . "/../domain/*") as $p) {
            if (is_dir($p) && file_exists($p . "/" . basename($p) . ".php")) {
                require_once $p . "/" . basename($p) . ".php";
            }
        }

        foreach (get_declared_classes() as $class) {
            if (get_parent_class($class) != "DomainRegistrar") {
                continue;
            }

            $obj = new $class;
            $arr[$obj->getShort()] = $obj;
        }

        return $arr;
    }

    public static function getRegistrarNames()
    {
        $arr = self::getRegistrars();
        foreach ($arr as &$v) {
            $v = $v->getName();
        }

        return $arr;
    }

    public static function getAuthTwoByTld($tld)
    {
        global $db, $CFG;
        $sql = $db->query("SELECT registrar FROM domain_auth2 WHERE tld = '" . ltrim($tld, '.') . "'");
        if ($sql->num_rows != 1) {
            return false;
        }

        $reg = $sql->fetch_object()->registrar;
        $regs = self::getRegistrars();
        if (array_key_exists($reg, $regs) && method_exists($regs[$reg], "requestAuthTwo")) {
            return $regs[$reg];
        }

        return false;
    }

    public static function getRegistrarByTld($tld)
    {
        global $db, $CFG;
        $sql = $db->query("SELECT registrar FROM domain_pricing WHERE tld = '" . ltrim($tld, '.') . "'");
        if ($sql->num_rows != 1) {
            return false;
        }

        $reg = $sql->fetch_object()->registrar;
        $regs = self::getRegistrars();
        if (array_key_exists($reg, $regs)) {
            return $regs[$reg];
        }

        return false;
    }

    public static function availibilityStatus($domain)
    {
        global $db, $CFG;
        $sql = $db->query("SELECT status, last_check FROM domain_cache WHERE domain = '" . $db->real_escape_string($domain) . "'");

        if ($sql->num_rows == 1 && is_object($info = $sql->fetch_object()) && $info->last_check >= date("Y-m-d H:i:s", time() - 180)) {
            if ($info->status == "FREE") {
                return true;
            } else if ($info->status == "TRANS") {
                return false;
            }

            return null;
        } else if ($sql->num_rows == 1) {
            $db->query("DELETE FROM domain_cache WHERE domain = '" . $db->real_escape_string($domain) . "'");
        }

        if (!is_object($obj) || !$obj->hasAvailibilityStatus()) {
            $found = false;

            foreach (self::getRegistrars() as $reg) {
                if ($reg->isActive() && $reg->hasAvailibilityStatus()) {
                    $obj = $reg;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                return "INVALID";
            }
        }

        $s = $obj->availibilityStatus($domain);
        $e = $s === true ? "FREE" : ($s === false ? "TRANS" : "INVALID");
        $db->query("INSERT INTO domain_cache (`domain`, `status`, `last_check`) VALUES ('" . $db->real_escape_string($domain) . "', '$e', '" . date("Y-m-d H:i:s") . "')");
        return $s;
    }
}
