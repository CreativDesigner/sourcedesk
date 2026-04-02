<?php

class InterNetWorX extends DomainRegistrar
{
    protected $short = "inwx";
    protected $name = "InterNetWorX";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "api_user" => array("type" => "text", "name" => $this->getLang("username")),
            "api_password" => array("type" => "password", "name" => $this->getLang("password")),
        );
    }

    public function availibilityStatus($domain)
    {
        if (!function_exists('xmlrpc_encode_request')) {
            return null;
        }

        if (!class_exists("domrobot")) {
            require __DIR__ . "/domrobot.class.php";
        }

        $addr = "https://api.domrobot.com/xmlrpc/";
        $domrobot = new domrobot($addr);
        $domrobot->setDebug(false);
        $domrobot->setLanguage('en');
        $res = $domrobot->login($this->options->api_user, $this->options->api_password);

        if ($res['code'] == "1000") {
            $res = $domrobot->call("domain", "check", array("domain" => $domain), $this);
        }

        if (!isset($res['resData']['domain'][0]['avail']) || $res['resData']['domain'][0]['avail'] === null) {
            return null;
        }

        $domrobot->logout();

        return (bool) $res['resData']['domain'][0]['avail'];
    }

    public function registerDomain($domain, $owner, $admin, $tech, $zone, $ns, $privacy = false)
    {
        if (!class_exists("domrobot")) {
            require __DIR__ . "/domrobot.class.php";
        }

        $addr = "https://api.domrobot.com/xmlrpc/";
        $domrobot = new domrobot($addr);
        $domrobot->setDebug(false);
        $domrobot->setLanguage('en');
        $res = $domrobot->login($this->options->api_user, $this->options->api_password);

        if ($res['code'] != "1000") {
            return $res;
        }

        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
            $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);

            $type = empty($i[2]) ? "PERSON" : "ORG";
            if ($t == "admin") {
                $type = "PERSON";
            }
            if ($t == "tech" || $t == "zone") {
                if ($type == "ORG") {
                    $type = "ROLE";
                }
            }

            $res = $domrobot->call("contact", "create", array(
                "type" => $type,
                "name" => $i[0] . " " . $i[1],
                "org" => $i[2],
                "street" => str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]),
                "city" => $i[6],
                "pc" => $i[5],
                "cc" => $i[4],
                "voice" => $this->convertTelephone($i[7]),
                "fax" => $this->convertTelephone($i[8]),
                "email" => $i[9],
            ), $this, $domain);

            if ($res['code'] != "1000") {
                return $res;
            }

            $$t = $res['resData']['id'];
        }

        $res = $domrobot->call("domain", "create", array(
            "domain" => $domain,
            "registrant" => $owner,
            "admin" => $admin,
            "tech" => $tech,
            "billing" => $zone,
            "ns" => $ns,
            "renewalMode" => "AUTOEXPIRE",
        ), $this);

        if ($res['code'] != "1000" && $res['code'] != "1001") {
            return $res;
        }

        $domrobot->logout();
        return true;
    }

    public function transferDomain($domain, $owner, $admin, $tech, $zone, $authCode, $ns, $privacy = false)
    {
        if (!class_exists("domrobot")) {
            require __DIR__ . "/domrobot.class.php";
        }

        $addr = "https://api.domrobot.com/xmlrpc/";
        $domrobot = new domrobot($addr);
        $domrobot->setDebug(false);
        $domrobot->setLanguage('en');
        $res = $domrobot->login($this->options->api_user, $this->options->api_password);

        if ($res['code'] != "1000") {
            return $res;
        }

        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
            $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);

            $type = empty($i[2]) ? "PERSON" : "ORG";
            if ($t == "admin") {
                $type = "PERSON";
            }
            if ($t == "tech" || $t == "zone") {
                if ($type == "ORG") {
                    $type = "ROLE";
                }
            }

            $res = $domrobot->call("contact", "create", array(
                "type" => $type,
                "name" => $i[0] . " " . $i[1],
                "org" => $i[2],
                "street" => str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]),
                "city" => $i[6],
                "pc" => $i[5],
                "cc" => $i[4],
                "voice" => $this->convertTelephone($i[7]),
                "fax" => $this->convertTelephone($i[8]),
                "email" => $i[9],
            ), $this, $domain);

            if ($res['code'] != "1000") {
                return $res;
            }

            $$t = $res['resData']['id'];
        }

        $res = $domrobot->call("domain", "transfer", array(
            "domain" => $domain,
            "registrant" => $owner,
            "admin" => $admin,
            "tech" => $tech,
            "billing" => $zone,
            "ns" => $ns,
            "renewalMode" => "AUTOEXPIRE",
            "authCode" => $authCode,
        ), $this);

        if ($res['code'] != "1000" && $res['code'] != "1001") {
            return $res;
        }

        $domrobot->logout();
        return true;
    }

    public function deleteDomain($domain, $transit = 0)
    {
        if (!class_exists("domrobot")) {
            require __DIR__ . "/domrobot.class.php";
        }

        $addr = "https://api.domrobot.com/xmlrpc/";
        $domrobot = new domrobot($addr);
        $domrobot->setDebug(false);
        $domrobot->setLanguage('en');
        $res = $domrobot->login($this->options->api_user, $this->options->api_password);

        if ($res['code'] != "1000") {
            return false;
        }

        if ($transit == 0) {
            $res = $domrobot->call("domain", "delete", array(
                "domain" => $domain,
            ), $this);
        } else {
            $res = $domrobot->call("domain", "push", array(
                "domain" => $domain,
            ), $this);
        }

        if ($res['code'] != "1000" && $res['code'] != "1001") {
            return false;
        }

        return true;
    }

    public function getAuthCode($domain)
    {
        if (!class_exists("domrobot")) {
            require __DIR__ . "/domrobot.class.php";
        }

        $addr = "https://api.domrobot.com/xmlrpc/";
        $domrobot = new domrobot($addr);
        $domrobot->setDebug(false);
        $domrobot->setLanguage('en');
        $res = $domrobot->login($this->options->api_user, $this->options->api_password);

        if ($res['code'] != "1000") {
            return false;
        }

        $res = $domrobot->call("domain", "info", array(
            "domain" => $domain,
        ), $this);

        if ($res['code'] != "1000" || empty($res['resData']['authCode'])) {
            return false;
        }

        return "AUTH:" . $res['resData']['authCode'];
    }

    public function changeNameserver($domain, $ns)
    {
        if (!class_exists("domrobot")) {
            require __DIR__ . "/domrobot.class.php";
        }

        $addr = "https://api.domrobot.com/xmlrpc/";
        $domrobot = new domrobot($addr);
        $domrobot->setDebug(false);
        $domrobot->setLanguage('en');
        $res = $domrobot->login($this->options->api_user, $this->options->api_password);

        if ($res['code'] != "1000") {
            return false;
        }

        $res = $domrobot->call("domain", "update", array(
            "domain" => $domain,
            "ns" => $ns,
        ), $this);

        if ($res['code'] != "1000" && $res['code'] != "1001") {
            return false;
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
        if (!class_exists("domrobot")) {
            require __DIR__ . "/domrobot.class.php";
        }

        $addr = "https://api.domrobot.com/xmlrpc/";
        $domrobot = new domrobot($addr);
        $domrobot->setDebug(false);
        $domrobot->setLanguage('en');
        $res = $domrobot->login($this->options->api_user, $this->options->api_password);

        if ($res['code'] != "1000") {
            return false;
        }

        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
            $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);

            $type = empty($i[2]) ? "PERSON" : "ORG";
            if ($t == "admin") {
                $type = "PERSON";
            }
            if ($t == "tech" || $t == "zone") {
                if ($type == "ORG") {
                    $type = "ROLE";
                }
            }

            $res = $domrobot->call("contact", "create", array(
                "type" => $type,
                "name" => $i[0] . " " . $i[1],
                "org" => $i[2],
                "street" => str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]),
                "city" => $i[6],
                "pc" => $i[5],
                "cc" => $i[4],
                "voice" => $this->convertTelephone($i[7]),
                "fax" => $this->convertTelephone($i[8]),
                "email" => $i[9],
            ), $this, $domain);

            if ($res['code'] != "1000") {
                return false;
            }

            $$t = $res['resData']['id'];
        }

        $res = $domrobot->call("domain", "update", array(
            "domain" => $domain,
            "registrant" => $owner,
            "admin" => $admin,
            "tech" => $tech,
            "billing" => $zone,
        ), $this);

        if ($res['code'] != "1000" && $res['code'] != "1001") {
            return false;
        }

        return true;
    }

    public function setRegLock($domain, $status = false, $error = false)
    {
        if (!class_exists("domrobot")) {
            require __DIR__ . "/domrobot.class.php";
        }

        $addr = "https://api.domrobot.com/xmlrpc/";
        $domrobot = new domrobot($addr);
        $domrobot->setDebug(false);
        $domrobot->setLanguage('en');
        $res = $domrobot->login($this->options->api_user, $this->options->api_password);

        if ($res['code'] != "1000") {
            return $res;
        }

        $res = $domrobot->call("domain", "update", array(
            "domain" => $domain,
            "transferLock" => $status,
        ), $this);

        if ($res['code'] != "1000" && $res['code'] != "1001") {
            return $res;
        }

        return true;
    }

    public function changeAll($domain, $owner, $admin, $tech, $zone, $ns, $status = false, $renew = true, $privacy = false)
    {
        if (!class_exists("domrobot")) {
            require __DIR__ . "/domrobot.class.php";
        }

        $addr = "https://api.domrobot.com/xmlrpc/";
        $domrobot = new domrobot($addr);
        $domrobot->setDebug(false);
        $domrobot->setLanguage('en');
        $res = $domrobot->login($this->options->api_user, $this->options->api_password);

        if ($res['code'] != "1000") {
            return $res;
        }

        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            if (count($i) == 0) {
                $r = $this->syncDomain($domain);
                if (empty($r) || !is_array($r) || empty($r['ownerc'])) {
                    return false;
                }

                $$t = $r['ownerc'];
            } else {
                $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
                $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);

                $type = empty($i[2]) ? "PERSON" : "ORG";
                if ($t == "admin") {
                    $type = "PERSON";
                }
                if ($t == "tech" || $t == "zone") {
                    if ($type == "ORG") {
                        $type = "ROLE";
                    }
                }

                $res = $domrobot->call("contact", "create", array(
                    "type" => $type,
                    "name" => $i[0] . " " . $i[1],
                    "org" => $i[2],
                    "street" => str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]),
                    "city" => $i[6],
                    "pc" => $i[5],
                    "cc" => $i[4],
                    "voice" => $this->convertTelephone($i[7]),
                    "fax" => $this->convertTelephone($i[8]),
                    "email" => $i[9],
                ), $this, $domain);

                if ($res['code'] != "1000") {
                    return $res;
                }

                $$t = $res['resData']['id'];
            }
        }

        $res = $domrobot->call("domain", "update", array(
            "domain" => $domain,
            "registrant" => $owner,
            "admin" => $admin,
            "tech" => $tech,
            "billing" => $zone,
            "transferLock" => $status,
            "renewalMode" => $renew ? "AUTORENEW" : "AUTOEXPIRE",
            "ns" => $ns,
        ), $this);

        if ($res['code'] != "1000" && $res['code'] != "1001") {
            return $res;
        }

        return true;
    }

    public function syncDomain($domain, $kkSync = false)
    {
        if (!class_exists("domrobot")) {
            require __DIR__ . "/domrobot.class.php";
        }

        $addr = "https://api.domrobot.com/xmlrpc/";
        $domrobot = new domrobot($addr);
        $domrobot->setDebug(false);
        $domrobot->setLanguage('en');
        $res = $domrobot->login($this->options->api_user, $this->options->api_password);

        if ($res['code'] != "1000") {
            return [
                "status" => true,
            ];
        }

        $res = $domrobot->call("domain", "info", array(
            "domain" => $domain,
            "wide" => "2",
        ), $this);

        if ($res['code'] != "1000") {
            if ($kkSync) {
                return ["status" => "waiting_kk"];
            }

            if (substr($res['code'], 0, 1) == "2") {
                return [
                    "status" => true,
                ];
            }

            return false;
        }

        $info = array(
            "auto_renew" => $res['resData']['renewalMode'] == "AUTORENEW",
            "expiration" => date("Y-m-d", $res['resData']['exDate']->timestamp),
            "status" => $res['resData']['status'] == "OK",
            "transfer_lock" => (bool) $res['resData']['transferLock'],
            "ownerc" => $res['resData']['contact']['registrant']['id'],
            "privacy" => false,
        );

        $ns = [];

        if (is_array($res['resData']['ns'])) {
            foreach ($res['resData']['ns'] as $dns) {
                if (!empty($dns)) {
                    array_push($ns, $dns);
                }
            }
        }

        if (count($ns) >= 2) {
            $info['ns'] = $ns;
        }
        
        foreach ([
            "admin" => "admin",
            "registrant" => "owner",
            "tech" => "tech",
            "billing" => "zone",
        ] as $ix => $sd) {
            if (!is_array($res['resData']['contact'][$ix])) {
                continue;
            }

            $hi = (object) $res['resData']['contact'][$ix];

            list($fname, $lname) = explode(" ", $hi->name, 2);

            $info[$sd] = [
                $fname,
                $lname,
                $hi->org,
                $hi->street,
                $hi->cc,
                $hi->pc,
                $hi->city,
                str_replace("-", "", $hi->voice),
                str_replace("-", "", $hi->fax),
                $hi->email,
                $hi->remarks,
            ];
        }

        return $info;
    }

    public function trade($domain, $owner, $admin, $tech, $zone)
    {
        if (!class_exists("domrobot")) {
            require __DIR__ . "/domrobot.class.php";
        }

        $addr = "https://api.domrobot.com/xmlrpc/";
        $domrobot = new domrobot($addr);
        $domrobot->setDebug(false);
        $domrobot->setLanguage('en');
        $res = $domrobot->login($this->options->api_user, $this->options->api_password);

        if ($res['code'] != "1000") {
            return false;
        }

        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
            $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);

            $type = empty($i[2]) ? "PERSON" : "ORG";
            if ($t == "admin") {
                $type = "PERSON";
            }
            if ($t == "tech" || $t == "zone") {
                if ($type == "ORG") {
                    $type = "ROLE";
                }
            }

            $res = $domrobot->call("contact", "create", array(
                "type" => $type,
                "name" => $i[0] . " " . $i[1],
                "org" => $i[2],
                "street" => str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]),
                "city" => $i[6],
                "pc" => $i[5],
                "cc" => $i[4],
                "voice" => $this->convertTelephone($i[7]),
                "fax" => $this->convertTelephone($i[8]),
                "email" => $i[9],
            ), $this, $domain);

            if ($res['code'] != "1000") {
                return false;
            }

            $$t = $res['resData']['id'];
        }

        $res = $domrobot->call("domain", "trade", array(
            "domain" => $domain,
            "registrant" => $owner,
            "admin" => $admin,
            "tech" => $tech,
            "billing" => $zone,
        ), $this);

        if ($res['code'] != "1000" && $res['code'] != "1001") {
            return false;
        }

        return true;
    }

    public function changeValues($domain, $status = false, $renew = true, $privacy = false)
    {
        if (!class_exists("domrobot")) {
            require __DIR__ . "/domrobot.class.php";
        }

        $addr = "https://api.domrobot.com/xmlrpc/";
        $domrobot = new domrobot($addr);
        $domrobot->setDebug(false);
        $domrobot->setLanguage('en');
        $res = $domrobot->login($this->options->api_user, $this->options->api_password);

        if ($res['code'] != "1000") {
            return $res;
        }

        $res = $domrobot->call("domain", "update", array(
            "domain" => $domain,
            "renewalMode" => $renew ? "AUTORENEW" : "AUTOEXPIRE",
            "transferLock" => $status,
        ), $this);

        if ($res['code'] != "1000" && $res['code'] != "1001") {
            return $res;
        }

        return true;
    }
}
