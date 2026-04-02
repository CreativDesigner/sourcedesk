<?php

class AutoDNS extends DomainRegistrar
{
    protected $short = "autodns";
    protected $name = "AutoDNS";
    protected $version = "1.2";

    public function getSettings()
    {
        return array(
            "api_url" => array("type" => "text", "name" => $this->getLang("url"), "default" => "https://gateway.autodns.com"),
            "api_user" => array("type" => "text", "name" => $this->getLang("username")),
            "api_password" => array("type" => "password", "name" => $this->getLang("password")),
            "api_context" => array("type" => "text", "name" => $this->getLang("context"), "default" => "4"),
            "ssl_url" => array("type" => "text", "name" => $this->getLang("SSL") . ": " . $this->getLang("url"), "default" => "https://gateway.autodns.com"),
            "ssl_user" => array("type" => "text", "name" => $this->getLang("SSL") . ": " . $this->getLang("username")),
            "ssl_password" => array("type" => "password", "name" => $this->getLang("SSL") . ": " . $this->getLang("password")),
            "ssl_context" => array("type" => "text", "name" => $this->getLang("SSL") . ": " . $this->getLang("context"), "default" => "9"),
            "ssl_contact" => array("type" => "text", "name" => $this->getLang("SSL") . ": " . $this->getLang("cid")),
            "ssl_disabled" => array("type" => "text", "name" => $this->getLang("NQ"), "hint" => $this->getLang("nqh")),
            "whoisproxy" => array("type" => "checkbox", "name" => $this->getLang("WP"), "hint" => $this->getLang("wph")),
        );
    }

    public function getUserDefined()
    {
        return array(
            "subuser" => array("type" => "text", "name" => $this->getLang("subuser"), "placeholder" => $this->getLang("subuserh")),
        );
    }

    public function hasAvailibilityStatus()
    {
        return boolval($this->options->whoisproxy ?? false);
    }

    public function subuserXml()
    {
        if (!empty($this->udd["subuser"])) {
            return "<owner><user>{$this->udd["subuser"]}</user><context></context></owner>";
        }
        return "";
    }

    public function domainNumber()
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->options->api_user . '</user>
				 		<password>' . $this->options->api_password . '</password>
				 		<context>' . $this->options->api_context . '</context>
				 	</auth>
				 	<language>de</language>
				 	<task>
				 		<code>0105</code>
						<view>
							<offset>0</offset>
							<limit>50000</limit>
							<children></children>
						</view>
				 	</task>
				</request>';

        $ch = curl_init($this->options->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $xml = new SimpleXMLElement($res);
        return intval($xml->result->data->summary);
    }

    private function convertTelephone($number)
    {
        preg_match_all("/\d+/", $number, $result);
        $number = implode("", $result[0]);

        if (substr($number, 0, 1) == "0" && substr($number, 1, 1) != "0") {
            return "+49." . ltrim($number, "0");
        }

        return "+" . substr(ltrim($number, "0"), 0, 2) . "." . substr(ltrim($number, "0"), 2);
    }

    public function registerDomain($domain, $owner, $admin, $tech, $zone, $ns, $privacy = false)
    {
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
            $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);
            $i[2] = str_replace(array("&"), " ", $i[2]);

            foreach ($i as &$v) {
                $v = ($v);
            }

            $$t = '<alias></alias>
					<type>' . ($t == "tech" || $t == "zone" ? "ROLE" : ($t == "admin" ? "PERSON" : (empty($i[2]) ? "PERSON" : "ORG"))) . '</type>
					<fname>' . $i[0] . '</fname>
					<lname>' . $i[1] . '</lname>
					<title></title>
					<organization>' . $i[2] . '</organization>
					<address>' . str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]) . '</address>
					<pcode>' . $i[5] . '</pcode>
					<city>' . $i[6] . '</city>
					<state></state>
					<country>' . $i[4] . '</country>
					<phone>' . str_replace(".", "-", $this->convertTelephone($i[7])) . '</phone>
					<fax>' . str_replace(".", "-", $this->convertTelephone($i[8])) . '</fax>
					<email>' . $i[9] . '</email>
					<sip></sip>
					<protection>B</protection>
					<remarks></remarks>';
        }

        $nsXml = "";
        foreach ($ns as $n) {
            if (!empty($n)) {
                $nsXml .= "<nserver><name>$n</name></nserver>";
            }
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->options->api_user . '</user>
				 		<password>' . $this->options->api_password . '</password>
				 		<context>' . $this->options->api_context . '</context>
					</auth>
					{$this->subuserXml()}
				 	<language>de</language>
				 	<task>
				 		<code>0101</code>
						<domain>
							<name>' . $domain . '</name>
						 	<ownerc>' . $owner . '</ownerc>
						 	<adminc>' . $admin . '</adminc>
						 	<techc>' . $tech . '</techc>
						 	<zonec>' . $zone . '</zonec>
						 	' . $nsXml . '
							<confirm_order>1</confirm_order>
							<use_trustee>0</use_trustee>
							<use_privacy>' . ($privacy ? "true" : "false") . '</use_privacy>
 							<use_auto_dnssec>false</use_auto_dnssec>
						</domain>
				 	</task>
				</request>';

        $ch = curl_init($this->options->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        $xml = new SimpleXMLElement($res);
        $res2 = $xml->result->status->code;
        if (empty($res2) || !in_array(substr($res2, 0, 1), array("N", "S"))) {
            return !empty($xml->msg->status->text) ? (String) $xml->msg->status->text : $res;
        }

        return true;
    }

    public function transferDomain($domain, $owner, $admin, $tech, $zone, $authCode, $ns, $privacy = false)
    {
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
            $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);
            $i[2] = str_replace(array("&"), " ", $i[2]);

            foreach ($i as &$v) {
                $v = ($v);
            }

            $$t = '<alias></alias>
					<type>' . ($t == "tech" || $t == "zone" ? "ROLE" : ($t == "admin" ? "PERSON" : (empty($i[2]) ? "PERSON" : "ORG"))) . '</type>
					<fname>' . $i[0] . '</fname>
					<lname>' . $i[1] . '</lname>
					<title></title>
					<organization>' . $i[2] . '</organization>
					<address>' . str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]) . '</address>
					<pcode>' . $i[5] . '</pcode>
					<city>' . $i[6] . '</city>
					<state></state>
					<country>' . $i[4] . '</country>
					<phone>' . str_replace(".", "-", $this->convertTelephone($i[7])) . '</phone>
					<fax>' . str_replace(".", "-", $this->convertTelephone($i[8])) . '</fax>
					<email>' . $i[9] . '</email>
					<sip></sip>
					<protection>B</protection>
					<remarks></remarks>';
        }

        $nsXml = "";
        foreach ($ns as $n) {
            if (!empty($n)) {
                $nsXml .= "<nserver><name>$n</name></nserver>";
            }
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->options->api_user . '</user>
				 		<password>' . $this->options->api_password . '</password>
				 		<context>' . $this->options->api_context . '</context>
					</auth>
					{$this->subuserXml()}
				 	<language>de</language>
				 	<task>
				 		<code>0104</code>
						<domain>
							<name>' . $domain . '</name>
							<authinfo><![CDATA[' . $authCode . ']]></authinfo>
						 	<ownerc>' . $owner . '</ownerc>
						 	<adminc>' . $admin . '</adminc>
						 	<techc>' . $tech . '</techc>
						 	<zonec>' . $zone . '</zonec>
						 	' . $nsXml . '
							<confirm_order>1</confirm_order>
							<use_trustee>0</use_trustee>
							<use_privacy>' . ($privacy ? "true" : "false") . '</use_privacy>
 							<use_auto_dnssec>false</use_auto_dnssec>
 							<confirm_owner_consent>1</confirm_owner_consent>
						</domain>
				 	</task>
				</request>';

        $ch = curl_init($this->options->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        $xml = new SimpleXMLElement($res);
        $res2 = $xml->result->status->code;
        if (empty($res2) || !in_array(substr($res2, 0, 1), array("N", "S"))) {
            return !empty($xml->msg->status->text) ? (String) $xml->msg->status->text : $res;
        }

        return true;
    }

    public function deleteDomain($domain, $transit = 0)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->options->api_user . '</user>
				 		<password>' . $this->options->api_password . '</password>
				 		<context>' . $this->options->api_context . '</context>
					</auth>
					{$this->subuserXml()}
				 	<language>de</language>
				 	<task>
				 		<code>0103</code>
						<domain>
							<name>' . $domain . '</name>
							<transit>' . ($transit > 0 ? "1" : "0") . '</transit>
							<disconnect>' . ($transit == 2 ? "1" : "0") . '</disconnect>
						</domain>
				 	</task>
				</request>';

        $ch = curl_init($this->options->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        $xml = new SimpleXMLElement($res);
        $res = $xml->result->status->code;
        if (empty($res) || !in_array(substr($res, 0, 1), array("N", "S"))) {
            return !empty($xml->msg->status->text) ? (String) $xml->msg->status->text : $res;
        }

        return true;
    }

    public function getAuthCode($domain)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->options->api_user . '</user>
				 		<password>' . $this->options->api_password . '</password>
				 		<context>' . $this->options->api_context . '</context>
					</auth>
					{$this->subuserXml()}
				 	<language>de</language>
				 	<task>
				 		<code>0105</code>
						<domain>
						 	<name>' . $domain . '</name>
						</domain>
				 	</task>
				</request>';

        $ch = curl_init($this->options->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        $xml = new SimpleXMLElement($res);
        if (!empty($xml->result->data->domain->authinfo)) {
            return "AUTH:" . $xml->result->data->domain->authinfo;
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->options->api_user . '</user>
				 		<password>' . $this->options->api_password . '</password>
				 		<context>' . $this->options->api_context . '</context>
					</auth>
					{$this->subuserXml()}
				 	<language>de</language>
				 	<task>
				 		<code>0113001</code>
						<domain>
							<name>' . $domain . '</name>
						</domain>
				 	</task>
				</request>';

        $ch = curl_init($this->options->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        $xml = new SimpleXMLElement($res);
        $res = $xml->result->status->object;
        if (empty($res) || $res->type != "authinfo" || empty($res->value)) {
            return !empty($xml->msg->status->text) ? (String) $xml->msg->status->text : $res;
        }

        return "AUTH:" . $res->value;
    }

    public function changeNameserver($domain, $ns)
    {
        $nsXml = "";
        foreach ($ns as $n) {
            if (!empty($n)) {
                $nsXml .= "<nserver><name>$n</name></nserver>";
            }
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->options->api_user . '</user>
				 		<password>' . $this->options->api_password . '</password>
				 		<context>' . $this->options->api_context . '</context>
					</auth>
					{$this->subuserXml()}
				 	<language>de</language>
				 	<task>
				 		<code>0102</code>
						<domain>
						 	<name>' . $domain . '</name>
						 	' . $nsXml . '
						</domain>
				 	</task>
				</request>';

        $ch = curl_init($this->options->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        $xml = new SimpleXMLElement($res);
        $res = $xml->result->status->code;
        if (empty($res) || !in_array(substr($res, 0, 1), array("N", "S"))) {
            return !empty($xml->msg->status->text) ? (String) $xml->msg->status->text : $res;
        }

        return true;
    }

    public function changeContact($domain, $owner, $admin, $tech, $zone)
    {
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
            $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);
            $i[2] = str_replace(array("&"), " ", $i[2]);

            foreach ($i as &$v) {
                $v = ($v);
            }

            $$t = '<alias></alias>
					<type>' . ($t == "tech" || $t == "zone" ? "ROLE" : ($t == "admin" ? "PERSON" : (empty($i[2]) ? "PERSON" : "ORG"))) . '</type>
					<fname>' . $i[0] . '</fname>
					<lname>' . $i[1] . '</lname>
					<title></title>
					<organization>' . $i[2] . '</organization>
					<address>' . str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]) . '</address>
					<pcode>' . $i[5] . '</pcode>
					<city>' . $i[6] . '</city>
					<state></state>
					<country>' . $i[4] . '</country>
					<phone>' . str_replace(".", "-", $this->convertTelephone($i[7])) . '</phone>
					<fax>' . str_replace(".", "-", $this->convertTelephone($i[8])) . '</fax>
					<email>' . $i[9] . '</email>
					<sip></sip>
					<protection>B</protection>
					<remarks>' . $i[10] . '</remarks>';
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->options->api_user . '</user>
				 		<password>' . $this->options->api_password . '</password>
				 		<context>' . $this->options->api_context . '</context>
					</auth>
					{$this->subuserXml()}
				 	<language>de</language>
				 	<task>
				 		<code>0102</code>
						<domain>
						 	<name>' . $domain . '</name>
						 	<ownerc>' . $owner . '</ownerc>
						 	<adminc>' . $admin . '</adminc>
						 	<techc>' . $tech . '</techc>
						 	<zonec>' . $zone . '</zonec>
							 <confirm_owner_consent>1</confirm_owner_consent>
						</domain>
				 	</task>
				</request>';

        $ch = curl_init($this->options->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        $xml = new SimpleXMLElement($res);
        $res = $xml->result->status->code;
        if (empty($res) || !in_array(substr($res, 0, 1), array("N", "S"))) {
            return !empty($xml->msg->status->text) ? (String) $xml->msg->status->text : $res;
        }

        return true;
    }

    public function changeAll($domain, $owner, $admin, $tech, $zone, $ns, $status = false, $renew = true, $privacy = false)
    {
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            if (count($i) == 0) {
                $r = $this->syncDomain($domain);
                if (empty($r) || !is_array($r) || empty($r['ownerc'])) {
                    return !empty($xml->msg->status->text) ? (String) $xml->msg->status->text : $res;
                }

                $$t = $r['ownerc'];
            } else {
                $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
                $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);
                $i[2] = str_replace(array("&"), " ", $i[2]);

                foreach ($i as &$v) {
                    $v = ($v);
                }

                $$t = '<alias></alias>
					<type>' . ($t == "tech" || $t == "zone" ? "ROLE" : ($t == "admin" ? "PERSON" : (empty($i[2]) ? "PERSON" : "ORG"))) . '</type>
					<fname>' . $i[0] . '</fname>
					<lname>' . $i[1] . '</lname>
					<title></title>
					<organization>' . $i[2] . '</organization>
					<address>' . str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3]) . '</address>
					<pcode>' . $i[5] . '</pcode>
					<city>' . $i[6] . '</city>
					<state></state>
					<country>' . $i[4] . '</country>
					<phone>' . str_replace(".", "-", $this->convertTelephone($i[7])) . '</phone>
					<fax>' . str_replace(".", "-", $this->convertTelephone($i[8])) . '</fax>
					<email>' . $i[9] . '</email>
					<sip></sip>
					<protection>B</protection>
					<remarks>' . $i[10] . '</remarks>';
            }
        }

        $nsXml = "";
        foreach ($ns as $n) {
            if (!empty($n)) {
                $nsXml .= "<nserver><name>$n</name></nserver>";
            }
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->options->api_user . '</user>
				 		<password>' . $this->options->api_password . '</password>
				 		<context>' . $this->options->api_context . '</context>
					</auth>
					{$this->subuserXml()}
				 	<language>de</language>
				 	<task>
				 		<code>0102</code>
						<domain>
						 	<name>' . $domain . '</name>
						 	<ownerc>' . $owner . '</ownerc>
						 	<adminc>' . $admin . '</adminc>
						 	<techc>' . $tech . '</techc>
						 	<zonec>' . $zone . '</zonec>
						 	<registry_status>' . ($status ? "lock" : "active") . '</registry_status>
						 	<use_privacy>' . ($privacy ? "true" : "false") . '</use_privacy>
						 	<autorenew>' . ($renew ? "true" : "false") . '</autorenew>
						 	' . $nsXml . '
							<confirm_owner_consent>1</confirm_owner_consent>
						</domain>
				 	</task>
				</request>';

        $ch = curl_init($this->options->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        $xml = new SimpleXMLElement($res);
        $res2 = $xml->result->status->code;
        if (empty($res2) || !in_array(substr($res2, 0, 1), array("N", "S"))) {
            return $res;
        }

        $this->setRenew($domain, $renew);

        return true;
    }

    public function setRenew($domain, $renew = true)
    {
        if ($renew) {
            $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->options->api_user . '</user>
				 		<password>' . $this->options->api_password . '</password>
				 		<context>' . $this->options->api_context . '</context>
					</auth>
					{$this->subuserXml()}
				 	<language>de</language>
				 	<task>
				 		<code>0103103</code>
						<cancelation>
						 	<domain>' . $domain . '</domain>
						 	<execdate>expire</execdate>
						</cancelation>
				 	</task>
				</request>';

            $ch = curl_init($this->options->api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            $res = curl_exec($ch);
            curl_close($ch);

            $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);
        } else {
            $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->options->api_user . '</user>
				 		<password>' . $this->options->api_password . '</password>
				 		<context>' . $this->options->api_context . '</context>
					</auth>
					{$this->subuserXml()}
				 	<language>de</language>
				 	<task>
				 		<code>0103101</code>
						<cancelation>
						 	<domain>' . $domain . '</domain>
						 	<type>delete</type>
						 	<execdate>expire</execdate>
						</cancelation>
				 	</task>
				</request>';

            $ch = curl_init($this->options->api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            $res = curl_exec($ch);
            curl_close($ch);

            $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);
        }

        return true;
    }

    public function massDomainSync(array $domains)
    {
        $domXml = "";
        foreach ($domains as $domain) {
            $domXml .= '<or>
            <key>name</key>
            <operator>eq</operator>
            <value>' . $domain . '</value>
        </or>';
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->options->api_user . '</user>
				 		<password>' . $this->options->api_password . '</password>
				 		<context>' . $this->options->api_context . '</context>
					</auth>
					{$this->subuserXml()}
				 	<language>de</language>
				 	<task>
				 		<code>0105</code>
                        <where>
                            ' . $domXml . '
                        </where>
                        <key>ownerc</key>
                        <key>adminc</key>
                        <key>techc</key>
                        <key>zonec</key>
                        <key>nserver</key>
                        <key>expire</key>
                        <key>payable</key>
                        <key>created</key>
                        <key>cancelation</key>
                        <key>status</key>
                        <key>registry_status</key>
                        <key>use_privacy</key>
                        <show_handle_details>all</show_handle_details>
				 	</task>
				</request>';

        $ch = curl_init($this->options->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        if (!$res) {
            return false;
        }

        @$xml = new SimpleXMLElement($res);

        if (!$xml || empty($xml->result->status->code) || substr($xml->result->status->code, 0, 1) != "S") {
            return false;
        }

        $ret = [];

        $res = [];
        foreach ($xml->result->data->domain as $obj) {
            $res[(String) $obj->name] = $obj;
        }

        foreach ($domains as $id => $domain) {
            if (!array_key_exists($domain, $res)) {
                $ret[$id] = ["status" => false];
                continue;
            }

            $info = $res[$domain];

            $ns = array();
            foreach ($info->nserver as $sns) {
                if (!empty($sns)) {
                    array_push($ns, trim(strval($sns->name)));
                }
            }

            $expiration = date("Y-m-d", strtotime(max((string) $info->expire, (string) $info->payable)));

            // Switch for monthly .de domains
            $ex = explode(".", $domain);
            $sld = array_shift($ex);
            $tld = implode(".", $ex);

            if ($tld == "de") {
                $current = date("Y-m-d", strtotime((string) $info->created));
                while ($current < $expiration) {
                    $current = date("Y-m-d", strtotime("+1 year", strtotime($current)));
                }

                $expiration = $current;
            }

            $ret[$id] = array(
                "auto_renew" => empty($info->cancelation),
                "expiration" => $expiration,
                "status" => (String) $info->status == "SUCCESS",
                "transfer_lock" => $info->registry_status == "LOCK",
                "ownerc" => (String) $info->ownerc->id,
                "privacy" => $info->use_privacy == "1" || $info->use_privacy == "true",
                "ns" => $ns,
            );
        }

        return $ret;
    }

    public function getBatchSize()
    {
        return 50;
    }

    public function syncDomain($domain, $kkSync = false)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->options->api_user . '</user>
				 		<password>' . $this->options->api_password . '</password>
				 		<context>' . $this->options->api_context . '</context>
					</auth>
					{$this->subuserXml()}
				 	<language>de</language>
				 	<task>
				 		<code>0105</code>
						<domain>
						 	<name>' . $domain . '</name>
                        </domain>
                        <show_handle_details>all</show_handle_details>
				 	</task>
				</request>';

        $ch = curl_init($this->options->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        if (!$res) {
            return "Connection failure";
        }

        @$xml = new SimpleXMLElement($res);

        if (!$xml || empty($xml->result->status->code)) {
            return null;
        }

        if ($xml->result->status->code == "E0105") {
            if ($kkSync) {
                return ["status" => "waiting_kk"];
            }

            return false;
        }

        if (substr($xml->result->status->code, 0, 1) != "S") {
            return !empty($xml->msg->status->text) ? (String) $xml->msg->status->text : $res;
        }

        if (empty($xml->result->data->domain->nserver)) {
            return "XML incomplete";
        }

        $ns = array();
        foreach ($xml->result->data->domain->nserver as $sns) {
            if (!empty($sns)) {
                array_push($ns, trim(strval($sns->name)));
            }
        }

        foreach (["owner", "admin", "tech", "zone"] as $h) {
            $hv = $h . "c";
            $xml3 = $xml->result->data->domain->$hv;

            $$h = [
                $xml3->fname,
                $xml3->lname,
                $xml3->organization,
                $xml3->address,
                $xml3->country,
                $xml3->pcode,
                $xml3->city,
                str_replace("-", "", implode(".", explode("-", $xml3->phone, 2))),
                str_replace("-", "", implode(".", explode("-", $xml3->fax, 2))),
                $xml3->email,
                $xml3->remarks,
            ];

            foreach ($$h as &$v) {
                $v = strval($v);
            }
        }

        $expiration = date("Y-m-d", strtotime(max((string) $xml->result->data->domain->expire, (string) $xml->result->data->domain->payable)));

        // Switch for monthly .de domains
        $ex = explode(".", $domain);
        $sld = array_shift($ex);
        $tld = implode(".", $ex);

        if ($tld == "de") {
            $current = date("Y-m-d", strtotime((string) $xml->result->data->domain->created));
            while ($current < $expiration) {
                $current = date("Y-m-d", strtotime("+1 year", strtotime($current)));
            }

            $expiration = $current;
        }

        return array(
            "auto_renew" => empty($xml->result->data->domain->cancelation),
            "expiration" => $expiration,
            "status" => (String) $xml->result->data->domain->status == "SUCCESS",
            "transfer_lock" => $xml->result->data->domain->registry_status == "LOCK",
            "ownerc" => (String) $xml->result->data->domain->ownerc->id,
            "privacy" => $xml->result->data->domain->use_privacy == "1" || $xml->result->data->domain->use_privacy == "true",
            "ns" => $ns,
            "owner" => $owner,
            "admin" => $admin,
            "tech" => $tech,
            "zone" => $zone,
        );
    }

    public function allowedSSL($uid)
    {
        $ex = explode(",", $this->options->ssl_disabled);
        foreach ($ex as &$v) {
            $v = trim($v);
        }

        return !in_array($uid, $ex);
    }

    public function freeSSL($domain, $csr)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
		<request>
			<auth>
		 		<user>' . $this->options->ssl_user . '</user>
		 		<password>' . $this->options->ssl_password . '</password>
		 		<context>' . $this->options->ssl_context . '</context>
		 	</auth>
		 	<language>de</language>
		 	<task>
		 		<code>400110</code>
				<certificate_request>
				 	<product>BASIC_SSL</product>
				 	<plain><![CDATA[' . $csr . ']]></plain>
				</certificate_request>
		 	</task>
		</request>';

        $ch = curl_init($this->options->ssl_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        $xml = new SimpleXMLElement($res);
        $xml = $xml->result->data->certificate_request->authentication;
        foreach ($xml as $a) {
            if ($a->method == "DNS") {
                $dns = $a->dns;
            }
        }

        if (empty($dns)) {
            return false;
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>
		<request>
			<auth>
		 		<user>' . $this->options->ssl_user . '</user>
		 		<password>' . $this->options->ssl_password . '</password>
		 		<context>' . $this->options->ssl_context . '</context>
		 	</auth>
		 	<language>de</language>
		 	<task>
		 		<code>400101</code>
				<certificate>
				 	<admin><id>' . $this->options->ssl_contact . '</id></admin>
				 	<technical><id>' . $this->options->ssl_contact . '</id></technical>
				 	<name>' . ($domain) . '</name>
				 	<product>BASIC_SSL</product>
				 	<lifetime>12</lifetime>
				 	<software>APACHESSL</software>
				 	<csr><![CDATA[' . $csr . ']]></csr>
					<authentication>
						<method>DNS</method>
						<dns>' . $dns . '</dns>
						<provisioning>0</provisioning>
					</authentication>
				</certificate>
		 	</task>
		</request>';

        $ch = curl_init($this->options->ssl_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        $xml = new SimpleXMLElement($res);
        $id = $xml->result->data->certificate_job->job->id;
        if (empty($id)) {
            return false;
        }

        $dns = explode("\t", $dns);

        $name = "";
        $type = "";
        $content = "";

        foreach ($dns as $c) {
            if (empty($c) || $c == "IN" || is_numeric($c)) {
                continue;
            }

            if (empty($name)) {
                $name = $c;
                continue;
            }

            if (empty($type)) {
                $type = $c;
                continue;
            }

            if (empty($content)) {
                $content = $c;
                break;
            }
        }

        return array(rtrim($name, "."), $type, rtrim(trim($content, '"'), "."));
    }

    public function csrSync($domain)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
		<request>
			<auth>
		 		<user>' . $this->options->ssl_user . '</user>
		 		<password>' . $this->options->ssl_password . '</password>
		 		<context>' . $this->options->ssl_context . '</context>
		 	</auth>
		 	<language>de</language>
		 	<task>
		 		<code>400105</code>
		 	</task>
		</request>';

        $ch = curl_init($this->options->ssl_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        $xml = new SimpleXMLElement($res);
        foreach ($xml->result->data->certificate as $c) {
            if ($c->name != $domain) {
                continue;
            }

            $id = intval($c->id);

            $xml = '<?xml version="1.0" encoding="utf-8"?>
			<request>
				<auth>
			 		<user>' . $this->options->ssl_user . '</user>
			 		<password>' . $this->options->ssl_password . '</password>
			 		<context>' . $this->options->ssl_context . '</context>
			 	</auth>
			 	<language>de</language>
			 	<task>
			 		<code>400104</code>
			 		<certificate><id>' . $id . '</id></certificate>
			 	</task>
			</request>';

            $ch = curl_init($this->options->ssl_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            $res = curl_exec($ch);
            curl_close($ch);

            $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

            $pos = stripos($res, "-----BEGIN CERTIFICATE REQUEST-----");
            $end = stripos($res, "-----END CERTIFICATE REQUEST-----");
            $crt = substr($res, $pos, $end - $pos + strlen("-----END CERTIFICATE REQUEST-----"));
            return $crt;
        }

        return false;
    }

    public function sslSync($domain)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
		<request>
			<auth>
		 		<user>' . $this->options->ssl_user . '</user>
		 		<password>' . $this->options->ssl_password . '</password>
		 		<context>' . $this->options->ssl_context . '</context>
		 	</auth>
		 	<language>de</language>
		 	<task>
		 		<code>400105</code>
		 	</task>
		</request>';

        $ch = curl_init($this->options->ssl_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        $xml = new SimpleXMLElement($res);
        foreach ($xml->result->data->certificate as $c) {
            if ($c->name != $domain) {
                continue;
            }

            $id = intval($c->id);

            $xml = '<?xml version="1.0" encoding="utf-8"?>
			<request>
				<auth>
			 		<user>' . $this->options->ssl_user . '</user>
			 		<password>' . $this->options->ssl_password . '</password>
			 		<context>' . $this->options->ssl_context . '</context>
			 	</auth>
			 	<language>de</language>
			 	<task>
			 		<code>400104</code>
			 		<certificate><id>' . $id . '</id></certificate>
			 	</task>
			</request>';

            $ch = curl_init($this->options->ssl_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            $res = curl_exec($ch);
            curl_close($ch);

            $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

            $pos = stripos($res, "-----BEGIN CERTIFICATE-----");
            $end = stripos($res, "-----END CERTIFICATE-----");
            $crt = substr($res, $pos, $end - $pos + strlen("-----END CERTIFICATE-----"));
            return $crt;
        }

        return false;
    }

    public function getPrivacyRules()
    {
        return "PrivateName.pdf";
    }

    public function setRegLock($domain, $status = false, $error = false)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->options->api_user . '</user>
				 		<password>' . $this->options->api_password . '</password>
				 		<context>' . $this->options->api_context . '</context>
					</auth>
					{$this->subuserXml()}
				 	<language>de</language>
				 	<task>
				 		<code>0102</code>
						<domain>
						 	<name>' . $domain . '</name>
						 	<registry_status>' . ($status ? "lock" : "active") . '</registry_status>
						</domain>
				 	</task>
				</request>';

        $ch = curl_init($this->options->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        $xml = new SimpleXMLElement($res);
        $res = $xml->result->status->code;
        if (empty($res) || !in_array(substr($res, 0, 1), array("N", "S"))) {
            return !empty($xml->msg->status->text) ? (String) $xml->msg->status->text : $res;
        }

        return true;
    }

    public function changeValues($domain, $status = false, $renew = true, $privacy = false)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->options->api_user . '</user>
				 		<password>' . $this->options->api_password . '</password>
				 		<context>' . $this->options->api_context . '</context>
					</auth>
					{$this->subuserXml()}
				 	<language>de</language>
				 	<task>
				 		<code>0102</code>
						<domain>
						 	<name>' . $domain . '</name>
						 	<registry_status>' . ($status ? "lock" : "active") . '</registry_status>
						 	<use_privacy>' . ($privacy ? "true" : "false") . '</use_privacy>
						</domain>
				 	</task>
				</request>';

        $ch = curl_init($this->options->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->logRequest($this->options->api_url, str_replace('<password>' . $this->options->api_password . '</password>', '<password>...</password>', $xml), $res, $domain);

        $this->setRenew($domain, $renew);

        $xml = new SimpleXMLElement($res);
        $res = $xml->result->status->code;
        if (empty($res) || !in_array(substr($res, 0, 1), array("N", "S"))) {
            return !empty($xml->msg->status->text) ? (String) $xml->msg->status->text : $res;
        }

        return true;
    }

    public function availibilityStatus($domain)
    {
        if (!$this->hasAvailibilityStatus()) {
            return null;
        }

        $fp = @fsockopen("whois.autodns3.de", 43, $errno, $errstr, 10);
        if (!$fp) {
            return null;
        }

        fputs($fp, $domain . "\r\n");
        $out = "";

        while (!feof($fp)) {
            $out .= fgets($fp);
        }
        fclose($fp);

        $ex = explode("\n", $out);
        foreach ($ex as $line) {
            $line = trim($line);
            $ex2 = explode(":", $line);

            if (count($ex2) == 2 && trim($ex2[0]) == $domain) {
                $status = trim($ex2[1]);

                if ($status == "free") {
                    return true;
                }

                if ($status == "assigned") {
                    return false;
                }

                return null;
            }
        }
    }
}
