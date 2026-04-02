<?php

class HetznerCloud extends Provisioning
{
    protected $name = "Hetzner Cloud";
    protected $short = "hetzner_cloud";
    protected $lang;
    protected $options = array();
    protected $ssh;
    protected $serverMgmt = true;

    private function call($action, $data = null, $method = "POST")
    {
        $ch = curl_init("https://api.hetzner.cloud/v1/$action");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $header = [
            "Authorization: Bearer " . $this->getOption("token"),
        ];

        if (is_array($data)) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $header[] = "Content-Type: application/json";
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res);
    }

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if ($this->getData("server_id") === false) {
            $this->setData("server_id", "");
        }

        if (isset($_POST['token'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $this->options["token"] = $_POST['token'];

            $types = $this->call("server_types");
            if (!isset($types->server_types)) {
                $fail = $this->getLang("CONNFAIL");

                if (!empty($types->error->message)) {
                    $fail = $types->error->message;
                }

                die("<div class='alert alert-danger'>$fail</div>");
            }

            $images = $this->call("images");
            if (!isset($images->images)) {
                $fail = $this->getLang("CONNFAIL");

                if (!empty($images->error->message)) {
                    $fail = $images->error->message;
                }

                die("<div class='alert alert-danger'>$fail</div>");
            }

            $locations = $this->call("locations");
            if (!isset($locations->locations)) {
                $fail = $this->getLang("CONNFAIL");

                if (!empty($locations->error->message)) {
                    $fail = $locations->error->message;
                }

                die("<div class='alert alert-danger'>$fail</div>");
            }

            ?>
            <div class="row">
                <div class="col-md-4">
                    <label><?=$this->getLang("STYPE");?></label>
                    <select data-setting="type" class="form-control prov_settings">
                        <?php foreach ($types->server_types as $t) {?>
                        <option value="<?=$t->name;?>"<?=$t->name == $this->getOption("type") ? ' selected="selected"' : '';?>><?=$t->description;?></option>
                        <?php }?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label><?=$this->getLang("LOC");?></label>
                    <select data-setting="location" class="form-control prov_settings">
                        <?php foreach ($locations->locations as $t) {?>
                        <option value="<?=$t->name;?>"<?=$t->name == $this->getOption("location") ? ' selected="selected"' : '';?>><?=$t->description;?></option>
                        <?php }?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label><?=$this->getLang("IMAGE");?></label>
                    <select data-setting="image" class="form-control prov_settings">
                        <?php foreach ($images->images as $t) {?>
                        <option value="<?=$t->name;?>"<?=$t->name == $this->getOption("image") ? ' selected="selected"' : '';?>><?=$t->description;?></option>
                        <?php }?>
                    </select>
                </div>
            </div><br />
            <?php
exit;
        }

        ob_start();?>

        <input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

        <div class="form-group" mgmt="1">
            <label><?=$this->getLang("TOKEN");?></label>
            <input type="password" data-setting="token" value="<?=$this->getOption("token");?>" placeholder="<?=$this->getLang("SECRET");?>" class="form-control prov_settings" />
        </div>

		<a href="#" id="check_conn" mgmt="0" class="btn btn-default btn-block"><?=$this->getLang("CONNC");?></a><br />

		<script>
        if (!mgmt) {
		var doing = false;
		function request(){
			if(doing) return;
			doing = !doing;
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("DBF");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=hetzner_cloud", {
				token: $("[data-setting=token]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("CONNC");?>');
				$("#server_conf").html(r);
			});
		}
		<?php if (!empty($this->getOption("token"))) {
            echo 'request();';
        }
        ?>

		$("#check_conn").click(function(e){
			e.preventDefault();
			request();
		});
        }
		</script>

		<div id="server_conf"></div>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Create($id)
    {
        $this->loadOptions($id);

        $res = $this->call("servers", [
            "name" => "s$id",
            "server_type" => $this->getOption("type"),
            "image" => $this->getOption("image"),
            "labels" => [
                "Vertrags-ID" => $id,
            ],
            "location" => $this->getOption("location"),
        ], "POST");

        if (!isset($res->server)) {
            if (!empty($res->error->message)) {
                return [false, $res->error->message];
            }

            return [false];
        }

        return array(true, array(
            "server_id" => $res->server->id,
            "pwd" => $res->root_password,
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $sid = $this->getData("server_id");
        $res = $this->call("servers/$sid", [], "DELETE");

        if (!isset($res->action) || $res->action->command != "delete_server") {
            if (!empty($res->error->message)) {
                return [false, $res->error->message];
            }

            return [false];
        }

        return [true];
    }

    private function shutdown()
    {
        $sid = $this->getData("server_id");
        $res = $this->call("servers/$sid/actions/shutdown", ["id" => $sid], "POST");
        return isset($res->action) && $res->action->command == "shutdown_server";
    }

    private function poweroff()
    {
        $sid = $this->getData("server_id");
        $res = $this->call("servers/$sid/actions/poweroff", ["id" => $sid], "POST");
        return isset($res->action) && $res->action->command == "stop_server";
    }

    private function poweron()
    {
        $sid = $this->getData("server_id");
        $res = $this->call("servers/$sid/actions/poweron", ["id" => $sid], "POST");
        return isset($res->action) && $res->action->command == "start_server";
    }

    private function online()
    {
        $sid = $this->getData("server_id");
        $res = $this->call("servers/$sid");
        return $res->server->status == "running";
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        $start = false;
        if ($this->online()) {
            if (!$this->shutdown()) {
                return [false, $this->getLang("SF")];
            }

            $start = true;

            for ($i = 0; $i <= 5; $i++) {
                if ($i == 5) {
                    $this->poweroff();
                    break;
                }

                if (!$this->online()) {
                    break;
                }

                sleep(5);
            }
        }

        $sid = $this->getData("server_id");
        $res = $this->call("servers/$sid/actions/change_type", [
            "upgrade_disk" => true,
            "server_type" => $this->getOption("type"),
        ], "POST");

        if (!isset($res->action) || $res->action->command != "change_server_type") {
            if (!empty($res->error->message)) {
                return [false, $res->error->message];
            }

            return [false];
        }

        if ($start) {
            $this->poweron();
        }

        return [true];
    }

    public function Output($id, $task = "")
    {
        global $pars;

        $this->loadOptions($id);
        $res = $this->call("servers/" . $this->getData("server_id"));

        if (!isset($res->server)) {
            return '<div class="alert alert-danger" style="margin-bottom: 0;">' . $this->getLang("SNF") . '</div>';
        }

        ob_start();

        if ($task == "poweron") {
            if ($this->poweron()) {
                $_SESSION['htz_html'] = '<div class="alert alert-success">' . $this->getLang("SPO") . '</div>';
            } else {
                $_SESSION['htz_html'] = '<div class="alert alert-danger">' . $this->getLang("SPOF") . '</div>';
            }

            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "shutdown") {
            if ($this->shutdown()) {
                $_SESSION['htz_html'] = '<div class="alert alert-success">' . $this->getLang("SSD") . '</div>';
            } else {
                $_SESSION['htz_html'] = '<div class="alert alert-danger">' . $this->getLang("SSDF") . '</div>';
            }

            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "poweroff") {
            if ($this->poweroff()) {
                $_SESSION['htz_html'] = '<div class="alert alert-success">' . $this->getLang("SOF") . '</div>';
            } else {
                $_SESSION['htz_html'] = '<div class="alert alert-danger">' . $this->getLang("SOFF") . '</div>';
            }

            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if (!empty($_SESSION['htz_html'])) {
            echo $_SESSION['htz_html'];
            $_SESSION['htz_html'] = "";
        }

        if ($task == "rdns") {
            if (isset($_POST['rdns']) && is_array($_POST['rdns'])) {
                $sid = $this->getData("server_id");

                foreach ($_POST['rdns'] as $ip => $rdns) {
                    if ($ip == "newv6") {
                        $ip = $_POST['newv6'];
                    }

                    $this->call("servers/$sid/actions/change_dns_ptr", [
                        "ip" => $ip,
                        "dns_ptr" => $rdns,
                    ]);
                }

                $res = $this->call("servers/" . $this->getData("server_id"));
            }
            ?>
			<div class="panel panel-default">
			  <div class="panel-heading"><?=$this->getLang("MANRDNS");?></div>
			  <div class="panel-body">
				<p style="text-align: justify;"><?=$this->getLang("RDNSI");?></p>

				<form method="POST" style="margin-top: 20px;">
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <tr>
                <th width="40%"><?=$this->getLang("IPV4");?></th>
                <th><?=$this->getLang("PTR");?></th>
              </tr>

              <tr>
                <td style="vertical-align: middle;"><?=$res->server->public_net->ipv4->ip;?></td>
                <td style="vertical-align: middle;"><input type="text" name="rdns[<?=$res->server->public_net->ipv4->ip;?>]" class="form-control input-sm" value="<?=$res->server->public_net->ipv4->dns_ptr;?>"></td>
              </tr>
            </table>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <tr>
                <th width="40%"><?=$this->getLang("IPV6");?></th>
                <th><?=$this->getLang("PTR");?></th>
              </tr>

                <?php
foreach ($res->server->public_net->ipv6->dns_ptr as $ip) {if (!filter_var($ip->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                continue;
            }
                ?>
              <tr>
                <td style="vertical-align: middle;"><?=$ip->ip;?></td>
                <td style="vertical-align: middle;"><input type="text" name="rdns[<?=$ip->ip;?>]" class="form-control input-sm" value="<?=$ip->dns_ptr;?>"></td>
              </tr>
              <?php }?>

              <tr>
                <td style="vertical-align: middle;"><input type="text" name="newv6" class="form-control input-sm" placeholder="<?=$this->getLang("NEWV6");?>"></td>
                <td style="vertical-align: middle;"><input type="text" name="rdns[newv6]" class="form-control input-sm" placeholder="<?=$this->getLang("NEWPTR");?>"></td>
              </tr>
            </table>
          </div>
          <div class="row">
            <div class="col-md-6">
					    <input type="submit" class="btn btn-primary btn-block" value="<?=$this->getLang("SAVERDNS");?>" />
            </div>
            <div class="col-md-6">
					    <a href="<?php unset($_GET['task']);
            echo isset($GLOBALS['adminInfo']) ? "?" . http_build_query($_GET) : "./";?>" class="btn btn-default btn-block"><?=$this->getLang("BACK");?></a>
            </div>
          </div>
				</form>
			  </div>
			</div>
		<?php } else {?>
		<div class="panel panel-default">
		  <div class="panel-heading"><i class="fa fa-circle" style="color: <?=$res->server->status == "running" ? "green" : "red";?>"></i> <?=$this->getLang("SERVER");?></div>
		  <div class="panel-body">
            <?php
echo "<b>{$this->getLang("IPV4")}:</b> " . htmlentities($res->server->public_net->ipv4->ip) . "<br />";
            echo "<b>{$this->getLang("IPV6")}:</b> " . htmlentities($res->server->public_net->ipv6->ip) . "<br />";
            echo "<b>{$this->getLang("OPSYS")}:</b> " . htmlentities($res->server->image->description) . "<br /><br />";

            echo "<b>{$this->getLang("INITRPW")}:</b> " . htmlentities($this->getData("pwd")) . "<br /><br />";

            echo "<b>{$this->getLang("INCTRAF")}:</b> " . number_format(round($res->server->ingoing_traffic / 1000000000, 2), 2, ',', '.') . " {$this->getLang("GB")}<br />";
            echo "<b>{$this->getLang("OUTTRAF")}:</b> " . number_format(round($res->server->outgoing_traffic / 1000000000, 2), 2, ',', '.') . " {$this->getLang("GB")}<br />";
            ?>
          </div>
		</div>
        <?php
}
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

    public function OwnFunctions($id)
    {
        $this->loadOptions($id);

        $actions = array();
        if (!$this->online()) {
            $actions["poweron"] = $this->getLang("ACTPO");
        } else {
            $actions["shutdown"] = $this->getLang("ACTSD");
            $actions["poweroff"] = $this->getLang("ACTSTOP");
        }

        $actions["rdns"] = $this->getLang("ACTRDNS");
        return $actions;
    }
}
