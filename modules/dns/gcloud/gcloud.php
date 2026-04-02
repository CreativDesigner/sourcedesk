<?php

class GCloudDNS extends DNSProvider
{
    protected $short = "gcloud";
    protected $name = "Google Cloud DNS";
    protected $version = "1.0";
    protected $client;
    protected $project;

    public function getSettings()
    {
        return array(
            "key" => array("type" => "textarea", "name" => $this->getLang("json")),
        );
    }

    private function getServiceHandler($service)
    {
        $json = @json_decode($this->options->key, true);
        if (!is_array($json)) {
            throw new Exception("Invalid key file");
        }
        $this->project = $json['project_id'];

        $client = new Google_Client;
        $client->setAuthConfig($this->options->key);
        $client->setApplicationName("haseDESK");
        $client->setScopes(['https://www.googleapis.com/auth/ndev.clouddns.readwrite']);

        return new $service($client);
    }

    public function addZone($domain, array $ns)
    {
        global $CFG;

        $body = new Google_Service_Dns_ManagedZone();
        $body->setDnsName($domain . ".");
        $body->setName($this->getZoneName($domain));
        $body->setDescription("");

        $service = $this->getServiceHandler("Google_Service_Dns");
        $res = $service->managedZones->create($this->project, $body);

        if (!($res instanceof Google_Service_Dns_ManagedZone)) {
            return false;
        }

        return true;
    }

    public function getZones()
    {
        $res = [];

        $service = $this->getServiceHandler("Google_Service_Dns");
        $zones = $service->managedZones->listManagedZones($this->project, ["maxResults" => "1000"])->managedZones;
        foreach ($zones as $zone) {
            $res[trim($zone->getDnsName(), ".")] = 0;
        }

        return $res;
    }

    private function getZoneName($domain)
    {
        $domain = strtolower($domain);
        return str_replace(".", "--", $domain);
    }

    public function getZone($domain, $force = 0)
    {
        try {
            $service = $this->getServiceHandler("Google_Service_Dns");
            $zone = $service->resourceRecordSets->listResourceRecordSets($this->project, $this->getZoneName($domain), ["maxResults" => "1000"]);
        } catch (Exception $ex) {
            return false;
        }

        $records = [];
        foreach ($zone->getRrsets() as $rr) {
            $sub = $this->trimDomain(trim($rr->getName(), "."), $domain);
            if (!$force && $this->isDynDNS($sub . "." . $domain)) {
                continue;
            }

            foreach ($rr->getRrdatas() as $data) {
                $priority = "0";
                $data = trim($data, ".");

                if ($rr->getType() == "MX") {
                    $ex = explode(" ", $data, 2);
                    $priority = array_shift($ex);
                    $data = array_shift($ex);
                }

                $records[] = [
                    $sub,
                    $rr->getType(),
                    $data,
                    $rr->getTtl(),
                    $priority,
                    0,
                    $this->isDynDNS($sub . "." . $domain),
                ];
            }
        }

        return $records;
    }

    public function recordTypes($admin = false)
    {
        $a = array("MX", "A", "AAAA", "CNAME");
        if ($admin) {
            array_push($a, "NS", "SOA");
        }

        return $a;
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

    public function addRecord($domain, $record, $hidden = 0, $admin = true)
    {
        $service = $this->getServiceHandler("Google_Service_Dns");

        $body = new Google_Service_Dns_Change;
        $add = [];

        $name = $this->trimDomain($record[0], $domain);
        if ($name) {
            $name .= "." . $domain;
        }
        $name .= ".";

        $val = $record[2];
        if ($record[1] == "MX") {
            $val = $record[4] . " " . $val;
        }

        $rr = new Google_Service_Dns_ResourceRecordSet;
        $rr->setName($name);
        $rr->setType($record[1]);
        $rr->setRrdatas([$val]);
        $rr->setTtl($record[3]);
        array_push($add, $rr);

        $body->setAdditions($add);
        $service->changes->create($this->project, $this->getZoneName($domain), $body);

        return true;
    }

    public function editRecord($domain, $record, $new, $force = 0)
    {
        if (!$this->removeRecord($domain, $record)) {
            return false;
        }

        return $this->addRecord($domain, $new);
    }

    public function removeRecord($domain, $record, $force = 0)
    {
        $service = $this->getServiceHandler("Google_Service_Dns");

        try {
            $body = new Google_Service_Dns_Change;

            $record = $this->getZone($domain, $force)[$record];

            $name = $record[0];
            $name .= ($name ? "." : "") . "$domain.";

            $rr = new Google_Service_Dns_ResourceRecordSet;
            $rr->setName($name);
            $rr->setType($record[1]);
            $rr->setRrdatas([($record[1] == "MX" ? $record[4] . " " : "") . $record[2]]);
            $rr->setTtl($record[3]);

            $body->setDeletions([$rr]);
            $service->changes->create($this->project, $this->getZoneName($domain), $body);
            return true;
        } catch (Exception $ex) {
            return false;
        }

        return true;
    }

    public function removeZone($domain)
    {
        $service = $this->getServiceHandler("Google_Service_Dns");

        $body = new Google_Service_Dns_Change;
        $del = [];

        try {
            $service = $this->getServiceHandler("Google_Service_Dns");
            $zone = $service->resourceRecordSets->listResourceRecordSets($this->project, $this->getZoneName($domain), ["maxResults" => "1000"]);
        } catch (Exception $ex) {
            return false;
        }

        foreach ($zone->getRrsets() as $rr) {
            if (in_array($rr->getType(), ["NS", "SOA"])) {
                continue;
            }

            array_push($del, $rr);
        }

        if (count($del)) {
            $body->setDeletions($del);
            $service->changes->create($this->project, $this->getZoneName($domain), $body);
        }

        return empty($service->managedZones->delete($this->project, $this->getZoneName($domain)));
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
