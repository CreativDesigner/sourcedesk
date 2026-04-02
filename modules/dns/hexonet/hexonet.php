<?php

class HexonetDNS extends DNSProvider
{
    protected $short = "hexonet";
    protected $name = "Hexonet";
    protected $version = "1.1";

    public function getSettings()
    {
        return array(
            "lid" => array("type" => "text", "name" => $this->getLang("lid")),
            "password" => array("type" => "password", "name" => $this->getLang("password")),
        );
    }

    private function send($xml)
    {
        $ch = curl_init($this->options->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        return new SimpleXMLElement($res);
    }

    public function addZone($domain, array $ns)
    {
        global $CFG;

        if ($this->call([
            "COMMAND" => "CreateDNSZone",
            "DNSZONE" => $domain . ".",
        ])['CODE'] != 200) {
            return false;
        }

        return true;
    }

    public function getZones()
    {
        $res = $this->call([
            "COMMAND" => "QueryDNSZoneList",
        ]);

        if ($res["CODE"] != 200) {
            return false;
        }

        $zones = [];
        foreach ($res["PROPERTY"]["RR"] as $zone) {
            $zones[trim($zone, ".")] = 0;
        }

        return $zones;
    }

    public function getZone($domain, $force = 0)
    {
        // Select all records except for MX
        $res = $this->call([
            "COMMAND" => "QueryDNSZoneRRList",
            "DNSZONE" => $domain . ".",
            "EXTENDED" => "1",
        ]);

        if ($res["CODE"] != 200) {
            return false;
        }

        foreach ($res["PROPERTY"]["RR"] as $rr) {
            $fields = explode(" ", $rr);
            $sub = array_shift($fields);
            $ttl = array_shift($fields);
            $class = array_shift($fields);
            $rrtype = array_shift($fields);
            $priority = 0;

            $content = "";
            switch ($rrtype) {
                case "SRV":
                    $priority = array_shift($fields);
                // Intentional fall-through

                case "TXT":
                    $content = implode(" ", $fields);
                    break;

                case "X-HTTP":
                    continue 2;

                default:
                    $content = array_shift($fields);
            }

            $sub = $this->trimDomain($sub, $domain . ".");

            if (!$force && $this->isDynDNS($sub . "." . $domain)) {
                continue;
            }

            $hash = hash("sha512", $sub . $rrtype . $content . $ttl . $class . $priority);
            $rid = "";
            while (strlen($rid) < 12) {
                $char = substr($hash, 0, 1);
                $hash = substr($hash, 1);

                if (in_array($char, ["1", "2", "3", "4", "5", "6", "7", "8", "9"])) {
                    $rid .= $char;
                }
            }

            $records[$rid] = [
                $sub,
                $rrtype,
                $content,
                $ttl,
                $priority,
                0,
                $this->isDynDNS($sub . "." . $domain) ? 1 : 0,
            ];
        }

        // Select MX records
        $res = $this->call([
            "COMMAND" => "QueryDNSZoneRRList",
            "DNSZONE" => $domain . ".",
            "SHORT" => "1",
        ]);

        if ($res["CODE"] != 200) {
            return false;
        }

        foreach ($res["PROPERTY"]["RR"] as $rr) {
            $fields = explode(" ", $rr);
            $sub = array_shift($fields);
            $ttl = array_shift($fields);
            $class = array_shift($fields);
            $rrtype = array_shift($fields);

            if ($rrtype != "MX") {
                continue;
            }

            $priority = array_shift($fields);
            $content = array_shift($fields);

            $sub = $this->trimDomain($sub, $domain . ".");

            $hash = hash("sha512", $sub . $rrtype . $content . $ttl . $class . $priority);
            $rid = "";
            while (strlen($rid) < 12) {
                $char = substr($hash, 0, 1);
                $hash = substr($hash, 1);

                if (in_array($char, ["1", "2", "3", "4", "5", "6", "7", "8", "9"])) {
                    $rid .= $char;
                }
            }

            $records[$rid] = [
                $sub,
                $rrtype,
                $content,
                $ttl,
                $priority,
                0,
                0,
            ];
        }

        return $records;
    }

    public function recordTypes($admin = false)
    {
        return array("MX", "A", "AAAA", "CNAME", "SRV", "TXT");
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

    public function addDynDNS($domain, $sub, $password)
    {
        $r = [$sub, "A", "127.0.0.1", "180", "0"];
        $this->addRecord($domain, $r);

        $r = [$sub, "AAAA", "::1", "180", "0"];
        $this->addRecord($domain, $r);

        $this->setDynDNS($sub . "." . $domain, $password);

        return true;
    }

    public function getDynDNS($domain)
    {
        $records = $this->getZone($domain, true);
        $dyn = [];

        foreach ($records as $i => $r) {
            if (!in_array($r[1], ["A", "AAAA"])) {
                continue;
            }

            if (!$this->isDynDNS($r[0] . "." . $domain)) {
                continue;
            }

            if (array_key_exists($r[0], $dyn)) {
                $dyn[$r[0]][$r[1] == "A" ? 1 : 2] = $r[2];
                $dyn[$r[0]][$r[1] == "A" ? 4 : 5] = $i;
            } else {
                $dyn[$r[0]] = [
                    $r[0],
                    $r[1] == "A" ? $r[2] : "",
                    $r[1] == "AAAA" ? $r[2] : "",
                    $this->isDynDNS($r[0] . "." . $domain),
                    $r[1] == "A" ? $i : 0,
                    $r[1] == "AAAA" ? $i : 0,
                ];
            }
        }

        return array_values($dyn);
    }

    public function delDynDNS($domain, $sub)
    {
        $rec = $this->getDynDNS($domain);

        foreach ($rec as $r) {
            if ($r[0] == $sub) {
                $this->removeRecord($domain, [$r[4], $r[5]], true);
                $this->setDynDNS($sub . "." . $domain, "");

                break;
            }
        }
    }

    public function updateDynDNS($domain, $password, $ip, $ip6)
    {
        $ex = explode(".", $domain, 2);
        $sub = array_shift($ex);
        $domain = array_shift($ex);

        $rec = $this->getDynDNS($domain);

        foreach ($rec as $r) {
            if ($r[0] == $sub && $r[3] == $password) {
                if ($r[4]) {
                    $newRecord = [$sub, "A", $ip, "180", "0"];
                    $this->editRecord($domain, $r[4], $newRecord, true);
                }

                if ($ip6) {
                    $newRecord = [$sub, "AAAA", $ip6, "180", "0"];

                    if ($r[5]) {
                        $this->editRecord($domain, $r[5], $newRecord, true);
                    } else {
                        $this->addRecord($domain, $newRecord);
                    }
                } else if ($r[5]) {
                    $this->removeRecord($domain, $r[5], true);
                }

                return true;
            }
        }

        return false;
    }

    public function addRecord($domain, $record, $hidden = 0, $admin = true)
    {
        $name = $this->trimDomain($record[0], $domain);
        if (!empty($name)) {
            $name .= ".";
        }
        $name .= $domain . ".";

        $content = $record[2];
        if (in_array($record[1], ["SRV", "MX"])) {
            $content = $record[4] . " " . $content;
        }

        return $this->call([
            "COMMAND" => "UpdateDNSZone",
            "DNSZONE" => $domain . ".",
            "ADDRR0" => $name . " " . $record[3] . " IN " . $record[1] . " " . $content,
        ])["CODE"] == 200;
    }

    public function editRecord($domain, $record, $new, $force = 0)
    {
        if (!$this->removeRecord($domain, $record, $force)) {
            return false;
        }

        return $this->addRecord($domain, $new);
    }

    public function removeRecord($domain, $record, $force = 0)
    {
        $records = $this->getZone($domain, $force);

        if (!is_array($records)) {
            return false;
        }

        foreach ($records as $rid => $rr) {
            if ($rid == $record) {
                $record = $rr;
                break;
            }
        }

        if (!is_array($record)) {
            return false;
        }

        $name = $this->trimDomain($record[0], $domain);
        if (!empty($name)) {
            $name .= ".";
        }
        $name .= $domain . ".";

        $content = $record[2];
        if (in_array($record[1], ["SRV", "MX"])) {
            $content = $record[4] . " " . $content;
        }

        return $this->call([
            "COMMAND" => "UpdateDNSZone",
            "DNSZONE" => $domain . ".",
            "DELRR0" => $name . " " . $record[3] . " IN " . $record[1] . " " . $content,
        ])["CODE"] == 200;
    }

    public function removeZone($domain)
    {
        return $this->call([
            "COMMAND" => "DeleteDNSZone",
            "DNSZONE" => $domain . ".",
        ])["CODE"] == 200;
    }

    private function call($command)
    {
        global $CFG;

        $args = array(
            "s_login" => $this->options->lid,
            "s_pw" => $this->options->password,
            "s_command" => $this->encodeCommand($command),
        );

        $ch = curl_init("https://api.ispapi.net/api/call.cgi");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
        curl_setopt($ch, CURLOPT_USERAGENT, "ISPAPI via sourceDESK");
        curl_setopt($ch, CURLOPT_REFERER, $CFG['PAGEURL']);
        $res = curl_exec($ch);
        curl_close($ch);

        if (is_array($res)) {
            return $res;
        }

        $hash = array(
            "PROPERTY" => array(),
            "CODE" => "423",
            "DESCRIPTION" => "Empty response from API",
        );

        if (!$res) {
            return $hash;
        }

        $rlist = explode("\n", $res);
        foreach ($rlist as $item) {
            if (preg_match("/^([^\=]*[^\t\= ])[\t ]*=[\t ]*(.*)$/", $item, $m)) {
                $attr = $m[1];
                $value = $m[2];
                $value = preg_replace("/[\t ]*$/", "", $value);
                if (preg_match("/^property\[([^\]]*)\]/i", $attr, $m)) {
                    $prop = strtoupper($m[1]);
                    $prop = preg_replace("/\s/", "", $prop);
                    if (in_array($prop, array_keys($hash["PROPERTY"]))) {
                        array_push($hash["PROPERTY"][$prop], $value);
                    } else {
                        $hash["PROPERTY"][$prop] = array($value);
                    }
                } else {
                    $hash[strtoupper($attr)] = $value;
                }
            }
        }

        if ((!$hash["CODE"]) || (!$hash["DESCRIPTION"])) {
            $hash = array(
                "PROPERTY" => array(),
                "CODE" => "423",
                "DESCRIPTION" => "Invalid response from API",
            );
        }

        return $hash;
    }

    private function encodeCommand($commandarray)
    {
        if (!is_array($commandarray)) {
            return $commandarray;
        }
        $command = "";
        foreach ($commandarray as $k => $v) {
            if (is_array($v)) {
                $v = $this->encodeCommand($v);
                $l = explode("\n", trim($v));
                foreach ($l as $line) {
                    $command .= "$k$line\n";
                }
            } else {
                $v = preg_replace("/\r|\n/", "", $v);
                $command .= "$k=$v\n";
            }
        }
        return $command;
    }
}
