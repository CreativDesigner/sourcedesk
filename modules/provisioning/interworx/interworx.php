<?php

class InterWorxProv extends Provisioning
{
    protected $name = "InterWorx";
    protected $short = "interworx";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;

    private function call($controller, $action, $data = null)
    {
        $auth = [
            "email" => $this->options['user'],
            "password" => $this->options['password'],
        ];

        if (!is_array($data)) {
            $data = [];
        }

        try {
            $client = new SoapClient($this->options['url'] . "/soap?wsdl");
            $res = $client->route($auth, $controller, $action, $data);

            if ($res['status'] !== 0) {
                throw new Exception($res['payload'] ?: "Internal Server Error");
            }

            return $res;
        } catch (SoapException $ex) {
            throw new Exception($ex->getMessage());
        } catch (SoapFault $ex) {
            throw new Exception($ex->getMessage());
        }
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
                $res = $this->call("/nodeworx/packages", "listPackages");

                if (!count($res['payload'])) {
                    throw new Exception($this->getLang("NOPLANS"));
                }

                $plans = [];
                foreach ($res['payload'] as $p) {
                    $plans[$p->id] = $p->name;
                }
            } catch (Exception $ex) {
                die('<div class="alert alert-danger">' . $ex->getMessage() . '</div>');
            }

            $html = '<div class="form-group"><label>' . $this->getLang("PLAN") . '</label><select data-setting="plan" class="form-control prov_settings">';

            foreach ($plans as $k => $p) {
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
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://web.sourceway.de:2443" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("mail");?></label>
					<input type="email" data-setting="user" value="<?=$this->getOption("user");?>" placeholder="admin@sourceway.de" class="form-control prov_settings" />
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
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=interworx", {
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

    public function Create($id)
    {
        global $sec, $CFG;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        try {
            $ip = $this->call("/nodeworx/siteworx", "listFreeIps")['payload'][0][0];

            $this->call("/nodeworx/siteworx", "add", [
                "domainname" => "c" . $id . ".example.com",
                "ipaddress" => $ip,
                "uniqname" => $username = $this->getUsername($id),
                "nickname" => $username,
                "email" => $u->get()['mail'],
                "password" => $pwd = $sec->generatePassword(12, false, "lud"),
                "confirm_password" => $pwd,
                "packagetemplate" => $this->getOption("plan"),
            ]);

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
            $this->call("/nodeworx/siteworx", "delete", [
                "domain" => "c" . $id . ".example.com",
                "confirm_action" => "1",
            ]);

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

    public function Suspend($id, $status = "0")
    {
        $this->loadOptions($id);

        try {
            $this->call("/nodeworx/siteworx", "edit", [
                "domain" => "c" . $id . ".example.com",
                "status" => $status,
            ]);

            return [true];
        } catch (Exception $ex) {
            return [false, nl2br($ex->getMessage())];
        }
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, "1");
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