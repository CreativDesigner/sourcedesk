<?php

class AtomiaDNS extends DNSProvider
{
    protected $short = "atomiadns";
    protected $name = "Atomia DNS";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "url" => array("type" => "text", "name" => $this->getLang("url"), "placeholder" => "https://dns.sourceway.de"),
            "user" => array("type" => "text", "name" => $this->getLang("username"), "placeholder" => $this->getLang("hint")),
            "password" => array("type" => "password", "name" => $this->getLang("password"), "placeholder" => $this->getLang("hint")),
        );
    }

    public function addZone($domain, array $ns)
    {
        global $CFG;
        $domain = $this->idn($domain);

        foreach ($ns as $k => $v) {
            if (empty(trim($v))) {
                unset($ns[$k]);
            }
        }

        $data = [
            $domain,
            3600,
            array_values($ns)[0] . ".",
            str_replace("@", ".", $CFG['PAGEMAIL']) . ".",
            10800,
            3600,
            604800,
            86400,
            $ns,
            "default",
        ];

        $res = $this->call("AddZone", $data);
        if ($res == "ok") {
            return true;
        }

        return false;
    }

    private function call($method, $params = null)
    {
        $ch = curl_init($this->options->url . "/pretty/atomiadns.json/" . $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Auth-Username: " . $this->options->user,
            "X-Auth-Password: " . $this->options->password,
        ]);

        if (is_array($params) && count($params) > 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array_values($params)));
        }

        $res = @json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $res;
    }

    public function getZones()
    {
        $arr = [];
        $res = $this->call("GetAllZones");

        foreach ($res as $r) {
            $arr[$this->idd($r["name"])] = 0;
        }

        return $arr;
    }

    public function getZone($domain, $force = 0)
    {
        $domain = $this->idn($domain);
        $res = $this->call("GetZone", [$domain]);

        if (empty($res[0])) {
            return false;
        }

        $records = [];
        foreach ($res as $v) {
            foreach ($v["records"] as $r) {
                $priority = 0;
                if ($r['type'] == "MX") {
                    $ex = explode($r['rdata'], " ", 2);
                    $priority = $ex[0];
                    $r['rdata'] = $ex[1];
                }

                $records[$r['id']] = [
                    $r['label'],
                    $r['type'],
                    $this->idd($r['rdata']),
                    $r['ttl'],
                    $priority,
                    0,
                    0,
                ];
            }
        }

        return $records;
    }

    public function recordTypes($admin = false)
    {
        return array("MX", "A", "AAAA", "CNAME", "URL", "IFRAME", "SPF", "SRV", "TXT", "AFSDB", "CERT", "DHCID", "DLV", "DNSKEY", "DS", "EUI48", "EUI64", "HINFO", "IPSECKEY", "KEY", "KX", "LOC", "MINFO", "MR", "NAPTR", "NSEC", "NSEC3", "NSEC3PARAM", "OPT", "PTR", "RKEY", "RP", "RRSIG", "SSHFP", "TLSA", "TSIG", "WKS");
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

        if ($record[1] == "MX") {
            $record[2] = $record[4] . " " . $record[2];
        }

        $res = $this->call("AddDnsRecords", [
            $domain,
            [
                [
                    "label" => $this->trimDomain($record[0], $domain) ?: "@",
                    "class" => "IN",
                    "ttl" => $record[3],
                    "type" => $record[1],
                    "rdata" => $this->idn($record[2]),
                ],
            ],
        ]);

        if (!empty($res[0]) && is_numeric($res[0])) {
            return $res[0];
        }

        return false;
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
        $domain = $this->idn($domain);

        $res = $this->call("DeleteDnsRecords", [
            $domain,
            [
                [
                    "id" => $record,
                    "label" => "www",
                    "class" => "IN",
                    "ttl" => 3600,
                    "type" => "A",
                    "rdata" => "8.8.8.8",
                ],
            ],
        ]);

        return $res == "ok";
    }

    public function removeZone($domain)
    {
        $domain = $this->idn($domain);
        return $this->call("DeleteZone", [$domain]) == "ok";
    }
}
