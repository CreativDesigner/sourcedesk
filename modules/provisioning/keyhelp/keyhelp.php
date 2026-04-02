<?php

class KeyHelpProv extends Provisioning
{
    protected $name = "KeyHelp Scraping (Legacy)";
    protected $short = "keyhelp";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;
    protected $version = "1.1";

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['host'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            require __DIR__ . "/KeyApi.class.php";
            $api = new KeyApi("https://" . $_POST['host']);
            if (!$api->login($_POST['user'], $_POST['password'])) {
                die('<div class="alert alert-danger">' . $this->getLang("logfail") . '</div>');
            }

            $packages = $api->getPackages();

            if (!is_array($packages)) {
                die('<div class="alert alert-danger">' . $this->getLang("techerr") . '</div>');
            }

            if (count($packages) == 0) {
                die('<div class="alert alert-danger">' . $this->getLang("Noplans") . '</div>');
            }

            ?>
			<div class="form-group">
				<label><?=$this->getLang("plan");?></label>
				<select data-setting="package" class="form-control prov_settings">
					<?php foreach ($packages as $id => $name) {?>
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
			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("hostname");?></label>
					<input type="text" data-setting="host" value="<?=$this->getOption("host");?>" placeholder="s1.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("username");?></label>
					<input type="text" data-setting="user" value="<?=$this->getOption("user");?>" placeholder="admin" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("password");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="GlMo=1O!" class="form-control prov_settings" />
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
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=keyhelp", {
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

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        $username = $this->getUsername($id);
        while (strlen($username) > 11) {
            $username = substr($username, 0, -1);
        }

        $password = $sec->generatePassword(12, false, "lud");

        require __DIR__ . "/KeyApi.class.php";
        $api = new KeyApi("https://" . $this->getOption('host'), true);
        if (!$api->login($this->getOption('user'), $this->getOption('password'))) {
            return array(false, $this->getLang("logfail"));
        }

        $data = array(
            "email" => $u->get()['mail'],
            "sendmail" => "0",
            "adminnotes" => "Vertrag $id",
            "firstname" => $u->get()['firstname'],
            "lastname" => $u->get()['lastname'],
            "company" => $u->get()['company'],
            "change_personal_data" => "0",
        );

        $username = str_replace(array("ö", "ü", "ä", "ß"), array("oe", "ue", "ae", "ss"), strtolower($username));

        if (!$api->createAccount($username, $password, $data, $this->getOption('package'), false)) {
            return array(false);
        }

        return array(true, array(
            "username" => $username,
            "password" => $password,
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        require __DIR__ . "/KeyApi.class.php";
        $api = new KeyApi("https://" . $this->getOption('host'), true);
        if (!$api->login($this->getOption('user'), $this->getOption('password'))) {
            return array(false, $this->getLang("logfail"));
        }

        if (!$api->deleteAccount($this->getData("username"))) {
            return array(false);
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

            require __DIR__ . "/KeyApi.class.php";
            $api = new KeyApi("https://" . $this->getOption('host'), true);
            if (!$api->login($this->getOption('user'), $this->getOption('password'))) {
                echo '<div class="alert alert-danger">' . $this->getLang("techerr") . '</div>';
            }

            if (!$api->changePassword($this->getData("username"), $this->getData("password"))) {
                echo '<div class="alert alert-danger">' . $this->getLang("techerr") . '</div>';
            } else {
                echo '<div class="alert alert-success">' . $this->getLang("newpwset") . '</div>';
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

    public function Suspend($id)
    {
        $this->loadOptions($id);

        require __DIR__ . "/KeyApi.class.php";
        $api = new KeyApi("https://" . $this->getOption('host'), true);
        if (!$api->login($this->getOption('user'), $this->getOption('password'))) {
            return array(false, $this->getLang("LOGFAIL"));
        }

        if (!$api->suspendAccount($this->getData("username"))) {
            return array(false);
        }

        return array(true);
    }

    public function Unsuspend($id)
    {
        $this->loadOptions($id);

        require __DIR__ . "/KeyApi.class.php";
        $api = new KeyApi("https://" . $this->getOption('host'), true);
        if (!$api->login($this->getOption('user'), $this->getOption('password'))) {
            return array(false, $this->getLang("LOGFAIL"));
        }

        if (!$api->unsuspendAccount($this->getData("username"))) {
            return array(false);
        }

        return array(true);
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        require __DIR__ . "/KeyApi.class.php";
        $api = new KeyApi("https://" . $this->getOption('host'), true);
        if (!$api->login($this->getOption('user'), $this->getOption('password'))) {
            return array(false, $this->getLang("LOGFAIL"));
        }

        if (!$api->changePackage($this->getData("username"), $this->getOption("package"))) {
            return array(false);
        }

        return array(true);
    }

    public function AssignDomain($id, $domain)
    {
        $this->loadOptions($id);

        require __DIR__ . "/KeyApi.class.php";
        $api = new KeyApi("https://" . $this->getOption('host'), true);
        if (!$api->login($this->getOption('user'), $this->getOption('password'))) {
            return array(false, $this->getLang("LOGFAIL"));
        }

        if (!$api->assignDomain($this->getData("username"), $domain)) {
            return array(false);
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

        $data = [];
        $data["firstname"] = $c->get()["firstname"];
        $data["lastname"] = $c->get()["lastname"];
        $data["company"] = $c->get()["company"];
        $data["email"] = $c->get()["mail"];

        require __DIR__ . "/KeyApi.class.php";
        $api = new KeyApi("https://" . $this->getOption('host'), true);
        if (!$api->login($this->getOption('user'), $this->getOption('password'))) {
            return;
        }

        $api->profileChange($this->getData("username"), $data);
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

        require __DIR__ . "/KeyApi.class.php";
        $api = new KeyApi("https://" . $this->getOption('host'), true);
        if (!$api->login($this->getOption('user'), $this->getOption('password'))) {
            die(json_encode(array("code" => "810", "message" => "Technical error occured.", "data" => array())));
        }

        if (!$api->changePassword($this->getData("username"), $pwd)) {
            die(json_encode(array("code" => "811", "message" => "Changing password failed. Maybe not complex enough?", "data" => array())));
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