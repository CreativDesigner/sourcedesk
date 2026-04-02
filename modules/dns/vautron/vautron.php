<?php

class VautronDNS extends DNSProvider
{
    protected $short = "vautron";
    protected $name = "Vautron";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "api_url" => array("type" => "text", "name" => $this->getLang("URL"), "default" => "https://backend.antagus.de/bdom"),
            "user_id" => array("type" => "text", "name" => $this->getLang("UID")),
        );
    }

    public function addZone($domain, array $ns)
    {
        global $CFG;

        $domain = strtolower($domain);

        $rrXml = "";

        foreach ($ns as $k => $v) {
            $v = trim($v);
            if (empty($v)) {
                unset($ns[$k]);
            } else {
                $rrXml .= "<record_item><content>$v</content><name>$domain</name><ttl>86400</ttl><type>NS</type></record_item>";
            }
        }
        $ns = array_values($ns);

        $rrXml .= "<record_item><content>$domain</content><name>$domain</name><ttl>86400</ttl><type>MX</type><priority>10</priority></record_item>";

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
		<zone xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNameSpaceSchemaLocation="DnsZone.xsd">
			<name>' . $domain . '</name>
			<user_id>' . $this->options->user_id . '</user_id>
			<record_list>
				' . $rrXml . '
			</record_list>
			<soa>
				<mname>' . $ns[0] . '</mname>
				<rname>' . $CFG['PAGEMAIL'] . '</rname>
				<serial>' . gmdate("YmdHis") . '</serial>
				<ttl>86400</ttl>
			</soa>
		</zone>';

        if (!class_exists('Vautron\httpRequest')) {
            require_once __DIR__ . "/httpRequest.php";
            require_once __DIR__ . "/httpResponse.php";
        }

        $obj = new Vautron\httpRequest($this->options->api_url);
        $res = new Vautron\httpResponse($obj->put("/dns/domain/-/{$this->options->user_id}/", $xml));
        $res = (array) $res->body();

        return !empty($res['domain_id']);
    }

    public function getZones()
    {
        // Not supported by API
        return [];
    }

    public function getZone($domain, $force = 0, $id = false)
    {
        $domain = strtolower($domain);

        if (!class_exists('Vautron\httpRequest')) {
            require_once __DIR__ . "/httpRequest.php";
            require_once __DIR__ . "/httpResponse.php";
        }

        $obj = new Vautron\httpRequest($this->options->api_url);
        $res = new Vautron\httpResponse($obj->get("/dns/domain/$domain/{$this->options->user_id}/"));
        $res = (array) $res->body();

        if (empty($res['name']) || $res['name'] != $domain) {
            return false;
        }

        if ($id) {
            return $res['domain_id'];
        }

        $records = [];
        foreach ($res['record_list']->record_item as $r) {
            $r = (array) $r;

            $sub = $this->trimDomain($r['name'], $domain);
            if (!$force && $this->isDynDNS($sub . "." . $domain)) {
                continue;
            }

            $records[$r['record_id']] = [
                $this->trimDomain($r['name'], $domain),
                $r['type'],
                $r['content'],
                $r['ttl'],
                $r['priority'] ?: "0",
                0,
                $this->isDynDNS($sub . "." . $domain) ? 1 : 0,
            ];
        }

        return $records;
    }

    public function recordTypes($admin = false)
    {
        return array("MX", "A", "AAAA", "CNAME", "CAA", "TXT", "SRV");
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

    private function addDomain($text, $domain)
    {
        if ($text == $domain || empty($text)) {
            return $domain;
        }

        if (substr($text, (strlen($domain) + 1) / -1) == "." . $domain) {
            return $text;
        }

        return $text . "." . $domain;
    }

    private function updateZone($domain, $records)
    {
        global $CFG;

        $domain = strtolower($domain);

        $primaryNs = [];
        $rrXml = "";

        foreach ($records as $r) {
            $name = $this->addDomain($r[0], $domain);
            $type = $r[1];
            $content = $r[2];
            $ttl = $r[3];
            $priority = $r[4];

            if ($type == "NS") {
                array_push($primaryNs, $content);
            }

            $rrXml .= "<record_item><content>$content</content><name>$name</name><ttl>$ttl</ttl><type>$type</type>";
            if ($type == "MX") {
                $rrXml .= "<priority>$priority</priority>";
            }
            $rrXml .= "</record_item>";
        }

        $primaryNs = min($primaryNs);
        $id = $this->getZone($domain, 0, true);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
		<zone xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNameSpaceSchemaLocation="DnsZone.xsd">
			<name>' . $domain . '</name>
			<domain_id>' . $id . '</domain_id>
			<user_id>' . $this->options->user_id . '</user_id>
			<record_list>
				' . $rrXml . '
			</record_list>
			<soa>
				<mname>' . $primaryNs . '</mname>
				<rname>' . $CFG['PAGEMAIL'] . '</rname>
				<serial>' . gmdate("YmdHis") . '</serial>
				<ttl>86400</ttl>
			</soa>
		</zone>';

        if (!class_exists('Vautron\httpRequest')) {
            require_once __DIR__ . "/httpRequest.php";
            require_once __DIR__ . "/httpResponse.php";
        }

        $obj = new Vautron\httpRequest($this->options->api_url);
        $res = new Vautron\httpResponse($obj->post("/dns/domain/$domain/{$this->options->user_id}/", $xml));
        $res = (array) $res->body();

        return !empty($res['domain_id']);
    }

    public function addRecord($domain, $record, $hidden = 0, $admin = true)
    {
        $records = $this->getZone($domain);
        $record[0] = $this->trimDomain($record[0], $domain);
        $records[] = $record;

        return $this->updateZone($domain, $records);
    }

    public function editRecord($domain, $record, $new, $force = 0)
    {
        $records = $this->getZone($domain);

        if (!array_key_exists($record, $records)) {
            return false;
        }

        $new[0] = $this->trimDomain($new[0], $domain);
        $records[$record] = $new;
        return $this->updateZone($domain, $records);
    }

    public function removeRecord($domain, $record, $force = 0)
    {
        $records = $this->getZone($domain);

        if (!array_key_exists($record, $records)) {
            return false;
        }

        unset($records[$record]);
        return $this->updateZone($domain, $records);
    }

    public function removeZone($domain)
    {
        if (!class_exists('Vautron\httpRequest')) {
            require_once __DIR__ . "/httpRequest.php";
            require_once __DIR__ . "/httpResponse.php";
        }

        $domain = strtolower($domain);

        $obj = new Vautron\httpRequest($this->options->api_url);
        $res = new Vautron\httpResponse($obj->delete("/dns/domain/$domain/{$this->options->user_id}/", ""));
        $res = (array) $res->body();
        return $res['status'] == "OK";
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

                if ($r[5]) {
                    $r = [$sub, "AAAA", $ip6, "180", "0"];
                    $this->editRecord($domain, $r[5], $r, true);
                }

                return true;
            }
        }

        return false;
    }
}
