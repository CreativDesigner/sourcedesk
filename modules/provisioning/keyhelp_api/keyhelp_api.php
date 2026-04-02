<?php

class KeyHelpApiProv extends Provisioning
{
    protected $name = "KeyHelp API";
    protected $short = "keyhelp_api";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;
    protected $version = "1.1";

    public function usagePars()
    {
        return [
            "disk_space" => $this->getLang("DISK_SPACEGB"),
            "traffic" => $this->getLang("TRAFFICGB"),
            "domains" => $this->getLang("DOMAINSX"),
            "subdomains" => $this->getLang("SUBDOMAINSX"),
            "email_accounts" => $this->getLang("EMAILACCOUNTSX"),
            "email_addresses" => $this->getLang("EMAILADDRESSESX"),
            "databases" => $this->getLang("DATABASESX"),
            "ftp_users" => $this->getLang("FTPUSERSX"),
            "scheduled_tasks" => $this->getLang("SCHEDULEDTASKSX"),
        ];
    }

    public function usageFetch($id)
    {
        $this->loadOptions($id);

        try {
            $res = $this->Call("clients/" . $this->getData("id") . "/stats");
        } catch (Exception $ex) {
            return false;
        }

        return [
            "disk_space" => floor(intval($res["disk_space"]["value"]) / 1000000000),
            "traffic" => floor(intval($res["traffic"]["value"]) / 1000000000),
            "domains" => intval($res["domains"]["value"]),
            "subdomains" => intval($res["subdomains"]["value"]),
            "email_accounts" => intval($res["email_accounts"]["value"]),
            "email_addresses" => intval($res["email_addresses"]["value"]),
            "databases" => intval($res["databases"]["value"]),
            "ftp_users" => intval($res["ftp_users"]["value"]),
            "scheduled_tasks" => intval($res["scheduled_tasks"]["value"]),
        ];
    }

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['host'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $this->options["host"] = $_POST['host'];
            $this->options["api_key"] = $_POST['api_key'];

            try {
                $packages = $this->Call("hosting-plans");
            } catch (Exception $ex) {
                die('<div class="alert alert-danger">' . $ex->getMessage() . '</div>');
            }

            if (count($packages) == 0) {
                die('<div class="alert alert-danger">' . $this->getLang("Noplans") . '</div>');
            }

            ?>
			<div class="form-group">
				<label><?=$this->getLang("plan");?></label>
				<select data-setting="package" class="form-control prov_settings">
					<?php foreach ($packages as $pack) {
                $id = $pack["id"];
                $name = htmlentities($pack["name"]);
                ?>
					<option value="<?=$id;?>"<?php if (!empty($this->getOption('package')) && $this->getOption('package') == $id) {
                    echo ' selected="selected"';
                }
                ?>><?=$name;?></option>
					<?php }?>
				</select>
			</div>
			<?php
exit;
        }

        ob_start();?>

		<div class="row" mgmt="1">
		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">
			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("hostname");?></label>
					<input type="text" data-setting="host" value="<?=$this->getOption("host");?>" placeholder="s1.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("api_key");?></label>
					<input type="password" data-setting="api_key" value="<?=$this->getOption("api_key");?>" placeholder="xxxxxxxx.xxxxxxx..." class="form-control prov_settings" />
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
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=keyhelp_api", {
				host: $("[data-setting=host]").val(),
				api_key: $("[data-setting=api_key]").val(),
				_mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("GDFS");?>');
				$("#server_conf").html(r);
			});
		}
		<?php if (!empty($this->getOption("host"))) {
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

    private function Call($action, $data = [], $method = "GET")
    {
        $ch = curl_init("https://" . rtrim($this->getOption("host"), "/") . "/api/v1/" . $action);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-API-Key: " . $this->getOption("api_key"),
            "Accept: application/json",
        ]);

        if ($method != "GET") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if (is_array($data) && count($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $res = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $data = @json_decode($res, true);
        if (!is_array($data)) {
            if (substr($status, 0, 1) != "2") {
                throw new Exception("HTTP status code $status");
            }

            if ($status != "204") {
                throw new Exception("Unexpected response");
            }
        }

        if (substr($status, 0, 1) != "2" && !empty($data["message"])) {
            throw new Exception($data["message"]);
        }

        return $data;
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        $username = $this->getUsername($id);
        while (strlen($username) > 11) {
            $username = substr($username, 0, -1);
        }

        $username = str_replace(array("ö", "ü", "ä", "ß"), array("oe", "ue", "ae", "ss"), strtolower($username));

        $password = $sec->generatePassword(12, false, "lud");

        try {
            $res = $this->Call("clients", [
                "username" => $username,
                "email" => $u->get()["mail"],
                "password" => $password,
                "notes" => "Vertrag $id",
                "id_hosting_plan" => $this->getOption("package"),
                "send_login_credentials" => false,
                "contact_data" => [
                    "first_name" => $u->get()['firstname'],
                    "last_name" => $u->get()['lastname'],
                    "company" => $u->get()['company'],
                    "client_id" => $u->get()['ID'],
                ],
            ], "POST");
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }

        if (empty($res["id"])) {
            return [false, "No user ID returned"];
        }

        return array(true, array(
            "id" => $res["id"],
            "username" => $username,
            "password" => $password,
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        try {
            $this->Call("clients/" . $this->getData("id"), [], "DELETE");
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }

        return array(true);
    }

    public function Output($id, $task = "")
    {
        global $sec;
        $this->loadOptions($id);

        ob_start();

        if ($task == "reset_pw") {
            $this->setData("password", $sec->generatePassword(12, false, "lud"));

            try {
                $this->Call("clients/" . $this->getData("id"), [
                    "password" => $this->getData("password"),
                ], "PUT");

                echo '<div class="alert alert-success">' . $this->getLang("newpwset") . '</div>';
            } catch (Exception $ex) {
                echo '<div class="alert alert-danger">' . $this->getLang("techerr") . '</div>';
            }

        }
        ?>
		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-default">
				  <div class="panel-heading"><?=$this->getLang("man");?></div>
				  <div class="panel-body">
				    <b><?=$this->getLang("URL");?>:</b> <a href="https://<?=$this->getOption("host");?>/" target="_blank">https://<?=$this->getOption("host");?>/</a><br />
				    <b><?=$this->getLang("username");?>:</b> <?=$this->getData("username");?><br />
				    <b><?=$this->getLang("password");?>:</b> <?=$this->getData("password");?>
				  </div>
				</div>
			</div>

			<div class="col-md-6">
				<div class="panel panel-default">
				  <div class="panel-heading"><?=$this->getLang("FTPCRED");?></div>
				  <div class="panel-body">
				    <b><?=$this->getLang("host");?>:</b> <?=$this->getOption("host");?> (<?=$this->getLang("PORT");?> 21)<br />
				    <b><?=$this->getLang("username");?>:</b> <?=$this->getData("username");?><br />
				    <b><?=$this->getLang("password");?>:</b> <?=$this->getData("password");?>
				  </div>
				</div>
			</div>
		</div>

		<form method="POST" action="https://<?=$this->getOption("host");?>/" target="_blank">
			<input type="hidden" name="username" value="<?=$this->getData("username");?>">
			<input type="hidden" name="password" value="<?=$this->getData("password");?>">
			<input type="hidden" name="lang" value="0">
			<input type="hidden" name="submit" value="1">
			<input type="submit" class="btn btn-block btn-primary" value="<?=$this->getLang("LOGNOW");?>">
		</form>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $status = true)
    {
        $this->loadOptions($id);

        try {
            $this->Call("clients/" . $this->getData("id"), [
                "is_suspended" => $status,
            ], "PUT");
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }

        return array(true);
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, false);
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        try {
            $this->Call("clients/" . $this->getData("id"), [
                "id_hosting_plan" => $this->getOption("package"),
            ], "PUT");
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }

        return array(true);
    }

    public function AssignDomain($id, $domain)
    {
        $this->loadOptions($id);

        try {
            $this->Call("domains", [
                "id_user" => $this->getData("id"),
                "domain" => $domain,
            ], "POST");
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }

        return array(true);
    }

    public function ClientChanged($id, array $changedFields)
    {
        if (!count(array_intersect($changedFields, ["company", "mail", "firstname", "lastname"]))) {
            return;
        }

        $this->loadOptions($id);
        $c = $this->getClient($id);

        $data = [
            "contact_data" => [],
        ];
        $data["contact_data"]["first_name"] = $c->get()["firstname"];
        $data["contact_data"]["last_name"] = $c->get()["lastname"];
        $data["contact_data"]["company"] = $c->get()["company"];
        $data["email"] = $c->get()["mail"];

        try {
            $this->Call("clients/" . $this->getData("id"), $data, "PUT");
        } catch (Exception $ex) {}
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
            "url" => "https://" . $this->getOption("host") . "/",
            "user" => $this->getData("username"),
            "password" => $this->getData("password"),
            "ftp_host" => $this->getOption("host"),
            "ftp_user" => $this->getData("username"),
            "ftp_password" => $this->getData("password"),
        );
    }

    public function ContractInfo($id)
    {
        $this->loadOptions($id);

        return [
            $this->getLang("USERNAME") => $this->getData("username"),
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

        $pwd = $req['pwd'];

        try {
            $this->Call("clients/" . $this->getData("id"), [
                "password" => $pwd,
            ], "PUT");
        } catch (Exception $ex) {
            die(json_encode(array("code" => "810", "message" => $ex->getMessage(), "data" => array())));
        }

        $this->setData("password", $pwd);

        die(json_encode(array("code" => "100", "message" => "Password changed successfully.", "data" => array())));
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
}