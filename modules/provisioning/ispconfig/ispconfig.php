<?php

class ISPConfigProv extends Provisioning
{
    protected $name = "ISPConfig";
    protected $short = "ispconfig";
    protected $lang;
    protected $options = array();
    private $session_id;
    protected $serverMgmt = true;
    protected $usernameMgmt = true;

    private function call()
    {
        try {
            if (func_num_args() < 1) {
                return $this->getLang("NOARGS");
            }

            $url = rtrim($this->getOption("url"), "/") . "/remote/";

            $client = new SoapClient(null, [
                "location" => $url . "index.php",
                "uri" => $url,
            ]);

            if (empty($this->session_id)) {
                if (!($this->session_id = $client->login($this->getOption("username"), $this->getOption("password")))) {
                    return $this->getLang("LOGINFAIL");
                }
            }

            $args = func_get_args();
            $method = array_shift($args);
            $pars = [$this->session_id];

            if (count($args) > 0) {
                $pars = array_merge($pars, $args);
            }

            return call_user_func_array([$client, $method], $pars);
        } catch (SoapFault $ex) {
            return $ex->getMessage();
        }
    }

    public function __destruct()
    {
        if (!empty($this->session_id)) {
            $this->call("logout");
            $this->session_id = null;
        }
    }

    public function Config($id, $product = true)
    {
        global $lang;
        $this->loadOptions($id, $product);

        if (isset($_POST['url'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $this->options["url"] = $_POST['url'];
            $this->options["username"] = $_POST['username'];
            $this->options["password"] = $_POST['password'];

            $res = $this->call("client_templates_get_all");
            if (is_string($res)) {
                die('<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . htmlentities($res) . '</div>');
            }

            if (!count($res)) {
                die('<div class="alert alert-warning">' . $this->getLang("NOPLANS") . '</div>');
            }

            $html = '<div class="form-group"><label>' . $this->getLang("PLAN") . '</label><select data-setting="plan" class="form-control prov_settings">';

            foreach ($res as $p) {
                $id = $p['template_id'];
                $p = htmlentities($p['template_name']);

                if (!empty($this->getOption("plan")) && $this->getOption("plan") == $id) {
                    $html .= "<option selected='selected' value='$id'>$p</option>";
                } else {
                    $html .= "<option value='$id'>$p</option>";
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
					<label><?=$this->getLang("URL");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://isp.sourceway.de:8080" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("USER");?></label>
					<input type="text" data-setting="username" value="<?=$this->getOption("username");?>" placeholder="admin" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("PWD");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="<?=$this->getLang("SECRET");?>" class="form-control prov_settings" />
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
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=ispconfig", {
				url: $("[data-setting=url]").val(),
				username: $("[data-setting=username]").val(),
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

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        $res = $this->call("client_templates_get_all");
        if (is_string($res)) {
            return [false, $res];
        }

        $data = null;
        foreach ($res as $p) {
            if ($p['template_id'] == $this->getOption("plan")) {
                $data = $p;
            }
        }

        if (!is_array($data)) {
            return [false, $this->getLang("PNF")];
        }

        $data["company_name"] = $u->get()['company'];
        $data["contact_firstname"] = $u->get()['firstname'];
        $data["contact_name"] = $u->get()['lastname'];
        $data["customer_no"] = $id;
        $data["email"] = $u->get()['mail'];
        $data["notes"] = "Kunde #" . $u->get()['ID'] . ", Vertrag #$id";
        $data["username"] = $username = $this->getUsername($id);
        $data["password"] = $sec->generatePassword(12, false, "lud");
        $data["language"] = substr($u->get()['language'], 0, 2) ?: "de";
        $res = $this->call("client_add", 0, $data);

        if (!is_numeric($res)) {
            return [false, $res];
        }

        return [true, [
            "username" => $username,
            "client_id" => $res,
            "password" => $data["password"],
        ]];
    }

    public function ClientChanged($id, array $changedFields)
    {
        if (!count(array_intersect($changedFields, ["company", "mail", "firstname", "lastname"]))) {
            return;
        }

        $this->loadOptions($id);
        $c = $this->getClient($id);

        $res = $this->call("client_get", $this->getData("client_id"));
        if (empty($res["username"]) || $res["username"] != ($this->getData("username") ?: "c$id")) {
            return;
        }

        $res["contact_firstname"] = $c->get()["firstname"];
        $res["contact_name"] = $c->get()["lastname"];
        $res["company_name"] = $c->get()["company"];
        $res["email"] = $c->get()["mail"];
        $this->call("client_update", $this->getData("client_id"), 0, $res);
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $res = $this->call("client_delete_everything", $this->getData("client_id"));
        if (!$res) {
            return [false];
        }

        return [true];
    }

    public function Output($id, $task = "")
    {
        global $pars, $raw_cfg, $CFG;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        ob_start();
        ?>
        <div class="panel panel-default">
            <div class="panel-heading"><?=$this->getLang("CRED");?></div>
            <div class="panel-body">
            <b><?=$this->getLang("URL2");?>:</b> <a href="<?=$this->getOption("url");?>"><?=$this->getOption("url");?></a><br />
            <b><?=$this->getLang("USERNAME");?>:</b> <?=$this->getData("username") ?: "c$id";?><br />
            <b><?=$this->getLang("PASSWORD");?>:</b> <?=$this->getData("password");?>
            </div>
        </div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $locked = "y")
    {
        $this->loadOptions($id);

        $res = $this->call("client_get", $this->getData("client_id"));
        if (empty($res["username"]) || $res["username"] != ($this->getData("username") ?: "c$id")) {
            return [false, $this->getLang("CNFCD")];
        }

        $res["locked"] = $locked;
        $res = $this->call("client_update", $this->getData("client_id"), 0, $res);
        if ($res !== 1) {
            return [false, strval($res)];
        }

        return [true];
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, "n");
    }

    public function AllEmailVariables()
    {
        return array(
            "url",
            "username",
            "password",
        );
    }

    public function EmailVariables($id)
    {
        $this->loadOptions($id);

        return array(
            "url" => $this->getOption("url"),
            "username" => $this->getData("username") ?: "c$id",
            "password" => $this->getData("password"),
        );
    }

    public function ContractInfo($id)
    {
        $this->loadOptions($id);

        return [
            $this->getLang("USERNAME") => $this->getData("username") ?: "c$id",
        ];
    }
}