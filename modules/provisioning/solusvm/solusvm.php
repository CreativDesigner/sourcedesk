<?php

class SolusVMProv extends Provisioning
{
    protected $name = "SolusVM";
    protected $short = "solusvm";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['host'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = array_merge(["server_type" => $_POST["server_type"]], $this->serverData($_POST['_mgmt_server']));
            }

            $this->options["host"] = $_POST['host'];
            $this->options["api_id"] = $_POST['api_id'];
            $this->options["api_password"] = $_POST['api_password'];
            $this->options["server_type"] = $_POST['server_type'];

            $s = $this->call("listnodes", ["type" => $this->options['server_type']]);
            $p = $this->call("listplans", ["type" => $this->options['server_type']]);

            if (!is_array($s) || empty($s = $s['nodes'])) {
                die('<div class="alert alert-danger">' . $this->getLang("CFNS") . '</div>');
            }

            if (!is_array($p) || count($p = $p['plans']) == 0) {
                die('<div class="alert alert-danger">' . $this->getLang("NOPLANS") . '</div>');
            }

            ?>
			<div class="form-group">
				<label><?=$this->getLang("plan");?></label>
				<select data-setting="plan" class="form-control prov_settings">
					<?php
$ex = explode(",", $p);
            foreach ($ex as $plan) {?>
					<option <?php if ($this->getOption('plan') == $plan) {
                echo ' selected=""';
            }
                ?>><?=$plan;?></option>
					<?php }?>
				</select>
			</div>

			<div class="form-group">
				<label><?=$this->getLang("server");?></label>
				<select data-setting="server" class="form-control prov_settings">
					<?php
$ex = explode(",", $s);
            foreach ($ex as $server) {?>
					<option<?php if ($this->getOption('server') == $server) {
                echo ' selected=""';
            }
                ?>><?=$server;?></option>
					<?php }?>
				</select>
			</div>

			<div class="row">
				<div class="col-md-4">
					<div class="form-group">
						<label><?=$this->getLang("OWRAM");?></label>
						<input type="text" data-setting="ram" value="<?=$this->getOption("ram");?>" placeholder="<?=$this->getLang("FN");?>" class="form-control prov_settings" />
					</div>
				</div>

				<div class="col-md-4">
					<div class="form-group">
						<label><?=$this->getLang("OWSPACE");?></label>
						<input type="text" data-setting="space" value="<?=$this->getOption("space");?>" placeholder="<?=$this->getLang("FN");?>" class="form-control prov_settings" />
					</div>
				</div>

				<div class="col-md-4">
					<div class="form-group">
						<label><?=$this->getLang("OWCPU");?></label>
						<input type="text" data-setting="cores" value="<?=$this->getOption("cores");?>" placeholder="<?=$this->getLang("FN");?>" class="form-control prov_settings" />
					</div>
				</div>
			</div>
			<?php
exit;
        }

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row">
			<div class="col-md-4" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("HOST");?></label>
					<input type="text" data-setting="host" value="<?=$this->getOption("host");?>" placeholder="vm01.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("APIID");?></label>
					<input type="password" data-setting="api_id" value="<?=$this->getOption("api_id");?>" placeholder="oyaiztmjo92xmxp6be7afwo53trpgohr" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("APIKEY");?></label>
					<input type="password" data-setting="api_password" value="<?=$this->getOption("api_password");?>" placeholder="5xyypj1lymnyvydfk14aqekhaiklebng" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-12" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("VTYPE");?></label>
					<select data-setting="server_type" class="form-control prov_settings">
						<option value="openvz"<?=$this->getOption("server_type") == "openvz" ? " selected=''" : "";?>>OpenVZ</option>
						<option value="kvm"<?=$this->getOption("server_type") == "kvm" ? " selected=''" : "";?>>KVM</option>
						<option value="xen"<?=$this->getOption("server_type") == "xen" ? " selected=''" : "";?>>Xen</option>
						<option value="xen hvm"<?=$this->getOption("server_type") == "xen hvm" ? " selected=''" : "";?>>Xen HVM</option>
					</select>
				</div>
			</div>
		</div>

		<a href="#" id="check_conn" mgmt="0" class="btn btn-default btn-block"><?=$this->getLang("CHECKCONN");?></a>

		<script>
        if (!mgmt) {
		var doing = false;
		function request(){
			if(doing) return;
			doing = !doing;
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("CHECKINGCONN");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=solusvm", {
				host: $("[data-setting=host]").val(),
				api_id: $("[data-setting=api_id]").val(),
				api_password: $("[data-setting=api_password]").val(),
				server_type: $("[data-setting=server_type]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("CHECKCONN");?>');
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

    private function call($action, $data = null)
    {
        $postfields = array();
        $postfields["id"] = $this->getOption("api_id");
        $postfields["key"] = $this->getOption("api_password");
        $postfields["action"] = $action;

        if (is_array($data)) {
            $postfields = array_merge($postfields, $data);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://" . $this->getOption("host") . ":5656/api/admin/command.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        $data = curl_exec($ch);
        curl_close($ch);

        preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $match);
        $result = array();
        foreach ($match[1] as $x => $y) {
            $result[$y] = $match[2][$x];
        }

        return $result;
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);

        $user = $this->getClient($id);
        $username = $this->getUsername($id);
        $pwd = Security::generatePassword(12, false, "lud");

        if (!is_object($user) || !($user instanceof User)) {
            return [false, "Customer not found"];
        }

        if (!empty($this->getData("vpsid"))) {
            return [false, "Server already exist"];
        }

        $acc = $user->getAccount("solusvm");

        if ($acc) {
            $username = $acc["username"] ?? "";
            $pwd = $acc["password"] ?? "";
        } else {
            $res = $this->call("client-create", [
                "username" => $username,
                "password" => $pwd,
                "email" => $user->get()['mail'],
                "firstname" => $user->get()['firstname'],
                "lastname" => $user->get()['lastname'],
            ]);

            if ($res['status'] != "success") {
                return [false, $res['statusmsg']];
            }

            $user->setAccount("solusvm", ["username" => $username, "password" => $pwd]);
        }

        $data = [
            "type" => $this->getOption("server_type"),
            "node" => $this->getOption("server"),
            "plan" => $this->getOption("plan"),
            "hostname" => "v" . $id . ".example",
            "password" => $pwd,
            "username" => $username,
            "ips" => 1,
            "randomipv4" => true,
        ];

        $ram = $this->getOption("ram");
        if (!empty($ram) && isset($this->cf[$ram])) {
            $data['custommemory'] = $this->cf[$ram] * 1024;
        }

        $space = $this->getOption("space");
        if (!empty($space) && isset($this->cf[$space])) {
            $data['customdiskspace'] = $this->cf[$space];
        }

        $cores = $this->getOption("cores");
        if (!empty($cores) && isset($this->cf[$cores])) {
            $data['customcpu'] = $this->cf[$cores];
        }

        $res = $this->call("vserver-create", $data);

        if ($res['status'] != "success") {
            return [false, $res['statusmsg']];
        }

        return array(true, array(
            "ip" => $res['mainipaddress'],
            "username" => $username,
            "password" => $pwd,
            "rootpassword" => $res['rootpassword'],
            "vpsid" => $res['vserverid'],
            "virtid" => $res['virtid'],
            "nodeid" => $res['nodeid'],
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $res = $this->call("vserver-terminate", [
            "vserverid" => $this->getData("vpsid"),
            "deleteclient" => true,
        ]);

        if ($res['status'] != "success") {
            return [false, $res['statusmsg']];
        }

        return array(true);
    }

    public function Output($id, $task = "")
    {
        global $pars, $sec;
        $this->loadOptions($id);

        ob_start();
        ?>
		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-default">
				  <div class="panel-heading"><?=$this->getLang("VSD");?></div>
				  <div class="panel-body">
					<b><?=$this->getLang("IPA");?>:</b> <?=$this->getData("ip");?>
					<br /><b><?=$this->getLang("RPW");?>:</b> <?=$this->getData("rootpassword");?>
				  </div>
				</div>
			</div>
			<div class="col-md-6">
				<?php $url = "https://" . $this->getOption("host") . ":5656/";?>
				<div class="panel panel-default" style="margin-bottom: 0;">
					<div class="panel-heading"><?=$this->getLang("MNGT");?></div>
					<div class="panel-body">
						<b><?=$this->getLang("URL");?>:</b> <a href="<?=$url;?>" target="_blank"><?=$url;?></a><br />
						<b><?=$this->getLang("username");?>:</b> <?=$this->getData("username");?><br />
						<b><?=$this->getLang("Password");?>:</b> <?=$this->getData("password");?>
					</div>
				</div>
			</div>
		</div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $prefix = "")
    {
        $this->loadOptions($id);

        $res = $this->call("vserver-{$prefix}suspend", [
            "vserverid" => $this->getData("vpsid"),
        ]);

        if ($res['status'] != "success") {
            return [false, $res['statusmsg']];
        }

        return array(true);
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, "un");
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        $res = $this->call("vserver-change", [
            "vserverid" => $this->getData("vpsid"),
            "plan" => $this->getData("plan"),
            "changehdd" => true,
        ]);

        if ($res['status'] != "success") {
            return [false, $res['statusmsg']];
        }

        $ram = $this->getOption("ram");
        if (!empty($ram) && isset($this->cf[$ram])) {
            $this->call("vserver-change-memory", [
                "vserverid" => $this->getData("vpsid"),
                "memory" => $this->cf[$ram] * 1024,
            ]);

            if ($res['status'] != "success") {
                return [false, $res['statusmsg']];
            }
        }

        $space = $this->getOption("space");
        if (!empty($space) && isset($this->cf[$space])) {
            $this->call("vserver-change-hdd", [
                "vserverid" => $this->getData("vpsid"),
                "hdd" => $this->cf[$space],
            ]);

            if ($res['status'] != "success") {
                return [false, $res['statusmsg']];
            }
        }

        $cores = $this->getOption("cores");
        if (!empty($cores) && isset($this->cf[$cores])) {
            $plan['cores'] = $this->cf[$cores];

            $this->call("vserver-change-cpu", [
                "vserverid" => $this->getData("vpsid"),
                "cpu" => $this->cf[$cores],
            ]);

            if ($res['status'] != "success") {
                return [false, $res['statusmsg']];
            }
        }

        return array(true);
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

        return array(
            "url" => $raw_cfg['PAGEURL'] . "/hosting/" . $id,
        );
    }
}
