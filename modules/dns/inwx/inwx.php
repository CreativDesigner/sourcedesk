<?php

class INWXDNS extends DNSProvider
{
    protected $short = "inwx";
    protected $name = "INWX";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "api_user" => array("type" => "text", "name" => $this->getLang("username")),
            "api_password" => array("type" => "password", "name" => $this->getLang("password")),
        );
    }

    private function client()
    {
        require_once __DIR__ . "/domrobot.class.php";
        $domrobot = new domrobot("https://api.domrobot.com/xmlrpc/");
        $domrobot->setDebug(false);
        $domrobot->setLanguage('en');
        $res = $domrobot->login($this->options->api_user, $this->options->api_password);

        return $res['code'] == "1000" ? $domrobot : false;
    }

    public function addZone($domain, array $ns)
    {
        global $CFG;

        if (!($c = $this->client())) {
            return false;
        }

        foreach ($ns as $k => $v) {
            if (empty($v)) {
                unset($ns[$k]);
            }
        }
        $ns = array_values($ns);

        $res = $c->call("nameserver", "create", [
            "domain" => $domain,
            "type" => "MASTER",
            "ns" => $ns,
            "soaEmail" => $CFG['PAGEMAIL'],
        ]);

        if ($res['code'] != "1000") {
            return false;
        }

        return true;
    }

    public function getZones()
    {
        if (!($c = $this->client())) {
            return false;
        }

        $res = $c->call("nameserver", "list");
        if ($res['code'] != '1000') {
            return false;
        }

        $arr = [];

        foreach ($res['resData']['domains'] as $d) {
            $arr[$d['domain']] = 0;
        }

        return $arr;
    }

    public function getZone($domain, $force = 0)
    {
        if (!($c = $this->client())) {
            return false;
        }

        $res = $c->call("nameserver", "info", [
            "domain" => $domain,
        ]);

        if ($res['code'] != "1000") {
            return false;
        }

        $records = [];
        foreach ($res['resData']['record'] as $r) {
            $sub = $this->trimDomain($r['name'], $domain);
            if (!$force && $this->isDynDNS($sub . "." . $domain)) {
                continue;
            }

            $records[$r['id']] = [
                $sub,
                $r['type'],
                $r['content'],
                $r['ttl'],
                $r['prio'],
                0,
                $this->isDynDNS($sub . "." . $domain) ? 1 : 0,
            ];
        }

        return $records;
    }

    public function recordTypes($admin = false)
    {
        $a = array("MX", "A", "AAAA", "CAA", "CNAME", "SRV", "TXT");

        if ($admin) {
            array_push($a, "SOA", "NS");
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
        if (!($c = $this->client())) {
            return false;
        }

        $res = $c->call("nameserver", "createRecord", [
            "domain" => $domain,
            "type" => $record[1],
            "content" => $record[2],
            "name" => $this->trimDomain($record[0], $domain),
            "ttl" => $record[3],
            "prio" => $record[4],
        ]);

        return $res['code'] == "1000";
    }

    public function editRecord($domain, $record, $new, $force = 0)
    {
        if (!($c = $this->client())) {
            return false;
        }

        $res = $c->call("nameserver", "updateRecord", [
            "id" => $record,
            "type" => $new[1],
            "content" => $new[2],
            "name" => $this->trimDomain($new[0], $domain),
            "ttl" => $new[3],
            "prio" => $new[4],
        ]);

        return $res['code'] == "1000";
    }

    public function removeRecord($domain, $record, $force = 0)
    {
        if (!($c = $this->client())) {
            return false;
        }

        return $c->call("nameserver", "deleteRecord", [
            "id" => $record,
        ])['code'] == "1000";
    }

    public function removeZone($domain)
    {
        if (!($c = $this->client())) {
            return false;
        }

        return $c->call("nameserver", "delete", [
            "domain" => $domain,
        ])['code'] == "1000";
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
                if ($r[4]) {
                    $this->removeRecord($domain, $r[4], true);
                }

                if ($r[5]) {
                    $this->removeRecord($domain, $r[5], true);
                }

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
                    $r = [$sub, "A", $ip, "180", "0"];
                    $this->editRecord($domain, $r[4], $r, true);
                }

                if ($ip6) {
                    $r = [$sub, "AAAA", $ip6, "180", "0"];

                    if ($r[5]) {
                        $this->editRecord($domain, $r[5], $r, true);
                    } else {
                        $this->addRecord($domain, $r);
                    }
                } else if ($r[5]) {
                    $this->removeRecord($domain, $r[5]);
                }

                return true;
            }
        }

        return false;
    }
}
