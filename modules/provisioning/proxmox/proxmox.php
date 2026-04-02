<?php

class ProxmoxProv extends Provisioning
{
    protected $name = "Proxmox VE (CT)";
    protected $short = "proxmox";
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

            $templates = [];
            foreach ($nodes as &$v) {
                $v = $v["node"];

                @$storage = $this->call("nodes/$v/storage")["data"];

                foreach ($storage as $s) {
                    $res = $this->call("nodes/$v/storage/{$s['storage']}/content")["data"];
                    foreach ($res as $r) {
                        if ($r["content"] != "vztmpl") {
                            continue;
                        }

                        array_push($templates, $r["volid"]);
                    }
                }
            }
            unset($v);

            if (!is_array($templates) || !count($templates)) {
                die('<div class="alert alert-danger">' . $this->getLang("notemp") . '</div>');
            }
            ?>
			<div class="form-group">
				<label><?=$this->getLang("template");?></label>
				<select data-setting="template" class="form-control prov_settings">
					<?php
foreach ($templates as $template) {?>
					<option <?php if ($this->getOption('template') == $template) {
                echo ' selected=""';
            }
                ?>><?=$template;?></option>
					<?php }?>
				</select>
			</div>

			<div class="form-group">
				<label><?=$this->getLang("SERVER");?></label>
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
				<div class="col-md-4">
					<div class="form-group">
						<label><?=$this->getLang("CPUCORES");?></label>
						<input type="text" data-setting="cores" value="<?=$this->getOption("cores");?>" placeholder="1 - 128" class="form-control prov_settings" />
					</div>
				</div>

				<div class="col-md-4">
					<div class="form-group">
						<label><?=$this->getLang("CPULIMIT");?></label>
						<input type="text" data-setting="cpulimit" value="<?=$this->getOption("cpulimit");?>" placeholder="0 - 128" class="form-control prov_settings" />
					</div>
				</div>

				<div class="col-md-4">
					<div class="form-group">
						<label><?=$this->getLang("CPUUNITS");?></label>
						<input type="text" data-setting="cpuunits" value="<?=$this->getOption("cpuunits");?>" placeholder="0 - 500000" class="form-control prov_settings" />
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
						<label><?=$this->getLang("SWAPMB");?></label>
						<input type="text" data-setting="swap" value="<?=$this->getOption("swap");?>" placeholder="0 - <?=$this->getLang("unlimited");?>" class="form-control prov_settings" />
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label><?=$this->getLang("STORPOINT");?></label>
						<input type="text" data-setting="storagepoint" value="<?=$this->getOption("storagepoint") ?: "local";?>" placeholder="local" class="form-control prov_settings" />
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

		<a href="#" id="check_conn" class="btn btn-default btn-block" mgmt="0"><?=$this->getLang("CHECKCONN");?></a>

		<script>
        if (!mgmt) {
		var doing = false;
		function request(){
			if(doing) return;
			doing = !doing;
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("CHECKINGCONN");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=proxmox", {
				url: $("[data-setting=url]").val(),
				username: $("[data-setting=username]").val(),
				password: $("[data-setting=password]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("CHECKCONN");?>');
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
        $template = $this->getOption("template");

        $cores = $this->getOption("cores");
        $cpulimit = $this->getOption("cpulimit");
        $cpuunits = $this->getOption("cpuunits");
        $memory = $this->getOption("memory");
        $swap = $this->getOption("swap");
        $storagepoint = $this->getOption("storagepoint") ?: "local";
        $storage = $this->getOption("storage");
        $pwd = $sec->generatePassword(12, false, "lud");

        $bridge = $this->getDedicatedIP();
        if (!$bridge) {
            return [false, $this->getLang("NOIP")];
        }

        $res = $this->call("nodes/$node/lxc", [
            "ostemplate" => $template,
            "vmid" => $vmid,
            "cores" => $cores,
            "cpulimit" => $cpulimit,
            "cpuunits" => $cpuunits,
            "description" => "Vertrag #$id",
            "hostname" => "v$id.local",
            "memory" => $memory,
            "onboot" => "1",
            "password" => $pwd,
            "start" => "1",
            "swap" => $swap,
            "net0" => "name=net0,bridge=$bridge",
            "rootfs" => "$storagepoint:$storage",
        ]);

        if (substr($res['data'], 0, 5) != "UPID:") {
            return [false, implode(", ", $res['errors'])];
        }

        $this->call("access/users", [
            "userid" => ($username = $this->getUsername($id)) . "@pve",
            "password" => $pwd,
        ], "POST");

        $this->call("access/acl", [
            "path" => "/vms/$vmid",
            "users" => $username . "@pve",
            "roles" => "PVEVMUser",
            "propagate" => "1",
        ], "PUT");

        return [true, [
            "username" => $username,
            "vmid" => $vmid,
            "pwd" => $pwd,
        ]];
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $node = $this->getOption("server");
        $vmid = $this->getData("vmid");

        $this->call("access/users/" . ($this->getData("username") ?: "v$id") . "@pve", [], "DELETE");

        $this->call("access/acl", [
            "delete" => "1",
            "path" => "/vms/$vmid",
            "roles" => "PVEVMUser",
            "users" => ($this->getData("username") ?: "v$id") . "@pve",
        ], "PUT");

        $res = $this->call("nodes/$node/lxc/$vmid", [], "DELETE");
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
					<b><?=$this->getLang("IPA");?>:</b> <?=$ip;?>
					<br /><b><?=$this->getLang("RPW");?>:</b> <?=$this->getData("pwd");?>
				  </div>
				</div>
			</div>
			<div class="col-md-6">
				<?php $url = $this->getOption("url");?>
				<div class="panel panel-default" style="margin-bottom: 0;">
					<div class="panel-heading"><?=$this->getLang("MNGT");?></div>
					<div class="panel-body">
						<b><?=$this->getLang("url");?>:</b> <a href="<?=$url;?>" target="_blank"><?=$url;?></a><br />
						<b><?=$this->getLang("username");?>:</b> <?=($this->getData("username") ?: "v$id");?><br />
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
