<?php

class NetcupDomain extends DomainRegistrar
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

    private function makeHandle($contact) {
        $opt = [
            [
                "item" => "state",
                "value" => $contact[4],
            ]
        ];
        if ($contact[8]) {
            $opt[] = [
                "item" => "fax",
                "value" => $this->convertTelephone($contact[8]),
            ];
        }

        $res = $this->req([
            "action" => "createHandle",
            "param" => [
                "type" => $contact[2] ? "organisation" : "person",
                "name" => $contact[0] . " " . $contact[1],
                "organisation" => $contact[2],
                "street" => $contact[3],
                "postalcode" => $contact[5],
                "city" => $contact[6],
                "countrycode" => $contact[4],
                "telephone" => $this->convertTelephone($contact[7]),
                "email" => $contact[9],
                "optionalhandleattributes" => $opt,
            ],
        ]);

        if ($res['statuscode'] != 2000) {
            return false;
        }

        return $res['responsedata']['id'];
    }

    public function registerDomain($domain, $owner, $admin, $tech, $zone, $ns, $privacy = false)
    {
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $res = $this->makeHandle($$t);
            if (!$res) {
                return ucfirst($t) . " handle failed";
            }

            $$t = $res;
        }

        $nsArr = [];
        foreach ($ns as $n) {
            if (!empty($n)) {
                $nsArr[] = [
                    "hostname" => $n,
                    "ipv4" => null,
                    "ipv6" => null,
                ];
            }
        }

        $res = $this->req([
            "action" => "createDomain",
            "param" => [
                "domainname" => $domain,
                "contacts" => [
                    "ownerc" => $owner,
                    "adminc" => $admin,
                    "techc" => $tech,
                    "zonec" => $zone,
                    "billingc" => $tech,
                    "onsitec" => $tech,
                    "generalrequest" => $tech,
                    "abusecontact" => $tech,
                ],
                "nameservers" => $nsArr,
            ],
        ]);

        if ($res['statuscode'] != 2000) {
            return $res["longmessage"] ?? false;
        }

        return true;
    }

    public function transferDomain($domain, $owner, $admin, $tech, $zone, $authCode, $ns, $privacy = false)
    {
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $res = $this->makeHandle($$t);
            if (!$res) {
                return ucfirst($t) . " handle failed";
            }

            $$t = $res;
        }

        $nsArr = [];
        foreach ($ns as $n) {
            if (!empty($n)) {
                $nsArr[] = [
                    "hostname" => $n,
                    "ipv4" => null,
                    "ipv6" => null,
                ];
            }
        }

        $res = $this->req([
            "action" => "transferDomain",
            "param" => [
                "domainname" => $domain,
                "contacts" => [
                    "ownerc" => $owner,
                    "adminc" => $admin,
                    "techc" => $tech,
                    "zonec" => $zone,
                    "billingc" => $tech,
                    "onsitec" => $tech,
                    "generalrequest" => $tech,
                    "abusecontact" => $tech,
                ],
                "nameservers" => $nsArr,
                "authcode" => $authCode,
            ],
        ]);

        if ($res['statuscode'] != 2000) {
            return $res["longmessage"] ?? false;
        }

        return true;
    }

    public function deleteDomain($domain, $transit = 0)
    {
        $res = $this->req([
            "action" => "cancelDomain",
            "param" => [
                "domainname" => $domain,
            ],
        ]);

        return $res['statuscode'] == 2000;
    }

    public function getAuthCode($domain)
    {
        $res = $this->req([
            "action" => "getAuthcodeDomain",
            "param" => [
                "domainname" => $domain,
            ],
        ]);

        if ($res['statuscode'] != 2000) {
            return false;
        }
        
        return "AUTH:" . $res["responsedata"]["authcode"];
    }

    public function changeNameserver($domain, $ns)
    {
        $domDet = $this->syncDomain($domain);
        if (!$domDet) {
            return "Domain status invalid";
        }

        $nsArr = [];
        foreach ($ns as $n) {
            if (!empty($n)) {
                $nsArr[] = [
                    "hostname" => $n,
                    "ipv4" => null,
                    "ipv6" => null,
                ];
            }
        }
        
        $res = $this->req([
            "action" => "updateDomain",
            "param" => [
                "domainname" => $domain,
                "nameserver" => $nsArr,
                "contacts" => $domDet["contacts"],
            ],
        ]);

        if ($res['statuscode'] != 2000) {
            return $res["longmessage"] ?? false;
        }

        return true;
    }

    private function convertTelephone($number)
    {
        if (empty($number)) {
            return "";
        }

        preg_match_all("/\d+/", $number, $result);
        $number = implode("", $result[0]);

        if (substr($number, 0, 1) == "0" && substr($number, 1, 1) != "0") {
            return "+49." . ltrim($number, "0");
        }

        return "+" . substr(ltrim($number, "0"), 0, 2) . "." . substr(ltrim($number, "0"), 2);
    }

    public function changeContact($domain, $owner, $admin, $tech, $zone)
    {
        $domDet = $this->syncDomain($domain);
        if (!$domDet) {
            return "Domain status invalid";
        }
        
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $res = $this->makeHandle($$t);
            if (!$res) {
                return ucfirst($t) . " handle failed";
            }

            $domDet["contacts"][$t . "c"] = $res;
        }

        $nsArr = [];
        foreach ($domDet["ns"] as $n) {
            if (!empty($n)) {
                $nsArr[] = [
                    "hostname" => $n,
                    "ipv4" => null,
                    "ipv6" => null,
                ];
            }
        }
        
        $res = $this->req([
            "action" => "updateDomain",
            "param" => [
                "domainname" => $domain,
                "nameserver" => $nsArr,
                "contacts" => $domDet["contacts"],
            ],
        ]);

        if ($res['statuscode'] != 2000) {
            return $res["longmessage"] ?? false;
        }

        return true;
    }

    public function setRegLock($domain, $status = false, $error = false)
    {
        // Not supported by API.
        return true;
    }

    public function syncDomain($domain, $kkSync = false)
    {
        $res = $this->req([
            "action" => "infoDomain",
            "param" => [
                "domainname" => $domain,
            ],
        ]);

        if ($res['statuscode'] != 2000) {
            if ($kkSync) {
                return ["status" => "waiting_kk"];
            }

            return false;
        }

        $res = $res['responsedata'];
        
        $nsArr = [];
        foreach ($res["nameserverentry"] as $ns) {
            if (!empty($ns["hostname"])) {
                $nsArr[] = $ns["hostname"];
            }
        }

        return [
            "ns" => $nsArr,
            "expiration" => $res["nextbilling"],
            "auto_renew" => $res["cancellationrunning"] == "FALSE",
            "status" => true,
            "privacy" => false,
            "transfer_lock" => false,
            "contacts" => $res["assignedcontacts"],
        ];
    }

    public function changeValues($domain, $status = false, $renew = true, $privacy = false)
    {
        // Privacy and transfer lock not supported by API.
        
        if (!$renew) {
            return $this->deleteDomain($domain);
        }
    }
}
