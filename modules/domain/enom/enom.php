<?php

class Enom extends DomainRegistrar
{
    protected $short = "enom";
    protected $name = "Enom";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "api_user" => array("type" => "text", "name" => $this->getLang("UID")),
            "api_password" => array("type" => "password", "name" => $this->getLang("PASSWORD")),
        );
    }

    private function client()
    {
        spl_autoload_register(function ($class) {
            if (substr($class, 0, 8) != 'arleslie') {
                return false;
            }

            $class = substr($class, 14);
            $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
            require_once __DIR__ . "/lib/" . $class . ".php";
        });

        return new \arleslie\Enom\Client($this->options->api_user, $this->options->api_password, false);
    }

    public function availibilityStatus($domain)
    {
        list($sld, $tld) = explode(".", $domain, 2);

        try {
            $res = $this->client()->DomainRegistration()->check($sld, $tld);

            if (boolval($res->IsPremiumName)) {
                return null;
            }

            return strval($res->RRPCode) == "210";
        } catch (\arleslie\Enom\EnomException $ex) {
            return null;
        }
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
        foreach ($ns as $k => $v) {
            $v = trim($v);
            if (empty($v)) {
                unset($ns[$k]);
            }
        }
        $ns = array_values($ns);

        list($sld, $tld) = explode(".", $domain, 2);
        $res = $this->client->DomainRegistration()->Purchase(
            $sld,
            $tld,
            $owner[0],
            $owner[1],
            $owner[2],
            "",
            $owner[3],
            "",
            $owner[6],
            $owner[4],
            $owner[5],
            $owner[4],
            $owner[9],
            $this->formatPhone($owner[7]),
            $this->formatPhone($owner[8]),
            1,
            true,
            true,
            false,
            "",
            false,
            true,
            $ns
        );

        return boolval($res->Done);
    }

    public function transferDomain($domain, $owner, $admin, $tech, $zone, $authCode, $ns, $privacy = false)
    {
        foreach ($ns as $k => $v) {
            $v = trim($v);
            if (empty($v)) {
                unset($ns[$k]);
            }
        }
        $ns = array_values($ns);

        list($sld, $tld) = explode(".", $domain, 2);
        $res = $this->client->DomainRegistration()->TP_CreateOrder(
            $sld,
            $tld,
            $owner[0],
            $owner[1],
            $owner[2],
            "",
            $owner[3],
            "",
            $owner[6],
            $owner[4],
            $owner[5],
            $owner[4],
            $owner[9],
            $this->formatPhone($owner[7]),
            $this->formatPhone($owner[8]),
            1,
            true,
            true,
            false,
            "",
            false,
            true,
            $ns,
            $authCode
        );

        return boolval($res->Done);
    }

    public function changeContact($domain, $owner, $admin, $tech, $zone)
    {
        list($sld, $tld) = explode(".", $domain, 2);
        return boolval($this->client->DomainRegistration()->Contacts(
            $sld,
            $tld,
            $owner[0],
            $owner[1],
            $owner[2],
            "",
            $owner[3],
            "",
            $owner[6],
            $owner[4],
            $owner[5],
            $owner[4],
            $owner[9],
            $this->formatPhone($owner[7]),
            $this->formatPhone($owner[8])
        )->Done);
    }

    public function syncDomain($domain, $kkSync = false)
    {
        list($sld, $tld) = explode(".", $domain, 2);
        $info = $this->client->DomainRegistration()->GetDomainInfo($sld, $tld);

        if (!($exp = strtotime(strval($info->status->expiration)))) {
            if ($kkSync) {
                return ["status" => "waiting_kk"];
            }

            return false;
        }

        return array(
            "expiration" => date("Y-m-d", $exp),
            "status" => true,
            "privacy" => false,
            "transfer_lock" => false,
        );
    }

    public function changeValues($domain, $status = false, $renew = true, $privacy = false)
    {
        return true;
    }
}
