<?php

class NamecheapDNS extends DNSProvider
{
    protected $short = "namecheap";
    protected $name = "Namecheap";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "api_user" => array("type" => "text", "name" => $this->getLang("username")),
            "api_password" => array("type" => "password", "name" => $this->getLang("key")),
        );
    }

    private function client($class = "DomainsDns")
    {
        if (!class_exists("NamecheapApi")) {
            require_once __DIR__ . "/lib/namecheap_api.php";
        }

        $api = new NamecheapApi($this->options->api_user, $this->options->api_password, false);
        $class = "Namecheap" . $class;
        return new $class($api);
    }

    public function addZone($domain, array $ns)
    {
        list($sld, $tld) = explode(".", $domain, 2);
        if ($this->client()->setCustom([
            "SLD" => $sld,
            "TLD" => $tld,
        ])->response()->DomainDNSSetDefaultResult->{'@attributes'}->Updated != "true") {
            return false;
        }

        return true;
    }

    private function setHosts($domain, $records)
    {
        list($sld, $tld) = explode(".", $domain, 2);

        $data = [
            "SLD" => $sld,
            "TLD" => $tld,
            "EmailType" => "MX",
        ];

        $i = 1;
        foreach ($records as $r) {
            $data["Hostname" . $i] = $this->trimDomain($r[0], $domain) ?: "@";
            $data["RecordType" . $i] = $r[1];
            $data["Address" . $i] = $r[2];
            $data["TTL" . $i] = $r[3];

            if ($r[1] == "MX") {
                $data["MXPref" . $i] = $r[4];
            }

            $i++;
        }

        return $this->client()->setHosts($data)->response()->DomainDNSSetHostsResult->{'@attributes'}->IsSuccess == "true";
    }

    public function getZones()
    {
        // Not supported by API
        return [];
    }

    public function getZone($domain, $force = 0)
    {
        list($sld, $tld) = explode(".", $domain, 2);
        $res = $this->client()->getHosts([
            "SLD" => $sld,
            "TLD" => $tld,
        ])->response()->DomainDNSGetHostsResult;

        if ($res->{'@attributes'}->IsUsingOurDNS != "true") {
            return false;
        }

        $records = [];
        foreach ($res as $r) {
            $r = $r->{'@attributes'};

            $records[$r->HostId] = [
                $r->Name == "@" ? "" : $r->Name,
                $r->Type,
                $r->Address,
                $r->TTL,
                $r->MXPref,
                0,
                0,
            ];
        }

        return $records;
    }

    public function recordTypes($admin = false)
    {
        return array("MX", "A", "AAAA", "CNAME");
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
        $records = $this->getZone()[$domain];
        $records[] = $record;
        return $this->setHosts($domain, $records);
    }

    public function editRecord($domain, $record, $new, $force = 0)
    {
        $records = $this->getZone()[$domain];
        $records[$record] = $new;
        return $this->setHosts($domain, $records);
    }

    public function removeRecord($domain, $record, $force = 0)
    {
        $records = $this->getZone()[$domain];
        unset($records[$record]);
        return $this->setHosts($domain, $records);
    }

    public function removeZone($domain)
    {
        // Not supported by API
        return false;
    }
}
