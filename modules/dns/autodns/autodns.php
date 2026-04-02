<?php

class AutoDNSDNS extends DNSProvider
{
    protected $short = "autodns";
    protected $name = "AutoDNS";
    protected $version = "1.1";

    public function getSettings()
    {
        return array(
            "api_url" => array("type" => "text", "name" => $this->getLang("url"), "default" => "https://gateway.autodns.com"),
            "api_user" => array("type" => "text", "name" => $this->getLang("username")),
            "api_password" => array("type" => "password", "name" => $this->getLang("password")),
            "api_context" => array("type" => "text", "name" => $this->getLang("context"), "default" => "4"),
        );
    }

    private function send($xml)
    {
        $ch = curl_init($this->options->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        return new SimpleXMLElement($res);
    }

    private function rrXml($domain, $record, $tag = "rr")
    {
        $xml = '<' . $tag . '>
		<name>' . $this->trimDomain($record[0], $domain) . '</name>
		<ttl>' . $record[3] . '</ttl>
		<type>' . $record[1] . '</type>';

        if ($record[1] == "MX") {
            $xml .= '<pref>' . $record[4] . '</pref>';
        }

	if (in_array(strtolower($record[1]), ["CNAME", "MX"])) {
 	    $record[2] = rtrim($record[2], ".") . ".";
	}

        return $xml . '<value>' . $record[2] . '</value>
		</' . $tag . '>';
    }

    public function addZone($domain, array $ns)
    {
        global $CFG;

        $nsXml = "";
        foreach ($ns as $n) {
            if (!empty($n)) {
                $nsXml .= '<nserver><name>' . $n . '</name><ttl>86400</ttl></nserver>';
            }
        }

        $rrXml = "";

        $xml = $this->send('<?xml version="1.0" encoding="utf-8"?>
		<request>
			<auth>
				<user>' . $this->options->api_user . '</user>
				<password>' . $this->options->api_password . '</password>
				<context>' . $this->options->api_context . '</context>
			</auth>
			<language>de</language>
			<task>
				<code>0201</code>
				<zone>
					<name>' . $domain . '</name>
					<ns_action>complete</ns_action>
					<www_include>0</www_include>
					<soa>
						<level>1</level>
						<refresh>43200</refresh>
						<retry>7200</retry>
						<expire>1209600</expire>
						<ttl>86400</ttl>
						<email>' . ($CFG['PAGEMAIL'] ?: "hostmaster@example.com") . '</email>
					</soa>
					' . $nsXml . '
					' . $rrXml . '
				</zone>
			</task>
        </request>');

        return strval($xml->result->status->code) == "S0201";
    }

    public function getZones()
    {
        $xml = $this->send('<?xml version="1.0" encoding="utf-8"?>
		<request>
		<auth>
			<user>' . $this->options->api_user . '</user>
			<password>' . $this->options->api_password . '</password>
			<context>' . $this->options->api_context . '</context>
		</auth>
		<task>
			<code>0205</code>
		</task>
		</request>');

        if (strval($xml->result->status->code) != "S0205") {
            return false;
        }

        $zones = [];
        foreach ($xml->result->data->zone as $z) {
            $zones[strval($z->name)] = 0;
        }

        return $zones;
    }

    public function getZone($domain, $force = 0)
    {
        $xml = $this->send('<?xml version="1.0" encoding="utf-8"?>
		<request>
		<auth>
			<user>' . $this->options->api_user . '</user>
			<password>' . $this->options->api_password . '</password>
			<context>' . $this->options->api_context . '</context>
		</auth>
		<task>
			<code>0205</code>
			<zone>
				<name>' . $domain . '</name>
			</zone>
		</task>
        </request>');

        if (strval($xml->result->status->code) != "S0205") {
            return false;
        }

        $records = [];
        foreach ($xml->result->data->zone->rr as $i => $r) {
            $sub = $this->trimDomain(strval($r->name), $domain);
            if (!$force && $this->isDynDNS($sub . "." . $domain)) {
                continue;
            }

            $hash = hash("sha512", $sub . strval($r->type) . strval($r->value) . strval($r->ttl) . (strval($r->pref) ?: "0"));
            $rid = "";
            while (strlen($rid) < 12) {
                $char = substr($hash, 0, 1);
                $hash = substr($hash, 1);

                if (in_array($char, ["1", "2", "3", "4", "5", "6", "7", "8", "9"])) {
                    $rid .= $char;
                }
            }

            $records[$rid] = [
                $sub,
                strval($r->type),
                strval($r->value),
                strval($r->ttl),
                strval($r->pref) ?: "0",
                0,
                $this->isDynDNS($sub) ? 1 : 0,
            ];
        }

        return $records;
    }

    public function recordTypes($admin = false)
    {
        return array("MX", "A", "AAAA", "CNAME", "SRV", "TXT");
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
                $this->removeRecord($domain, [$r[4], $r[5]], true);
                $this->setDynDNS($sub . "." . $domain, "");

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
                    $newRecord = [$sub, "A", $ip, "180", "0"];
                    $this->editRecord($domain, $r[4], $newRecord, true);
                }

                if ($ip6) {
                    $newRecord = [$sub, "AAAA", $ip6, "180", "0"];

                    if ($r[5]) {
                        $this->editRecord($domain, $r[5], $newRecord, true);
                    } else {
                        $this->addRecord($domain, $newRecord);
                    }
                } else if ($r[5]) {
                    $this->removeRecord($domain, $r[5], true);
                }

                return true;
            }
        }

        return false;
    }

    public function addRecord($domain, $record, $hidden = 0, $admin = true)
    {
        $xml = $this->send('<?xml version="1.0" encoding="utf-8"?>
		<request>
		<auth>
			<user>' . $this->options->api_user . '</user>
			<password>' . $this->options->api_password . '</password>
			<context>' . $this->options->api_context . '</context>
		</auth>
		<task>
			<code>0202001</code>
			<zone>
				<name>' . $domain . '</name>
			</zone>
			<default>
				' . $this->rrXml($domain, $record, 'rr_add') . '
			</default>
		</task>
		</request>');

        return strval($xml->result->status->code) == "S0202001";
    }

    public function editRecord($domain, $record, $new, $force = 0)
    {
        if (!$this->removeRecord($domain, $record, $force)) {
            return false;
        }

        return $this->addRecord($domain, $new);
    }

    public function removeRecord($domain, $record, $force = 0)
    {
        $records = $this->getZone($domain, $force);

        if (!is_array($record)) {
            $record = [$record];
        }

        foreach ($record as $r) {
            $xml = $this->send('<?xml version="1.0" encoding="utf-8"?>
            <request>
            <auth>
                <user>' . $this->options->api_user . '</user>
                <password>' . $this->options->api_password . '</password>
                <context>' . $this->options->api_context . '</context>
            </auth>
            <task>
                <code>0202001</code>
                <zone>
                    <name>' . $domain . '</name>
                </zone>
                <default>
                    ' . $this->rrXml($domain, $records[$r], 'rr_rem') . '
                </default>
            </task>
            </request>');

            if (strval($xml->result->status->code) != "S0202001") {
                return false;
            }
        }

        return true;
    }

    public function removeZone($domain)
    {
        $xml = $this->send('<?xml version="1.0" encoding="utf-8"?>
		<request>
		<auth>
			<user>' . $this->options->api_user . '</user>
			<password>' . $this->options->api_password . '</password>
			<context>' . $this->options->api_context . '</context>
		</auth>
		<task>
			<code>0203</code>
			<zone>
				<name>' . $domain . '</name>
			</zone>
		</task>
		</request>');

        return strval($xml->result->status->code) == "S0203";
    }
}
