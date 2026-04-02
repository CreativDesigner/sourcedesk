<?php

class LiveConfigProv extends Provisioning
{
    protected $name = "LiveConfig";
    protected $short = "liveconfig";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;
    protected $version = "1.2";

    public function Config($id, $product = true)
    {
        global $lang;
        $this->loadOptions($id, $product);

        if (isset($_POST['url'])) {
            $cred = $_POST;
            if ($_POST['_mgmt_server']) {
                $cred = $this->serverData($_POST['_mgmt_server']);
            }

            $res = $this->Call("HostingPlanGet", [], $cred);

            if (is_string($res)) {
                die('<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . htmlentities($res) . '</div>');
            }

            if (count($res) == 0) {
                die('<div class="alert alert-warning">' . $this->getLang("NOPLANS") . '</div>');
            }

            $html = '<div class="form-group"><label>' . $this->getLang("PLAN") . '</label><select data-setting="plan" class="form-control prov_settings">';

            foreach ($res->plans->HostingPlanDetails as $p) {
                $p = $p->name;
                if (!empty($this->getOption("plan")) && $this->getOption("plan") == $p) {
                    $html .= "<option selected='selected'>$p</option>";
                } else {
                    $html .= "<option>$p</option>";
                }

            }

            $html .= "</select></div>";

            $html .= '<div class="form-group"><label>' . $this->getLang("WMDS") . '</label><div class="row">
			<div class="col-md-4">
				<input type="text" data-setting="webserver" value="' . ($this->getOption("webserver") ?: "localhost") . '" placeholder="' . $this->getLang("WEBSERVER") . '" class="form-control prov_settings">
			</div>
			<div class="col-md-4">
				<input type="text" data-setting="mailserver" value="' . ($this->getOption("mailserver") ?: "localhost") . '" placeholder="' . $this->getLang("MAILSERVER") . '" class="form-control prov_settings">
			</div>
			<div class="col-md-4">
				<input type="text" data-setting="dbserver" value="' . ($this->getOption("dbserver") ?: "localhost") . '" placeholder="' . $this->getLang("DBSERVER") . '" class="form-control prov_settings">
			</div>
			</div>

            <div class="form-group" style="margin-top: 15px;"><label>' . $this->getLang("PHPS") . '</label><input type="text" data-setting="php_settings" value="' . ($this->getOption("php_settings") ?: "") . '" placeholder="memory_limit=64M|..." class="form-control prov_settings"></div>

            <div class="row">
			<div class="col-md-6">
				<input type="text" data-setting="webmail_url" value="' . ($this->getOption("webmail_url") ?: "") . '" placeholder="' . $this->getLang("WEBMAIL_URL") . '" class="form-control prov_settings">
			</div>
			<div class="col-md-6">
				<input type="text" data-setting="pma_url" value="' . ($this->getOption("pma_url") ?: "") . '" placeholder="' . $this->getLang("PMA_URL") . '" class="form-control prov_settings">
			</div>
			</div>';
            die($html);
        }

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("LURL");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://webspacepanel.de:8443" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("LUSER");?></label>
					<input type="text" data-setting="user" value="<?=$this->getOption("user");?>" placeholder="admin" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("SOAPPW");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="<?=$this->getLang("soappwp");?>" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<a href="#" id="check_conn" mgmt="0" class="btn btn-default btn-block"><?=$this->getLang("GDFS");?></a>

		<script>
        if (!mgmt) {
		var doing = false;
		function request(){
			if(doing) return;
			doing = !doing;
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("DBF");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=liveconfig", {
				url: $("[data-setting=url]").val(),
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
		<?php if (!empty($this->getOption("url"))) {
            echo 'request();';
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

    public function ContractInfo($id)
    {
        $this->loadOptions($id);
        $u = $this->getClient($id);

        return [
            $this->getLang("USERNAME") => $this->getData("login") ?: "KD" . $u->get()['ID'],
        ];
    }

    private function Call($func, $data = "", $opt = false)
    {
        try {
            if (!is_array($opt)) {
                $opt = $this->options;
            }

            if ($func == "GetLiveConfigUrl") {
                return $opt["url"];
            }

            if (!is_array($data)) {
                $data = [];
            }

            foreach ($data as &$v) {
                $v = str_replace("##apiusr##", $opt["user"], $v);
            }

            $wsdl_url = $opt["url"] . "/liveconfig/soap?wsdl&l=" . urlencode($opt["user"]) . "&p=" . urlencode($opt["password"]);
            $client = new SoapClient(
                $wsdl_url,
                [
                    "style" => SOAP_DOCUMENT,
                    "use" => SOAP_LITERAL,
                ]
            );

            $ts = gmdate("Y-m-d") . "T" . gmdate("H:i:s") . ".000Z";
            $token = base64_encode(hash_hmac("sha1", "LiveConfig" . $opt["user"] . $func . $ts, $opt["password"], true));

            $data["auth"] = [
                "login" => $opt["user"],
                "timestamp" => $ts,
                "token" => $token,
            ];

            return $client->$func($data);
        } catch (SoapFault $soapFault) {
            return $soapFault->faultstring != "SOAP-ERROR: Parsing WSDL: Couldn't bind to service" ? $soapFault->faultstring : $this->getLang("WRONGCRED");
        }
    }

    private function PHP($subscription_name, array $settings)
    {
        $formatted = [];

        foreach ($settings as $k => $v) {
            $formatted[] = [
                "option" => $k,
                "value" => strtolower($v),
            ];
        }

        $this->Call("HostingSubscriptionEdit", [
            "subscriptionname" => $subscription_name,
            "phpini" => $formatted,
        ]);
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        // Check if customer exists
        $res = $this->Call("CustomerGet", [
            "cid" => $u->get()["ID"],
        ]);

        if (isset($res->customers->CustomerDetails->id)) {
            $cid = $res->customers->CustomerDetails->id;
        } else {
            // Create contact
            $res = $this->Call("ContactAdd", [
                "type" => "0",
                "salutation" => "0",
                "firstname" => $u->get()['firstname'],
                "lastname" => $u->get()['lastname'],
                "company" => $u->get()['company'],
                "address1" => $u->get()['street'] . " " . $u->get()['street_number'],
                "zipcode" => $u->get()['postcode'],
                "city" => $u->get()['city'],
                "country" => $u->get()['country_alpha2'],
                "phone" => $u->get()['telephone'],
                "email" => $u->get()['mail'],
                "website" => $u->get()['website'],
            ]);

            if (empty($res->id)) {
                return [false, strval($res)];
            }

            $contact = $res->id;

            // Create customer
            $res = $this->Call("CustomerAdd", [
                "cid" => $u->get()['ID'],
                "owner_c" => $contact,
                "admin_c" => $contact,
                "billing_c" => $contact,
                "locked" => "0",
            ]);

            if (empty($res->id)) {
                return [false, strval($res)];
            }

            $cid = $res->id;

            // Create user
            $res = $this->Call("UserAdd", [
                "customer" => $cid,
                "contact" => $contact,
                "login" => $this->getUsername($id),
                "password" => $sec->generatePassword(16, false, "lud"),
            ]);

            if (empty($res->id)) {
                return [false, strval($res)];
            }

        }

        $res = $this->Call("HostingSubscriptionAdd", [
            "subscriptionname" => "web" . $id,
            "password" => $pwd = $sec->generatePassword(16, false, "lud"),
            "webserver" => $this->getOption("webserver"),
            "mailserver" => $this->getOption("mailserver"),
            "dbserver" => $this->getOption("dbserver"),
            "customerid" => $cid,
            "plan" => $this->getOption("plan"),
        ]);

        if (empty($res->id)) {
            return [false, strval($res)];
        }

        // Set PHP settings
        if (!empty($this->getOption("php_settings"))) {
            $ex = explode("|", $this->getOption("php_settings"));
            $settings = [];

            foreach ($ex as $v) {
                $ex2 = explode("=", $v);
                if (count($ex2) != 2) {
                    continue;
                }

                $settings[$ex2[0]] = $ex2[1];
            }

            try {
                $this->PHP("web" . $id, $settings);
            } catch (Exception $ex) {
                return [false, "Failed to set PHP settings: " . $ex->getMessage()];
            }
        }

        return [true, [
            "subscription" => "web" . $id,
            "login" => $this->getUsername($id),
            "ftp_password" => $pwd,
        ]];
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $res = $this->Call("HostingSubscriptionDelete", [
            "subscriptionname" => $this->getData("subscription") ?: "web" . $id,
        ]);

        if (empty($res->status) || $res->status != "ok") {
            return [false, strval($res)];
        }

        return [true];
    }

    public function Output($id, $task = "")
    {
        global $pars, $raw_cfg, $CFG;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        ob_start();

        if (!empty($_GET['lc_sso'])) {
            if (!empty($pars)) {
                $exit_url = $CFG['PAGEURL'] . "hosting/$id";
            } else {
                $exit_url = $raw_cfg['PAGEURL'] . "admin/?p=hosting&id=$id";
            }

            $res = $this->Call("SessionCreate", [
                "login" => $this->getData("login") ?: "KD" . $u->get()['ID'],
                "exiturl" => $exit_url,
            ]);

            if (!empty($res->token)) {
                header('Location: ' . rtrim($this->getOption("url"), "/") . '/liveconfig/login/sso?token=' . $res->token);
                exit;
            } else {
                echo '<div class="alert alert-danger">' . htmlentities(strval($res)) . '</div>';
            }
        }

        if (!empty($pars)) {
            $sso_url = "?lc_sso=1";
        } else {
            $sso_url = "?p=hosting&id=$id&lc_sso=1";
        }

        ?>
		<div class="modal fade" id="passwordHint" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Schlie&szlig;en"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title"><?=$this->getLang("NOLPW");?></h4>
				</div>
				<div class="modal-body">
					<?=$this->getLang("NOLPWT");?>
				</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-default">
				  <div class="panel-heading"><?=$this->getLang("LCCRED");?></div>
				  <div class="panel-body">
				    <b><?=$this->getLang("URL");?>:</b> <a href="<?=$sso_url;?>"><?=$this->getOption("url");?></a><br />
				    <b><?=$this->getLang("USERNAME");?>:</b> <?=$this->getData("login") ?: ("KD" . $u->get()['ID']);?><br />
				    <b><?=$this->getLang("PASSWORD");?>:</b> <i><?=$this->getLang("NOEX");?></i> <a href="#" data-toggle="modal" data-target="#passwordHint"><i class="fa fa-question-circle"></i></a>
				  </div>
				</div>
			</div>

			<div class="col-md-6">
				<div class="panel panel-default">
				  <div class="panel-heading"><?=$this->getLang("FTPCRED");?></div>
				  <div class="panel-body">
				    <b><?=$this->getLang("HOST");?>:</b> <?=$this->getOption("webserver");?> (<?=$this->getLang("PORT");?> 21)<br />
				    <b><?=$this->getLang("USERNAME");?>:</b> <?=$this->getData("subscription") ?: ("web" . $id);?><br />
				    <b><?=$this->getLang("PASSWORD");?>:</b> <?=$this->getData("ftp_password");?>
				  </div>
				</div>
			</div>
		</div>

		<div class="panel panel-default">
			<div class="panel-heading"><?=$this->getLang("HELPLINKS");?></div>
			<div class="panel-body">
            <?php
$webmail_url = $this->getOption("webmail_url") ?: "https://" . $this->getOption("mailserver") . "/mail/";
        $pma_url = $this->getOption("pma_url") ?: "https://" . $this->getOption("mailserver") . "/pma/";
        ?>
			<b><?=$this->getLang("WEBMAIL");?>:</b> <a href="<?=$webmail_url;?>" target="_blank"><?=$webmail_url;?></a><br />
			<b><?=$this->getLang("PMA");?>:</b> <a href="<?=$pma_url;?>" target="_blank"><?=$pma_url;?></a>
			</div>
		</div>

		<a href="<?=$sso_url;?>" class="btn btn-primary btn-block"><?=$this->getLang("LTLC");?></a>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $status = 7)
    {
        $this->loadOptions($id);

        $res = $this->Call("HostingSubscriptionEdit", [
            "subscriptionname" => $this->getData("subscription") ?: "web" . $id,
            "locked" => $status,
            "locktype" => "2",
        ]);

        if (empty($res->status) || $res->status != "ok") {
            return [false, strval($res)];
        }

        return [true];
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, 0);
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        $res = $this->Call("HostingSubscriptionEdit", [
            "subscriptionname" => $this->getData("subscription") ?: "web" . $id,
            "plan" => $this->getOption("plan"),
        ]);

        if (empty($res->status) || $res->status != "ok") {
            return [false, strval($res)];
        }

        // Set PHP settings
        if (!empty($this->getOption("php_settings"))) {
            $ex = explode("|", $this->getOption("php_settings"));
            $settings = [];

            foreach ($ex as $v) {
                $ex2 = explode("=", $v);
                if (count($ex2) != 2) {
                    continue;
                }

                $settings[$ex2[0]] = $ex2[1];
            }

            try {
                $this->PHP($this->getData("subscription") ?: "web" . $id, $settings);
            } catch (Exception $ex) {
                return [false, "Failed to set PHP settings: " . $ex->getMessage()];
            }
        }

        return [true];
    }

    public function AssignDomain($id, $domain)
    {
        $this->loadOptions($id);

        $res = $this->Call("HostingDomainAdd", [
            "subscription" => $this->getData("subscription") ?: "web" . $id,
            "domain" => $domain,
            "mail" => "1",
        ]);

        if (empty($res->id)) {
            return [false, strval($res)];
        }

        return [true];
    }

    public function AllEmailVariables()
    {
        return array(
            "url",
            "ftp_host",
            "ftp_user",
            "ftp_password",
        );
    }

    public function EmailVariables($id)
    {
        global $raw_cfg;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        return array(
            "url" => $raw_cfg['PAGEURL'] . "/hosting/" . $id,
            "ftp_host" => $this->getOption("webserver"),
            "ftp_user" => $this->getData("subscription") ?: "web" . $id,
            "ftp_password" => $this->getData("ftp_password"),
        );
    }

    public function GetIP($id)
    {
        $this->loadOptions($id);
        return gethostbyname($this->getOption("webserver"));
    }
}