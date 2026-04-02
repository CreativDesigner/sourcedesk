<?php

class OpenVZWebPanelProv extends Provisioning
{
    protected $name = "OpenVZ Web Panel";
    protected $short = "owp";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['hostname'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $this->options["hostname"] = $_POST['hostname'];
            $this->options["username"] = $_POST['username'];
            $this->options["password"] = $_POST['password'];

            $nodes = $serverTemplates = $osTemplates = [];

            $res = $this->call("hardware_servers/list");
            if (!is_array($res->hardware_server) || !count($res->hardware_server)) {
                die('<div class="alert alert-danger">' . $this->getLang("CFNN") . '</div>');
            }

            foreach ($res->hardware_server as $hardwareServer) {
                $nodes[] = strval($hardwareServer->host);
                $serverTemplatesResult = $this->call("hardware_servers/server_templates", ["id" => intval($hardwareServer->id)]);
                foreach ($serverTemplatesResult as $serverTemplate) {
                    $serverTemplates[] = strval($serverTemplate->name);
                }

                $osTemplatesResult = $this->call("hardware_servers/os_templates", ["id" => intval($hardwareServer->id)]);
                foreach ($osTemplatesResult as $osTemplate) {
                    $osTemplates[] = strval($osTemplate->name);
                }
            }

            $serverTemplates = array_unique($serverTemplates);
            $osTemplates = array_unique($osTemplates);

            if (!count($serverTemplates)) {
                die('<div class="alert alert-danger">' . $this->getLang("NOTPL") . '</div>');
            }

            if (!count($osTemplates)) {
                die('<div class="alert alert-danger">' . $this->getLang("NOOS") . '</div>');
            }

            $userRoles = [];
            $res = $this->call("roles/list");
            foreach ($res->role as $role) {
                $userRoles[] = $role->name;
            }

            if (!count($userRoles)) {
                die('<div class="alert alert-danger">' . $this->getLang("NOROLES") . '</div>');
            }
            ?>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label><?=$this->getLang("PLAN");?></label>
                        <select data-setting="plan" class="form-control prov_settings">
                            <?php
$ex = explode(",", $serverTemplates);
            foreach ($ex as $plan) {?>
                            <option <?php if ($this->getOption('plan') == $plan) {
                echo ' selected=""';
            }
                ?>><?=$plan;?></option>
                            <?php }?>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label><?=$this->getLang("SERVER");?></label>
                        <select data-setting="server" class="form-control prov_settings">
                            <?php
$ex = explode(",", $nodes);
            foreach ($ex as $server) {?>
                            <option <?php if ($this->getOption('server') == $server) {
                echo ' selected=""';
            }
                ?>><?=$server;?></option>
                            <?php }?>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label><?=$this->getLang("OS");?></label>
                        <select data-setting="os" class="form-control prov_settings">
                            <?php
$ex = explode(",", $osTemplates);
            foreach ($ex as $os) {?>
                            <option <?php if ($this->getOption('os') == $os) {
                echo ' selected=""';
            }
                ?>><?=$os;?></option>
                            <?php }?>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label><?=$this->getLang("ROLE");?></label>
                        <select data-setting="role" class="form-control prov_settings">
                            <?php
$ex = explode(",", $userRoles);
            foreach ($ex as $role) {?>
                            <option <?php if ($this->getOption('role') == $role) {
                echo ' selected=""';
            }
                ?>><?=$role;?></option>
                            <?php }?>
                        </select>
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
					<label><?=$this->getLang("HOSTNAME");?></label>
					<input type="text" data-setting="hostname" value="<?=$this->getOption("hostname");?>" placeholder="vm01.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("USERNAME");?></label>
					<input type="text" data-setting="username" value="<?=$this->getOption("username");?>" placeholder="admin" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("PASSWORD");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="<?=$this->getLang("SECRET");?>" class="form-control prov_settings" />
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
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=owp", {
				hostname: $("[data-setting=hostname]").val(),
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
		<?php if (!empty($this->getOption("hostname"))) {
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

    private function call($method, $data = [])
    {
        $params = "";
        if (is_array($data) && count($data)) {
            $params = "?" . http_build_query($data);
        }

        $ch = curl_init("https://" . $this->getOption("hostname") . "/api/$method$params");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->getOption("username") . ":" . $this->getOption("password"));
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) {
            return false;
        }

        return simplexml_load_string($res);
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);

        $ipAddress = $this->getDedicatedIP();
        if (!$ipAddress) {
            return [false, $this->getLang("NOIP")];
        }

        $client = $this->getClient($id);
        if (!is_object($client) || !($client instanceof User)) {
            $this->releaseDedicatedIP();
            return [false, $this->getLang("NOCL")];
        }

        $res = $this->call("users/create", [
            "login" => $username = $this->getUsername($id),
            "password" => $pwd = $sec->generatePassword(12, false, "lud"),
            "role_id" => intval($this->call("roles/get_by_name", ["name" => $this->getOption("role")])->id),
            "contact_name" => $client->get()['name'],
            "email" => $client->get()['mail'],
        ]);

        if ($res->getName() === "error") {
            $this->releaseDedicatedIP();
            return [false, strval($res->message)];
        }

        $uid = intval($res->details->id);

        $res = $this->call("virtual_servers/create", [
            "hardware_server_id" => intval($this->call("hardware_servers/get_by_host", ["host" => $this->getOption("node")])->id),
            "orig_os_template" => $this->getOption("os"),
            "orig_server_template" => $this->getOption("plan"),
            "host_name" => "s" . $id . ".local",
            "ip_address" => $ipAddress,
            "password" => $pwd,
            "user_id" => $uid,
        ]);

        if ($res->getName() === "error") {
            $this->releaseDedicatedIP();
            return [false, strval($res->message)];
        }

        $vid = intval($res->details->id);

        $res = $this->call("virtual_servers/start", [
            "id" => $vid,
        ]);

        if ($res->getName() === "error") {
            $this->releaseDedicatedIP();
            return [false, strval($res->message)];
        }

        return [true, [
            "ip_address" => $ipAddress,
            "password" => $pwd,
            "user_id" => $uid,
            "server_id" => $vid,
            "username" => $username,
        ]];
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $res = $this->call("users/delete", [
            "id" => $this->getData("user_id"),
        ]);

        if ($res->getName() === "error") {
            return [false, strval($res->message)];
        }

        $res = $this->call("virtual_servers/delete", [
            "id" => $this->getData("server_id"),
        ]);

        if ($res->getName() === "error") {
            return [false, strval($res->message)];
        }

        $this->releaseDedicatedIP();

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
				  <div class="panel-heading"><?=$this->getLang("SD");?></div>
				  <div class="panel-body">
					<b><?=$this->getLang("IPA");?>:</b> <?=$this->getData("ip_address");?>
					<br /><b><?=$this->getLang("RPW");?>:</b> <?=$this->getData("password");?>
				  </div>
				</div>
			</div>
			<div class="col-md-6">
				<?php $url = "https://" . $this->getOption("hostname") . "/";?>
				<div class="panel panel-default" style="margin-bottom: 0;">
					<div class="panel-heading"><?=$this->getLang("MANAGE");?></div>
					<div class="panel-body">
						<b><?=$this->getLang("URL");?>:</b> <a href="<?=$url;?>" target="_blank"><?=$url;?></a><br />
						<b><?=$this->getLang("USERNAME");?>:</b> <?=$this->getData("username") ?: ("s" . $id);?><br />
						<b><?=$this->getLang("PASSWORD");?>:</b> <?=$this->getData("password");?>
					</div>
				</div>
			</div>
		</div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $method = "disable", $method2 = "stop")
    {
        $this->loadOptions($id);

        $res = $this->call("users/$method", [
            "id" => $this->getData("user_id"),
        ]);

        if ($res->getName() === "error") {
            return [false, strval($res->message)];
        }

        $res = $this->call("virtual_servers/$method2", [
            "id" => $this->getData("server_id"),
        ]);

        if ($res->getName() === "error") {
            return [false, strval($res->message)];
        }

        return array(true);
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, "enable", "start");
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