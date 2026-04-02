<?php

class PleskProv extends Provisioning
{
    protected $name = "Plesk";
    protected $short = "plesk";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;
    protected $version = "1.3";

    public function usagePars()
    {
        return [
            "disk_space" => $this->getLang("HDDGB"),
            "traffic" => $this->getLang("TRAFFICGB"),
        ];
    }

    public function usageFetch($id)
    {
        $this->loadOptions($id);

        if (!class_exists("PleskApiClient")) {
            require __DIR__ . "/PleskApiClient.php";
        }

        $client = new PleskApiClient($this->getOption("host"));
        $client->setCredentials($this->getOption("user"), $this->getOption("password"));

        $xml = '<packet version="1.6.3.0">
		<customer>
		   <get>
		      <filter>
		        <id>' . $this->getData("customer") . '</id>
			  </filter>
			  <dataset>
				<stat/>
			  </dataset>
		   </get>
		</customer>
		</packet>';

        $res = $client->request($xml);

        try {
            $xml = new SimpleXMLElement($res);
        } catch (Exception $ex) {
            return false;
        }

        if (!isset($xml->customer->get->result->status) || strval($xml->customer->get->result->status) != "ok") {
            return false;
        }

        return [
            "disk_space" => intval($xml->customer->get->result->data->stat->disk_space),
            "traffic" => floor(intval($xml->customer->get->result->data->stat->traffic) / 1000000000),
        ];
    }

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['host'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            if (!class_exists("PleskApiClient")) {
                require __DIR__ . "/PleskApiClient.php";
            }

            $client = new PleskApiClient($_POST['host']);
            $client->setCredentials($_POST['user'], $_POST['password']);

            $xml = '<packet>
			<service-plan>
			<get>
			   <filter/>
			</get>
			</service-plan>
            </packet>';

            $res = $client->request($xml);
            if (!$res) {
                die('<div class="alert alert-danger">' . $this->getLang("CONNFAIL") . '</div>');
            }

            try {
                $xml = new SimpleXMLElement($res);
            } catch (Exception $ex) {
                die($ex->getMessage());
            }

            if (isset($xml->system->status) && $xml->system->status == "error") {
                die('<div class="alert alert-danger">' . $this->getLang("CREDFAIL") . '</div>');
            }

            $k = "service-plan";
            $plans = array();
            foreach ($xml->$k->get->result as $p) {
                array_push($plans, $p->name);
            }

            asort($plans);

            if (count($plans) == 0) {
                die('<div class="alert alert-danger">' . $this->getLang("NOPLANS") . '</div>');
            }

            $html = '<div class="form-group"><label>' . $this->getLang("plan") . '</label><select data-setting="plan" class="form-control prov_settings">';

            foreach ($plans as $p) {
                if (!empty($this->getOption("plan")) && $this->getOption("plan") == $p) {
                    $html .= "<option value='$p' selected='selected'>$p</option>";
                } else {
                    $html .= "<option value='$p'>$p</option>";
                }

            }

            $html .= "</select></div>";
            die($html);
        }

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("PHOST");?></label>
					<input type="text" data-setting="host" value="<?=$this->getOption("host");?>" placeholder="web.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("PUSER");?></label>
					<input type="text" data-setting="user" value="<?=$this->getOption("user");?>" placeholder="admin" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("PPWD");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="GlMo=1O!" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<a href="#" id="check_conn" class="btn btn-default btn-block" mgmt="0"><?=$this->getLang("GDFS");?></a>

		<script>
        if (!mgmt) {
		var doing = false;
		function request(){
			if(doing) return;
			doing = !doing;
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("DBF");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=plesk", {
				host: $("[data-setting=host]").val(),
				user: $("[data-setting=user]").val(),
				password: $("[data-setting=password]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("GDFS");?>');
				$("#server_conf").html(r);
			});
		}
		<?php if (!empty($this->getOption("host"))) {
            ?>request();<?php
}
        ?>

		$("#check_conn").click(function(e){
			e.preventDefault();
			request();
		});
        }
		</script>

		<br /><div id="server_conf"></div>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    private function fixUmlaut($s)
    {
        $s = str_replace(["ü", "ö", "ä", "ß"], ["ue", "oe", "ae", "ss"], $s);
        $s = str_replace(["Ü", "Ö", "Ä"], ["Ue", "Oe", "Ae"], $s);
        $s = str_replace(["&"], "", $s);
        return $s;
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        if (!class_exists("PleskApiClient")) {
            require __DIR__ . "/PleskApiClient.php";
        }

        $client = new PleskApiClient($this->getOption("host"));
        $client->setCredentials($this->getOption("user"), $this->getOption("password"));

        $xml = '<packet>
		<ip>
		  <get/>
		</ip>
		</packet>';

        $res = $client->request($xml);

        try {
            $xml = new SimpleXMLElement($res);
        } catch (Exception $ex) {
            return [false, $ex->getMessage() . " (" . __LINE__ . ")"];
        }

        $ip = "";
        foreach ($xml->ip->get->result->addresses->ip_info as $ip) {
            if ($ip->type == "shared" && filter_var($ip->ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ip = $ip->ip_address;
                break;
            }
        }
        if (empty($ip)) {
            return array(false, "No IP address found");
        }

        $username = $this->getUsername($id);
        $password = $sec->generatePassword(12, false, "luds");

        $xml = '<packet version="1.6.3.0">
		<customer>
		<add>
		   <gen_info>
		       <cname>' . htmlentities($this->fixUmlaut($u->get()['company'])) . '</cname>
		       <pname>' . htmlentities($this->fixUmlaut($u->get()['firstname'])) . ' ' . htmlentities($this->fixUmlaut($u->get()['lastname'])) . '</pname>
		       <login>' . htmlentities($this->fixUmlaut($username)) . '</login>
		       <passwd><![CDATA[' . $password . ']]></passwd>
		       <status>0</status>
		       <email>' . $u->get()['mail'] . '</email>
		       <country>' . $u->get()['country_alpha2'] . '</country>
		   </gen_info>
		</add>
		</customer>
		</packet>';

        $res = $client->request($xml);

        try {
            $xml = new SimpleXMLElement($res);
        } catch (Exception $ex) {
            return [false, $ex->getMessage() . " (" . __LINE__ . ")"];
        }

        if (!$xml) {
            return array(false, $res);
        }

        if (!isset($xml->customer->add->result->status) || strval($xml->customer->add->result->status) != "ok") {
            return array(false, strval($xml->system->errtext ?: $xml->customer->add->result->errtext));
        }

        $cid = strval($xml->customer->add->result->id);

        $xml = '<packet>
		<webspace>
		<add>
		   <gen_setup>
		      <name>web' . $id . '.' . $this->getOption("host") . '</name>
		      <htype>vrt_hst</htype>
		      <status>0</status>
		      <owner-id>' . $cid . '</owner-id>
		      <ip_address>' . $ip . '</ip_address>
		   </gen_setup>
		   <hosting>
		      <vrt_hst>
		      	<property>
		      	  <name>ftp_login</name>
		      	  <value>web' . $id . '</value>
		      	</property>
		      	<property>
		      	  <name>ftp_password</name>
		      	  <value><![CDATA[' . $password . ']]></value>
		      	</property>
		      </vrt_hst>
		    </hosting>
		   <plan-name>' . $this->getOption("plan") . '</plan-name>
		</add>
		</webspace>
		</packet>';

        $res = $client->request($xml);

        try {
            $xml = new SimpleXMLElement($res);
        } catch (Exception $ex) {
            return [false, $ex->getMessage() . " (" . __LINE__ . ")"];
        }

        if (!$xml) {
            return [false, $res];
        }

        if (empty($xml->webspace->add->result->id)) {
            return array(false, strval($xml->webspace->add->result->errtext));
        }

        return array(true, array(
            "customer" => $cid,
            "username" => $this->fixUmlaut($username),
            "password" => $password,
            "webspace" => strval($xml->webspace->add->result->id),
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        if (!class_exists("PleskApiClient")) {
            require __DIR__ . "/PleskApiClient.php";
        }

        $client = new PleskApiClient($this->getOption("host"));
        $client->setCredentials($this->getOption("user"), $this->getOption("password"));

        $xml = '<packet version="1.6.3.0">
		<customer>
		   <del>
		      <filter>
		          <id>' . $this->getData("customer") . '</id>
		      </filter>
		   </del>
		</customer>
		</packet>';

        $res = $client->request($xml);

        try {
            $xml = new SimpleXMLElement($res);
        } catch (Exception $ex) {
            return [false, $ex->getMessage() . " (" . __LINE__ . ")"];
        }

        if (!isset($xml->customer->del->result->status) || strval($xml->customer->del->result->status) != "ok") {
            return array(false, strval($xml->customer->del->result->errtext));
        }

        return array(true);
    }

    public function Output($id, $task = "")
    {
        global $CFG;
        $this->loadOptions($id);

        ob_start();

        if (!class_exists("PleskApiClient")) {
            require __DIR__ . "/PleskApiClient.php";
        }

        $client = new PleskApiClient($this->getOption("host"));
        $client->setCredentials($this->getOption("user"), $this->getOption("password"));

        if ($task == "reset_pw") {
            $res = $client->request('<packet version="1.6.3.0">
			<customer>
			  <set>
			    <filter>
			       <login>' . $this->getData("username") . '</login>
			    </filter>
			    <values>
			      <gen_info>
			        <passwd><![CDATA[' . $this->getData("password") . ']]></passwd>
			      </gen_info>
			    </values>
			  </set>
			</customer>
			</packet>');

            try {
                $xml = new SimpleXMLElement($res);
            } catch (Exception $ex) {
                $xml = new stdClass;
            }

            if ($xml->customer->set->result->status == "ok") {
                echo '<div class="alert alert-success">' . $this->getLang("RESOK") . '</div>';
            } else {
                echo '<div class="alert alert-danger">' . $this->getLang("TECERR") . '</div>';
            }
        }

        $res = $client->request('<packet version="1.6.3.5">
        <server>
            <create_session>
                <login>' . $this->getData("username") . '</login>
                <data>
                    <user_ip>' . base64_encode(ip()) . '</user_ip>
                    <source_server>' . base64_encode($CFG['PAGEURL']) . '</source_server>
                </data>
            </create_session>
        </server>
        </packet>');

        $link = "";

        try {
            $xml = new SimpleXMLElement($res);
        } catch (Exception $ex) {}

        if ($xml->server->create_session->result->status == "ok") {
            $link = "https://" . $this->getOption("host") . ":8443/enterprise/rsession_init.php?PHPSESSID=" . $xml->server->create_session->result->id;
        }
        ?>
		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-default">
				  <div class="panel-heading"><?=$this->getLang("CRED");?></div>
				  <div class="panel-body">
				    <b><?=$this->getLang("URL");?>:</b> <a href="https://<?=$this->getOption("host");?>:8443/" target="_blank">https://<?=$this->getOption("host");?>:8443/</a><br />
				    <b><?=$this->getLang("username");?>:</b> <?=$this->getData("username");?><br />
				    <b><?=$this->getLang("password");?>:</b> <?=$this->getData("password");?>
				  </div>
				</div>
			</div>

			<div class="col-md-6">
				<div class="panel panel-default">
				  <div class="panel-heading"><?=$this->getLang("FTPCRED");?></div>
				  <div class="panel-body">
				    <b><?=$this->getLang("HOST");?>:</b> <?=$this->getOption("host");?> (<?=$this->getLang("PORT");?> 21)<br />
				    <b><?=$this->getLang("username");?>:</b> <?=$this->getData("ftp_user") ?: "web$id";?><br />
				    <b><?=$this->getLang("password");?>:</b> <?=$this->getData("password");?>
				  </div>
				</div>
			</div>
		</div>

        <?php if ($link) {?>
        <a href="<?=$link;?>" class="btn btn-block btn-primary" target="_blank"><?=$this->getLang("LOGINNOW");?></a>
        <?php } else {?>
		<form method="POST" action="https://<?=$this->getOption("host");?>:8443/login_up.php3" target="_blank">
			<input type="hidden" name="login_name" value="<?=$this->getData("username");?>">
			<input type="hidden" name="passwd" value="<?=$this->getData("password");?>">
			<input type="hidden" name="locale_id" value="default">
			<input type="submit" name="send" class="btn btn-block btn-primary" value="<?=$this->getLang("LOGINNOW");?>">
		</form>
        <?php }?>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $status = 32)
    {
        $this->loadOptions($id);
        $wid = $this->getData("webspace");

        if (!class_exists("PleskApiClient")) {
            require __DIR__ . "/PleskApiClient.php";
        }

        $client = new PleskApiClient($this->getOption("host"));
        $client->setCredentials($this->getOption("user"), $this->getOption("password"));

        $xml = '<packet>
		<webspace>
		<set>
		   <filter>
		      <id>' . $wid . '</id>
		   </filter>
		   <values>
		   	<gen_setup>
		   	 <status>' . $status . '</status>
		   	</gen_setup>
		   </values>
		</set>
		</webspace>
		</packet>';

        $res = $client->request($xml);

        try {
            $xml = new SimpleXMLElement($res);
        } catch (Exception $ex) {
            return [false, $ex->getMessage() . " (" . __LINE__ . ")"];
        }

        if (!isset($xml->webspace->set->result->status) || strval($xml->webspace->set->result->status) != "ok") {
            return array(false, strval($xml->webspace->set->result->errtext));
        }

        return array(true);
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, 0);
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);
        $wid = $this->getData("webspace");

        if (!class_exists("PleskApiClient")) {
            require __DIR__ . "/PleskApiClient.php";
        }

        $client = new PleskApiClient($this->getOption("host"));
        $client->setCredentials($this->getOption("user"), $this->getOption("password"));

        $xml = '<packet>
		<service-plan>
		<get>
		   <filter/>
		   <owner-login>' . $this->getOption("user") . '</owner-login>
		</get>
		</service-plan>
		</packet>';

        $res = $client->request($xml);

        try {
            $xml = new SimpleXMLElement($res);
        } catch (Exception $ex) {
            return [false, $ex->getMessage() . " (" . __LINE__ . ")"];
        }

        $k = "service-plan";
        $guid = "";
        foreach ($xml->$k->get->result as $p) {
            if ($p->name == $this->getOption("plan")) {
                $guid = strval($p->guid);
            }
        }

        if (empty($guid)) {
            return array(false, "Plan not found");
        }

        $xml = '<packet>
		<webspace>
		<switch-subscription>
		   <filter>
		   	<id>' . $wid . '</id>
		   </filter>
		   <plan-guid>' . $guid . '</plan-guid>
		</switch-subscription>
		</webspace>
		</packet>';

        $res = $client->request($xml);

        try {
            $xml = new SimpleXMLElement($res);
        } catch (Exception $ex) {
            return [false, $ex->getMessage() . " (" . __LINE__ . ")"];
        }

        $k = "switch-subscription";
        if (!isset($xml->webspace->$k->result->status) || strval($xml->webspace->$k->result->status) != "ok") {
            return array(false, strval($xml->webspace->$k->result->errtext));
        }

        return array(true);
    }

    public function AssignDomain($id, $domain)
    {
        $this->loadOptions($id);
        $wid = $this->getData('webspace');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
		<packet>
		  <site>
		   <add>
		      <gen_setup>
		        <name>' . $domain . '</name>
		        <webspace-id>' . $wid . '</webspace-id>
		      </gen_setup>
		      <hosting>
		      	<vrt_hst>
		      	 <property>
		      	  <name>cgi_mode</name>
		      	  <value>webspace</value>
		      	 </property>
		      	</vrt_hst>
		      </hosting>
		   </add>
		  </site>
		 </packet>';

        if (!class_exists("PleskApiClient")) {
            require __DIR__ . "/PleskApiClient.php";
        }

        $client = new PleskApiClient($this->getOption("host"));
        $client->setCredentials($this->getOption("user"), $this->getOption("password"));

        $res = $client->request($xml);

        try {
            $xml = new SimpleXMLElement($res);
        } catch (Exception $ex) {
            return [false, $ex->getMessage() . " (" . __LINE__ . ")"];
        }

        if ($xml->site->add->result->status != "ok") {
            return array(false, $xml->site->add->result->errtext);
        }

        return array(true);
    }

    public function AllEmailVariables()
    {
        return array(
            "url",
            "user",
            "password",
            "ftp_host",
            "ftp_user",
            "ftp_password",
        );
    }

    public function EmailVariables($id)
    {
        $this->loadOptions($id);

        return array(
            "url" => "https://" . $this->getOption("host") . ":8443/",
            "user" => $this->getData("username"),
            "password" => $this->getData("password"),
            "ftp_host" => $this->getOption("host"),
            "ftp_user" => "web" . $id,
            "ftp_password" => $this->getData("password"),
        );
    }

    public function ContractInfo($id)
    {
        $this->loadOptions($id);

        return [
            $this->getLang("PUSER") => $this->getData("username"),
        ];
    }

    public function OwnFunctions($id)
    {
        return array(
            "reset_pw" => $this->getLang("RESPW"),
        );
    }

    public function AdminFunctions($id)
    {
        return array(
            "reset_pw" => $this->getLang("RESPW"),
        );
    }

    public function ApiTasks($id)
    {
        return array(
            "SetUserPassword" => "pwd",
        );
    }

    public function SetUserPassword($id, $req)
    {
        $this->loadOptions($id);

        if (!class_exists("PleskApiClient")) {
            require __DIR__ . "/PleskApiClient.php";
        }

        $client = new PleskApiClient($this->getOption("host"));
        $client->setCredentials($this->getOption("user"), $this->getOption("password"));

        $res = $client->request('<packet version="1.6.3.0">
		<customer>
		  <set>
		    <filter>
		       <login>' . $this->getData("username") . '</login>
		    </filter>
		    <values>
		      <gen_info>
		        <passwd><![CDATA[' . $req['pwd'] . ']]></passwd>
		      </gen_info>
		    </values>
		  </set>
		</customer>
		</packet>');

        try {
            $xml = new SimpleXMLElement($res);
        } catch (Exception $ex) {
            return [false, $ex->getMessage() . " (" . __LINE__ . ")"];
        }

        if ($xml->customer->set->result->status == "ok") {
            $this->setData("password", $req['pwd']);
            die(json_encode(array("code" => "100", "message" => "Password changed successfully.", "data" => array())));
        }

        die(json_encode(array("code" => "810", "message" => "Technical error occured.", "data" => array())));
    }

    public function GetIP($id)
    {
        $this->loadOptions($id);

        $host = $this->getOption("host");
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $host;
        }

        return gethostbyname($host);
    }

    public function ClientChanged($id, array $changedFields)
    {
        if (!count(array_intersect($changedFields, ["company", "mail", "firstname", "lastname"]))) {
            return;
        }

        $this->loadOptions($id);
        $c = $this->getClient($id);

        if (!class_exists("PleskApiClient")) {
            require __DIR__ . "/PleskApiClient.php";
        }

        $client = new PleskApiClient($this->getOption("host"));
        $client->setCredentials($this->getOption("user"), $this->getOption("password"));

        $client->request('<packet version="1.6.3.0">
		<customer>
		  <set>
		    <filter>
		       <login>' . $this->getData("username") . '</login>
		    </filter>
		    <values>
		      <gen_info>
		        <email>' . $c->get()["mail"] . '</email>
		      </gen_info>
		    </values>
		  </set>
		</customer>
        </packet>');
    }
}