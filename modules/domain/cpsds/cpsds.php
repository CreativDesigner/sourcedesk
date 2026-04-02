<?php

class CPSDS extends DomainRegistrar
{
    protected $short = "cpsds";
    protected $name = "CPS-Datensysteme";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "api_cid" => array("type" => "text", "name" => $this->getLang("cstnr")),
            "api_user" => array("type" => "text", "name" => $this->getLang("username"), "default" => "master"),
            "api_password" => array("type" => "password", "name" => $this->getLang("password")),
            "outgoing_ip" => array("text" => "text", "name" => $this->getLang("outgoing"), "hint" => $this->getLang("optional")),
        );
    }

    private function request($data, $domain = "")
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url = "https://orms.cps-datensysteme.de:700");
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_POST, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS, utf8_encode('<?xml version="1.0" encoding="utf-8"?>' . $data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($this->options->outgoing_ip)) {
            curl_setopt($curl, CURLOPT_INTERFACE, $this->options->outgoing_ip);
        }

        $res = curl_exec($curl);
        curl_close($curl);

        $this->logRequest($url, str_replace("<pwd>{$this->options->api_password}</pwd>", "<pwd>...</pwd>", $data), $res, $domain);

        return $res;
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
        $domain = strtolower($domain);
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
            $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);

            $$t = "<contact_type>" . (empty($i[2]) ? "person" : "organisation") . "</contact_type>
						<orgname>" . htmlentities(empty($i[2]) ? str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[0] . " " . $i[1]) : str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[2])) . "</orgname>
					<firstname>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[0])) . "</firstname>
					<lastname>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[1])) . "</lastname>
						<street>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3])) . "</street>
						<postal>{$i[5]}</postal>
						<city>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[6])) . "</city>
						<state>Nordrhein-Westfalen</state>
						<iso_country>{$i[4]}</iso_country>
						<phone>" . $this->convertTelephone($i[7]) . "</phone>
						<fax>" . $this->convertTelephone(empty($i[8]) ? $i[7] : $i[8]) . "</fax>
						<email>{$i[9]}</email>
						<privacy_rule>user</privacy_rule>";
        }

        $idn = new IdnaConvert;

        $xml = "<request>
		  <auth>
		    <cid>{$this->options->api_cid}</cid>
		    <user>{$this->options->api_user}</user>
		    <pwd>{$this->options->api_password}</pwd>
		    <secure_token></secure_token>
		  </auth>
		  <transaction>
		    <group>domain</group>
		    <action>create</action>
		    <attribute>domain</attribute>
		    <object>" . $idn->encode($domain) . "</object>
		    <values>
		      <ownerc>$owner</ownerc>
		      <adminc>$admin</adminc>
		      <techc>$tech</techc>
		      <billc>$zone</billc>
		      <dns>
		        <hostname>{$ns[0]}</hostname>
		        <hostip></hostip>
		      </dns>
		      <dns>
		        <hostname>{$ns[1]}</hostname>
		        <hostip></hostip>
		      </dns>
		      <dns>
		        <hostname>{$ns[2]}</hostname>
		        <hostip></hostip>
		      </dns>
		      <dns>
		        <hostname>{$ns[3]}</hostname>
		        <hostip></hostip>
		      </dns>
		      <dns>
		        <hostname>{$ns[4]}</hostname>
		        <hostip></hostip>
		      </dns>
		      <nsentry></nsentry>
		      <nsentry></nsentry>
		      <nsentry></nsentry>
		      <nsentry></nsentry>
		      <nsentry></nsentry>
		    </values>
		  </transaction>
		</request>";

        $res = $this->request($xml, $domain);
        $xml = new SimpleXMLElement($res);
        return $xml->result->code == "1000" ? true : (!empty($xml->result->message) ? (String) $xml->result->message : $res);
    }

    public function transferDomain($domain, $owner, $admin, $tech, $zone, $authCode, $ns, $privacy = false)
    {
        $domain = strtolower($domain);
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
            $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);

            $$t = "<contact_type>" . (empty($i[2]) ? "person" : "organisation") . "</contact_type>
				<orgname>" . htmlentities(empty($i[2]) ? str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[0] . " " . $i[1]) : str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[2])) . "</orgname>
				<firstname>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[0])) . "</firstname>
			<lastname>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[1])) . "</lastname>
				<street>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3])) . "</street>
				<postal>{$i[5]}</postal>
				<city>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[6])) . "</city>
				<state>Nordrhein-Westfalen</state>
				<iso_country>{$i[4]}</iso_country>
				<phone>" . $this->convertTelephone($i[7]) . "</phone>
				<fax>" . $this->convertTelephone(empty($i[8]) ? $i[7] : $i[8]) . "</fax>
				<email>{$i[9]}</email>
				<privacy_rule>user</privacy_rule>";
        }

        $idn = new IdnaConvert;

        $xml = "<request>
		  <auth>
		    <cid>{$this->options->api_cid}</cid>
		    <user>{$this->options->api_user}</user>
		    <pwd>{$this->options->api_password}</pwd>
		    <secure_token></secure_token>
		  </auth>
		  <transaction>
		    <group>domain</group>
		    <action>transfer</action>
		    <attribute>domain</attribute>
		    <object>" . $idn->encode($domain) . "</object>
		    <values>
		      <ownerc>$owner</ownerc>
		      <adminc>$admin</adminc>
		      <techc>$tech</techc>
		      <billc>$zone</billc>
		      <dns>
		        <hostname>{$ns[0]}</hostname>
		        <hostip></hostip>
		      </dns>
		      <dns>
		        <hostname>{$ns[1]}</hostname>
		        <hostip></hostip>
		      </dns>
		      <dns>
		        <hostname>{$ns[2]}</hostname>
		        <hostip></hostip>
		      </dns>
		      <dns>
		        <hostname>{$ns[3]}</hostname>
		        <hostip></hostip>
		      </dns>
		      <dns>
		        <hostname>{$ns[4]}</hostname>
		        <hostip></hostip>
		      </dns>
		      <nsentry></nsentry>
		      <nsentry></nsentry>
		      <nsentry></nsentry>
		      <nsentry></nsentry>
		      <nsentry></nsentry>
		      <auth_info><![CDATA[$authCode]]></auth_info>
		    </values>
		  </transaction>
		</request>";

        $res = $this->request($xml, $domain);
        $xml = new SimpleXMLElement($res);
        return $xml->result->code == "1000" ? true : (!empty($xml->result->message) ? (String) $xml->result->message : $res);
    }

    public function deleteDomain($domain, $transit = 0)
    {
        $idn = new IdnaConvert;
        $domain = strtolower($domain);
        if ($transit == 0) {
            $xml = "<request>
			  <auth>
			    <cid>{$this->options->api_cid}</cid>
			    <user>{$this->options->api_user}</user>
			    <pwd>{$this->options->api_password}</pwd>
			    <secure_token></secure_token>
			  </auth>
			  <transaction>
			    <group>domain</group>
			    <action>delete</action>
			    <attribute>domain</attribute>
			    <object>" . $idn->encode($domain) . "</object>
			  </transaction>
			</request>";
        } else {
            $xml = "<request>
			  <auth>
			    <cid>{$this->options->api_cid}</cid>
			    <user>{$this->options->api_user}</user>
			    <pwd>{$this->options->api_password}</pwd>
			    <secure_token></secure_token>
			  </auth>
			  <transaction>
			    <group>domain</group>
			    <action>delete</action>
			    <attribute>release</attribute>
			    <object>" . $idn->encode($domain) . "</object>
			  </transaction>
			</request>";
        }

        $res = $this->request($xml, $domain);
        $xml = new SimpleXMLElement($res);

        if ($xml->result->code != "1000") {
            return !empty($xml->result->message) ? (String) $xml->result->message : $res;
        }

        return true;
    }

    public function getAuthCode($domain)
    {
        $idn = new IdnaConvert;
        $domain = strtolower($domain);
        $xml = "<request>
		  <auth>
		    <cid>{$this->options->api_cid}</cid>
		    <user>{$this->options->api_user}</user>
		    <pwd>{$this->options->api_password}</pwd>
		    <secure_token></secure_token>
		  </auth>
		  <transaction>
		    <group>domain</group>
		    <action>info</action>
		    <attribute>domain</attribute>
		    <object>" . $idn->encode($domain) . "</object>
		  </transaction>
		</request>";

        $res = $this->request($xml, $domain);
        $xml = new SimpleXMLElement($res);

        if ($xml->result->code != "1000") {
            return !empty($xml->result->message) ? (String) $xml->result->message : $res;
        }

        if (empty($xml->result->detail->values->auth_info)) {
            return "Bitte Transfersperre deaktivieren.";
        }

        return "AUTH:" . $xml->result->detail->values->auth_info;
    }

    public function changeNameserver($domain, $ns)
    {
        $domain = strtolower($domain);
        $nsXml = "";
        foreach ($ns as $n) {
            if (!empty($n)) {
                $nsXml .= "<dns>
			        <hostip></hostip>
			        <hostname>$n</hostname>
						</dns>";
            }
        }

        $idn = new IdnaConvert;

        $xml = "<request>
		  <auth>
		    <cid>{$this->options->api_cid}</cid>
		    <user>{$this->options->api_user}</user>
		    <pwd>{$this->options->api_password}</pwd>
		    <secure_token></secure_token>
		  </auth>
		  <transaction>
		    <group>domain</group>
		    <action>modify</action>
		    <attribute>domain</attribute>
		    <object>" . $idn->encode($domain) . "</object>
		    <values>
		    	$nsXml
		    </values>
		  </transaction>
		</request>";

        $res = $this->request($xml, $domain);
        $xml = new SimpleXMLElement($res);

        if ($xml->result->code != "1000") {
            return !empty($xml->result->message) ? (String) $xml->result->message : $res;
        }

        return true;
    }

    public function changeContact($domain, $owner, $admin, $tech, $zone)
    {
        $domain = strtolower($domain);
        $arr = array("owner", "admin", "tech", "zone");

        foreach ($arr as $t) {
            $i = $$t;

            $i[7] = str_replace(array(" ", "-", "/"), "", $i[7]);
            $i[8] = str_replace(array(" ", "-", "/"), "", $i[8]);

            $xml = "<request>
					<auth>
						<cid>{$this->options->api_cid}</cid>
						<user>{$this->options->api_user}</user>
						<pwd>{$this->options->api_password}</pwd>
						<secure_token></secure_token>
					</auth>
					<transaction>
						<group>contact</group>
						<action>create</action>
						<attribute>contact</attribute>
						<object>%%AUTO%%</object>
						<values>
							<contact_type>" . (empty($i[2]) ? "person" : "organisation") . "</contact_type>
							<orgname>" . htmlentities(empty($i[2]) ? str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[0] . " " . $i[1]) : str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[2])) . "</orgname>
							<firstname>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[0])) . "</firstname>
						<lastname>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[1])) . "</lastname>
							<street>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3])) . "</street>
							<postal>{$i[5]}</postal>
							<city>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[6])) . "</city>
							<state>Nordrhein-Westfalen</state>
							<iso_country>{$i[4]}</iso_country>
							<phone>" . $this->convertTelephone($i[7]) . "</phone>
							<fax>" . $this->convertTelephone(empty($i[8]) ? $i[7] : $i[8]) . "</fax>
							<email>{$i[9]}</email>
							<privacy_rule>user</privacy_rule>
						</values>
					</transaction>
				</request>";

            $res = $this->request($xml, $domain);
            $xml = new SimpleXMLElement($res);

            if ($xml->result->code != "1000") {
                return !empty($xml->result->message) ? (String) $xml->result->message : $res;
            }

            $$t = $xml->result->auto_values->contact_id;
        }

        $idn = new IdnaConvert;

        $xml = "<request>
		  <auth>
		    <cid>{$this->options->api_cid}</cid>
		    <user>{$this->options->api_user}</user>
		    <pwd>{$this->options->api_password}</pwd>
		    <secure_token></secure_token>
		  </auth>
		  <transaction>
		    <group>domain</group>
		    <action>modify</action>
		    <attribute>domain</attribute>
		    <object>" . $idn->encode($domain) . "</object>
		    <values>
		    	<ownerc>$owner</ownerc>
			    <adminc>$admin</adminc>
			    <techc>$tech</techc>
			    <billc>$zone</billc>
		    </values>
		  </transaction>
		</request>";

        $res = $this->request($xml, $domain);
        $xml = new SimpleXMLElement($res);

        if ($xml->result->code != "1000") {
            return !empty($xml->result->message) ? (String) $xml->result->message : $res;
        }

        return true;
    }

    public function setRegLock($domain, $status = false, $error = false)
    {
        $idn = new IdnaConvert;
        $domain = strtolower($domain);
        $xml = "<request>
		  <auth>
		    <cid>{$this->options->api_cid}</cid>
		    <user>{$this->options->api_user}</user>
		    <pwd>{$this->options->api_password}</pwd>
		    <secure_token></secure_token>
		  </auth>
		  <transaction>
		    <group>domain</group>
		    <action>modify</action>
		    <attribute>transfer_lock</attribute>
		    <object>" . $idn->encode($domain) . "</object>
		    <values>
		    	<status>" . ($status ? "active" : "disabled") . "</status>
		    </values>
		  </transaction>
		</request>";

        $res = $this->request($xml, $domain);
        $xml = new SimpleXMLElement($res);
        return $xml->result->code == "1000" ? true : ($error ? (!empty($xml->result->message) ? (String) $xml->result->message : $res) : false);
    }

    public function setRenew($domain, $renew = true)
    {
        $domain = strtolower($domain);
        $idn = new IdnaConvert;
        $xml = "<request>
		  <auth>
		    <cid>{$this->options->api_cid}</cid>
		    <user>{$this->options->api_user}</user>
		    <pwd>{$this->options->api_password}</pwd>
		    <secure_token></secure_token>
		  </auth>
		  <transaction>
		    <group>domain</group>
		    <action>modify</action>
		    <attribute>auto_renew</attribute>
		    <object>" . $idn->encode($domain) . "</object>
		    <values>
		    	<status>" . ($renew ? "active" : "disabled") . "</status>
		    </values>
		  </transaction>
		</request>";

        $this->request($xml, $domain);
        return true;
    }

    public function changeAll($domain, $owner, $admin, $tech, $zone, $ns, $status = false, $renew = true, $privacy = false)
    {
        $domain = strtolower($domain);
        $this->setRegLock($domain, $status, true);

        $nsXml = "";
        foreach ($ns as $n) {
            if (!empty($n)) {
                $nsXml .= "<dns>
			        <hostip></hostip>
			        <hostname>$n</hostname>
			      </dns>";
            }
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

                $xml = "<request>
						<auth>
							<cid>{$this->options->api_cid}</cid>
							<user>{$this->options->api_user}</user>
							<pwd>{$this->options->api_password}</pwd>
							<secure_token></secure_token>
						</auth>
						<transaction>
							<group>contact</group>
							<action>create</action>
							<attribute>contact</attribute>
							<object>%%AUTO%%</object>
							<values>
								<contact_type>" . (empty($i[2]) ? "person" : "organisation") . "</contact_type>
								<orgname>" . htmlentities(empty($i[2]) ? str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[0] . " " . $i[1]) : str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[2])) . "</orgname>
								<firstname>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[0])) . "</firstname>
							<lastname>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[1])) . "</lastname>
								<street>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[3])) . "</street>
								<postal>{$i[5]}</postal>
								<city>" . htmlentities(str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $i[6])) . "</city>
								<state>Nordrhein-Westfalen</state>
								<iso_country>{$i[4]}</iso_country>
								<phone>" . $this->convertTelephone($i[7]) . "</phone>
								<fax>" . $this->convertTelephone(empty($i[8]) ? $i[7] : $i[8]) . "</fax>
								<email>{$i[9]}</email>
								<privacy_rule>user</privacy_rule>
							</values>
						</transaction>
					</request>";

                $res = $this->request($xml, $domain);
                $xml = new SimpleXMLElement($res);

                if ($xml->result->code != "1000") {
                    return !empty($xml->result->message) ? (String) $xml->result->message : $res;
                }

                $$t = $xml->result->auto_values->contact_id;
            }
        }

        $idn = new IdnaConvert;

        $xml = "<request>
		  <auth>
		    <cid>{$this->options->api_cid}</cid>
		    <user>{$this->options->api_user}</user>
		    <pwd>{$this->options->api_password}</pwd>
		    <secure_token></secure_token>
		  </auth>
		  <transaction>
		    <group>domain</group>
		    <action>modify</action>
		    <attribute>domain</attribute>
		    <object>" . $idn->encode($domain) . "</object>
		    <values>
		    	<ownerc>$owner</ownerc>
			    <adminc>$admin</adminc>
			    <techc>$tech</techc>
			    <billc>$zone</billc>
			    $nsXml
		    </values>
		  </transaction>
		</request>";

        $res = $this->request($xml, $domain);
        $xml = new SimpleXMLElement($res);

        if ($xml->result->code != "1000") {
            return !empty($xml->result->message) ? (String) $xml->result->message : $res;
        }

        $idn = new IdnaConvert;
        $xml = "<request>
		  <auth>
		    <cid>{$this->options->api_cid}</cid>
		    <user>{$this->options->api_user}</user>
		    <pwd>{$this->options->api_password}</pwd>
		    <secure_token></secure_token>
		  </auth>
		  <transaction>
		    <group>domain</group>
		    <action>modify</action>
		    <attribute>auto_renew</attribute>
		    <object>" . $idn->encode($domain) . "</object>
		    <values>
		    	<status>" . ($renew ? "active" : "disabled") . "</status>
		    </values>
		  </transaction>
		</request>";

        $this->request($xml, $domain);
        return true;
    }

    public function syncDomain($domain, $kkSync = false)
    {
        $domain = strtolower($domain);
        $idn = new IdnaConvert;
        $xml = "<request>
		  <auth>
		    <cid>{$this->options->api_cid}</cid>
		    <user>{$this->options->api_user}</user>
		    <pwd>{$this->options->api_password}</pwd>
		    <secure_token></secure_token>
		  </auth>
		  <transaction>
		    <group>domain</group>
		    <action>info</action>
		    <attribute>domain</attribute>
		    <object>" . $idn->encode($domain) . "</object>
		  </transaction>
		</request>";

        $res = $this->request($xml, $domain);
        if (!$res) {
            return "Connection failure";
        }

        $xml = new SimpleXMLElement($res);

        if ($xml->result->code != "1000") {
            if ($kkSync) {
                return ["status" => "waiting_kk"];
            }

            return !empty($xml->result->message) ? (String) $xml->result->message : false;
        }

        $info = array(
            "auto_renew" => $xml->result->detail->values->auto_renew == "active",
            "expiration" => date("Y-m-d", strtotime($xml->result->detail->values->expire)),
            "status" => (String) $xml->result->detail->values->status == "active",
            "transfer_lock" => $xml->result->detail->values->transfer_lock == "active",
            "ownerc" => (String) $xml->result->detail->values->ownerc,
            "privacy" => false,
        );

        $ns = [];
        foreach ($xml->result->detail->values->dns as $dns) {
            if (!empty($dns->hostname)) {
                array_push($ns, $dns->hostname);
            }
        }

        if (count($ns) >= 2) {
            $info['ns'] = $ns;
        }

        foreach ([
            "adminc" => "admin",
            "billc" => "zone",
            "techc" => "tech",
            "ownerc" => "owner",
        ] as $cps => $sd) {
            $req = "<request>
				<auth>
					<cid>{$this->options->api_cid}</cid>
					<user>{$this->options->api_user}</user>
					<pwd>{$this->options->api_password}</pwd>
					<secure_token></secure_token>
				</auth>
				<transaction>
					<group>contact</group>
					<action>info</action>
					<attribute>contact</attribute>
					<object>" . $xml->result->detail->values->$cps . "</object>
					<values></values>
				</transaction>
			</request>";

            $res = $this->request($req, $domain);
            if (!$res) {
                continue;
            }

            $xml = new SimpleXMLElement($res);

            if ($xml->result->code != "1000") {
                continue;
            }

            $xml3 = $xml->result->detail->values;

            $info[$sd] = [
                $xml3->firstname,
                $xml3->lastname,
                $xml3->orgname,
                $xml3->street,
                $xml3->iso_country,
                $xml3->postal,
                $xml3->city,
                $xml3->phone,
                $xml3->fax,
                $xml3->email,
                "",
            ];
        }

        return $info;
    }

    public function trade($domain, $owner, $admin, $tech, $zone)
    {
        $domain = strtolower($domain);
        $idn = new IdnaConvert;
        $xml = "<request>
		  <auth>
		    <cid>{$this->options->api_cid}</cid>
		    <user>{$this->options->api_user}</user>
		    <pwd>{$this->options->api_password}</pwd>
		    <secure_token></secure_token>
		  </auth>
		  <transaction>
		    <group>domain</group>
			<action>modify</action>
			<attribute>owner_change</attribute>
			<object>" . $idn->encode($domain) . "</object>
			<values>
				<ownerc>
				  <contact_type>" . (empty($owner[2]) ? "person" : "organisation") . "</contact_type>
			      <orgname>" . (empty($owner[2]) ? str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $owner[0] . " " . $owner[1]) : str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $owner[2])) . "</orgname>
			      <firstname>" . str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $owner[0]) . "</firstname>
				  <lastname>" . str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $owner[1]) . "</lastname>
			      <street>" . str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $owner[3]) . "</street>
			      <postal>{$owner[5]}</postal>
			      <city>" . str_replace(array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü"), array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue"), $owner[6]) . "</city>
			      <state>Nordrhein-Westfalen</state>
			      <iso_country>{$owner[4]}</iso_country>
			      <phone>" . $this->convertTelephone($owner[7]) . "</phone>
			      <fax>" . $this->convertTelephone(empty($owner[8]) ? $owner[7] : $owner[8]) . "</fax>
			      <email>{$owner[9]}</email>
			      <privacy_rule>user</privacy_rule>
				</ownerc>
			</values>
		  </transaction>
		</request>";

        $res = $this->request($xml, $domain);
        $xml = new SimpleXMLElement($res);

        if ($xml->result->code != "1000") {
            return !empty($xml->result->message) ? (String) $xml->result->message : $res;
        }

        return true;
    }

    public function changeValues($domain, $status = false, $renew = true, $privacy = false)
    {
        $domain = strtolower($domain);
        $r = $this->setRegLock($domain, $status);
        if ($r !== true) {
            return $r;
        }

        $r = $this->setRenew($domain, $renew);
        if ($r !== true) {
            return $r;
        }

        return true;
    }
}
