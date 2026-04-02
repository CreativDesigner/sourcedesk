<?php
// Abstract class for provisioning modules

abstract class Provisioning
{
    protected $lang;
    protected $langLoaded;
    protected $options = array();
    protected $data = array();
    protected $id = 0;
    protected $pd = null;
    protected $cf = array();
    protected $product = 0;
    protected $serverMgmt = false;
    protected $usernameMgmt = false;

    abstract public function Config($id, $product = true);
    abstract public function Create($id);
    abstract public function Output($id, $task = "");

    public function ApiTasks($id)
    {return array();}
    public function OwnFunctions($id)
    {return array();}
    public function AdminFunctions($id)
    {return array();}

    public function usagePars()
    {
        return [];
    }

    public function usageFetch($id)
    {
        return [];
    }

    public function getUsername($id)
    {
        global $db, $CFG;

        $id = intval($id);

        $sql = $db->query("SELECT username FROM client_products WHERE ID = $id");
        if ($sql->num_rows && $username = $sql->fetch_object()->username) {
            return $username;
        }

        if ($this->usernameMgmt) {
            $sql = $db->query("SELECT product FROM client_products WHERE ID = $id");
            if ($sql->num_rows) {
                $pid = $sql->fetch_object()->product;
                $sql = $db->query("SELECT username_format, username_next, username_step FROM products WHERE ID = $pid");
                if ($sql->num_rows) {
                    $info = $sql->fetch_object();
                    $incrementing = $info->username_next;

                    $db->query("UPDATE products SET username_next = username_next + GREATEST(1, username_step) WHERE ID = $pid");
                    $username = $info->username_format;

                    $replace = [
                        "customerId" => 0,
                        "contractId" => $id,
                        "firstName" => "",
                        "lastName" => "",
                        "firstNameFirstLetter" => "",
                        "lastNameFirstLetter" => "",
                        "email" => "",
                        "emailLocalPart" => "",
                        "year" => date("Y"),
                        "month" => date("m"),
                        "day" => date("d"),
                        "incrementing" => $incrementing,
                    ];

                    $client = $this->getClient($id);
                    if ($client) {
                        $replace["customerId"] = $client->get()['ID'];
                        $replace["firstName"] = $client->get()['firstname'];
                        $replace["lastName"] = $client->get()['lastname'];
                        $replace["firstNameFirstLetter"] = substr($client->get()['firstname'], 0, 1);
                        $replace["lastNameFirstLetter"] = substr($client->get()['lastname'], 0, 1);
                        $replace["email"] = $client->get()['mail'];
                        $replace["emailLocalPart"] = array_shift(explode("@", $client->get()['mail']));
                    }

                    $username = str_replace(array_map(function ($v) {return "{" . $v . "}";}, array_keys($replace)), array_values($replace), $username);
                }
            }
        }

        if (empty($username)) {
            $username = "c" . $id;
        }

        $db->query("UPDATE client_products SET username = '" . $db->real_escape_string($username) . "' WHERE ID = $id");
        return $username;
    }

    final public function __construct()
    {
        $name = $this->getLang("name");
        if (is_string($name)) {
            $this->name = $name;
        }
    }

    final public function getLang($str = "")
    {
        $lang = currentLang();

        $str = strtoupper($str);

        if (!is_array($this->lang) || $lang != $langLoaded) {
            if (file_exists($path = __DIR__ . "/../provisioning/" . $this->short . "/language/" . basename($lang) . ".php")) {
                require $path;
            } else {
                return false;
            }
        }

        return !empty(trim($str)) && isset($this->lang[$str]) ? $this->lang[$str] : $this->lang;
    }

    final public function getOption($n)
    {
        if (!empty($n) && is_array($this->cf) && array_key_exists($n, $this->cf)) {
            return $this->cf[$n];
        }

        return isset($this->options[$n]) ? $this->options[$n] : false;
    }

    final public function getData($n)
    {
        return isset($this->data[$n]) ? $this->data[$n] : false;
    }

    final public function getServerMgmt()
    {
        return boolval($this->serverMgmt);
    }

    final public function getUsernameMgmt()
    {
        return boolval($this->usernameMgmt);
    }

    final public function setData($k, $v)
    {
        global $db, $CFG;

        if (!$this->id) {
            return false;
        }

        $this->data[$k] = $v;
        $db->query("UPDATE client_products SET module_data = '" . $db->real_escape_string(encrypt(serialize($this->data))) . "' WHERE ID = {$this->id} LIMIT 1");
    }

    public function loadOptions($id, $pd = false)
    {
        global $db, $CFG;

        $this->id = $id;
        $this->pd = $pd;
        $this->options = array();

        if ($pd) {
            if ($id >= 0) {
                $sql = $db->query("SELECT setting, value FROM product_provisioning WHERE module = '" . $db->real_escape_string($this->getShort()) . "' AND pid = " . intval($id));

                while ($row = $sql->fetch_object()) {
                    $this->options[$row->setting] = decrypt($row->value);
                }
            } else {
                $serverId = intval(abs($id));
                $short = $db->real_escape_string($this->getShort());
                $sql = $db->query("SELECT `data` FROM panels WHERE server = $serverId AND module = '$short'");
                if ($sql->num_rows) {
                    $data = @unserialize(decrypt($sql->fetch_object()->data));
                    if (is_array($data)) {
                        $this->options = $data;
                    }
                }
            }
        } else {
            $sql = $db->query("SELECT module_settings, module_data, cf, product FROM client_products WHERE module = '" . $db->real_escape_string($this->getShort()) . "' AND ID = " . intval($id));
            if ($sql->num_rows == 1) {
                $info = $sql->fetch_object();
                $this->product = $info->product;
                $this->options = unserialize(decrypt($info->module_settings));
                $this->data = unserialize(decrypt($info->module_data));
                $this->cf = unserialize($info->cf);
                if (!is_array($this->cf)) {
                    $this->cf = [];
                }

                foreach ($this->cf as $id => $v) {
                    $cfSql = $db->query("SELECT name FROM products_cf WHERE ID = " . intval($id));
                    if ($cfSql->num_rows == 1) {
                        $this->cf[$cfSql->fetch_object()->name] = $v;
                    }

                }
            }
        }

        if (is_array($this->options) && array_key_exists("_mgmt_server", $this->options) && $this->options["_mgmt_server"]) {
            $sd = $this->serverData($this->options["_mgmt_server"]);
            if (is_array($sd)) {
                foreach ($sd as $k => $v) {
                    $this->options[$k] = $v;
                }
            }
        }
    }

    final protected function serverData($serverId)
    {
        global $CFG, $db;

        if (substr($serverId, 0, 5) == "group") {
            $groupId = intval(substr($serverId, 5));

            $group = MonitoringServerGroup::getInstance($groupId);
            if ($group) {
                $server = $group->getLeastFullServer();
                if ($server) {
                    $serverId = $server->ID;

                    if ($this->id && $this->pd === false) {
                        $this->options["_mgmt_server"] = $serverId;

                        $sql = $db->query("SELECT module_settings FROM client_products WHERE ID = " . intval($this->id));
                        if ($sql->num_rows) {
                            $ms = @unserialize(decrypt($sql->fetch_object()->module_settings));
                            if ($ms && is_array($ms)) {
                                $ms["_mgmt_server"] = $serverId;
                                $ms = $db->real_escape_string(encrypt(serialize($ms)));
                                $db->query("UPDATE client_products SET module_settings = '$ms' WHERE ID = " . intval($this->id));
                            }
                        }
                    }
                }
            }
        }

        $serverId = intval($serverId);
        $short = $db->real_escape_string($this->getShort());
        $sql = $db->query("SELECT `data` FROM panels WHERE server = $serverId AND module = '$short'");
        if ($sql->num_rows) {
            $data = @unserialize(decrypt($sql->fetch_object()->data));
            if (is_array($data)) {
                return $data;
            }
        }

        return false;
    }

    final public function getClient($id)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT user FROM client_products WHERE module = '" . $db->real_escape_string($this->getShort()) . "' AND ID = " . intval($id));
        if ($sql->num_rows == 1) {
            return User::getInstance($sql->fetch_object()->user, "ID");
        }

        return false;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getShort()
    {
        return $this->short;
    }

    public function getVersion()
    {
        return $this->version ?? "1.0";
    }

    final protected function getProductInfo()
    {
        return ProvisioningHandler::getProductInfo($this->product);
    }

    final public function getDedicatedIP()
    {
        return ProvisioningHandler::getDedicatedIP($this->id, $this->product);
    }

    final public function releaseDedicatedIP()
    {
        ProvisioningHandler::releaseDedicatedIP($this->id);
    }
}

// Class for handling provisioning modules

class ProvisioningHandler
{
    protected $modules = array();
    protected $mgmtModules = array();

    public function __construct()
    {
        global $var, $CFG, $db, $adminInfo;
        $language = isset($var['language']) ? $var['language'] : $CFG['LANG'];

        $addonHandle = opendir(__DIR__ . '/../provisioning/');
        while ($f = readdir($addonHandle)) {
            if (is_dir(__DIR__ . '/../provisioning/' . $f) && substr($f, 0, 1) != "." && file_exists(__DIR__ . '/../provisioning/' . $f . '/' . $f . '.php')) {
                require_once __DIR__ . '/../provisioning/' . $f . '/' . $f . '.php';
            }
        }

        closedir($addonHandle);

        $name = $name2 = array();
        foreach (get_declared_classes() as $class) {
            if (get_parent_class($class) == "Provisioning" || get_parent_class(get_parent_class($class)) == "Provisioning") {
                $obj = new $class($language);
                $this->modules[$obj->getShort()] = $obj;

                if ($obj->getServerMgmt()) {
                    $this->mgmtModules[$obj->getShort()] = $obj;
                    $name2[$obj->getShort()] = strtolower($obj->getName());
                }

                $name[$obj->getShort()] = strtolower($obj->getName());
            }
        }

        array_multisort($name, SORT_ASC, $this->modules);
        array_multisort($name2, SORT_ASC, $this->mgmtModules);
    }

    public static function getDedicatedIP($id, $product, $force = true)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT ip FROM ip_addresses WHERE contract = " . intval($id));
        if ($sql->num_rows) {
            return $sql->fetch_object()->ip;
        } else if (!$force) {
            return false;
        }

        $info = self::getProductInfo($product);
        if (!$info) {
            return false;
        }

        $pid = intval(self::getProductInfo($product)->ip_product ?: $product);
        $sql = $db->query("SELECT ip FROM ip_addresses WHERE product = $pid AND contract = 0 LIMIT 1");

        if ($sql->num_rows) {
            $ip = $sql->fetch_object()->ip;
            $db->query("UPDATE ip_addresses SET contract = " . intval($id) . " WHERE ip = '" . $db->real_escape_string($ip) . "' AND contract = 0");
            return $db->affected_rows ? $ip : false;
        }

        return false;
    }

    public static function releaseDedicatedIP($id)
    {
        global $db, $CFG;
        $db->query("UPDATE ip_addresses SET contract = 0 WHERE contract = " . intval($id));
    }

    public static function getProductInfo($product)
    {
        global $db, $CFG;

        if (empty($product)) {
            return false;
        }

        $sql = $db->query("SELECT * FROM products WHERE ID = " . intval($product));
        if ($sql->num_rows != 1) {
            return false;
        }

        return $sql->fetch_object();
    }

    public function get($serverMgmt = false)
    {
        return $serverMgmt ? $this->mgmtModules : $this->modules;
    }

    public function getCancellationDates($id)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT mct, ct, np, billing, `date` FROM client_products WHERE ID = " . intval($id));
        if ($sql->num_rows == 0) {
            return date("Y-m-d");
        }

        $info = $sql->fetch_object();

        if ($info->billing == "onetime" || empty($info->billing)) {
            return date("Y-m-d");
        }

        $interval = array(
            "monthly" => "1 month",
            "quarterly" => "3 months",
            "semiannually" => "6 months",
            "annually" => "1 year",
            "biennially" => "2 years",
            "trinnially" => "3 years",
        );

        if (!array_key_exists($info->billing, $interval)) {
            return date("Y-m-d");
        }
        $interval = $interval[$info->billing];

        $firstInterval = $interval;
        $firstCt = $info->ct;
        if (strtotime("+" . $info->mct) !== false && strtotime("+" . $info->mct) != time()) {
            $firstInterval = $firstCt = $info->mct;
        }

        $dates = array();
        if (strtotime("+" . $info->ct) == time() || strtotime("+" . $info->ct) === false) {
            if (strtotime("+" . $info->np) == time() || strtotime("+" . $info->np) === false) {
                $date = strtotime("+" . $firstInterval, $info->date);
                while (count($dates) < 5) {
                    if ($date > time()) {
                        $dates[] = date("Y-m-d", $date);
                    }

                    $date = strtotime("+" . $interval, $date);
                }
            } else {
                $date = strtotime("+" . $firstInterval, $info->date);
                while (count($dates) < 5) {
                    if ($date > strtotime("+" . $info->np)) {
                        $dates[] = date("Y-m-d", $date);
                    }

                    $date = strtotime("+" . $interval, $date);
                }
            }
        } else {
            if (strtotime("+" . $info->np) == time() || strtotime("+" . $info->np) === false) {
                $date = strtotime("+" . $firstCt, $info->date);
                while (count($dates) < 5) {
                    if ($date > time()) {
                        $dates[] = date("Y-m-d", $date);
                    }

                    $date = strtotime("+" . $info->ct, $date);
                }
            } else {
                $date = strtotime("+" . $firstCt, $info->date);
                while (count($dates) < 5) {
                    if ($date > strtotime("+" . $info->np)) {
                        $dates[] = date("Y-m-d", $date);
                    }

                    $date = strtotime("+" . $info->ct, $date);
                }
            }
        }
        return $dates;
    }
}

$provisioning = new ProvisioningHandler;
