<?php

class ResellerClub extends DomainRegistrar
{
    protected $short = "resellerclub";
    protected $name = "ResellerClub";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "api_user" => array("type" => "text", "name" => $this->getLang("uid")),
            "api_password" => array("type" => "password", "name" => $this->getLang("key")),
            "customer_id" => array("type" => "text", "name" => $this->getLang("cid")),
        );
    }

    private function client()
    {
        spl_autoload_register(function ($class) {
            if (substr($class, 0, 5) != 'habil') {
                return false;
            }

            $class = substr($class, 19);
            $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
            require_once __DIR__ . "/lib/" . $class . ".php";
        });

        return new \habil\ResellerClub\ResellerClub($this->options->api_user, $this->options->api_password);
    }

    public function availibilityStatus($domain)
    {
        list($sld, $tld) = explode(".", $domain, 2);
        $res = $this->client()->domains()->available([$sld], [$tld]);

        if ($res['unknown']) {
            return null;
        }

        return boolval($res['available']);
    }

    private function formatPhone($phone)
    {
        preg_match_all("/\d+/", $number, $result);
        $number = implode("", $result[0]);

        if (substr($number, 0, 1) == "0" && substr($number, 1, 1) != "0") {
            return ["49", substr(ltrim($number, "0"), 0)];
        }

        return [substr(ltrim($number, "0"), 0, 2), substr(ltrim($number, "0"), 2)];
    }

    private function getOrderId($domain)
    {
        return $this->client()->domains()->getOrderId($domain) ?: false;
    }

    public function registerDomain($domain, $owner, $admin, $tech, $zone, $ns, $privacy = false)
    {
        foreach ($ns as $k => &$v) {
            $v = trim($v);
            if (empty($v)) {
                unset($ns[$k]);
            }
        }
        unset($v);

        foreach (["owner", "admin", "tech", "zone"] as $t) {
            $i = $$t;

            $phone = $this->formatPhone($i[7]);

            $$t = $this->client()->contacts()->add(
                $i[0] . " " . $i[1],
                $i[2],
                $i[9],
                $i[3],
                $i[6],
                $i[4],
                $i[5],
                $phone[0],
                $phone[1],
                $this->options->customer_id,
                "Contact"
            );
        }

        return $this->client()->domains()->register(
            $domain,
            1,
            $ns,
            $this->options->customer_id,
            $owner,
            $admin,
            $tech,
            $zone,
            "NoInvoice"
        )['status'] != "ERROR";
    }

    public function transferDomain($domain, $owner, $admin, $tech, $zone, $authCode, $ns, $privacy = false)
    {
        foreach ($ns as $k => &$v) {
            $v = trim($v);
            if (empty($v)) {
                unset($ns[$k]);
            }
        }
        unset($v);

        foreach (["owner", "admin", "tech", "zone"] as $t) {
            $i = $$t;

            $phone = $this->formatPhone($i[7]);

            $$t = $this->client()->contacts()->add(
                $i[0] . " " . $i[1],
                $i[2],
                $i[9],
                $i[3],
                $i[6],
                $i[4],
                $i[5],
                $phone[0],
                $phone[1],
                $this->options->customer_id,
                "Contact"
            );
        }

        return $this->client()->domains()->transfer(
            $domain,
            $this->options->customer_id,
            $owner,
            $admin,
            $tech,
            $zone,
            "NoInvoice",
            $authCode,
            $ns
        )['status'] != "ERROR";
    }

    public function changeContact($domain, $owner, $admin, $tech, $zone)
    {
        if (!($id = $this->getOrderId($domain))) {
            return false;
        }

        foreach (["owner", "admin", "tech", "zone"] as $t) {
            $i = $$t;

            $phone = $this->formatPhone($i[7]);

            $$t = $this->client()->contacts()->add(
                $i[0] . " " . $i[1],
                $i[2],
                $i[9],
                $i[3],
                $i[6],
                $i[4],
                $i[5],
                $phone[0],
                $phone[1],
                $this->options->customer_id,
                "Contact"
            );
        }

        return $this->client()->domains()->modifyContact(
            $domain,
            $owner,
            $admin,
            $tech,
            $zone
        )['status'] != "ERROR";
    }

    public function syncDomain($domain, $kkSync = false)
    {
        if (!($id = $this->getOrderId($domain))) {
            if ($kkSync) {
                return ["status" => "waiting_kk"];
            }

            return false;
        }

        $info = $this->client()->domains()->getDetailsByOrderId($id);

        return array(
            "expiration" => date("Y-m-d", strtotime(strval($info['endtime']))),
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
