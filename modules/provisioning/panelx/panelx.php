<?php

class PanelXProv extends Provisioning
{
    protected $name = "PanelX";
    protected $short = "panelx";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;
    protected $version = "1.0";

    public function Config($id, $product = true)
    {
        global $lang;
        $this->loadOptions($id, $product);

        if (isset($_POST['email'])) {
            $cred = $_POST;
            if ($_POST['_mgmt_server']) {
                $cred = $this->serverData($_POST['_mgmt_server']);
            }

            $res = $this->Call("templates", [], "GET", $cred);

            if (!($res["status"] ?? false)) {
                die('<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . htmlentities($res["msg"] ?? "") . '</div>');
            }

            if (count($res["data"]) == 0) {
                die('<div class="alert alert-warning">' . $this->getLang("NOPLANS") . '</div>');
            }

            $html = '<div class="form-group"><label>' . $this->getLang("PLAN") . '</label><select data-setting="plan" class="form-control prov_settings">';

            foreach ($res["data"] as $p) {
                if (!empty($this->getOption("plan")) && $this->getOption("plan") == $p["id"]) {
                    $html .= "<option selected='selected' value='" . intval($p["id"]) . "'>" . htmlentities($p["name"]) . "</option>";
                } else {
                    $html .= "<option value='" . intval($p["id"]) . "'>" . htmlentities($p["name"]) . "</option>";
                }

            }

            $html .= "</select></div>";

            $res = $this->Call("server", [], "GET", $cred);

            if (!($res["status"] ?? false)) {
                die('<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . htmlentities($res["msg"] ?? "") . '</div>');
            }

            if (count($res["data"]) == 0) {
                die('<div class="alert alert-warning">' . $this->getLang("NOSERVER") . '</div>');
            }

            $html .= '<div class="form-group"><label>' . $this->getLang("SERVER") . '</label><select data-setting="server" class="form-control prov_settings">';

            foreach ($res["data"] as $p) {
                if (!empty($this->getOption("server")) && $this->getOption("server") == $p["id"]) {
                    $html .= "<option selected='selected' value='" . intval($p["id"]) . "'>" . htmlentities($p["hostname"]) . "</option>";
                } else {
                    $html .= "<option value='" . intval($p["id"]) . "'>" . htmlentities($p["hostname"]) . "</option>";
                }

            }

            $html .= "</select></div>";

            die($html);
        }

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("EMAIL");?></label>
					<input type="text" data-setting="email" value="<?=$this->getOption("email");?>" placeholder="admin@panelx.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("PASSWORD");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="<?=$this->getLang("PASSWORDP");?>" class="form-control prov_settings" />
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
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=panelx", {
				url: $("[data-setting=url]").val(),
				email: $("[data-setting=email]").val(),
				password: $("[data-setting=password]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("GDFS");?>');
				$("#server_conf").html(r);
			});
		}
		<?php if (!empty($this->getOption("email"))) {
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

    private function Call($func, $data = [], $method = null, $opt = false)
    {
        if (!$method) {
            $method = is_array($data) && count($data) ? "POST" : "GET";
        }

        if (!is_array($opt)) {
            $opt = $this->options;
        }

        $ch = curl_init("https://de.panelx.de/api/" . $func);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (is_array($data) && count($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        curl_setopt($ch, CURLOPT_USERPWD, $opt["email"] . ":" . $opt["password"]);
        $res = curl_exec($ch);
        curl_close($ch);

        $res = @json_decode($res, true);

        return $res;
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        $account = $u->getAccount("panelx");

        if (!$account) {
            $res = $this->Call("users", [
                "name" => $u->get()["name"],
                "email" => $u->get()["mail"],
                "password" => $pwd = $sec->generatePassword(16, false, "lud"),
            ]);

            if (!is_array($res) || !($res["status"] ?? false)) {
                return [false, $res["msg"] ?? ""];
            }

            $u->setAccount("panelx", $account = ["id" => $res["data"]["id"], "pwd" => $pwd]);
        }

        $res = $this->Call("contracts", [
            "name" => $con = $this->getUsername($id),
            "client" => $account["id"],
            "server_id" => $this->getOption("server"),
            "template" => $this->getOption("plan"),
        ]);

        if (!is_array($res) || !($res["status"] ?? false)) {
            return [false, $res["msg"] ?? ""];
        }

        return [true, [
            "name" => $con,
            "id" => $res["data"]["id"],
            "uid" => $account["id"],
            "email" => $u->get()["mail"],
            "pwd" => $account["pwd"],
        ]];
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $res = $this->Call("contracts/" . $this->getData("id"), [], "DELETE");

        if (!is_array($res) || !($res["status"] ?? false)) {
            return [false, $res["msg"] ?? ""];
        }

        return [true];
    }

    public function Output($id, $task = "")
    {
        global $pars, $raw_cfg, $CFG;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        ob_start();

        $url = "https://panelx.de";
        ?>
		<div class="panel panel-default">
            <div class="panel-heading"><?=$this->getLang("CRED");?></div>
            <div class="panel-body">
                <b><?=$this->getLang("URL");?>:</b> <a href="<?=$url;?>"><?=$url;?></a><br />
                <b><?=$this->getLang("EMAIL");?>:</b> <?=$this->getData("email");?><br />
                <b><?=$this->getLang("PASSWORD");?>:</b> <?=$this->getData("pwd");?></a>
            </div>
        </div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $status = false)
    {
        $this->loadOptions($id);

        $res = $this->Call("users/" . $this->getData("uid"), [
            "status" => $status,
        ]);

        if (!is_array($res) || !($res["status"] ?? false)) {
            return [false, $res["msg"] ?? ""];
        }

        return [true];
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, true);
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        $res = $this->Call("contracts/" . $this->getData("id"), [
            "template" => $this->getOption("plan"),
        ]);

        if (!is_array($res) || !($res["status"] ?? false)) {
            return [false, $res["msg"] ?? ""];
        }

        return [true];
    }

    public function AllEmailVariables()
    {
        return array(
            "url",
        );
    }

    public function EmailVariables($id)
    {
        global $raw_cfg;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        return array(
            "url" => $raw_cfg['PAGEURL'] . "/hosting/" . $id,
        );
    }
}