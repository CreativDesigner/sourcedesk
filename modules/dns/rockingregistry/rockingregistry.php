<?php

class RockingRegistryDNS extends DNSProvider
{
    protected $short = "rockingregistry";
    protected $name = "RockingRegistry";
    protected $version = "1.0";
    protected $client;

    public function getSettings()
    {
        return array(
            "username" => array("type" => "text", "name" => $this->getLang("username")),
            "password" => array("type" => "password", "name" => $this->getLang("password")),
        );
    }

    private function client()
    {
        if ($this->client instanceof SoapClient) {
            return $this->client;
        }

        $params = [
            "login" => $this->options->username,
            "password" => $this->options->password,
        ];

        return $this->client = new SoapClient("https://soap.domain-bestellsystem.de/soap.wsdl", $params);
    }

    public function addZone($domain, array $ns)
    {
        global $CFG;

        try {
            $res = $this->client()->nameserverZoneCreate([
                "soaOrigin" => $domain,
                "soaExpire" => "604800",
                "soaMbox" => str_replace("@", ".", $CFG['PAGEMAIL']),
                "soaMinimum" => "86400",
                "soaRefresh" => "28800",
                "soaRetry" => "7200",
                "soaTtl" => "86400",
                "rr" => [],
                "clientTRID" => "",
            ]);

            return $res->returnCode == "1000";
        } catch (Exception $ex) {
            if ($ex->getMessage() == "Zone already exists") {
                return true;
            }

            return false;
        } catch (SoapException $ex) {
            if ($ex->getMessage() == "Zone already exists") {
                return true;
            }

            return false;
        }
    }

    public function getZones()
    {
        // Not supported by API
        return [];
    }

    protected function toArr($i)
    {
        return is_array($i) ? $i : [$i];
    }

    public function getZone($domain, $force = 0)
    {
        try {
            $res = $this->client()->nameserverZoneInfo([
                "soaOrigin" => $domain,
                "clientTRID" => "",
            ]);
        } catch (Exception $ex) {
            return false;
        }

        if ($res->returnCode != "1000") {
            return false;
        }

        $records = [];
        foreach ($this->toArr($res->rrList->item) as $r) {
            $sub = $this->trimDomain($r->name, $domain);
            if (!$force && $this->isDynDNS($sub . "." . $domain)) {
                continue;
            }

            if (!in_array(strtoupper($r->type), $this->recordTypes())) {
                continue;
            }

            $records[] = [
                $sub,
                $r->type,
                $r->data,
                $r->ttl,
                intval($r->aux),
                0,
                $this->isDynDNS($sub . "." . $domain),
            ];
        }

        return $records;
    }

    public function recordTypes($admin = false)
    {
        $a = array("MX", "A", "AAAA", "CNAME", "TXT");

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
        try {
            if ($record[1] == "NS" && empty($record[2])) {
                return false;
            }

            return $this->client()->nameserverRRCreate([
                "soaOrigin" => $domain,
                "rr" => [
                    [
                        "name" => $this->trimDomain($record[0], $domain),
                        "type" => $record[1],
                        "data" => $record[2],
                        "aux" => intval($record[4]),
                        "ttl" => intval($record[3]),
                    ],
                ],
                "clientTRID" => "",
            ])->returnCode == "1000";
        } catch (Exception $ex) {
            return false;
        } catch (SoapException $ex) {
            return false;
        }
    }

    public function editRecord($domain, $record, $new, $force = 0)
    {
        if (!$this->removeRecord($domain, $record)) {
            return false;
        }

        return $this->addRecord($domain, $new);
    }

    public function removeRecord($domain, $record, $force = 0)
    {
        $record = $this->getZone($domain)[$record];

        try {
            return $this->client()->nameserverRRDelete([
                "soaOrigin" => $domain,
                "rr" => [
                    "name" => $this->trimDomain($record[0], $domain),
                    "type" => $record[1],
                    "data" => $record[2],
                    "aux" => intval($record[4]),
                    "ttl" => intval($record[3]),
                ],
                "clientTRID" => "",
            ])->returnCode == "1000";
        } catch (Exception $ex) {
            return false;
        } catch (SoapException $ex) {
            return false;
        }
    }

    public function removeZone($domain)
    {
        try {
            return $this->client()->nameserverZoneDelete([
                "soaOrigin" => $domain,
                "clientTRID" => "",
            ])->returnCode == "1000";
        } catch (Exception $ex) {
            return false;
        } catch (SoapException $ex) {
            return false;
        }
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
