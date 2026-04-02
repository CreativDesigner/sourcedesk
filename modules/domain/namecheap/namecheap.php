<?php

class Namecheap extends DomainRegistrar
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

    private function client($class = "Domains")
    {
        if (!class_exists("NamecheapApi")) {
            require_once __DIR__ . "/lib/namecheap_api.php";
        }

        $api = new NamecheapApi($this->options->api_user, $this->options->api_password, false);
        $class = "Namecheap" . $class;
        return new $class($api);
    }

    public function availibilityStatus($domain)
    {
        $res = $this->client()->check(["DomainList" => $domain])->response()->DomainCheckResult;

        if ($res->{'@attributes'}->IsPremiumName == "true") {
            return null;
        }

        return $res->{'@attributes'}->Available == "true";
    }

    private function formatPhone($phone)
    {
        preg_match_all("/\d+/", $number, $result);
        $number = implode("", $result[0]);

        if (substr($number, 0, 1) == "0" && substr($number, 1, 1) != "0") {
            return "+49." . substr(ltrim($number, "0"), 0);
        }

        return "+" . substr(ltrim($number, "0"), 0, 2) . "." . substr(ltrim($number, "0"), 2);
    }

    public function registerDomain($domain, $owner, $admin, $tech, $zone, $ns, $privacy = false)
    {
        $idn = new IdnaConvert;

        foreach ($ns as $k => &$v) {
            $v = trim($v);
            if (empty($v)) {
                unset($ns[$k]);
            }
        }
        unset($v);

        $data = [
            "DomainName" => $domain,
            "IdnCode" => $idn->encode($domain),
            "Years" => "1",
            "Nameservers" => implode(",", $ns),
        ];

        if ($privacy) {
            $data["AddFreeWhoisguard"] = "yes";
            $data["WGEnabled"] = "yes";
        }

        $arr = [
            "Registrant" => "owner",
            "Admin" => "admin",
            "Tech" => "tech",
            "AuxBilling" => "zone",
        ];

        $ex = explode(".", $domain, 2);
        $tld = array_pop($ex);
        if ($tld == "de") {
            $data["DEConfirmAddress"] = "DE";
            $data["DEAgreeDelete"] = "Yes";
        }

        foreach ($arr as $f => $t) {
            $i = $$t;

            if (!empty($i[2])) {
                $data[$f . "OrganizationName"] = $i[2];
            }

            $data[$f . "FirstName"] = $i[0];
            $data[$f . "LastName"] = $i[1];
            $data[$f . "Address1"] = $i[3];
            $data[$f . "City"] = $i[6];
            $data[$f . "PostalCode"] = $i[5];
            $data[$f . "StateProvince"] = $i[4];
            $data[$f . "TechCountry"] = $i[4];
            $data[$f . "Phone"] = $this->formatPhone($i[7]);
            $data[$f . "Fax"] = $this->formatPhone($i[8]);
            $data[$f . "EmailAddress"] = $i[9];
        }

        return $this->client()->create($data)->response()->DomainCreateResult->Registered == "true";
    }

    public function transferDomain($domain, $owner, $admin, $tech, $zone, $authCode, $ns, $privacy = false)
    {
        $data = [
            "DomainName" => $domain,
            "Years" => "1",
            "EPPCode" => "base64:" . base64_encode($authCode),
        ];

        if ($privacy) {
            $data["AddFreeWhoisguard"] = "yes";
            $data["WGEnabled"] = "yes";
        }

        return $this->client("DomainsTransfer")->create($data)->response()->DomainTransferCreateResult->Transfer == "true";
    }

    public function changeContact($domain, $owner, $admin, $tech, $zone)
    {
        $data = [
            "DomainName" => $domain,
        ];

        $arr = [
            "Registrant" => "owner",
            "Admin" => "admin",
            "Tech" => "tech",
            "AuxBilling" => "zone",
        ];

        $ex = explode(".", $domain, 2);
        $tld = array_pop($ex);
        if ($tld == "de") {
            $data["DEConfirmAddress"] = "DE";
            $data["DEAgreeDelete"] = "Yes";
        }

        foreach ($arr as $f => $t) {
            $i = $$t;

            if (!empty($i[2])) {
                $data[$f . "OrganizationName"] = $i[2];
            }

            $data[$f . "FirstName"] = $i[0];
            $data[$f . "LastName"] = $i[1];
            $data[$f . "Address1"] = $i[3];
            $data[$f . "City"] = $i[6];
            $data[$f . "PostalCode"] = $i[5];
            $data[$f . "StateProvince"] = $i[4];
            $data[$f . "TechCountry"] = $i[4];
            $data[$f . "Phone"] = $this->formatPhone($i[7]);
            $data[$f . "Fax"] = $this->formatPhone($i[8]);
            $data[$f . "EmailAddress"] = $i[9];
        }

        return $this->client()->setContacts($data)->response()->DomainSetContactResult->IsSuccess == "true";
    }

    public function syncDomain($domain, $kkSync = false)
    {
        $info = $this->client()->getInfo(["DomainName" => $domain])->response()->DomainGetInfoResult;

        if ($info->{'@attributes'}->Status != "Ok") {
            if ($kkSync) {
                return ["status" => "waiting_kk"];
            }

            return false;
        }

        return array(
            "expiration" => date("Y-m-d", strtotime(strval($info->DomainDetails->ExpiredDate))),
            "status" => true,
            "privacy" => $info->Whoisguard->{'@attributes'}->Enabled == "true",
            "transfer_lock" => $this->client()->getRegistrarLock(["DomainName" => $domain])->response()->DomainGetRegistrarLockResult->{'@attributes'}->RegistrarLockStatus == "true",
        );
    }

    public function changeValues($domain, $status = false, $renew = true, $privacy = false)
    {
        if ($this->client()->setRegistrarLock([
            "DomainName" => $domain,
            "LockAction" => $status ? "LOCK" : "UNLOCK",
        ])->response()->DomainSetRegistrarLockResult->{'@attributes'}->IsSuccess != "true") {
            return false;
        }

        return true;
    }
}
