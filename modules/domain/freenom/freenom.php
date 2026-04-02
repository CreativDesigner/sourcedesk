<?php

class Freenom extends DomainRegistrar
{
    protected $short = "freenom";
    protected $name = "Freenom";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "api_mail" => array("type" => "text", "name" => $this->getLang("mail")),
            "api_password" => array("type" => "password", "name" => $this->getLang("password")),
        );
    }

    public function availibilityStatus($domain)
    {
        $url = "https://api.freenom.com/v2/domain/search?domainname=" . urlencode($domain) . "&domaintype=FREE";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        curl_close($ch);
        if (!$res) {
            return null;
        }

        $res = json_decode($res);
        if (!$res || !isset($res->domain[0]->status)) {
            return null;
        }

        return $res->domain[0]->status == "AVAILABLE";
    }

    public function registerDomain($domain, $owner, $admin, $tech, $zone, $ns, $privacy = false)
    {
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
            $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);
            $i[2] = str_replace(array("&"), " ", $i[2]);

            $params = array(
                "contact_organization" => $i[2],
                "contact_firstname" => $i[0],
                "contact_lastname" => $i[1],
                "contact_address" => str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]),
                "contact_city" => $i[6],
                "contact_zipcode" => $i[5],
                "contact_statecode" => "",
                "contact_countrycode" => $i[4],
                "contact_phone" => str_replace(".", "-", $i[7]),
                "contact_fax" => str_replace(".", "-", $i[8]),
                "contact_email" => $i[9],
                "email" => $this->options->api_mail,
                "password" => $this->options->api_password,
            );

            $ch = curl_init("https://api.freenom.com/v2/contact/register");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            $res = curl_exec($ch);
            curl_close($ch);

            if (!$res) {
                return "Creation of $t contact failed";
            }

            $res = json_decode($res);
            if (isset($res->error)) {
                return $res->error;
            }

            if (!$res || !isset($res->contact[0]->contact_id)) {
                return "Decoding of $t contact failed";
            }

            $$t = $res->contact[0]->contact_id;
        }

        $params = array(
            "domainname" => $domain,
            "period" => "1Y",
            "owner_id" => $owner,
            "billing_id" => $admin,
            "tech_id" => $tech,
            "admin_id" => $zone,
            "email" => $this->options->api_mail,
            "password" => $this->options->api_password,
            "domaintype" => "FREE",
            "idshield" => $privacy ? "enabled" : "disabled",
            "autorenew" => "enabled",
        );

        $ch = curl_init("https://api.freenom.com/v2/domain/register");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params) . "&nameserver=" . implode("&nameserver=", $ns));
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) {
            return "Creation of domain failed";
        }

        $res = json_decode($res);
        if (isset($res->error)) {
            return $res->error;
        }

        if (!$res || !isset($res->domain[0]->status) || $res->domain[0]->status != "REGISTERED") {
            return "Decoding of domain failed";
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
            $i[2] = str_replace(array("&"), " ", $i[2]);

            $params = array(
                "contact_organization" => $i[2],
                "contact_firstname" => $i[0],
                "contact_lastname" => $i[1],
                "contact_address" => str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]),
                "contact_city" => $i[6],
                "contact_zipcode" => $i[5],
                "contact_statecode" => "",
                "contact_countrycode" => $i[4],
                "contact_phone" => str_replace(".", "-", $i[7]),
                "contact_fax" => str_replace(".", "-", $i[8]),
                "contact_email" => $i[9],
                "email" => $this->options->api_mail,
                "password" => $this->options->api_password,
            );

            $ch = curl_init("https://api.freenom.com/v2/contact/register");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            $res = curl_exec($ch);
            curl_close($ch);

            if (!$res) {
                return "Creation of $t contact failed";
            }

            $res = json_decode($res);
            if (isset($res->error)) {
                return $res->error;
            }

            if (!$res || !isset($res->contact[0]->contact_id)) {
                return "Decoding of $t contact failed";
            }

            $$t = $res->contact[0]->contact_id;
        }

        $params = array(
            "domainname" => $domain,
            "period" => "1Y",
            "owner_id" => $owner,
            "billing_id" => $admin,
            "tech_id" => $tech,
            "admin_id" => $zone,
            "email" => $this->options->api_mail,
            "password" => $this->options->api_password,
            "domaintype" => "FREE",
            "idshield" => $privacy ? "enabled" : "disabled",
            "autorenew" => "enabled",
            "authcode" => $authCode,
        );

        $ch = curl_init("https://api.freenom.com/v2/domain/transfer/request");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params) . "&nameserver=" . implode("&nameserver=", $ns));
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) {
            return "Transfer of domain failed";
        }

        $res = json_decode($res);
        if (isset($res->error)) {
            return $res->error;
        }

        if (!$res || !isset($res->transfer[0]->status) || $res->transfer[0]->status != "REQUESTED") {
            return "Decoding of transfer failed";
        }

        return true;
    }

    public function deleteDomain($domain, $transit = 0)
    {
        $params = array(
            "domainname" => $domain,
            "email" => $this->options->api_mail,
            "password" => $this->options->api_password,
        );

        $ch = curl_init("https://api.freenom.com/v2/domain/delete");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) {
            return "Deletion of domain failed";
        }

        $res = json_decode($res);
        if (isset($res->error)) {
            return $res->error;
        }

        if (!$res || !isset($res->domain[0]->status) || $res->domain[0]->status != "DELETED") {
            return "Decoding of deletion failed";
        }

        return true;
    }

    public function getAuthCode($domain)
    {
        $params = array(
            "domainname" => $domain,
            "email" => $this->options->api_mail,
            "password" => $this->options->api_password,
        );

        $ch = curl_init("https://api.freenom.com/v2/domain/getinfo?" . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) {
            return "Getting domain failed";
        }

        $res = json_decode($res);
        if (isset($res->error)) {
            return $res->error;
        }

        if (!$res || !isset($res->domain[0]->authcode)) {
            return "Decoding of domain info failed";
        }

        return "AUTH:" . $res->domain[0]->authcode;
    }

    public function changeNameserver($domain, $ns)
    {
        $params = array(
            "domainname" => $domain,
            "email" => $this->options->api_mail,
            "password" => $this->options->api_password,
        );

        $ch = curl_init("https://api.freenom.com/v2/domain/modify");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params) . "&nameserver=" . implode("&nameserver=", $ns));
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) {
            return "Modification of domain failed";
        }

        $res = json_decode($res);
        if (isset($res->error)) {
            return $res->error;
        }

        if (!$res || !isset($res->domain[0]->status) || $res->domain[0]->status != "MODIFIED") {
            return "Decoding of modification failed";
        }

        return true;
    }

    public function changeContact($domain, $owner, $admin, $tech, $zone)
    {
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
            $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);
            $i[2] = str_replace(array("&"), " ", $i[2]);

            $params = array(
                "contact_organization" => $i[2],
                "contact_firstname" => $i[0],
                "contact_lastname" => $i[1],
                "contact_address" => str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]),
                "contact_city" => $i[6],
                "contact_zipcode" => $i[5],
                "contact_statecode" => "",
                "contact_countrycode" => $i[4],
                "contact_phone" => str_replace(".", "-", $i[7]),
                "contact_fax" => str_replace(".", "-", $i[8]),
                "contact_email" => $i[9],
                "email" => $this->options->api_mail,
                "password" => $this->options->api_password,
            );

            $ch = curl_init("https://api.freenom.com/v2/contact/register");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            $res = curl_exec($ch);
            curl_close($ch);

            if (!$res) {
                return "Creation of $t contact failed";
            }

            $res = json_decode($res);
            if (isset($res->error)) {
                return $res->error;
            }

            if (!$res || !isset($res->contact[0]->contact_id)) {
                return "Decoding of $t contact failed";
            }

            $$t = $res->contact[0]->contact_id;
        }

        $params = array(
            "domainname" => $domain,
            "owner_id" => $owner,
            "billing_id" => $admin,
            "tech_id" => $tech,
            "admin_id" => $zone,
            "email" => $this->options->api_mail,
            "password" => $this->options->api_password,
        );

        $ch = curl_init("https://api.freenom.com/v2/domain/modify");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) {
            return "Modification of domain failed";
        }

        $res = json_decode($res);
        if (isset($res->error)) {
            return $res->error;
        }

        if (!$res || !isset($res->domain[0]->status) || $res->domain[0]->status != "MODIFIED") {
            return "Decoding of modification failed";
        }

        return true;
    }

    public function setRenew($domain, $renew = true)
    {
        $params = array(
            "domainname" => $domain,
            "autorenew" => $renew ? "enabled" : "disabled",
            "email" => $this->options->api_mail,
            "password" => $this->options->api_password,
        );

        $ch = curl_init("https://api.freenom.com/v2/domain/modify");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) {
            return "Modification of domain failed";
        }

        $res = json_decode($res);
        if (isset($res->error)) {
            return $res->error;
        }

        if (!$res || !isset($res->domain[0]->status) || $res->domain[0]->status != "MODIFIED") {
            return "Decoding of modification failed";
        }

        return true;
    }

    public function changeValues($domain, $status = false, $renew = true, $privacy = false)
    {
        $params = array(
            "domainname" => $domain,
            "autorenew" => $renew ? "enabled" : "disabled",
            "idshield" => $privacy ? "enabled" : "disabled",
            "email" => $this->options->api_mail,
            "password" => $this->options->api_password,
        );

        $ch = curl_init("https://api.freenom.com/v2/domain/modify");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) {
            return "Modification of domain failed";
        }

        $res = json_decode($res);
        if (isset($res->error)) {
            return $res->error;
        }

        if (!$res || !isset($res->domain[0]->status) || $res->domain[0]->status != "MODIFIED") {
            return "Decoding of modification failed";
        }

        return true;
    }

    public function syncDomain($domain, $kkSync = false)
    {
        $params = array(
            "domainname" => $domain,
            "email" => $this->options->api_mail,
            "password" => $this->options->api_password,
        );

        $ch = curl_init("https://api.freenom.com/v2/domain/getinfo?" . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) {
            if ($kkSync) {
                return ["status" => "waiting_kk"];
            }

            return "Getting domain failed";
        }

        $res = json_decode($res);
        if (isset($res->error)) {
            if ($kkSync) {
                return ["status" => "waiting_kk"];
            }

            return $res->error;
        }
        if (!$res || !isset($res->domain[0]->expirationdate)) {
            if ($kkSync) {
                return ["status" => "waiting_kk"];
            }

            return false;
        }

        $info = array(
            "auto_renew" => $res->domain[0]->autorenew == "enabled",
            "expiration" => substr($res->domain[0]->expirationdate, 0, 4) . "-" . substr($res->domain[0]->expirationdate, 4, 2) . "-" . substr($res->domain[0]->expirationdate, 6, 2),
            "status" => $res->domain[0]->status == "ACTIVE",
            "transfer_lock" => false,
        );

        $ns = [];

        if (is_array($res->domain[0]->nameserver)) {
            foreach ($res->domain[0]->nameserver as $dns) {
                if (!empty($dns->hostname)) {
                    array_push($ns, $dns->hostname);
                }
            }
        }

        if (count($ns) >= 2) {
            $info['ns'] = $ns;
        }

        foreach ([
            "owner_contact" => "owner",
            "admin_contact" => "admin",
            "tech_contact" => "tech",
            "billing_contact" => "zone",
        ] as $fn => $sd) {
            $hi = $res->domain[0]->$fn ?? "";
            if (!$hi) {
                continue;
            }

            $info[$sd] = [
                $hi->contact_firstname,
                $hi->contact_lastname,
                $hi->contact_organization,
                $hi->contact_address,
                $hi->contact_countrycode,
                $hi->contact_zipcode,
                $hi->contact_city,
                str_replace("-", "", implode(".", explode("-", $hi->contact_phone, 2))),
                str_replace("-", "", implode(".", explode("-", $hi->contact_fax, 2))),
                $hi->contact_email,
                "",
            ];
        }

        return $info;
    }

    public function getPrivacyRules()
    {
        return "IDShield.pdf";
    }
}
