<?php
// Abstract class for a DNS provider

abstract class DNSProvider
{
    protected $short;
    protected $name;
    protected $version;
    protected $lang;
    public $options;

    public function __construct()
    {
        global $CFG, $db;
        $this->options = new stdClass;
        $sql = $db->query("SELECT setting, value FROM domain_dns_drivers WHERE driver = '" . $this->short . "'");
        while ($row = $sql->fetch_object()) {
            $k = $row->setting;
            $this->options->$k = decrypt($row->value);
        }

        $name = $this->getLang("name");
        if (is_string($name)) {
            $this->name = $name;
        }
    }

    public function getDnsTemplateId()
    {
        global $db, $CFG;

        if (!empty($_REQUEST['template'])) {
            return intval($_REQUEST['template']);
        }

        if (!($this->options->_different_template ?? false)) {
            return 1;
        }

        $id = intval($this->options->_different_template_id ?? 0);
        return $db->query("SELECT 1 FROM dns_templates WHERE ID = $id")->num_rows ? $id : 1;
    }

    public function getNs()
    {
        global $CFG;

        if ($this->options->_different_ns ?? false) {
            return [
                $this->options->_different_ns1 ?? "",
                $this->options->_different_ns2 ?? "",
                $this->options->_different_ns3 ?? "",
                $this->options->_different_ns4 ?? "",
                $this->options->_different_ns5 ?? "",
            ];
        }

        return [
            $CFG['NS1'] ?? "",
            $CFG['NS2'] ?? "",
            $CFG['NS3'] ?? "",
            $CFG['NS4'] ?? "",
            $CFG['NS5'] ?? "",
        ];
    }

    protected function getLang($str = "")
    {
        global $CFG, $adminInfo;
        $lang = $adminInfo->language ?: $CFG['LANG'];

        $str = strtoupper($str);

        if (!is_array($this->lang)) {
            if (file_exists($path = __DIR__ . "/../dns/" . $this->short . "/language/" . basename($lang) . ".php")) {
                require $path;
            } else {
                return false;
            }
        }

        return !empty(trim($str)) && isset($this->lang[$str]) ? $this->lang[$str] : $this->lang;
    }

    public function idd($domain)
    {
        $idn = new IdnaConvert;
        return $idn->decode($domain);
    }

    public function idn($domain)
    {
        $idn = new IdnaConvert;
        return $idn->encode($domain);
    }

    protected function isDynDNS($domain)
    {
        global $db, $CFG;
        $domain = $db->real_escape_string($domain);
        $sql = $db->query("SELECT `password` FROM external_dyndns WHERE domain = '$domain'");

        return $sql->num_rows ? $sql->fetch_object()->password : false;
    }

    protected function setDynDNS($domain, $password)
    {
        global $db, $CFG;
        $domain = $db->real_escape_string($domain);
        $password = $db->real_escape_string($password);

        $db->query("DELETE FROM external_dyndns WHERE `domain` = '$domain'");
        if ($password) {
            $db->query("INSERT INTO external_dyndns (`domain`, `password`) VALUES ('$domain', '$password')");
        }
    }

    abstract public function getSettings();
    abstract public function addZone($domain, array $ns);
    abstract public function getZones();
    abstract public function getZone($domain, $force = 0);
    abstract public function addRecord($domain, $record, $hidden = 0, $admin = false);
    abstract public function editRecord($domain, $record, $new, $force = 0);
    abstract public function removeRecord($domain, $record, $force = 0);
    abstract public function removeZone($domain);
    abstract public function recordTypes($admin = false);

    public function getName()
    {return $this->name;}
    public function getVersion()
    {return $this->version;}
    public function getShort()
    {return $this->short;}

    final public function applyTemplate($domain, array $ns, $ip = "", $ip6 = "", $hostname = "")
    {
        global $db, $CFG;

        $id = $this->getDnsTemplateId();
        $sql = $db->query("SELECT inclusive_id, addon_id FROM domains WHERE domain = '" . $db->real_escape_string($domain) . "' AND status = 'REG_OK'");
        if ($sql->num_rows) {
            $di = $sql->fetch_object();

            $cid = max($di->inclusive_id, $di->addon_id);
            if ($cid > 0) {
                $sql = $db->query("SELECT product FROM client_products WHERE ID = $cid");

                if ($sql->num_rows) {
                    $pid = $sql->fetch_object()->product;
                    $sql = $db->query("SELECT dns_template FROM products WHERE ID = $pid");
                    if ($sql->num_rows) {
                        $tid = $sql->fetch_object()->dns_template;
                        if ($tid > 0) {
                            $id = $tid;
                        }
                    }
                }
            }
        }

        $id = intval($id);
        if (!$db->query("SELECT 1 FROM dns_templates WHERE ID = $id")->num_rows) {
            $id = 1;
        }

        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip = $CFG['DEFAULT_IP'];

            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ip = "5.9.7.9";
            }
        }

        if (empty($ip6) || !filter_var($ip6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ip6 = "";
        }

        if (empty($hostname)) {
            $hostname = $ip;
        }

        $sql = $db->query("SELECT * FROM dns_template_records WHERE template_id = $id");
        while ($row = $sql->fetch_object()) {
            $row->content = str_replace(["%ip%", "%ip6%", "%hostname%"], [$ip, $ip6, $hostname], $row->content);

            $this->addRecord($domain, [$row->name, $row->type, $row->content, $row->ttl, $row->priority], (bool) $row->hidden, true);
        }

        if ($db->query("SELECT 1 FROM dns_templates WHERE ID = $id AND ns_set = 1")->num_rows) {
            foreach ($ns as $hostname) {
                if (!empty($hostname)) {
                    $this->addRecord($domain, ["", "NS", $hostname, 3600, 0], false, true);
                }
            }
        }
    }
}

class DummyDNSDriver
{
    public function getSettings()
    {
        return [];
    }

    public function getNs()
    {
        global $CFG;

        $res = [];
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($ns = $CFG["NS" . $i])) {
                array_push($res, $ns);
            }
        }

        return $ns;
    }

    public function addZone($domain, array $ns)
    {
        return true;
    }

    public function getZones()
    {
        return [];
    }

    public function getZone($domain, $force = 0)
    {
        return [];
    }

    public function addRecord($domain, $record, $hidden = 0, $admin = false)
    {
        return true;
    }

    public function editRecord($domain, $record, $new, $force = 0)
    {
        return true;
    }

    public function removeRecord($domain, $record, $force = 0)
    {
        return true;
    }

    public function removeZone($domain)
    {
        return true;
    }

    public function recordTypes($admin = false)
    {
        return [];
    }
}

class DNSHandler
{
    public static function getDrivers()
    {
        $arr = array();
        foreach (glob(__DIR__ . "/../dns/*") as $p) {
            if (is_dir($p) && file_exists($p . "/" . basename($p) . ".php") && basename($p) != "freenom") {
                require_once $p . "/" . basename($p) . ".php";
            }
        }

        foreach (get_declared_classes() as $class) {
            if (get_parent_class($class) != "DNSProvider") {
                continue;
            }

            $obj = new $class;
            $arr[$obj->getShort()] = $obj;
        }

        return $arr;
    }

    public static function getDriver($domain = null)
    {
        global $CFG, $db;

        if ($domain) {
            $sql = $db->query("SELECT dns_provider FROM domains WHERE domain = '" . $db->real_escape_string($domain) . "' LIMIT 1");
            if ($sql && $sql->num_rows) {
                $dns = $sql->fetch_object()->dns_provider;
                if (!empty($dns)) {
                    if (array_key_exists($dns, self::getDrivers())) {
                        return self::getDrivers()[$dns];
                    } else {
                        return new DummyDNSDriver;
                    }
                }
            }
        }

        return self::byDomain($domain);
    }

    public static function byDomain($domain = "")
    {
        if (!is_string($domain)) {
            $domain = "";
        }
        list($sld, $tld) = explode(".", $domain, 2);
        if (!is_string($tld)) {
            $tld = "";
        }
        return self::byTld($tld);
    }

    public static function byTld($tld = "")
    {
        global $CFG, $db;

        if (!is_string($tld)) {
            $tld = "";
        }
        $tld = trim($tld, ".");

        $sql = $db->query("SELECT dns_provider FROM domain_pricing WHERE tld = '" . $db->real_escape_string($tld) . "'");
        $provider = trim($sql->num_rows ? $sql->fetch_object()->dns_provider : "") ?: $CFG['DNS_DRIVER'];

        return self::getDrivers()[$provider] ?: (new DummyDNSDriver);
    }
}
