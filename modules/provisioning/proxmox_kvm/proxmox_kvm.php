<?php

class ProxmoxKvmProv extends Provisioning
{
    protected $name = "Proxmox VE (VM)";
    protected $short = "proxmox_kvm";
    protected $lang;
    protected $options = array();
    protected $ticket;
    protected $csrf;
    protected $serverMgmt = true;
    protected $usernameMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['url'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $this->options["url"] = $_POST['url'];
            $this->options["username"] = $_POST['username'];
            $this->options["password"] = $_POST['password'];

            @$nodes = $this->call("nodes")["data"];

            if (!is_array($nodes) || !count($nodes)) {
                die('<div class="alert alert-danger">' . $this->getLang("CFNN") . '</div>');
            }

            foreach ($nodes as &$v) {
                $v = $v["node"];
            }
            unset($v);
            ?>
			<div class="form-group">
				<label><?=$this->getLang("server");?></label>
				<select data-setting="server" class="form-control prov_settings">
					<?php
foreach ($nodes as $server) {?>
					<option<?php if ($this->getOption('server') == $server) {
                echo ' selected=""';
            }
                ?>><?=$server;?></option>
					<?php }?>
				</select>
			</div>

			<div class="row">
                <div class="col-md-6">
					<div class="form-group">
						<label><?=$this->getLang("cpusockets");?></label>
						<input type="text" data-setting="sockets" value="<?=$this->getOption("sockets");?>" placeholder="1 - 64" class="form-control prov_settings" />
					</div>
				</div>

				<div class="col-md-6">
					<div class="form-group">
						<label><?=$this->getLang("CPUCORES");?></label>
						<input type="text" data-setting="cores" value="<?=$this->getOption("cores");?>" placeholder="1 - 128" class="form-control prov_settings" />
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label><?=$this->getLang("MEMMB");?></label>
						<input type="text" data-setting="memory" value="<?=$this->getOption("memory");?>" placeholder="16 - <?=$this->getLang("unlimited");?>" class="form-control prov_settings" />
					</div>
				</div>

				<div class="col-md-6">
					<div class="form-group">
						<label><?=$this->getLang("storgb");?></label>
						<input type="text" data-setting="storage" value="<?=$this->getOption("storage");?>" placeholder="1 - <?=$this->getLang("unlimited");?>" class="form-control prov_settings" />
					</div>
				</div>
			</div>
			<?php
exit;
        }

        ob_start();?>
        <script>$("#ip_tab_btn").show();</script>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("url");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://your.server:8006" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("username");?></label>
					<input type="text" data-setting="username" value="<?=$this->getOption("username");?>" placeholder="root@pam" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("password");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="<?=$this->getLang("secret");?>" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<a href="#" mgmt="0" id="check_conn" class="btn btn-default btn-block"><?=$this->getLang("checkconn");?></a>

		<script>
        if (!mgmt) {
		var doing = false;
		function request(){
			if(doing) return;
			doing = !doing;
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("checkingconn");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=proxmox_kvm", {
				url: $("[data-setting=url]").val(),
				username: $("[data-setting=username]").val(),
				password: $("[data-setting=password]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("checkconn");?>');
				$("#server_conf").html(r);
			});
		}
		<?php if (!empty($this->getOption("url"))) {
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

    private function call($action, $data = null, $method = "POST")
    {
        if ($action != "access/ticket" && (empty($this->ticket) || empty($this->csrf))) {
            $res = $this->call("access/ticket", [
                "username" => $this->getOption("username"),
                "password" => $this->getOption("password"),
            ]);

            if (empty($res['data']['ticket']) || empty($res['data']['CSRFPreventionToken']) || $res['data']['username'] != $this->getOption("username")) {
                return false;
            }

            $this->ticket = $res['data']['ticket'];
            $this->csrf = $res['data']['CSRFPreventionToken'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getOption("url") . "/api2/json/" . $action);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);

        if (!empty($this->ticket)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Cookie: PVEAuthCookie=" . $this->ticket,
                "CSRFPreventionToken: " . $this->csrf,
            ));
        }

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if (is_array($data)) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);

        $vmid = $this->call("cluster/nextid");
        if (!isset($vmid['data']) || !is_numeric($vmid['data'])) {
            return [false, $this->getLang("VMIDFAIL")];
        }

        $vmid = $vmid["data"];
        $node = $this->getOption("server");

        $sockets = $this->getOption("sockets");
        $cores = $this->getOption("cores");
        $memory = $this->getOption("memory");
        $storage = $this->getOption("storage");
        $pwd = $sec->generatePassword(12, false, "lud");

        $bridge = $this->getDedicatedIP();
        if (!$bridge) {
            return [false, $this->getLang("NOIP")];
        }

        $res = $this->call("nodes/$node/qemu", [
            "vmid" => $vmid,
            "name" => "s" . $id,
            "ide2" => "none,media=cdrom",
            "ostype" => "l26",
            "scsihw" => "virtio-scsi-pci",
            "scsi0" => "local:$storage,format=qcow2",
            "sockets" => $sockets,
            "cores" => $cores,
            "numa" => "0",
            "memory" => $memory,
            "net0" => "virtio,bridge=" . $bridge,
        ]);

        if (substr($res['data'], 0, 5) != "UPID:") {
            return [false, implode(", ", $res['errors'])];
        }

        $this->call("access/users", [
            "userid" => ($this->getData("username") ?: "s$id") . "@pve",
            "password" => $pwd,
        ], "POST");

        $this->call("access/acl", [
            "path" => "/vms/$vmid",
            "users" => ($this->getData("username") ?: "s$id") . "@pve",
            "roles" => "PVEVMUser",
            "propagate" => "1",
        ], "PUT");

        return [true, [
            "username" => ($this->getData("username") ?: "s$id") . "@pve",
            "vmid" => $vmid,
            "pwd" => $pwd,
        ]];
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $node = $this->getOption("server");
        $vmid = $this->getData("vmid");

        $this->call("access/users/" . ($this->getData("username") ?: "s$id") . "@pve", [], "DELETE");

        $this->call("access/acl", [
            "delete" => "1",
            "path" => "/vms/$vmid",
            "roles" => "PVEVMUser",
            "users" => ($this->getData("username") ?: "s$id") . "@pve",
        ], "PUT");

        $res = $this->call("nodes/$node/qemu/$vmid", [], "DELETE");
        return [boolval($res['data'])];
    }

    public function Output($id, $task = "")
    {
        global $pars, $sec;
        $this->loadOptions($id);

        $ip = "<i>{$this->getLang("NOIPAS")}</i>";

        $node = $this->getOption("server");
        $iface = $this->getDedicatedIP();

        $res = $this->call("nodes/$node/network/$iface");
        if (@!empty($res['data']['address'])) {
            $ip = $res['data']['address'];
        }

        ob_start();
        ?>
		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-default">
				  <div class="panel-heading"><?=$this->getLang("SDATA");?></div>
				  <div class="panel-body">
					<b><?=$this->getLang("ipa");?>:</b> <?=$ip;?>
				  </div>
				</div>
			</div>
			<div class="col-md-6">
				<?php $url = $this->getOption("url");?>
				<div class="panel panel-default" style="margin-bottom: 0;">
					<div class="panel-heading"><?=$this->getLang("MNGT");?></div>
					<div class="panel-body">
						<b><?=$this->getLang("url");?>:</b> <a href="<?=$url;?>" target="_blank"><?=$url;?></a><br />
						<b><?=$this->getLang("username");?>:</b> <?=($this->getData("username") ?: "s$id");?><br />
						<b><?=$this->getLang("password");?>:</b> <?=$this->getData("pwd");?><br />
						<b><?=$this->getLang("REALM");?>:</b> Proxmox VE
					</div>
				</div>
			</div>
		</div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
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
