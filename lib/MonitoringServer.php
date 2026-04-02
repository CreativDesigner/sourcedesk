<?php

class MonitoringServer
{
    public function __construct($id)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT * FROM monitoring_server WHERE ID = " . intval($id));
        if ($sql->num_rows === 1) {
            while ($row = $sql->fetch_array()) {
                foreach ($row as $k => $v) {
                    $this->$k = $v;
                }
            }
        }
    }

    public static function getAllByGroup(MonitoringServerGroup $group)
    {
        global $db, $CFG;

        $res = [];
        $sql = $db->query("SELECT ID FROM monitoring_server WHERE server_group = " . $group->ID);
        while ($row = $sql->fetch_object()) {
            array_push($res, self::getInstance($row->ID));
        }

        return $res;
    }

    public static function getInstance($id)
    {
        $obj = new MonitoringServer($id);
        return $obj->ID == $id ? $obj : false;
    }

    public function countContracts()
    {
        global $db, $CFG;
        return $db->query("SELECT COUNT(*) c FROM client_products WHERE server_id = {$this->ID} AND active IN (0,1)")->fetch_object()->c;
    }

    public function getGroup()
    {
        return MonitoringServerGroup::getInstance($this->server_group);
    }

    public function save()
    {
        global $db, $CFG;

        $update = "";

        foreach (get_object_vars($this) as $k => $v) {
            if ($k == "ID" || is_numeric($k)) {
                continue;
            }

            $k = $db->real_escape_string($k);
            $v = $db->real_escape_string($v);
            $update .= "`$k` = '$v', ";
        }

        $update = rtrim($update, ", ");
        $db->query("UPDATE monitoring_server SET $update WHERE ID = " . intval($this->ID) . " LIMIT 1");
    }

    public function get($k)
    {
        if (!empty($k)) {
            return isset($this->$k) ? $this->$k : "";
        }

        return (array) $this;
    }

    public function set($k, $v)
    {
        $this->$k = $v;
    }

    public function check($log = false)
    {
        global $db, $CFG;

        $logPath = __DIR__ . "/../controller/crons/monitoring.lock";

        $types = Monitoring::serviceTypes();
        $sql = $db->query("SELECT * FROM monitoring_services WHERE server = " . intval($this->ID));

        $status = $this->getStatus(true);

        while ($row = $sql->fetch_object()) {
            try {
                if ($log) {
                    file_put_contents($logPath, "[" . date("Y-m-d H:i:s") . "] => Service {$row->ID}\n", FILE_APPEND);
                }

                if (!in_array($row->type, $types)) {
                    throw new Exception("Service type unknown");
                }

                $c = $row->type;
                $settings = @unserialize($row->settings) ?: [];
                throw new Exception(strval($c::check($settings)));
            } catch (Exception $ex) {
                $db->query("UPDATE monitoring_services SET last_called = " . time() . ", last_result = '" . $db->real_escape_string($ex->getMessage()) . "' WHERE ID = " . $row->ID);
            }
        }

        $newStatus = $this->getStatus(true);
        if ($newStatus != $status) {
            Telegram::sendMessage("[" . ($newStatus ? "OK" : "FAIL") . "] " . $this->name);
        }
    }

    public function shouldShow()
    {
        global $CFG;
        return !$CFG['HIDE_NORMAL_SERVER'] || !$this->getStatus();
    }

    public function getFormattedStatus($intern = false, $service = null)
    {
        global $lang;

        if (!isset($lang['STATUS']['OK'])) {
            $lang['STATUS']['OK'] = "OK";
        }

        if (!isset($lang['STATUS']['FAIL'])) {
            $lang['STATUS']['FAIL'] = "Fehler";
        }

        if ($service) {
            switch ($this->getStatus($intern, $service)) {
                case "ok":
                    return "<div class='label label-success'>{$lang['STATUS']['OK']}</div>";
                    break;

                case "fail":
                    return "<div class='label label-danger'>{$lang['STATUS']['FAIL']}</div>";
                    break;

                case "waiting":
                    return "<div class='label label-warning'>Wartet</div>";
                    break;

                default:
                    return "<div class='label label-default'>Inaktiv</div>";
            }
        }

        return $this->getStatus($intern) ? "<div class='label label-success'>{$lang['STATUS']['OK']}</div>" : "<div class='label label-danger'>{$lang['STATUS']['FAIL']}</div>";
    }

    public function getStatus($intern = false, $service = null)
    {
        global $db, $CFG;

        if ($service) {
            $service = intval($service);
            $sql = $db->query("SELECT active, last_result, last_called FROM monitoring_services WHERE server = {$this->ID} AND ID = $service");
            if ($sql->num_rows != 1) {
                return "unknown";
            }

            $info = $sql->fetch_object();
            if (!$info->active) {
                return "inactive";
            }

            if (!$info->last_called) {
                return "waiting";
            }

            return $info->last_result === "1" ? "ok" : "fail";
        }

        $where = !$intern ? " AND internal = 0" : "";
        return !$db->query("SELECT 1 FROM monitoring_services WHERE last_called > 0 AND server = {$this->ID} AND active = 1 AND last_result != '1'$where LIMIT 1")->num_rows;
    }

    public function countByStatus($status)
    {
        global $db, $CFG;

        if ($status == 0) {
            return $db->query("SELECT 1 FROM monitoring_services WHERE server = {$this->ID} AND active = 1 AND last_result != '1' AND last_called > 0")->num_rows;
        } else if ($status == 1) {
            return $db->query("SELECT 1 FROM monitoring_services WHERE server = {$this->ID} AND active = 1 AND last_result = '1' AND last_called > 0")->num_rows;
        } else if ($status == -1) {
            return $db->query("SELECT 1 FROM monitoring_services WHERE server = {$this->ID} AND active = 0")->num_rows;
        }
    }

    public function lastCheck($order = "ASC")
    {
        global $db, $CFG;
        $sql = $db->query("SELECT last_called FROM monitoring_services WHERE server = " . intval($this->ID) . " ORDER BY last_called $order LIMIT 1");
        return $sql->num_rows ? $sql->fetch_object()->last_called : 0;
    }

    public function checkUpdates()
    {
        global $db, $CFG;

        if (empty($this->ssh_host)) {
            return;
        }

        $this->set("ssh_last", time());
        $this->set("ssh_error", "");

        $ssh = new phpseclib\Net\SSH2($this->ssh_host, $this->ssh_port);

        try {
            $hostKey = $ssh->getServerPublicHostKey();
            if (!$hostKey) {
                throw new RuntimeException("SSH connection failed");
            }
            $this->set("ssh_fingerprint_last", $hostKey);

            if ($hostKey != $this->ssh_fingerprint) {
                $this->save();
                return;
            }

            $key = new phpseclib\Crypt\RSA;
            $key->load(decrypt($this->ssh_key));

            if (!$ssh->login("root", $key)) {
                throw new RuntimeException("SSH login failed");
            }

            $packages = [];
            $waitingUpdates = "";
            $sql = $db->query("SELECT * FROM monitoring_updates WHERE server = {$this->ID}");
            while ($row = $sql->fetch_object()) {
                if ($row->status == "waiting") {
                    $waitingUpdates .= " " . $row->package;
                } else {
                    $packages[$row->package] = $row->new;
                }
            }

            switch ($this->operating_system) {
                case "D":
                case "U":
                    if ($waitingUpdates) {
                        $ssh->setTimeout(500);
                        $ssh->exec("apt-get install --only-upgrade$waitingUpdates");
                        $db->query("DELETE FROM monitoring_updates WHERE server = {$this->ID} and status = 'waiting'");
                    }

                    $ssh->exec("apt-get autoclean -y");
                    $ssh->exec("apt-get update -qq");
                    $res = $ssh->exec("apt-get upgrade -s");

                    $ex = explode("<br />", nl2br($res));
                    foreach ($ex as $line) {
                        $line = trim($line);
                        if (substr($line, 0, 4) != "Inst") {
                            continue;
                        }

                        $line = trim(substr($line, 4));

                        $new = substr($line, strpos($line, "[") + 1);
                        $new = substr($new, 0, strpos($new, "]"));

                        $package = trim(substr($line, 0, strpos($line, "[")));

                        $package = $db->real_escape_string($package);
                        $new = $db->real_escape_string($new);

                        if (!array_key_exists($package, $packages)) {
                            $db->query("INSERT INTO monitoring_updates (server, package, new, status) VALUES ({$this->ID}, '$package', '$new', 'new')");
                        } else if ($packages[$package] != $new) {
                            $db->query("UPDATE monitoring_updates SET new = '$new', status = 'new' WHERE server = {$this->ID} AND package = '$package'");
                        }
                    }
                    break;

                case "R":
                    if ($waitingUpdates) {
                        $ssh->setTimeout(500);
                        $ssh->exec("yum update -y$waitingUpdates");
                        $db->query("DELETE FROM monitoring_updates WHERE server = {$this->ID} and status = 'waiting'");
                    }

                    $res = $ssh->exec("yum check-update -q");

                    $ex = explode("<br />", nl2br($res));
                    foreach ($ex as $line) {
                        $line = trim($line);
                        if (empty($line)) {
                            continue;
                        }

                        $tx = explode(" ", $line);
                        foreach ($tx as $k => $v) {
                            if (empty($v)) {
                                unset($tx[$k]);
                            }
                        }

                        $tx = array_values($tx);
                        if (count($tx) != 3) {
                            continue;
                        }

                        $package = explode(".", $tx[0]);
                        array_pop($package);
                        $package = implode(".", $package);
                        $new = $tx[1];

                        $package = $db->real_escape_string($package);
                        $new = $db->real_escape_string($new);

                        if (!array_key_exists($package, $packages)) {
                            $db->query("INSERT INTO monitoring_updates (server, package, new, status) VALUES ({$this->ID}, '$package', '$new', 'new')");
                        } else if ($packages[$package] != $new) {
                            $db->query("UPDATE monitoring_updates SET new = '$new', status = 'new' WHERE server = {$this->ID} AND package = '$package'");
                        }
                    }
                    break;
            }
        } catch (RuntimeException $ex) {
            $this->set("ssh_error", $ex->getMessage() ?: "Unknown error");
        }

        $this->save();
    }
}
