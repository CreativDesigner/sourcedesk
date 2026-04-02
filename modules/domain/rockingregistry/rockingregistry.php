<?php

class RockingRegistryReg extends DomainRegistrar
{
    protected $short = "rockingregistry";
    protected $name = "RockingRegistry";
    protected $version = "1.0";
    protected $client;
    public $delayDns = true;

    public function getSettings()
    {
        return array(
            "username" => array("type" => "text", "name" => $this->getLang("username")),
            "password" => array("type" => "password", "name" => $this->getLang("password")),
        );
    }

    public function getUserDefined()
    {
        return array(
            "for_reseller" => array("type" => "text", "name" => $this->getLang("reseller"), "placeholder" => $this->getLang("rid")),
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

    public function availibilityStatus($domain)
    {
        try {
            $result = $this->client()->domainCheck([
                "domainName" => $domain,
                "domainNameAce" => "",
                "clientTRID" => "",
                "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
            ]);
        } catch (Exception $ex) {
            return null;
        }

        if ($result->returnCode == "1000") {
            return $result->available == true;
        }

        return null;
    }

    private function convertTelephone($number)
    {
        if (empty(trim($number))) {
            return "";
        }

        preg_match_all("/\d+/", $number, $result);
        $number = implode("", $result[0]);

        if (substr($number, 0, 1) == "0" && substr($number, 1, 1) != "0") {
            return "+49 " . substr(ltrim($number, "0"), 0, 3) . " " . substr(ltrim($number, "0"), 3);
        }

        return "+" . substr(ltrim($number, "0"), 0, 2) . " " . substr(ltrim($number, "0"), 2, 3) . " " . substr(ltrim($number, "0"), 5);
    }

    public function registerDomain($domain, $owner, $admin, $tech, $zone, $ns, $privacy = false)
    {
        $arr = array("owner", "admin", "tech", "zone");

        try {
            foreach ($arr as $t) {
                $i = $$t;

                $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
                $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);

                try {
                    $$t = $this->client()->handleCreate([
                        "firstname" => $i[0],
                        "lastname" => $i[1],
                        "company" => $i[2],
                        "street" => $i[3],
                        "pcode" => $i[5],
                        "city" => $i[6],
                        "country" => $i[4],
                        "phone" => $this->convertTelephone($i[7]),
                        "fax" => $this->convertTelephone($i[8]),
                        "email" => $i[9],
                        "extension" => [
                            "vatId" => "",
                            "companyId" => "",
                            "personId" => "",
                            "trademarkId" => "",
                            "birthplace" => "",
                            "birthdate" => "",
                            "state" => "",
                            "atDiscloseVoice" => "hidden",
                            "atDiscloseFax" => "hidden",
                            "atDiscloseMail" => "hidden",
                            "idAuthority" => "",
                            "companyUrl" => "",
                            "companyType" => "",
                            "personJobTitle" => "",
                            "dunsId" => "",
                            "xxxId" => "",
                        ],
                        "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
                        "clientTRID" => "",
                    ]);
                } catch (Exception $ex) {
                    return "$t handle creation failed: " . $ex->getMessage();
                }

                if ($$t->returnCode != 1000) {
                    return "$t handle creation failed: " . $$t->returnMessage;
                }

                $$t = $$t->handleId;
            }

            $r = $this->client->domainCreate([
                "adminC" => $admin,
                "ownerC" => $owner,
                "techC" => $tech,
                "zoneC" => $zone,
                "nameserver" => $ns,
                "domainName" => $domain,
                "nsEntry" => [],
                "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
                "clientTRID" => "",
                "remarks" => "",
                "quoting" => "",
                "notify" => "",
                "extension" => "",
            ]);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }

        if ($r->returnCode != "1000" && $r->returnCode != "1001") {
            return "Domain registration failed: " . $r->returnMessage;
        }

        return true;
    }

    public function transferDomain($domain, $owner, $admin, $tech, $zone, $authCode, $ns, $privacy = false)
    {
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
            $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);

            try {
                $$t = $this->client()->handleCreate([
                    "firstname" => $i[0],
                    "lastname" => $i[1],
                    "company" => $i[2],
                    "street" => $i[3],
                    "pcode" => $i[5],
                    "city" => $i[6],
                    "country" => $i[4],
                    "phone" => $this->convertTelephone($i[7]),
                    "fax" => $this->convertTelephone($i[8]),
                    "email" => $i[9],
                    "extension" => [
                        "vatId" => "",
                        "companyId" => "",
                        "personId" => "",
                        "trademarkId" => "",
                        "birthplace" => "",
                        "birthdate" => "",
                        "state" => "",
                        "atDiscloseVoice" => "hidden",
                        "atDiscloseFax" => "hidden",
                        "atDiscloseMail" => "hidden",
                        "idAuthority" => "",
                        "companyUrl" => "",
                        "companyType" => "",
                        "personJobTitle" => "",
                        "dunsId" => "",
                        "xxxId" => "",
                    ],
                    "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
                    "clientTRID" => "",
                ]);
            } catch (Exception $ex) {
                return "$t handle creation failed: " . $ex->getMessage();
            }

            if ($$t->returnCode != 1000) {
                return "$t handle creation failed: " . $$t->returnMessage;
            }

            $$t = $$t->handleId;
        }

        try {
            $r = $this->client->domainTransfer([
                "adminC" => $admin,
                "ownerC" => $owner,
                "techC" => $tech,
                "zoneC" => $zone,
                "nameserver" => $ns,
                "domainName" => $domain,
                "authCode" => $authCode,
                "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
                "clientTRID" => "",
                "action" => "",
                "nackReason" => "",
                "extension" => "",
                "nsEntry" => "",
                "remarks" => "",
                "quoting" => "",
                "notify" => "",
            ]);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }

        if ($r->returnCode != "1000" && $r->returnCode != "1001") {
            return "Domain transfer failed: " . $r->returnMessage;
        }

        return true;
    }

    public function deleteDomain($domain, $transit = 0)
    {
        if ($transit === 0) {
            $r = $this->client()->domainDelete([
                "domainName" => $domain,
                "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
                "clientTRID" => "",
                "notify" => "NOCHANGE",
            ]);
        } else if ($transit === 1) {
            $r = $this->client()->domainTransit([
                "domainName" => $domain,
                "transitType" => "connected",
                "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
                "clientTRID" => "",
            ]);
        } else if ($transit === 2) {
            $r = $this->client()->domainTransit([
                "domainName" => $domain,
                "transitType" => "disconnected",
                "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
                "clientTRID" => "",
            ]);
        }

        if ($r->returnCode != "1000" && $r->returnCode != "1001") {
            return "Domain deletion failed: " . $r->returnMessage;
        }

        return true;
    }

    public function getAuthCode($domain)
    {
        $r = $this->client()->domainSetAuthCode([
            "domainName" => $domain,
            "deLiveTime" => "30",
            "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
            "clientTRID" => "",
        ]);

        if ($r->returnCode != "1000") {
            return "AuthCode creation failed: " . $r->returnMessage;
        }

        if (empty($r->authCode)) {
            return "Fetching AuthCode failed";
        }

        return "AUTH:" . $r->authCode;
    }

    public function changeNameserver($domain, $ns)
    {
        $r = $this->client()->domainUpdate([
            "domainName" => $domain,
            "nameserver" => $ns,
            "adminC" => "NOCHANGE",
            "ownerC" => "NOCHANGE",
            "techC" => "NOCHANGE",
            "zoneC" => "NOCHANGE",
            "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
            "clientTRID" => "",
            "remarks" => "",
            "quoting" => "",
            "notify" => "NOCHANGE",
            "nsEntry" => [],
        ]);

        if ($r->returnCode != "1000" && $r->returnCode != "1001") {
            return "Nameserver changing failed: " . $r->returnMessage;
        }

        return true;
    }

    public function changeContact($domain, $owner, $admin, $tech, $zone, $trade = false)
    {
        $arr = array("admin", "tech", "zone");
        if (!$trade) {
            $arr[] = "owner";
        }

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
            $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);

            $$t = $this->client()->handleCreate([
                "firstname" => $i[0],
                "lastname" => $i[1],
                "company" => $i[2],
                "street" => $i[3],
                "pcode" => $i[5],
                "city" => $i[6],
                "country" => $i[4],
                "phone" => $this->convertTelephone($i[7]),
                "fax" => $this->convertTelephone($i[8]),
                "email" => $i[9],
                "extension" => [
                    "vatId" => "",
                    "companyId" => "",
                    "personId" => "",
                    "trademarkId" => "",
                    "birthplace" => "",
                    "birthdate" => "",
                    "state" => "",
                    "atDiscloseVoice" => "hidden",
                    "atDiscloseFax" => "hidden",
                    "atDiscloseMail" => "hidden",
                    "idAuthority" => "",
                    "companyUrl" => "",
                    "companyType" => "",
                    "personJobTitle" => "",
                    "dunsId" => "",
                    "xxxId" => "",
                ],
                "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
                "clientTRID" => "",
            ]);

            if ($$t->returnCode != 1000) {
                return "$t handle creation failed: " . $$t->returnMessage;
            }

            $$t = $$t->handleId;
        }

        $r = $this->client()->domainUpdate([
            "domainName" => $domain,
            "adminC" => $admin,
            "ownerC" => $trade ? "NOCHANGE" : $owner,
            "techC" => $tech,
            "zoneC" => $zone,
            "nameserver" => ["NOCHANGE"],
            "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
            "clientTRID" => "",
            "remarks" => "",
            "quoting" => "",
            "notify" => "NOCHANGE",
            "nsEntry" => [],
        ]);

        if ($r->returnCode != "1000" && $r->returnCode != "1001") {
            return "Contact changing failed: " . $r->returnMessage;
        }

        return true;
    }

    public function trade($domain, $owner, $admin, $tech, $zone)
    {
        $arr = array("owner");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
            $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);

            $$t = $this->client()->handleCreate([
                "firstname" => $i[0],
                "lastname" => $i[1],
                "company" => $i[2],
                "street" => $i[3],
                "pcode" => $i[5],
                "city" => $i[6],
                "country" => $i[4],
                "phone" => $this->convertTelephone($i[7]),
                "fax" => $this->convertTelephone($i[8]),
                "email" => $i[9],
                "extension" => [
                    "vatId" => "",
                    "companyId" => "",
                    "personId" => "",
                    "trademarkId" => "",
                    "birthplace" => "",
                    "birthdate" => "",
                    "state" => "",
                    "atDiscloseVoice" => "hidden",
                    "atDiscloseFax" => "hidden",
                    "atDiscloseMail" => "hidden",
                    "idAuthority" => "",
                    "companyUrl" => "",
                    "companyType" => "",
                    "personJobTitle" => "",
                    "dunsId" => "",
                    "xxxId" => "",
                ],
                "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
                "clientTRID" => "",
            ]);

            if ($$t->returnCode != 1000) {
                return "$t handle creation failed: " . $$t->returnMessage;
            }

            $$t = $$t->handleId;
        }

        $r = $this->client()->domainTrade([
            "domainName" => $domain,
            "ownerC" => $owner,
            "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
            "clientTRID" => "",
        ]);

        if ($r->returnCode != "1000" && $r->returnCode != "1001") {
            return "Domain trade failed: " . $r->returnMessage;
        }

        return $this->changeContact($domain, $owner, $admin, $tech, $zone, true);
    }

    public function syncDomain($domain, $kkSync = false)
    {
        try {
            $r = $this->client()->domainInfo([
                "domainName" => $domain,
                "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
                "clientTRID" => "",
            ]);
        } catch (Exception $ex) {
            return $ex->getMessage() == "No such domain" ? false : null;
        } catch (SoapFault $ex) {
            return $ex->getMessage() == "No such domain" ? false : null;
        }

        if ($r->returnCode != "1000" && $r->returnCode != "1001") {
            return false;
        }

        $r = $r->domainsList->item;

        if (is_array($r)) {
            foreach ($r as $item) {
                if (in_array($item->domainStatus, [
                    "scheduledReg",
                    "scheduledTransfer",
                    "pendingReg",
                    "pendingTransfer",
                    "active",
                    "unknown",
                ])) {
                    $r = $item;
                    break;
                }
            }

            if (is_array($r)) {
                $r = array_shift($r);
            }
        }

        if ($kkSync && in_array($r->domainStatus, [
            "scheduledReg",
            "scheduledTransfer",
            "pendingReg",
            "pendingTransfer",
        ])) {
            return ["status" => "waiting_kk"];
        }

        if (!in_array($r->domainStatus, [
            "active",
            "unknown",
        ])) {
            return false;
        }

        if (!is_numeric($r->systemInDate)) {
            $r->systemInDate = strtotime($r->systemInDate);
        }

        while (date("Y-m-d", $r->systemInDate) <= date("Y-m-d")) {
            $r->systemInDate = strtotime("+1 year", $r->systemInDate);
        }

        $info = [
            "auto_renew" => empty($r->executionDate),
            "expiration" => date("Y-m-d", $r->systemInDate),
            "status" => in_array($r->domainStatus, [
                "active",
                "unknown",
            ]),
            "transfer_lock" => in_array("transferLock", (array) $r->domainSubStatus),
            "privacy" => in_array("ownerCWPP", (array) $r->domainSubStatus),
        ];

        $ns = [$r->primaryNameserver];

        if (is_array($r->secondaryNameserver->item)) {
            foreach ($r->secondaryNameserver->item as $dns) {
                if (!empty($dns)) {
                    array_push($ns, $dns);
                }
            }
        }

        if (count($ns) >= 2) {
            $info['ns'] = $ns;
        }

        foreach ([
            "adminC" => "admin",
            "ownerC" => "owner",
            "techC" => "tech",
            "zoneC" => "zone",
        ] as $dbs => $sd) {
            if (empty($r->$dbs)) {
                continue;
            }

            try {
                $r2 = $this->client()->handleInfo([
                    "handle" => $r->$dbs,
                    "clientTRID" => "",
                ]);

                if ($r2->returnCode != "1000") {
                    continue;
                }

                $info[$sd] = [
                    $r2->firstname,
                    $r2->lastname,
                    $r2->company,
                    $r2->street,
                    $r2->country,
                    $r2->pcode,
                    $r2->city,
                    str_replace("-", "", implode(".", explode(" ", $r2->phone, 2))),
                    str_replace("-", "", implode(".", explode(" ", $r2->fax, 2))),
                    $r2->email,
                    "",
                ];
            } catch (Exception $ex) {
                continue;
            } catch (SoapFault $ex) {
                continue;
            }
        }

        return $info;
    }

    public function setRegLock($domain, $status = false, $error = false)
    {
        $field = $status ? "addStatusFlag" : "removeStatusFlag";

        $r = $this->client()->domainUpdate([
            "domainName" => $domain,
            "nameserver" => ["NOCHANGE"],
            "adminC" => "NOCHANGE",
            "ownerC" => "NOCHANGE",
            "techC" => "NOCHANGE",
            "zoneC" => "NOCHANGE",
            $field => ["LOCK"],
            "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
            "clientTRID" => "",
            "remarks" => "",
            "quoting" => "",
            "notify" => "NOCHANGE",
            "nsEntry" => [],
        ]);

        if ($r->returnCode != "1000" && $r->returnCode != "1001") {
            return "Transfer lock changing failed: " . $r->returnMessage;
        }

        return true;
    }

    public function changeValues($domain, $status = false, $renew = true, $privacy = false)
    {
        $info = $this->syncDomain($domain);
        if (!is_array($info) || empty($info["expiration"])) {
            return is_string($info) ? $info : "Fetching domain info failed";
        }

        $field = $status ? "addStatusFlag" : "removeStatusFlag";

        $r = $this->client()->domainUpdate([
            "domainName" => $domain,
            "nameserver" => ["NOCHANGE"],
            "adminC" => "NOCHANGE",
            "ownerC" => "NOCHANGE",
            "techC" => "NOCHANGE",
            "zoneC" => "NOCHANGE",
            $field => ["LOCK"],
            "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
            "clientTRID" => "",
            "remarks" => "",
            "quoting" => "",
            "notify" => "NOCHANGE",
            "nsEntry" => [],
        ]);

        if ($r->returnCode != "1000" && $r->returnCode != "1001") {
            return "Transfer lock changing failed: " . $r->returnMessage;
        }

        $isRenew = $info["auto_renew"] ?? null;

        if ($renew) {
            if (!$isRenew) {
                $r = $this->client()->domainDeleteCancel([
                    "domainName" => $domain,
                    "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
                    "clientTRID" => "",
                ]);
            }
        } else {
            if ($isRenew) {
                $r = $this->client()->domainDelete([
                    "domainName" => $domain,
                    "startDate" => date("d.m.Y", strtotime($info["expiration"])),
                    "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
                    "clientTRID" => "",
                    "notify" => "NOCHANGE",
                ]);
            }
        }

        $field = $privacy ? "addStatusFlag" : "removeStatusFlag";

        $r = $this->client()->domainUpdate([
            "domainName" => $domain,
            "nameserver" => ["NOCHANGE"],
            "adminC" => "NOCHANGE",
            "ownerC" => "NOCHANGE",
            "techC" => "NOCHANGE",
            "zoneC" => "NOCHANGE",
            $field => ["adminCWPP", "ownerCWPP", "techCWPP", "zoneCWPP"],
            "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
            "clientTRID" => "",
            "remarks" => "",
            "quoting" => "",
            "notify" => "NOCHANGE",
            "nsEntry" => [],
        ]);

        if ($r->returnCode != "1000" && $r->returnCode != "1001") {
            return "WHOIS privacy changing failed: " . $r->returnMessage;
        }

        return true;
    }

    public function requestAuthTwo($domain)
    {
        return $this->client()->domainRequestAuthInfo2([
            "domainName" => $domain,
            "forReseller" => !empty($this->udd["for_reseller"]) ? $this->udd["for_reseller"] : "",
            "clientTRID" => "",
        ])->returnCode == "1000";
    }
}
