<?php
require_once __DIR__ . "/httpRequest.php";
require_once __DIR__ . "/httpResponse.php";

class Vautron extends DomainRegistrar
{
    protected $short = "vautron";
    protected $name = "Vautron";
    protected $version = "1.3";

    public function getSettings()
    {
        return array(
            "api_url" => array("type" => "text", "name" => $this->getLang("url"), "default" => "https://backend.antagus.de/bdom"),
            "user_id" => array("type" => "text", "name" => $this->getLang("uid")),
        );
    }

    protected function cdata($data)
    {
        return '<![CDATA[' . $data . ']]>';
    }

    public function availibilityStatus($domain)
    {
        $ex = explode(".", $domain, 2);
        $obj = new Vautron\httpRequest($this->options->api_url);
        $res = new Vautron\httpResponse($obj->get("/domain/check/{$ex[1]}/{$ex[0]}/{$this->options->user_id}/"));
        $res = $res->body();

        if (empty($res->status)) {
            return null;
        }

        return strval($res->status) === "available";
    }

    private function convertTelephone($number)
    {
        preg_match_all("/\d+/", $number, $result);
        $number = implode("", $result[0]);
        if (substr($number, 0, 1) == "0" && substr($number, 1, 1) != "0") {
            return "+49." . ltrim($number, "0");
        }

        $number = "+" . ltrim($number, "0");
        return substr($number, 0, 3) . "." . substr($number, 3);
    }

    private function createNS($ns)
    {
        $obj = new Vautron\httpRequest($this->options->api_url);

        foreach ($ns as $s) {
            $ip = gethostbyname($s);
            if ($ip == $s) {
                continue;
            }

            $obj->put("/nameserver/create/$s/{$this->options->user_id}/", "<request><hostname>$s</hostname><ip>$ip</ip></request>");
        }
    }

    public function registerDomain($domain, $owner, $admin, $tech, $zone, $ns, $privacy = false)
    {
        foreach (["owner", "admin", "tech", "zone"] as $h) {
            $i = $$h;

            $ex = explode(" ", $i[3]);
            if (count($ex) < 2) {
                return "Hausnummer nicht gefunden ($h)";
            }
            $number = array_pop($ex);
            $street = implode(" ", $ex);

            $body = "<request>
                <type>" . (empty($i[2]) ? "PERS" : "ORG") . "</type>
                <sex>MALE</sex>
                <first-name>" . $this->cdata($i[0]) . "</first-name>
                <last-name>" . $this->cdata($i[1]) . "</last-name>
                <organisation>" . $this->cdata($i[2]) . "</organisation>
                <street>" . $this->cdata($street) . "</street>
                <number>" . $this->cdata($number) . "</number>
                <postcode>" . $this->cdata($i[5]) . "</postcode>
                <city>" . $this->cdata($i[6]) . "</city>
                <country>" . $this->cdata($i[4]) . "</country>
                <phone>" . $this->cdata($this->convertTelephone($i[7])) . "</phone>
                <fax>" . $this->cdata($this->convertTelephone($i[8])) . "</fax>
                <email>" . $this->cdata($i[9]) . "</email>
                <protection>YES</protection>
            </request>";

            $obj = new Vautron\httpRequest($this->options->api_url);
            $res = new Vautron\httpResponse($obj->put("/contact/create/-/{$this->options->user_id}/", $body));
            $res = $res->body();

            if (empty($res->handle)) {
                if (!isset($res->umsg) || !isset($res->error) || !isset($res->error->code) || $res->error->code != "408") {
                    return "Handle $h konnte nicht angelegt werden: " . $res->umsg;
                }
                $ex = explode(" ", $res->umsg);
                $$h = array_pop($ex);
            } else {
                $$h = $res->handle;
            }
        }

        $this->createNS($ns);

        $nsXml = "";
        foreach ($ns as $s) {
            if (!empty($s)) {
                $nsXml .= "<ns><hostname>$s</hostname></ns>";
            }
        }

        $ex = explode(".", $domain, 2);

        $body = "<request>
        <sld>{$ex[0]}</sld>
        <contact-ids>
            <owner>$owner</owner>
            <admin>$admin</admin>
            <tech>$tech</tech>
            <zone>$zone</zone>
        </contact-ids>
        <nameservers>
            $nsXml
        </nameservers>
        </request>";

        $res = new Vautron\httpResponse($obj->put("/domain/create/{$ex[1]}/{$ex[0]}/{$this->options->user_id}/", $body));
        $res = $res->body();
        return isset($res->umsg) ? strval($res->umsg) : true;
    }

    public function transferDomain($domain, $owner, $admin, $tech, $zone, $authCode, $ns, $privacy = false)
    {
        foreach (["owner", "admin", "tech", "zone"] as $h) {
            $i = $$h;

            $ex = explode(" ", $i[3]);
            if (count($ex) < 2) {
                return "Hausnummer nicht gefunden ($h)";
            }
            $number = array_pop($ex);
            $street = implode(" ", $ex);

            $body = "<request>
                <type>" . (empty($i[2]) ? "PERS" : "ORG") . "</type>
                <sex>MALE</sex>
                <first-name>" . $this->cdata($i[0]) . "</first-name>
                <last-name>" . $this->cdata($i[1]) . "</last-name>
                <organisation>" . $this->cdata($i[2]) . "</organisation>
                <street>" . $this->cdata($street) . "</street>
                <number>" . $this->cdata($number) . "</number>
                <postcode>" . $this->cdata($i[5]) . "</postcode>
                <city>" . $this->cdata($i[6]) . "</city>
                <country>" . $this->cdata($i[4]) . "</country>
                <phone>" . $this->cdata($this->convertTelephone($i[7])) . "</phone>
                <fax>" . $this->cdata($this->convertTelephone($i[8])) . "</fax>
                <email>" . $this->cdata($i[9]) . "</email>
                <protection>YES</protection>
            </request>";

            $obj = new Vautron\httpRequest($this->options->api_url);
            $res = new Vautron\httpResponse($obj->put("/contact/create/-/{$this->options->user_id}/", $body));
            $res = $res->body();

            if (empty($res->handle)) {
                if (!isset($res->umsg) || !isset($res->error) || !isset($res->error->code) || $res->error->code != "408") {
                    return "Handle $h konnte nicht angelegt werden: " . $res->umsg;
                }
                $ex = explode(" ", $res->umsg);
                $$h = array_pop($ex);
            } else {
                $$h = $res->handle;
            }
        }

        $this->createNS($ns);

        $nsXml = "";
        foreach ($ns as $s) {
            if (!empty($s)) {
                $nsXml .= "<ns><hostname>$s</hostname></ns>";
            }
        }

        $ex = explode(".", $domain, 2);

        $body = "<request>
        <sld>{$ex[0]}</sld>
        <contact-ids>
            <owner>$owner</owner>
            <admin>$admin</admin>
            <tech>$tech</tech>
            <zone>$zone</zone>
        </contact-ids>
        <nameservers>
            $nsXml
        </nameservers>
        <password>$authCode</password>
        </request>";

        $res = new Vautron\httpResponse($obj->put("/domain/transfer-in/{$ex[1]}/{$ex[0]}/{$this->options->user_id}/", $body));
        $res = $res->body();
        return isset($res->umsg) ? strval($res->umsg) : true;
    }

    public function deleteDomain($domain, $transit = 0)
    {
        $obj = new Vautron\httpRequest($this->options->api_url);
        $ex = explode(".", $domain, 2);

        if (!$transit) {
            $body = "<request>
                <sld>" . $ex[0] . "</sld>
            </request>";

            $res = new Vautron\httpResponse($obj->delete("/domain/delete/{$ex[1]}/{$ex[0]}/{$this->options->user_id}/", $body));
        } else {
            $body = "<request>
                <sld>" . $ex[0] . "</sld>
                <disconnect>" . ($transit === 1 ? "NO" : "YES") . "</disconnect>
            </request>";

            $res = new Vautron\httpResponse($obj->post("/domain/hold/{$ex[1]}/{$ex[0]}/{$this->options->user_id}/", $body));
        }

        $res = (array) $res->body();

        if (!isset($res[0]) || isset($res['umsg'])) {
            return false;
        }
        return true;
    }

    public function getAuthCode($domain)
    {
        $obj = new Vautron\httpRequest($this->options->api_url);
        $ex = explode(".", $domain, 2);

        if (in_array($ex[1], ["de", "eu"])) {
            $body = "<request>
			    <sld>{$ex[0]}</sld>
            </request>";

            $res = new Vautron\httpResponse($obj->post("/domain/set-auth/{$ex[1]}/{$ex[0]}/{$this->options->user_id}/", $body));
            $res = (array) $res->body();

            if (!is_numeric($res[0]) && !isset($res['umsg'])) {
                return "AUTH:" . strval($res[0]);
            }

            return false;
        } else {
            $res = new Vautron\httpResponse($obj->get("/domain/status/{$ex[1]}/{$ex[0]}/{$this->options->user_id}/"));
            $res = $res->body();
            return $res->password ? "AUTH:" . strval($res->password) : false;
        }
    }

    public function changeNameserver($domain, $ns)
    {
        $obj = new Vautron\httpRequest($this->options->api_url);
        $ex = explode(".", $domain, 2);

        $res = new Vautron\httpResponse($obj->get("/domain/status/{$ex[1]}/{$ex[0]}/{$this->options->user_id}/"));
        $res = $res->body();

        foreach (["admin", "tech", "owner", "zone"] as $h) {
            if (empty($res->$h)) {
                return false;
            }

            $$h = $res->$h;
        }

        $this->createNS($ns);

        $nsXml = "";
        foreach ($ns as $s) {
            if (!empty($s)) {
                $nsXml .= "<ns><hostname>$s</hostname></ns>";
            }
        }

        $body = "<request>
        <sld>{$ex[0]}</sld>
        <contact-ids>
            <owner>$owner</owner>
            <admin>$admin</admin>
            <tech>$tech</tech>
            <zone>$zone</zone>
        </contact-ids>
        <nameservers>
            $nsXml
        </nameservers>
        </request>";

        $res = new Vautron\httpResponse($obj->post("/domain/update/{$ex[1]}/{$ex[0]}/{$this->options->user_id}/", $body));
        $res = (array) $res->body();

        if (empty($res[0]) || isset($res["umsg"])) {
            return false;
        }

        return true;
    }

    public function changeContact($domain, $owner, $admin, $tech, $zone)
    {
        foreach (["owner", "admin", "tech", "zone"] as $h) {
            $i = $$h;

            $ex = explode(" ", $i[3]);
            if (count($ex) < 2) {
                return "Hausnummer nicht gefunden ($h)";
            }
            $number = array_pop($ex);
            $street = implode(" ", $ex);

            $body = "<request>
                <type>" . (empty($i[2]) ? "PERS" : "ORG") . "</type>
                <sex>MALE</sex>
                <first-name>" . $this->cdata($i[0]) . "</first-name>
                <last-name>" . $this->cdata($i[1]) . "</last-name>
                <organisation>" . $this->cdata($i[2]) . "</organisation>
                <street>" . $this->cdata($street) . "</street>
                <number>" . $this->cdata($number) . "</number>
                <postcode>" . $this->cdata($i[5]) . "</postcode>
                <city>" . $this->cdata($i[6]) . "</city>
                <country>" . $this->cdata($i[4]) . "</country>
                <phone>" . $this->cdata($this->convertTelephone($i[7])) . "</phone>
                <fax>" . $this->cdata($this->convertTelephone($i[8])) . "</fax>
                <email>" . $this->cdata($i[9]) . "</email>
                <protection>YES</protection>
            </request>";

            $obj = new Vautron\httpRequest($this->options->api_url);
            $res = new Vautron\httpResponse($obj->put("/contact/create/-/{$this->options->user_id}/", $body));
            $res = $res->body();

            if (empty($res->handle)) {
                if (!isset($res->umsg) || !isset($res->error) || !isset($res->error->code) || $res->error->code != "408") {
                    return "Handle $h konnte nicht angelegt werden: " . $res->umsg;
                }
                $ex = explode(" ", $res->umsg);
                $$h = array_pop($ex);
            } else {
                $$h = $res->handle;
            }
        }

        $ex = explode(".", $domain, 2);
        $res = new Vautron\httpResponse($obj->get("/domain/status/{$ex[1]}/{$ex[0]}/{$this->options->user_id}/"));
        $res = $res->body();

        $nsXml = "";
        foreach ($res->nameservers as $s) {
            if (!empty($s)) {
                $nsXml .= "<ns><hostname>$s</hostname></ns>";
            }
        }

        $body = "<request>
        <sld>{$ex[0]}</sld>
        <contact-ids>
            <owner>$owner</owner>
            <admin>$admin</admin>
            <tech>$tech</tech>
            <zone>$zone</zone>
        </contact-ids>
        <nameservers>
            $nsXml
        </nameservers>
        </request>";

        $res = new Vautron\httpResponse($obj->post("/domain/update/{$ex[1]}/{$ex[0]}/{$this->options->user_id}/"));
        $res = (array) $res->body();

        if (empty($res[0]) || isset($res["umsg"])) {
            return false;
        }

        return true;
    }

    public function syncDomain($domain, $kkSync = false)
    {
        $ex = explode(".", $domain, 2);
        $obj = new Vautron\httpRequest($this->options->api_url);
        $res = new Vautron\httpResponse($obj->get("/domain/status/{$ex[1]}/{$ex[0]}/{$this->options->user_id}/"));
        $res = $res->body();

        if (strval($res->status ?? "") != "OK") {
            if ($kkSync) {
                return [
                    "status" => "waiting_kk",
                ];
            }

            return ($res->umsg ?? "") == "Domain belongs to different user or does not exist" ? false : null;
        }

        $info = array(
            "expiration" => explode(" ", $res->expiration)[0],
            "status" => true,
        );

        $ns = [];
        if (is_object($res->nameservers->nameserver)) {
            foreach ($res->nameservers->nameserver as $dns) {
                if (!empty($dns)) {
                    array_push($ns, strval($dns));
                }
            }
        }

        if (count($ns) >= 2) {
            $info["ns"] = $ns;
        }

        foreach ([
            "owner" => "owner",
            "admin" => "admin",
            "tech" => "tech",
            "zone" => "zone",
        ] as $vt => $sd) {
            if (empty($res->$vt)) {
                continue;
            }

            $obj = new Vautron\httpRequest($this->options->api_url);
            $res2 = new Vautron\httpResponse($obj->get("/contact/status/" . strval($res->$vt) . "/{$this->options->user_id}/"));
            $res2 = $res2->body();

            if (empty($res2->active)) {
                continue;
            }

            $info[$sd] = [
                strval($res2->{'first-name'}),
                strval($res2->{'last-name'}),
                strval($res2->organisation),
                strval($res2->street) . " " . strval($res2->number),
                $res2->country == "Germany" ? "DE" : strval($res2->country),
                strval($res2->postcode),
                strval($res2->city),
                str_replace("-", "", strval($res2->phone)),
                str_replace("-", "", strval($res2->fax)),
                strval($res2->email),
                "",
            ];
        }

        return $info;
    }

    public function changeValues($domain, $status = false, $renew = true, $privacy = false)
    {
        $ex = explode(".", $domain, 2);
        $obj = new Vautron\httpRequest($this->options->api_url);

        if ($renew) {
            $res = new Vautron\httpResponse($obj->get("/task/scheduled/-/-/-/-/{$this->options->user_id}/"));
            $res = $res->body();

            foreach ($res->response as $r) {
                if ($r->opcode == "delete" && $r->name == $domain) {
                    $obj->post("/task/delete/-/-/-/-/{$this->options->user_id}/", "<request><delete><task_id>{$r->task_id}</task_id></delete></request>");
                }
            }
        } else {
            $obj->post("/domain/delete/{$ex[1]}/{$ex[0]}/{$this->options->user_id}/", "<request><sld>{$ex[0]}</sld><exec-date>" . $this->syncDomain($domain)['expiration'] . "</exec-date></request>");
        }

        return true;
    }
}
