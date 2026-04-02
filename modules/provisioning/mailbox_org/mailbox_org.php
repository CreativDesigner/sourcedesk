<?php

class MailboxOrgProv extends Provisioning
{
    protected $name = "mailbox.org";
    protected $short = "mailbox_org";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;
    protected $accountLevel = "";

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['usr'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $this->options["usr"] = $_POST['usr'];
            $this->options["pwd"] = $_POST['pwd'];

            $api = $this->client();
            if (!is_object($api)) {
                die('<div class="alert alert-danger">' . htmlentities($api) . '</div>');
            }

            if ($this->accountLevel != "reseller") {
                die('<div class="alert alert-danger">' . $this->getLang("MBR") . '</div>');
            }

            ?>
            <div class="form-group">
                <label><?=$this->getLang("PLAN");?></label>
                <select data-setting="plan" class="form-control prov_settings">
                    <option value="basic"<?=$this->getOption("plan") == "basic" ? ' selected=""' : '';?>>Business Basic package</option>
                    <option value="profi"<?=$this->getOption("plan") == "profi" ? ' selected=""' : '';?>>Business Professional package</option>
                    <option value="profixl"<?=$this->getOption("plan") == "profixl" ? ' selected=""' : '';?>>Business Professional XL package</option>
                    <option value="reseller"<?=$this->getOption("plan") == "reseller" ? ' selected=""' : '';?>>Reseller special rate</option>
                </select>
            </div>
            <?php
exit;
        }

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("USR");?></label>
					<input type="text" data-setting="usr" value="<?=$this->getOption("usr");?>" placeholder="sourcedesk" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("PWD");?></label>
					<input type="password" data-setting="pwd" value="<?=$this->getOption("pwd");?>" placeholder="<?=$this->getLang("secret");?>" class="form-control prov_settings" />
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
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=mailbox_org", {
				usr: $("[data-setting=usr]").val(),
				pwd: $("[data-setting=pwd]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("GDFS");?>');
				$("#server_conf").html(r);
			});
		}
		<?php if (!empty($this->getOption("usr"))) {
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

    protected function client()
    {
        require_once __DIR__ . "/client.php";

        $api = new api_mailbox_org_client;
        $r = $api->call("auth", [
            "user" => $this->getOption("usr"),
            "pass" => $this->getOption("pwd"),
        ]);
        if ($r === false) {
            return $api->error_message();
        }

        $this->accountLevel = $r['level'] ?? "";
        $api->set_auth_id($r['session']);
        return $api;
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        $api = $this->client();
        if (is_string($api)) {
            return [false, $api];
        }

        $res = $api->call("account.add", [
            "account" => $username = $this->getUsername($id),
            "password" => $pwd = $sec->generatePassword(12, false, "lud"),
            "plan" => $this->getOption("plan"),
        ]);

        if (!$res) {
            return [false, strval($res) ?: ""];
        }

        return array(true, array(
            "username" => $username,
            "password" => $pwd,
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $api = $this->client();
        if (is_string($api)) {
            return [false, $api];
        }

        $res = $api->call("account.del", [
            "account" => $this->getData("username"),
        ]);

        return [boolval($res)];
    }

    public function Output($id, $task = "")
    {
        $this->loadOptions($id);

        ob_start();

        if ($task == "reset_pw") {
            $api = $this->client();
            if (is_string($api)) {
                return [false, $api];
            }

            $res = $api->call("account.set", [
                "account" => $this->getData("username"),
                "password" => $this->getData("password"),
            ]);

            if (!is_array($res)) {
                echo '<div class="alert alert-danger">' . $this->getLang("TECERR") . '</div>';
            } else {
                echo '<div class="alert alert-success">' . $this->getLang("RESOK") . '</div>';
            }
        }

        ?>
		<div class="panel panel-default">
            <div class="panel-heading"><?=$this->getLang("CRED");?></div>
            <div class="panel-body">
                <b><?=$this->getLang("URL");?>:</b> <a href="https://setup.mailbox.org" target="_blank">https://setup.mailbox.org</a><br />
                <b><?=$this->getLang("email");?>:</b> <?=$this->getData("username");?><br />
                <b><?=$this->getLang("password");?>:</b> <?=$this->getData("password");?>
            </div>
        </div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        $api = $this->client();
        if (is_string($api)) {
            return [false, $api];
        }

        $res = $api->call("account.set", [
            "account" => $this->getData("username"),
            "plan" => $this->getOption("plan"),
        ]);

        if (!is_array($res) || ($res['plan'] ?? "") != $this->getOption("plan")) {
            return [false];
        }

        return [true];
    }

    public function AllEmailVariables()
    {
        return array(
            "url",
            "user",
            "password",
        );
    }

    public function EmailVariables($id)
    {
        $this->loadOptions($id);

        return array(
            "url" => "https://setup.mailbox.org",
            "user" => $this->getData("username"),
            "password" => $this->getData("password"),
        );
    }

    public function ContractInfo($id)
    {
        $this->loadOptions($id);

        return [
            $this->getLang("EMAIL") => $this->getData("username"),
        ];
    }

    public function OwnFunctions($id)
    {
        return array(
            "reset_pw" => $this->getLang("RESPW"),
        );
    }
}