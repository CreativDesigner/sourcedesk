<?php

class EmailRegistrar extends DomainRegistrar
{
    protected $short = "email";
    protected $name = "E-Mail";
    protected $version = "1.1";

    public function getSettings()
    {
        return array(
            "email" => array("type" => "text", "name" => $this->getLang("email"), "placeholder" => $this->getLang("emailp")),
        );
    }

    protected function send($domain, $action, $data) {
        global $maq, $CFG;

        if (!empty($this->options->email) && filter_var($this->options->email, FILTER_VALIDATE_EMAIL)) {
            $mail = "";
            foreach ($data as $k => $v) {
                $mail .= "# $k\n\n" . print_r($v, true) . "\n\n";
            }

            $maq->enqueue([], null, $this->options->email, $action . " " . $domain, trim($mail), "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">");
        }
    }

    public function registerDomain($domain, $owner, $admin, $tech, $zone, $ns, $privacy = false)
    {
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $$t = [
                "firstname" => $$t[0],
                "lastname" => $$t[1],
                "company" => $$t[2],
                "address" => $$t[3],
                "postcode" => $$t[5],
                "city" => $$t[6],
                "country" => $$t[4],
                "phone" => $$t[7],
                "fax" => $$t[8],
                "email" => $$t[9],
            ];
        }

        $this->send($domain, "REGISTER", [
            "owner" => $owner,
            "admin" => $admin,
            "tech" => $tech,
            "zone" => $zone,
            "ns" => $ns,
            "privacy" => $privacy,
        ]);

        return true;
    }

    public function transferDomain($domain, $owner, $admin, $tech, $zone, $authCode, $ns, $privacy = false)
    {
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $$t = [
                "firstname" => $$t[0],
                "lastname" => $$t[1],
                "company" => $$t[2],
                "address" => $$t[3],
                "postcode" => $$t[5],
                "city" => $$t[6],
                "country" => $$t[4],
                "phone" => $$t[7],
                "fax" => $$t[8],
                "email" => $$t[9],
            ];
        }

        $this->send($domain, "TRANSFER-IN", [
            "owner" => $owner,
            "admin" => $admin,
            "tech" => $tech,
            "zone" => $zone,
            "authcode" => $authCode,
            "ns" => $ns,
            "privacy" => $privacy,
        ]);

        return true;
    }

    public function deleteDomain($domain, $transit = 0)
    {
        $this->send($domain, "DELETE", [
            "transit" => $transit,
        ]);

        return true;
    }

    public function getAuthCode($domain)
    {
        $this->send($domain, "AUTHCODE-REQ");

        return "requested";
    }

    public function changeNameserver($domain, $ns)
    {
        $this->send($domain, "NS-CHANGE", [
            "ns" => $ns,
        ]);

        return true;
    }

    public function changeContact($domain, $owner, $admin, $tech, $zone)
    {
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $$t = [
                "firstname" => $$t[0],
                "lastname" => $$t[1],
                "company" => $$t[2],
                "address" => $$t[3],
                "postcode" => $$t[5],
                "city" => $$t[6],
                "country" => $$t[4],
                "phone" => $$t[7],
                "fax" => $$t[8],
                "email" => $$t[9],
            ];
        }

        $this->send($domain, "CHANGE-CONTACT", [
            "owner" => $owner,
            "admin" => $admin,
            "tech" => $tech,
            "zone" => $zone,
        ]);

        return true;
    }

    public function setRenew($domain, $renew = true)
    {
        $this->send($domain, "CHANGE-RENEW", [
            "renew" => $renew,
        ]);

        return true;
    }

    public function syncDomain($domain, $kkSync = false)
    {
        return ["status" => true];
    }

    public function changeValues($domain, $status = false, $renew = true, $privacy = false)
    {
        $this->send($domain, "CHANGE-CONFIG", [
            "status" => $status,
            "renew" => $renew,
            "privacy" => $privacy,
        ]);

        return true;
    }
}
