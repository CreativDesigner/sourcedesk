<?php

class NetcupDNS extends DNSProvider
{
    protected $short = "netcup";
    protected $name = "netcup";
    protected $version = "1.0";
    protected $ch = null;
    protected $sid = null;

    public function getSettings()
    {
        return array(
            "customernr" => array("type" => "text", "name" => $this->getLang("customernr")),
            "apikey" => array("type" => "password", "name" => $this->getLang("apikey")),
            "apipassword" => array("type" => "password", "name" => $this->getLang("apipassword")),
        );
    }

    public function __destruct() {
        if ($this->ch) {
            $this->req([
                "action" => "logout",
                "param" => [],
            ]);

            curl_close($this->ch);
        }
    }

    private function req($data)
    {
        if (!$this->ch) {
            $this->ch = curl_init("https://ccp.netcup.net/run/webservice/servers/endpoint.php?JSON");
        
            curl_setopt_array($this->ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    "action" => "login",
                    "param" => [
                        "customernumber" => $this->options->customernr,
                        "apikey" => $this->options->apikey,
                        "apipassword" => $this->options->apipassword,
                    ],
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            ]);

            $res = curl_exec($this->ch);
            $res = @json_decode($res, true);

            if ($res["statuscode"] != 2000) {
                return false;
            }

            $this->sid = $res["responsedata"]["apisessionid"];
        }

        $data["param"]["customernumber"] = $this->options->customernr;
        $data["param"]["apikey"] = $this->options->apikey;
        $data["param"]["apisessionid"] = $this->sid;

        curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($data));
        $res = curl_exec($this->ch);

        return @json_decode($res, true);
    }

    public function addZone($domain, array $ns)
    {
        return false;
    }

    public function getZones()
    {
        return [];
    }

    private function rawZone($domain) {
        $res = $this->req([
            "action" => "infoDnsRecords",
            "param" => [
                "domainname" => $domain,
            ],
        ]);

        if ($res["statuscode"] != 2000) {
            return false;
        }

        return $res["responsedata"]["dnsrecords"];
    }

    public function getZone($domain, $force = 0)
    {
        $res = $this->rawZone($domain);
        if ($res === false) {
            return false;
        }

        $records = [];
        foreach ($res as $r) {
            if ($r["hostname"] == "@") {
                $r["hostname"] = "";
            }

            if (!$force && $this->isDynDNS($r["hostname"] . "." . $domain)) {
                continue;
            }

            $records[$r['id']] = [
                $r['hostname'],
                $r['type'],
                $r['destination'],
                0,
                $r['priority'],
                0,
                $this->isDynDNS($r["hostname"] . "." . $domain) ? 1 : 0,
            ];
        }

        return $records;
    }

    public function recordTypes($admin = false)
    {
        return array("MX", "A", "AAAA", "CNAME", "TXT");
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
        $records = [
            [
                "id" => "",
                "hostname" => $this->trimDomain($record[0], $domain),
                "type" => $record[1],
                "priority" => $record[4],
                "destination" => $record[2],
                "deleterecord" => false,
            ],
        ];

        $res = $this->req([
            "action" => "updateDnsRecords",
            "param" => [
                "domainname" => $domain,
                "dnsrecordset" => [
                    "dnsrecords" => $records,
                ],
            ],
        ]);

        return $res["statuscode"] == 2000;
    }

    public function editRecord($domain, $record, $new, $force = 0)
    {
        $record = [
            "id" => $record,
            "hostname" => $this->trimDomain($new[0], $domain),
            "type" => $new[1],
            "priority" => $new[4],
            "destination" => $new[2],
            "deleterecord" => false,
        ];

        $res = $this->req([
            "action" => "updateDnsRecords",
            "param" => [
                "domainname" => $domain,
                "dnsrecordset" => [
                    "dnsrecords" => [$record],
                ],
            ],
        ]);

        return $res["statuscode"] == 2000;
    }

    public function removeRecord($domain, $record, $force = 0)
    {
        $records = $this->rawZone($domain);

        if ($records === false) {
            return false;
        }

        $newRecords = [];

        foreach ($records as $v) {
            if ($v["id"] == $record) {
                unset($v["state"]);
                $v["deleterecord"] = true;
                $newRecords[] = $v;
            }
        }
        unset($v);

        $res = $this->req([
            "action" => "updateDnsRecords",
            "param" => [
                "domainname" => $domain,
                "dnsrecordset" => [
                    "dnsrecords" => $newRecords,
                ],
            ],
        ]);

        return $res["statuscode"] == 2000;
    }

    public function removeZone($domain)
    {
        return false;
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
