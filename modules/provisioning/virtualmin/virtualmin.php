<?php

class VirtualminProv extends Provisioning
{
    protected $name = "Virtualmin";
    protected $short = "virtualmin";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['url'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $this->options['url'] = $_POST['url'];
            $this->options['user'] = $_POST['user'];
            $this->options['password'] = $_POST['password'];

            try {
                $res = $this->call('list-plans')['data'];

                $plans = [];

                for ($i = 2; $i < count($res); $i++) {
                    $plan = $res[$i]['name'];
                    $ex = explode("  ", $plan);

                    foreach ($ex as $k => $v) {
                        if ($v == "") {
                            unset($ex[$k]);
                        }
                    }

                    $ex = array_values($ex);

                    if (!empty($ex[1])) {
                        array_push($plans, $ex[1]);
                    }
                }

                if (!count($plans)) {
                    throw new Exception($this->getLang("noplans"));
                }

                $res = $this->call('list-templates')['data'];

                $templates = [];

                for ($i = 2; $i < count($res); $i++) {
                    $template = $res[$i]['name'];
                    $ex = explode("  ", $template);

                    foreach ($ex as $k => $v) {
                        if ($v == "") {
                            unset($ex[$k]);
                        }
                    }

                    $ex = array_values($ex);

                    if (!empty($ex[1])) {
                        array_push($templates, $ex[1]);
                    }
                }

                if (!count($templates)) {
                    throw new Exception($this->getLang("nosplans"));
                }
            } catch (Exception $ex) {
                die('<div class="alert alert-danger">' . $ex->getMessage() . '</div>');
            }

            $html = '<div class="row"><div class="col-md-6">';

            $html .= '<div class="form-group"><label>' . $this->getLang("ACCPLAN") . '</label><select data-setting="plan" class="form-control prov_settings">';

            foreach ($plans as $p) {
                if (!empty($this->getOption("plan")) && $this->getOption("plan") == $p) {
                    $html .= "<option value='$p' selected='selected'>$p</option>";
                } else {
                    $html .= "<option value='$p'>$p</option>";
                }
            }

            $html .= "</select></div>";

            $html .= '</div><div class="col-md-6">';

            $html .= '<div class="form-group"><label>' . $this->getLang("SRVPLAN") . '</label><select data-setting="template" class="form-control prov_settings">';

            foreach ($templates as $p) {
                if (!empty($this->getOption("template")) && $this->getOption("template") == $p) {
                    $html .= "<option value='$p' selected='selected'>$p</option>";
                } else {
                    $html .= "<option value='$p'>$p</option>";
                }
            }

            $html .= "</select></div>";

            $html .= "</div></div>";

            die($html);
        }

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("VURL");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://web.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("USERNAME");?></label>
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
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=virtualmin", {
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
		<?=!empty($this->getOption("url")) ? 'request();' : '';?>

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

    private function call($method, $data = null)
    {
        $ch = curl_init($this->getOption("url") . "/virtual-server/remote.cgi?json=1&program=" . urlencode($method));

        if (is_array($data) && count($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->getOption("user") . ":" . $this->getOption("password"));
        $res = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        if (strpos($res, "Unauthorized") !== false) {
            throw new Exception("Authentication failed.");
        }

        $res = @json_decode($res, true);

        if (!$res || !is_array($res)) {
            throw new Exception("Invalid server response");
        }

        return $res;
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        try {
            $res = $this->call("create-domain", [
                "domain" => $username = $this->getUsername($id),
                "user" => $username,
                "pass" => $pwd = $sec->generatePassword(12, false, "lud"),
                "email" => $u->get()['mail'],
                "template" => $this->getOption("template"),
                "plan" => $this->getOption("plan"),
                "features-from-plan" => "",
            ]);

            if (array_key_exists("error", $res) && !empty($res['error'])) {
                return [false, $res['error']];
            }

            if ($res['status'] != "success") {
                return [false];
            }

            return [true, [
                "username" => $username,
                "pwd" => $pwd,
            ]];
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }
    }

    public function Delete($id, $method = "delete")
    {
        $this->loadOptions($id);

        try {
            $res = $this->call($method . "-domain", [
                "domain" => $this->getData("username") ?: ("c" . $id),
            ]);

            if (array_key_exists("error", $res) && !empty($res['error'])) {
                return [false, $res['error']];
            }

            if ($res['status'] != "success") {
                return [false];
            }

            return [true];
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }
    }

    public function Output($id, $task = "")
    {
        $this->loadOptions($id);

        ob_start();
        ?>
		<div class="panel panel-default">
            <div class="panel-heading"><?=$this->getLang("CRED");?></div>
            <div class="panel-body">
                <b><?=$this->getLang("url");?>:</b> <a href="<?=$this->getOption("url");?>" target="_blank"><?=$this->getOption("url");?></a><br />
                <b><?=$this->getLang("username");?>:</b> <?=$this->getData("username") ?: "c$id";?><br />
                <b><?=$this->getLang("password");?>:</b> <?=$this->getData("pwd");?>
            </div>
        </div>

		<form method="POST" action="<?=rtrim($this->getOption("url"), "/") . "/session_login.cgi";?>" target="_blank">
			<input type="hidden" name="user" value="<?=$this->getData("username") ?: "c$id";?>">
			<input type="hidden" name="pass" value="<?=$this->getData("pwd");?>">
			<input type="submit" class="btn btn-block btn-primary" value="<?=$this->getLang("LOGINNOW");?>">
		</form>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id)
    {
        return $this->Delete($id, "disable");
    }

    public function Unsuspend($id)
    {
        return $this->Delete($id, "enable");
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
            "url" => $this->getOption("url"),
            "user" => $this->getData("username") ?: ("c" . $id),
            "password" => $this->getData("pwd"),
        );
    }

    public function ContractInfo($id)
    {
        $this->loadOptions($id);

        return [
            $this->getLang("USERNAME") => $this->getData("username") ?: "c" . $id,
        ];
    }
}