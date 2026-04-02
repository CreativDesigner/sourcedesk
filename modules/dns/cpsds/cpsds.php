<?php

class CpsDsDNS extends DNSProvider
{
    protected $short = "cpsds";
    protected $name = "CPS-Datensysteme";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "api_cid" => array("type" => "text", "name" => $this->getLang("CSTNR")),
            "api_user" => array("type" => "text", "name" => $this->getLang("USERNAME"), "default" => "master"),
            "api_password" => array("type" => "password", "name" => $this->getLang("PASSWORD")),
            "outgoing_ip" => array("text" => "text", "name" => $this->getLang("OUTGOING"), "hint" => $this->getLang("OPTIONAL")),
        );
    }

    private function request($data, $head = "")
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://orms.cps-datensysteme.de:700");
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_POST, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS, utf8_encode('<?xml version="1.0" encoding="utf-8"?><request>' . $head . '
		<auth>
			<cid>' . $this->options->api_cid . '</cid>
			<user>' . $this->options->api_user . '</user>
			<pwd>' . $this->options->api_password . '</pwd>
			<secure_token></secure_token>
		</auth><transaction>' . $data . '</transaction></request>'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($this->options->outgoing_ip)) {
            curl_setopt($curl, CURLOPT_INTERFACE, $this->options->outgoing_ip);
        }

        $res = curl_exec($curl);
        curl_close($curl);

        return new SimpleXMLElement($res);
    }

    public function addZone($domain, array $ns)
    {
        $nsXml = "";
        foreach ($ns as $k => $v) {
            $v = trim($v);
            if (empty($v)) {
                unset($ns[$k]);
            } else {
                $nsXml .= "<ns>{$v}</ns>";
            }
        }
        $ns = array_values($ns);

        $res = $this->request("
			<group>dns</group>
			<action>create</action>
			<attribute>primsec</attribute>
			<object>" . ($domain = $this->idn($domain)) . "</object>
			<values>
			<soa>
				<ttl>86400</ttl>
				<primary_ns>{$ns[0]}</primary_ns>
				</soa>
				$nsXml
			</values>
		", "<version>1.8.9</version>");

        return strval($res->result->code) == "1000";
    }

    public function getZones()
    {
        $res = $this->request("
			<group>dns</group>
			<action>list</action>
			<attribute>primsec</attribute>
			<values>
				<domain>*</domain>
				<native_domain>*</native_domain>
				<user>{$this->options->api_user}</user>
			</values>
		");

        $arr = [];

        foreach ($res->result->detail->values as $d) {
            $arr[$d->domain] = 0;
        }

        return $arr;
    }

    public function getZone($domain, $force = 0)
    {
        $res = $this->request("
			<group>dns</group>
			<action>info</action>
			<attribute>dnszones_records</attribute>
			<object>" . ($domain = $this->idn($domain)) . "</object>
			<values></values>
		");

        if (empty($res->result->detail->values)) {
            return false;
        }

        $records = [];
        $i = 0;

        foreach ($res->result->detail->values as $r) {
            if (in_array(strval($r->record_type), ["soa", "ns"])) {
                continue;
            }

            if (!$force && $this->isDynDNS($r->source . "." . $domain)) {
                continue;
            }

            $records[$i++] = [
                strval($r->source) == "@" ? "" : strval($r->source),
                strtoupper(strval($r->record_type)),
                strval($r->target),
                3600,
                strval($r->record_type) == "mx" ? intval($r->rr_preference) : 0,
                0,
                $this->isDynDNS($r->source . "." . $domain) ? 1 : 0,
            ];
        }

        return $records;
    }

    public function recordTypes($admin = false)
    {
        return array("MX", "A", "AAAA", "CNAME", "TXT");
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

    public function getNameservers($domain)
    {
        $res = $this->request("
			<group>dns</group>
			<action>info</action>
			<attribute>dnszones_records</attribute>
			<object>" . ($domain = $this->idn($domain)) . "</object>
		");

        $ns = [];
        foreach ($res->result->detail->values as $r) {
            if (strval($r->record_type) == "ns") {
                array_push($ns, strval($r->target));
            }
        }

        return $ns;
    }

    public function setRecords($domain, $records)
    {
        $ns = $this->getNameservers($domain);

        $nsXml = "";
        foreach ($ns as $k => $v) {
            $v = trim($v);
            if (empty($v)) {
                unset($ns[$k]);
            } else {
                $nsXml .= "<ns>{$v}</ns>";
            }
        }
        $ns = array_values($ns);

        $recordXml = "";
        if (is_array($records)) {
            foreach ($records as $r) {
                $recordXml .= "<" . strtolower($r[1]) . ">";
                $recordXml .= "<rr_name>" . htmlentities($this->trimDomain($r[0], $domain) ?: "@") . "</rr_name>";
                $recordXml .= "<rr_value>" . htmlentities($r[2]) . "</rr_value>";
                if ($r[1] == "MX") {
                    $recordXml .= "<rr_preference>" . $r[4] . "</rr_preference>";
                }
                $recordXml .= "</" . strtolower($r[1]) . ">";
            }
        }

        $res = $this->request($xml = "
			<group>dns</group>
			<action>modify</action>
			<attribute>primsec</attribute>
			<object>" . ($domain = $this->idn($domain)) . "</object>
			<values>
			<soa>
				<ttl>86400</ttl>
				<primary_ns>{$ns[0]}</primary_ns>
				</soa>
				$nsXml
				$recordXml
			</values>
		", "<version>1.8.9</version>");

        return strval($res->result->code) == "1000";
    }

    public function addRecord($domain, $record, $hidden = 0, $admin = true)
    {
        $records = $this->getZone($domain);
        $records[] = $record;
        return $this->setRecords($domain, $records);
    }

    public function editRecord($domain, $record, $new, $force = 0)
    {
        $records = $this->getZone($domain);
        $records[$record] = $new;
        return $this->setRecords($domain, $records);
    }

    public function removeRecord($domain, $record, $force = 0)
    {
        $records = $this->getZone($domain);
        unset($records[$record]);
        return $this->setRecords($domain, $records);
    }

    public function removeZone($domain)
    {
        $res = $this->request("
			<group>dns</group>
			<action>delete</action>
			<attribute>primsec</attribute>
			<object>" . ($domain = $this->idn($domain)) . "</object>
		");

        return strval($res->result->code) == "1000";
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
