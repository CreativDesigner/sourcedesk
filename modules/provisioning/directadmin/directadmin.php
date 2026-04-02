<?php

class DirectAdminProv extends Provisioning
{
    protected $name = "DirectAdmin";
    protected $short = "directadmin";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;

    private function call($command, $data = null, $method = "POST")
    {
        if (!is_array($data)) {
            $data = [];
        }

        $ch = curl_init(rtrim($this->options['url'], "/") . "/" . $command);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->options['user'] . ":" . $this->options['password']);

        if (count($data)) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $res = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        parse_str($res, $data);
        return $data;
    }

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['url'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            foreach (["url", "user", "password"] as $k) {
                $this->options[$k] = $_POST[$k];
            }

            try {
                $plans = $this->call("CMD_API_PACKAGES_USER");
                if (!array_key_exists("list", $plans)) {
                    throw new Exception($this->getLang("noplans"));
                }

                $plans = $plans['list'];
                if (empty($plans)) {
                    throw new Exception($this->getLang("noplans"));
                }
            } catch (Exception $ex) {
                die('<div class="alert alert-danger">' . $ex->getMessage() . '</div>');
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
					<label><?=$this->getLang("URL");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://web.sourceway.de:2222" class="form-control prov_settings" />
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
					<label><?=$this->getLang("PASSWORD");?></label>
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
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=directadmin", {
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				url: $("[data-setting=url]").val(),
				user: $("[data-setting=user]").val(),
				password: $("[data-setting=password]").val(),
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

    public function Create($id)
    {
        global $sec, $CFG;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        try {
            $res = $this->call("CMD_API_ACCOUNT_USER", [
                "action" => "create",
                "add" => "Submit",
                "username" => $username = $this->getUsername($id),
                "email" => $u->get()['mail'],
                "passwd" => $pwd = $sec->generatePassword(12, false, "lud"),
                "passwd2" => $pwd,
                "package" => $this->getOption("plan"),
                "notify" => "no",
                "ip" => $this->call("CMD_API_SHOW_RESELLER_IPS")['list'][0],
            ]);

            if ($res['error']) {
                return [false, $res['details'] ?: $res['text']];
            }

            return [true, [
                "username" => $username,
                "pwd" => $pwd,
            ]];
        } catch (Exception $ex) {
            return [false, nl2br($ex->getMessage())];
        }
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        try {
            $res = $this->call("CMD_API_SELECT_USERS", [
                "location" => "CMD_SELECT_USERS",
                "confirmed" => "Confirm",
                "delete" => "yes",
                "select0" => $this->getData("username") ?: "c$id",
            ]);

            if ($res['error']) {
                return [false, $res['details'] ?: $res['text']];
            }

            return [true];
        } catch (Exception $ex) {
            return [false, nl2br($ex->getMessage())];
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
			<b><?=$this->getLang("URL");?>:</b> <a href="<?=$this->getOption("url");?>" target="_blank"><?=$this->getOption("url");?></a><br />
			<b><?=$this->getLang("USERNAME");?>:</b> <?=$this->getData("username") ?: "c$id";?><br />
			<b><?=$this->getLang("PASSWORD");?>:</b> <?=$this->getData("pwd");?>
			</div>
		</div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $field = "Suspend")
    {
        $this->loadOptions($id);

        try {
            $res = $this->call("CMD_API_SELECT_USERS", [
                "location" => "CMD_SELECT_USERS",
                "do" . strtolower($field) => $field,
                "select0" => $this->getData("username") ?: "c$id",
            ]);

            if ($res['error']) {
                return [false, $res['details'] ?: $res['text']];
            }

            return [true];
        } catch (Exception $ex) {
            return [false, nl2br($ex->getMessage())];
        }
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, "Unsuspend");
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
            "user" => $this->getData("username") ?: "c$id",
            "password" => $this->getData("pwd"),
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