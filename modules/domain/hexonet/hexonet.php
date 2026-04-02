<?php

class Hexonet extends DomainRegistrar
{
    protected $short = "hexonet";
    protected $name = "Hexonet";
    protected $version = "1.1";

    public function getSettings()
    {
        return array(
            "lid" => array("type" => "text", "name" => $this->getLang("lid")),
            "password" => array("type" => "password", "name" => $this->getLang("password")),
        );
    }

    public function availibilityStatus($domain)
    {
        $res = $this->call([
            "COMMAND" => "CheckDomain",
            "DOMAIN" => $domain,
        ]);

        if ($res["CODE"] == 210) {
            return true;
        }

        if ($res["CODE"] == 211) {
            return false;
        }

        return null;
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
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = $this->formatPhone($i[7]);
            $i[8] = $this->formatPhone($i[8]);

            $$t = [
                "FIRSTNAME" => $i[0],
                "LASTNAME" => $i[1],
                "ORGANIZATION" => $i[2],
                "STREET" => str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]),
                "CITY" => $i[6],
                "STATE" => "",
                "ZIP" => $i[5],
                "COUNTRY" => $i[4],
                "PHONE" => $i[7],
                "FAX" => $i[8],
                "EMAIL" => $i[9],
            ];
        }

        $args = [
            "COMMAND" => "AddDomain",
            "DOMAIN" => $domain,
            "PERIOD" => 1,
            "OWNERCONTACT0" => $owner,
            "ADMINCONTACT0" => $admin,
            "TECHCONTACT0" => $tech,
            "BILLINGCONTACT0" => $zone,
            "X-ACCEPT-WHOISTRUSTEE-TAC" => $privacy ? 1 : 0,
        ];

        foreach ($ns as $k => $v) {
            $args["NAMESERVER" . $k] = $v;
        }

        return $this->call($args)["CODE"] == 200;
    }

    public function transferDomain($domain, $owner, $admin, $tech, $zone, $authCode, $ns, $privacy = false)
    {
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = $this->formatPhone($i[7]);
            $i[8] = $this->formatPhone($i[8]);

            $$t = [
                "FIRSTNAME" => $i[0],
                "LASTNAME" => $i[1],
                "ORGANIZATION" => $i[2],
                "STREET" => str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]),
                "CITY" => $i[6],
                "STATE" => "",
                "ZIP" => $i[5],
                "COUNTRY" => $i[4],
                "PHONE" => $i[7],
                "FAX" => $i[8],
                "EMAIL" => $i[9],
            ];
        }

        $args = [
            "COMMAND" => "TransferDomain",
            "DOMAIN" => $domain,
            "PERIOD" => 1,
            "OWNERCONTACT0" => $owner,
            "ADMINCONTACT0" => $admin,
            "TECHCONTACT0" => $tech,
            "BILLINGCONTACT0" => $zone,
            "X-ACCEPT-WHOISTRUSTEE-TAC" => $privacy ? 1 : 0,
            "AUTH" => $authCode,
        ];

        foreach ($ns as $k => $v) {
            $args["NAMESERVER" . $k] = $v;
        }

        return $this->call($args)["CODE"] == 200;
    }

    public function deleteDomain($domain, $transit = 0)
    {
        if (!$transit) {
            return $this->call([
                "COMMAND" => "DeleteDomain",
                "DOMAIN" => $domain,
            ])["CODE"] == 200;
        } else {
            return $this->call([
                "COMMAND" => "PushDomain",
                "DOMAIN" => $domain,
                "TARGET" => "transit",
            ])["CODE"] == 200;
        }
    }

    public function getAuthCode($domain)
    {
        list($sld, $tld) = explode(".", $domain, 2);

        if ($tld == "de") {
            $res = $this->call([
                "COMMAND" => "DENIC_CreateAuthInfo1",
                "DOMAIN" => $domain,
            ]);
        } else if ($tld == "eu") {
            $res = $this->call([
                "COMMAND" => "RequestDomainAuthInfo",
                "DOMAIN" => $domain,
            ]);
        } else {
            $res = $this->call([
                "COMMAND" => "StatusDomain",
                "DOMAIN" => $domain,
            ]);
        }

        if ($res["CODE"] != 200) {
            return $res["DESCRIPTION"];
        }

        if ($res["PROPERTY"]["AUTH"][0] ?? "") {
            return "AUTH:" . $res["PROPERTY"]["AUTH"][0];
        } else {
            return false;
        }
    }

    public function changeNameserver($domain, $ns)
    {
        return $this->call([
            "COMMAND" => "ModifyDomain",
            "DOMAIN" => $domain,
            "NAMESERVER" => array_values($ns),
            "INTERNALDNS" => 1,
        ])["CODE"] == 200;
    }

    public function changeContact($domain, $owner, $admin, $tech, $zone, $wasTrade = false)
    {
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = $this->formatPhone($i[7]);
            $i[8] = $this->formatPhone($i[8]);

            $$t = [
                "FIRSTNAME" => $i[0],
                "LASTNAME" => $i[1],
                "ORGANIZATION" => $i[2],
                "STREET" => str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]),
                "CITY" => $i[6],
                "STATE" => "",
                "ZIP" => $i[5],
                "COUNTRY" => $i[4],
                "PHONE" => $i[7],
                "FAX" => $i[8],
                "EMAIL" => $i[9],
            ];
        }

        $notTrade = [];
        if (!$wasTrade) {
            $notTrade = [
                "TECHCONTACT0" => $tech,
                "BILLINGCONTACT0" => $zone,
            ];
        }

        return $this->call(array_merge([
            "COMMAND" => "ModifyDomain",
            "DOMAIN" => $domain,
            "OWNERCONTACT0" => $owner,
            "ADMINCONTACT0" => $admin,
        ], $notTrade))["CODE"] == 200;
    }

    public function setRegLock($domain, $status = false, $error = false)
    {
        return $this->call([
            "COMMAND" => "ModifyDomain",
            "DOMAIN" => $domain,
            "TRANSFERLOCK" => $status ? "1" : "0",
        ])["CODE"] == 200;
    }

    public function syncDomain($domain, $kkSync = false)
    {
        $res = $this->call([
            "COMMAND" => "StatusDomain",
            "DOMAIN" => $domain,
        ]);

        if ($res["CODE"] != 200) {
            if ($res["CODE"] != 541) {
                return ["status" => true];
            }

            if ($kkSync) {
                return ["status" => "waiting_kk"];
            }

            return false;
        }

        $res = $res["PROPERTY"];

        $info = [
            "status" => true,
            "expiration" => date("Y-m-d", strtotime($res["REGISTRATIONEXPIRATIONDATE"])),
        ];

        $ns = [];

        if (is_array($res["NAMESERVER"])) {
            foreach ($res["NAMESERVER"] as $dns) {
                if (!empty($dns)) {
                    array_push($ns, $dns);
                }
            }
        }

        if (count($ns) >= 2) {
            $info["ns"] = $ns;
        }

        foreach ([
            "OWNERCONTACT" => "owner",
            "ADMINCONTACT" => "admin",
            "TECHCONTACT" => "tech",
            "BILLINGCONTACT" => "billing",
        ] as $hn => $sd) {
            if (empty($res[$hn])) {
                continue;
            }

            $hi = $this->call([
                "COMMAND" => "StatusContact",
                "CONTACT" => $res[$hn],
            ]);

            if ($hi["CODE"] != "200") {
                continue;
            }

            $info[$sd] = [
                $hi["FIRSTNAME"],
                $hi["LASTNAME"],
                $hi["ORGANIZATION"],
                $hi["STREET"],
                $hi["COUNTRY"],
                $hi["ZIP"],
                $hi["CITY"],
                str_replace("-", "", implode(".", explode("-", $hi["PHONE"], 2))),
                str_replace("-", "", implode(".", explode("-", $hi["FAX"], 2))),
                $hi["EMAIL"],
                "",
            ];
        }

        return $info;
    }

    public function trade($domain, $owner, $admin, $tech, $zone)
    {
        $arr = array("owner", "admin");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = $this->formatPhone($i[7]);
            $i[8] = $this->formatPhone($i[8]);

            $$t = [
                "FIRSTNAME" => $i[0],
                "LASTNAME" => $i[1],
                "ORGANIZATION" => $i[2],
                "STREET" => str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]),
                "CITY" => $i[6],
                "STATE" => "",
                "ZIP" => $i[5],
                "COUNTRY" => $i[4],
                "PHONE" => $i[7],
                "FAX" => $i[8],
                "EMAIL" => $i[9],
            ];
        }

        if ($this->call([
            "COMMAND" => "TradeDomain",
            "DOMAIN" => $domain,
            "OWNERCONTACT0" => $owner,
            "ADMINCONTACT0" => $admin,
        ])["CODE"] != 200) {
            return false;
        }

        return $this->changeContact($domain, $owner, $admin, $tech, $zone, true);
    }

    public function changeValues($domain, $status = false, $renew = true, $privacy = false)
    {
        $this->call([
            "COMMAND" => "ModifyDomain",
            "DOMAIN" => $domain,
            "X-ACCEPT-WHOISTRUSTEE-TAC" => $privacy ? 1 : 0,
        ]);

        $this->call([
            "COMMAND" => "ModifyDomain",
            "DOMAIN" => $domain,
            "TRANSFERLOCK" => $status ? 1 : 0,
        ]);

        $this->call([
            "COMMAND" => "SetDomainRenewalMode",
            "DOMAIN" => $domain,
            "RENEWALMODE" => $renew ? "AUTORENEW" : "AUTOEXPIRE",
            "PERIOD" => "1Y",
        ]);

        return true;
    }

    private function call($command)
    {
        global $CFG;

        $args = array(
            "s_login" => $this->options->lid,
            "s_pw" => $this->options->password,
            "s_command" => $this->encodeCommand($command),
        );

        $ch = curl_init("https://api.ispapi.net/api/call.cgi");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
        curl_setopt($ch, CURLOPT_USERAGENT, "ISPAPI via haseDESK");
        curl_setopt($ch, CURLOPT_REFERER, $CFG['PAGEURL']);
        $res = curl_exec($ch);
        curl_close($ch);

        if (is_array($response)) {
            return $response;
        }

        $hash = array(
            "PROPERTY" => array(),
            "CODE" => "423",
            "DESCRIPTION" => "Empty response from API",
        );

        if (!$response) {
            return $hash;
        }

        $rlist = explode("\n", $response);
        foreach ($rlist as $item) {
            if (preg_match("/^([^\=]*[^\t\= ])[\t ]*=[\t ]*(.*)$/", $item, $m)) {
                $attr = $m[1];
                $value = $m[2];
                $value = preg_replace("/[\t ]*$/", "", $value);
                if (preg_match("/^property\[([^\]]*)\]/i", $attr, $m)) {
                    $prop = strtoupper($m[1]);
                    $prop = preg_replace("/\s/", "", $prop);
                    if (in_array($prop, array_keys($hash["PROPERTY"]))) {
                        array_push($hash["PROPERTY"][$prop], $value);
                    } else {
                        $hash["PROPERTY"][$prop] = array($value);
                    }
                } else {
                    $hash[strtoupper($attr)] = $value;
                }
            }
        }

        if ((!$hash["CODE"]) || (!$hash["DESCRIPTION"])) {
            $hash = array(
                "PROPERTY" => array(),
                "CODE" => "423",
                "DESCRIPTION" => "Invalid response from API",
            );
        }

        return $hash;
    }

    private function encodeCommand($commandarray)
    {
        if (!is_array($commandarray)) {
            return $commandarray;
        }
        $command = "";
        foreach ($commandarray as $k => $v) {
            if (is_array($v)) {
                $v = $this->encodeCommand($v);
                $l = explode("\n", trim($v));
                foreach ($l as $line) {
                    $command .= "$k$line\n";
                }
            } else {
                $v = preg_replace("/\r|\n/", "", $v);
                $command .= "$k=$v\n";
            }
        }
        return $command;
    }
}
