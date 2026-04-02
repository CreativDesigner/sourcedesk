<?php

class PowerDNS extends DNSProvider
{
    protected $short = "powerdns";
    protected $name = "PowerDNS (MySQL/MariaDB)";
    protected $version = "1.3";

    public function getSettings()
    {
        return array(
            "db_host" => array("type" => "text", "name" => $this->getLang("dbh")),
            "db_user" => array("type" => "text", "name" => $this->getLang("dbu")),
            "db_password" => array("type" => "password", "name" => $this->getLang("dbp")),
            "db_name" => array("type" => "text", "name" => $this->getLang("db")),
            "hint" => array("name" => $this->getLang("hint"), "help" => $this->getLang("hinth"), "type" => "hint"),
            "ws_ipv4" => array("type" => "text", "name" => $this->getLang("v4"), "placeholder" => $this->getLang("v4h")),
            "ws_ipv6" => array("type" => "text", "name" => $this->getLang("v6"), "placeholder" => $this->getLang("OPTIONAL")),
        );
    }

    public function addZone($domain, array $ns)
    {
        global $CFG;

        $domain = $this->idn($domain);

        $db = $this->getConnection();
        if (!$db) {
            return false;
        }

        if (!$db->query("INSERT INTO domains (`name`, `type`) VALUES ('" . $db->real_escape_string($domain) . "', 'MASTER')")) {
            return false;
        }

        $zoneId = $db->insert_id;

        $db->query("INSERT INTO records (`domain_id`, `name`, `type`, `content`, `ttl`, `prio`) VALUES ($zoneId, '" . $db->real_escape_string($domain) . "', 'SOA', '" . $ns[0] . " " . str_replace("@", ".", $CFG['PAGEMAIL']) . ". " . date("Ymd") . "01 3600 900 604800 3600', 3600, 0)");

        return true;
    }

    private function getConnection()
    {
        @$db = new MySQLi($this->options->db_host, $this->options->db_user, $this->options->db_password, $this->options->db_name);
        return $db->connect_errno ? false : $db;
    }

    public function getZones()
    {
        $db = $this->getConnection();
        if (!$db) {
            return false;
        }

        $sql = $db->query("SELECT name, id FROM domains ORDER BY name ASC");
        $arr = array();

        while ($row = $sql->fetch_object()) {
            $arr[$this->idd($row->name)] = $db->query("SELECT COUNT(*) AS c FROM records WHERE domain_id = {$row->id}")->fetch_object()->c;
        }

        return $arr;
    }

    public function getZone($domain, $force = 0)
    {
        $domain = $this->idn($domain);
        $db = $this->getConnection();
        if (!$db) {
            return false;
        }

        $sql = $db->query("SELECT id FROM domains WHERE name = '" . $db->real_escape_string($domain) . "'");
        if ($sql->num_rows != 1) {
            return false;
        }

        $id = $sql->fetch_object()->id;

        $typeOrder = $force ? "`type` = 'SOA' DESC, `type` = 'NS' DESC, " : "";
        foreach ($this->recordTypes() as $t) {
            $typeOrder .= "`type` = '" . $t . "' DESC, ";
        }

        $typeOrder = rtrim($typeOrder, ", ");
        $hidden = $force ? " AND hidden < 2" : " AND hidden = 0 AND dyndns = ''";

        $sql = $db->query("SELECT * FROM records WHERE domain_id = " . intval($id) . "$hidden ORDER BY $typeOrder, name ASC");
        $records = array();
        while ($row = $sql->fetch_object()) {
            if (in_array($row->type, $this->recordTypes()) || $force) {
                $records[$row->id] = array($this->trimDomain($row->name, $domain), $row->type, $this->idd($row->content), $row->ttl, $row->prio, $row->hidden, $row->dyndns);
            }
        }

        $sql = $db->query("SELECT * FROM redirects WHERE hostname LIKE '%" . $db->real_escape_string($domain) . "' ORDER BY hostname ASC");
        while ($row = $sql->fetch_object()) {
            $sql2 = $db->query("SELECT * FROM records WHERE domain_id = " . intval($id) . " AND hidden = 2 AND name LIKE '" . $db->real_escape_string($row->hostname) . "' LIMIT 1");
            if ($sql2->num_rows != 1) {
                continue;
            }

            $i = $sql2->fetch_object();

            $records[$i->id] = array($this->trimDomain($row->hostname, $domain), $row->type == "REDIRECT" ? "URL" : "IFRAME", $row->target, $i->ttl, $i->prio, 0, 0);
        }

        return $records;
    }

    public function recordTypes($admin = false)
    {
        $a = array("MX", "A", "AAAA", "CNAME", "URL", "IFRAME", "SPF", "SRV", "TXT", "AFSDB", "CERT", "DHCID", "DLV", "DNSKEY", "DS", "EUI48", "EUI64", "HINFO", "IPSECKEY", "KEY", "KX", "LOC", "MINFO", "MR", "NAPTR", "NSEC", "NSEC3", "NSEC3PARAM", "OPT", "PTR", "RKEY", "RP", "RRSIG", "SSHFP", "TLSA", "TSIG", "WKS");

        if ($admin) {
            array_push($a, "SOA", "NS");
        }

        if (empty($this->options->ws_ipv4) || !filter_var($this->options->ws_ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            unset($a[array_search("URL", $a)]);
            unset($a[array_search("IFRAME", $a)]);
        }

        return $a;
    }

    private function trimDomain($text, $domain, $r = true)
    {
        if (substr($text, strlen("." . $domain) / -1) == "." . $domain) {
            return substr($text, 0, strlen("." . $domain) / -1);
        }

        if (substr($text, strlen($domain) / -1) == $domain) {
            return substr($text, 0, strlen($domain) / -1);
        }

        if (!$r) {
            return $text;
        }

        return $this->trimDomain($text, $this->idn($domain), false);
    }

    public function addRecord($domain, $record, $hidden = 0, $admin = true)
    {
        $domain = $this->idn($domain);
        $db = $this->getConnection();
        if (!$db) {
            return false;
        }

        $sql = $db->query("SELECT id FROM domains WHERE name = '" . $db->real_escape_string($domain) . "'");
        if ($sql->num_rows != 1) {
            return false;
        }

        $id = $sql->fetch_object()->id;

        $record = $this->sanitizeRecord($domain, $record, $admin);
        if (!$record) {
            return false;
        }

        $name = $db->real_escape_string($record[0]);
        $type = $db->real_escape_string($record[1]);
        $content = $db->real_escape_string($this->idn($record[2]));
        $ttl = $db->real_escape_string($record[3]);
        $prio = $db->real_escape_string($record[4]);

        if ($type == "URL" || $type == "IFRAME") {
            $type = "A";
            $hidden = 2;
            $content = $this->options->ws_ipv4;

            if (!empty($this->options->ws_ipv6) && filter_var($this->options->ws_ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $this->addRecord($domain, array($record[0], "AAAA", $this->options->ws_ipv6, $record[3], $record[4]), 2);
            }

            $db->query("INSERT INTO redirects (hostname, type, target) VALUES ('" . $db->real_escape_string($record[0]) . "', '" . ($record[1] == "URL" ? "REDIRECT" : "FRAME") . "', '" . $db->real_escape_string($record[2]) . "')");
        }

        if ($db->query("INSERT INTO `records` (`name`, `type`, `content`, `ttl`, `prio`, `domain_id`, `hidden`) VALUES ('$name', '$type', '$content', $ttl, $prio, " . intval($id) . ", " . ($hidden ? "1" : "0") . ")")) {
            $record[0] = $this->trimDomain($record[0], $domain);
            $this->refreshZone($domain);
            return $record;
        }

        return false;
    }

    private function sanitizeRecord($domain, $record, $admin = false)
    {
        $domain = $this->idn($domain);
        $record[0] = $this->trimDomain($record[0], $domain);

        if (!ctype_alnum(str_replace(array(".", "-", "_", '@', '*'), "", $record[0]) . "a")) {
            return false;
        }

        if ($record[0] == "@") {
            $record[0] = "";
        }

        if (!empty($record[0])) {
            $record[0] .= ".";
        }

        $record[0] .= $domain;

        if (!in_array($record[1], $this->recordTypes($admin))) {
            return false;
        }

        if ($record[1] == "A" && !filter_var($record[2], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        if ($record[1] == "AAAA" && !filter_var($record[2], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return false;
        }

        if ($record[1] == "TXT") {
            $record[2] = '"' . trim($record[2], "\"'") . '"';
        }

        if (!is_numeric($record[3]) || $record[3] < 180) {
            $record[3] = 3600;
        }

        if (!is_numeric($record[4]) || $record[4] < 0) {
            $record[4] = 0;
        }

        return $record;
    }

    private function refreshZone($domain)
    {
        $domain = $this->idn($domain);
        $db = $this->getConnection();
        if (!$db) {
            return false;
        }

        $sql = $db->query("SELECT * FROM records WHERE name = '" . $db->real_escape_string($domain) . "' AND type = 'SOA'");
        while ($row = $sql->fetch_object()) {
            $ex = explode(" ", $row->content);
            $old = $ex[2];
            $soa = date("Ymd") . "01";
            while ($old >= $soa) {
                $soa++;
            }

            $ex[2] = $soa;
            $db->query("UPDATE records SET content = '" . $db->real_escape_string(implode(" ", $ex)) . "' WHERE id = {$row->id}");
        }
    }

    public function editRecord($domain, $record, $new, $force = 0)
    {
        $domain = $this->idn($domain);
        $db = $this->getConnection();
        if (!$db) {
            return false;
        }

        $sql = $db->query("SELECT id FROM domains WHERE name = '" . $db->real_escape_string($domain) . "'");
        if ($sql->num_rows != 1) {
            return false;
        }

        $id = $sql->fetch_object()->id;

        $new = $this->sanitizeRecord($domain, $new, $force);
        if (!$new) {
            return false;
        }

        $name = $db->real_escape_string($new[0]);
        $type = $db->real_escape_string($new[1]);
        $content = $db->real_escape_string($this->idn($new[2]));
        $ttl = $db->real_escape_string($new[3]);
        $prio = $db->real_escape_string($new[4]);
        $hidden = $force ? "" : " AND hidden != -1 AND dyndns = ''";

        $sql = $db->query("SELECT * FROM records WHERE id = " . intval($record) . " AND domain_id = " . intval($id));
        if ($sql->num_rows != 1) {
            return false;
        }

        $info = $sql->fetch_object();

        if ($type == "URL" || $type == "IFRAME") {
            if ($info->hidden == 2) {
                $db->query("UPDATE redirects SET type = '" . ($type == "URL" ? "REDIRECT" : "FRAME") . "', target = '" . $db->real_escape_string($content) . "' WHERE hostname LIKE '" . $db->real_escape_string($info->name) . "'");
            } else {
                $content = $db->real_escape_string($this->options->ws_ipv4);
                $db->query("UPDATE records SET `name` = '$name', `type` = 'A', `content` = '$content', `ttl` = $ttl, `prio` = $prio, `hidden` = 2 WHERE id = " . intval($record) . " AND domain_id = " . intval($id) . "$hidden LIMIT 1");

                if (!empty($this->options->ws_ipv6) && filter_var($this->options->ws_ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $db->query("INSERT INTO records (`domain_id`, `name`, `type`, `content`, `ttl`, `prio`, `hidden`) VALUES (" . intval($id) . ", '$name', 'AAAA', '" . $db->real_escape_string($this->options->ws_ipv6) . "', $ttl, $prio, 2)");
                }

                $db->query("INSERT INTO redirects (hostname, type, target) VALUES ('" . $db->real_escape_string($name) . "', '" . ($type == "URL" ? "REDIRECT" : "FRAME") . "', '" . $db->real_escape_string($new[2]) . "')");
            }

            return true;
        } else if ($info->hidden == 2) {
            $db->query("DELETE FROM redirects WHERE hostname LIKE '" . $db->real_escape_string($info->name) . "'");

            $db->query("DELETE FROM records WHERE name LIKE '" . $db->real_escape_string($info->name) . "' AND type = 'AAAA' AND content = '" . $db->real_escape_string($this->options->ws_ipv6) . "' AND id != " . intval($record));
            $db->query("DELETE FROM records WHERE name LIKE '" . $db->real_escape_string($info->name) . "' AND type = 'A' AND content = '" . $db->real_escape_string($this->options->ws_ipv4) . "' AND id != " . intval($record));

            $db->query("UPDATE records SET `hidden` = 0 WHERE id = " . intval($record) . " AND domain_id = " . intval($id) . "$hidden LIMIT 1");
        }

        $this->refreshZone($domain);
        if ($db->query("UPDATE records SET `name` = '$name', `type` = '$type', `content` = '$content', `ttl` = $ttl, `prio` = $prio WHERE id = " . intval($record) . " AND domain_id = " . intval($id) . "$hidden LIMIT 1")) {
            return true;
        }

        return false;
    }

    public function editHidden($domain, $record, $hidden = 0)
    {
        $domain = $this->idn($domain);
        $db = $this->getConnection();
        if (!$db) {
            return false;
        }

        $sql = $db->query("SELECT id FROM domains WHERE name = '" . $db->real_escape_string($domain) . "'");
        if ($sql->num_rows != 1) {
            return false;
        }

        $id = $sql->fetch_object()->id;

        if ($db->query("UPDATE records SET `hidden` = " . ($hidden ? "1" : "0") . " WHERE id = " . intval($record) . " AND domain_id = " . intval($id) . " LIMIT 1")) {
            return true;
        }

        return false;
    }

    public function removeRecord($domain, $record, $force = 0)
    {
        $domain = $this->idn($domain);
        $db = $this->getConnection();
        if (!$db) {
            return false;
        }

        $sql = $db->query("SELECT id FROM domains WHERE name = '" . $db->real_escape_string($domain) . "'");
        if ($sql->num_rows != 1) {
            return false;
        }

        $id = $sql->fetch_object()->id;
        $hidden = $force ? "" : " AND hidden != -1 AND dyndns = ''";

        $sql = $db->query("SELECT * FROM records WHERE id = " . intval($record) . " AND domain_id = " . intval($id));
        if ($sql->num_rows != 1) {
            return false;
        }

        $info = $sql->fetch_object();

        if ($info->type == "A" && $info->content == $this->options->ws_ipv4) {
            $db->query("DELETE FROM records WHERE name LIKE '" . $db->real_escape_string($info->name) . "' AND type = 'AAAA' AND content = '" . $db->real_escape_string($this->options->ws_ipv6) . "'");
            $db->query("DELETE FROM redirects WHERE hostname LIKE '" . $db->real_escape_string($info->name) . "'");
        } else if ($info->type == "AAAA" && $info->content == $this->options->ws_ipv6) {
            $db->query("DELETE FROM records WHERE name LIKE '" . $db->real_escape_string($info->name) . "' AND type = 'A' AND content = '" . $db->real_escape_string($this->options->ws_ipv4) . "'");
            $db->query("DELETE FROM redirects WHERE hostname LIKE '" . $db->real_escape_string($info->name) . "'");
        }

        $this->refreshZone($domain);
        if ($db->query("DELETE FROM records WHERE id = " . intval($record) . " AND domain_id = " . intval($id) . "$hidden LIMIT 1")) {
            return true;
        }

        return false;
    }

    public function removeZone($domain)
    {
        $domain = $this->idn($domain);
        $db = $this->getConnection();
        if (!$db) {
            return false;
        }

        $sql = $db->query("SELECT id FROM domains WHERE name = '" . $db->real_escape_string($domain) . "'");
        if ($sql->num_rows != 1) {
            return false;
        }

        $id = $sql->fetch_object()->id;

        return $db->query("DELETE FROM records WHERE domain_id = " . intval($id)) && $db->query("DELETE FROM domains WHERE id = " . intval($id));
    }

    public function addDynDNS($domain, $sub, $password)
    {
        $domain = $this->idn($domain);
        $db = $this->getConnection();
        if (!$db) {
            return false;
        }

        $sql = $db->query("SELECT id FROM domains WHERE name = '" . $db->real_escape_string($domain) . "'");
        if ($sql->num_rows != 1) {
            return false;
        }

        $id = $sql->fetch_object()->id;

        $name = $db->real_escape_string($sub) . "." . $db->real_escape_string($domain);
        $type = "A";
        $content = "127.0.0.1";
        $ttl = "180";
        $prio = "0";
        $password = $db->real_escape_string($password);

        if ($db->query("SELECT 1 FROM `records` WHERE `name` = '$name' AND `dyndns` != ''")->num_rows > 0) {
            return false;
        }

        $db->query("INSERT INTO `records` (`name`, `type`, `content`, `ttl`, `prio`, `domain_id`, `dyndns`) VALUES ('$name', '$type', '$content', $ttl, $prio, " . intval($id) . ", '$password')");

        $type = "AAAA";
        $content = "::1";

        $db->query("INSERT INTO `records` (`name`, `type`, `content`, `ttl`, `prio`, `domain_id`, `dyndns`) VALUES ('$name', '$type', '$content', $ttl, $prio, " . intval($id) . ", '$password')");

        return true;
    }

    public function getDynDNS($domain)
    {
        $domain = $this->idn($domain);
        $db = $this->getConnection();
        if (!$db) {
            return false;
        }

        $sql = $db->query("SELECT id FROM domains WHERE name = '" . $db->real_escape_string($domain) . "'");
        if ($sql->num_rows != 1) {
            return false;
        }

        $id = $sql->fetch_object()->id;

        $a = array();
        $sql = $db->query("SELECT * FROM `records` WHERE `type` = 'A' AND `domain_id` = $id AND `dyndns` != ''");
        while ($row = $sql->fetch_object()) {
            array_push($a, array($this->trimDomain($row->name, $domain), $row->content, $db->query("SELECT * FROM `records` WHERE `type` = 'AAAA' AND `domain_id` = $id AND `dyndns` != ''")->fetch_object()->content, $row->dyndns));
        }

        return $a;
    }

    public function delDynDNS($domain, $sub)
    {
        $domain = $this->idn($domain);
        $db = $this->getConnection();
        if (!$db) {
            return false;
        }

        $sql = $db->query("SELECT id FROM domains WHERE name = '" . $db->real_escape_string($domain) . "'");
        if ($sql->num_rows != 1) {
            return false;
        }

        $id = $sql->fetch_object()->id;

        $name = $db->real_escape_string($sub) . "." . $db->real_escape_string($domain);
        $db->query("DELETE FROM `records` WHERE `domain_id` = $id AND (`type` = 'A' OR `type` = 'AAAA') AND `dyndns` != '' AND `name` = '$name'");
    }

    public function updateDynDNS($domain, $password, $ip, $ip6)
    {
        $domain = $this->idn($domain);
        $db = $this->getConnection();
        if (!$db) {
            return false;
        }

        if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $db->query("UPDATE `records` SET `content` = '" . $db->real_escape_string($ip) . "' WHERE `dyndns` = '" . $db->real_escape_string($password) . "' AND name = '" . $db->real_escape_string($domain) . "' AND `type` = 'A'");
        }

        if (!empty($ip6) && filter_var($ip6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $db->query("UPDATE `records` SET `content` = '" . $db->real_escape_string($ip6) . "' WHERE `dyndns` = '" . $db->real_escape_string($password) . "' AND name = '" . $db->real_escape_string($domain) . "' AND `type` = 'AAAA'");
        }

    }

    public function pushToSlave($domain)
    {
        $db = $this->getConnection();
        if (!$db) {
            return false;
        }

        $db->query("UPDATE domains SET notified_serial = 0 WHERE name = '" . $db->real_escape_string($domain) . "' LIMIT 1");
        return (bool) $db->affected_rows;
    }
}
