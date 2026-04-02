<?php

class NamedManager extends DNSProvider
{
    protected $short = "namedmanager";
    protected $name = "NamedManager";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "url" => array("type" => "text", "name" => $this->getLang("URL"), "placeholder" => "https://dns.sourceway.de/namedmanager"),
            "api_key" => array("type" => "password", "name" => $this->getLang("KEY"), "placeholder" => $this->getLang("KEYP")),
        );
    }

    public function addZone($domain, array $ns)
    {
        return false;
    }

    private function call()
    {
        $args = func_get_args();
        $method = array_shift($args);

        try {
            $soap = new SoapClient($this->options->url . "/api/namedmanager.wsdl");
            $soap->__setLocation($this->options->url . "/api/namedmanager.php");

            if (!$soap->authenticate("ADMIN_API", $this->options->api_key)) {
                die("Authentication failed");
            }

            return call_user_func_array([$soap, $method], $args);
        } catch (SoapFault $ex) {
            die($ex->getMessage());
        }
    }

    public function getZones()
    {
        $arr = [];
        $res = $this->call("fetch_domains");

        foreach ($res as $r) {
            $arr[$this->idd($r['domain_name'])] = 0;
        }

        return $arr;
    }

    public function pushToSlave($domain)
    {
        $domain = $this->idn($domain);

        $id = $this->getId($domain);
        if (!$id) {
            return false;
        }

        $res = $this->call("update_serial", $id);
        if (substr($res, 0, 4) != date("Y")) {
            return false;
        }

        return true;
    }

    private function getId($domain)
    {
        $res = $this->call("fetch_domains");
        foreach ($res as $r) {
            if ($r['domain_name'] == $domain) {
                $id = $r['id'];
                break;
            }
        }

        if (empty($id)) {
            return false;
        }

        return $id;
    }

    public function getZone($domain, $force = 0)
    {
        $domain = $this->idn($domain);

        $id = $this->getId($domain);
        if (!$id) {
            return false;
        }

        $res = $this->call("fetch_records", $id);
        if (!$res) {
            return [];
        }

        $records = [];
        foreach ($res as $r) {
            $records[$r['id_record']] = [
                $r['record_name'],
                $r['record_type'],
                $this->idd($r['record_content']),
                $r['record_ttl'],
                $r['record_prio'],
                0,
                0,
            ];
        }

        return $records;
    }

    public function recordTypes($admin = false)
    {
        return array("MX", "A", "AAAA", "CNAME", "SRV", "SPF", "TXT");
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

    public function addRecord($domain, $record, $hidden = 0, $admin = true, $update = false)
    {
        $domain = $this->idn($domain);

        $id = $this->getId($domain);
        if (!$id) {
            return false;
        }

        return (bool) $this->call("update_record", $id, $update ?: 0, $this->trimDomain($record[0], $domain) ?: "@", $record[1], $this->idn($record[2]), $record[3], $record[4]);
    }

    public function editRecord($domain, $record, $new, $force = 0)
    {
        return $this->addRecord($domain, $new, 0, true, $record);
    }

    public function removeRecord($domain, $record, $force = 0)
    {
        return false;
    }

    public function removeZone($domain)
    {
        return false;
    }
}
