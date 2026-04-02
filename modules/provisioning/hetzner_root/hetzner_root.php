<?php

class HetznerRoot extends Provisioning
{
    protected $name = "Hetzner Rootserver";
    protected $short = "hetzner_root";
    protected $lang;
    protected $options = array();
    protected $ssh;
    protected $serverMgmt = true;

    private function client($usr = null, $pwd = null)
    {
        require_once __DIR__ . '/RobotRestClient.class.php';
        require_once __DIR__ . '/RobotClientException.class.php';
        require_once __DIR__ . '/RobotClient.class.php';
        return new RobotClient('https://robot-ws.your-server.de', $usr ?: $this->getOption('husr'), $pwd ?: $this->getOption('hpwd'));
    }

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if ($this->getData("robot_user") === false) {
            $this->setData("robot_user", "");
        }

        if ($this->getData("robot_password") === false) {
            $this->setData("robot_password", "");
        }

        if ($this->getData("server_id") === false) {
            $this->setData("server_id", "");
        }

        if (isset($_POST['husr'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $c = $this->client($_POST['husr'], $_POST['hpwd']);

            try {
                $r = $c->serverGetAll();
                if ($product) {
                    die('<div class="alert alert-success" style="margin-top: 20px; margin-bottom: 0;">' . $this->getLang("CONSUC") . '</div>');
                }

                if (count($r) == 0) {
                    die('<div class="alert alert-info" style="margin-top: 0px; margin-bottom: 20px;">' . $this->getLang("NOSERVERS") . '</div>');
                }

                echo '<select class="form-control prov_settings" data-setting="server_id" style="margin-top: 0px; margin-bottom: 20px;">';
                echo '<option value="0">' . $this->getLang("NSA") . '</option>';
                foreach ($r as $s) {
                    echo '<option value="' . $s->server->server_number . '"' . ($this->getOption("server_id") == $s->server->server_number ? ' selected=""' : '') . '>' . "{$s->server->product} ({$s->server->traffic}) #{$s->server->server_number}" . (!empty($s->server->server_name) ? " | " . $s->server->server_name : "") . '</option>';
                }

                die('</select>');
            } catch (RobotClientException $ex) {
                die('<div class="alert alert-danger" style="margin-top: 20px; margin-bottom: 0;">' . $this->getLang("CONFAIL") . '</div>');
            }
        }

        ob_start();?>

    <div class="row">
	<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("WSU");?></label>
					<input type="text" data-setting="husr" value="<?=$this->getOption("husr");?>" placeholder="#ws+..." class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
  					<label><?=$this->getLang("WSP");?></label>
  					<input type="password" data-setting="hpwd" value="<?=$this->getOption("hpwd");?>" placeholder="GlMo=1O!" class="form-control prov_settings" />
  				</div>
  		</div>
		</div>

		<a href="#" id="check_conn" class="btn btn-default btn-block"><?=$this->getLang("CHECKCON");?></a><br />

		<script>
        if (!mgmt) {
		var doing = false;
		function request(){
			if(doing) return;
			doing = !doing;
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("DBF");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=hetzner_root", {
				husr: $("[data-setting=husr]").val(),
				hpwd: $("[data-setting=hpwd]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("CHECKCON");?>');
				$("#server_conf").html(r);
			});
		}
		<?php if (!empty($this->getOption("husr"))) {
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
        global $sec;
        $this->loadOptions($id);

        // Order processing
        if ($this->getData("server_id")) {
            try {
                if (!$this->getData("order_id")) {
                    // Place a new order
                    $pwd = $sec->generatePassword(32, false, "lud");
                    $this->setData("root_password", $pwd);
                    $r = $this->client()->orderMarketServer($this->getData("server_id"), array(), $pwd, false);
                    if (empty($r->transaction->id)) {
                        return [false, "Order failed"];
                    }

                    $this->setData("order_id", $r->transaction->id);
                } else {
                    // Check order status
                    $r = $this->client()->orderServerMarketTransactionGet($this->getData("order_id"));
                    if ($r->transaction->status == "cancelled") {
                        return [false, "Order cancelled"];
                    }

                    if ($r->transaction->status != "ready") {
                        return ["wait"];
                    }

                    $this->setOption("server_id", $r->transaction->server_number);
                    return [true];
                }

                return ["wait"];
            } catch (RobotClientException $ex) {
                return [false, $ex->getMessage()];
            }
        }

        return [false];
    }

    public function Output($id, $task = "")
    {
        global $pars, $sec, $CFG;
        $this->loadOptions($id);
        $c = $this->client();

        ob_start();

        if ($task == "swr") {
            $r = $c->serverGet($this->getOption("server_id"))->server;
            $c->resetExecute($r->server_ip, "sw");
            $_SESSION['htz_html'] = '<div class="alert alert-success">' . $this->getLang("WFSR") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "hwr") {
            $r = $c->serverGet($this->getOption("server_id"))->server;
            $c->resetExecute($r->server_ip, "sw");
            $_SESSION['htz_html'] = '<div class="alert alert-success">' . $this->getLang("WFHR") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "manr") {
            $r = $c->serverGet($this->getOption("server_id"))->server;
            $c->resetExecute($r->server_ip, "man");
            $_SESSION['htz_html'] = '<div class="alert alert-success">' . $this->getLang("WFMR") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "wol") {
            $r = $c->serverGet($this->getOption("server_id"))->server;
            $c->wolSend($r->server_ip);
            $_SESSION['htz_html'] = '<div class="alert alert-success">' . $this->getLang("WFWOL") . '</div>';
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

        if ($task == "ping" || $pars[1] == "ping") {
            $r = $c->serverGet($this->getOption("server_id"))->server;
            $sv4 = $sv6 = [];
            foreach ($r->ip as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    array_push($sv4, $ip);
                } else {
                    array_push($sv6, $ip);
                }

            }

            if (filter_var($_POST['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                if (!in_array($_POST['ip'], $sv4)) {
                    exit;
                }

            } else {
                if (!in_array($_POST['ip'], $sv6)) {
                    exit;
                }

            }

            $ping = new Ping($_POST['ip']);
            if ($ping->ping() !== false) {
                die("ok");
            }

            exit;
        }

        if ($task == "rdns") {
            if (isset($_POST['rdns']) && is_array($_POST['rdns'])) {
                $r = $c->serverGet($this->getOption("server_id"))->server;
                $sv4 = $sv6 = [];
                $nv4 = $nv6 = [];

                foreach ($r->ip as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        array_push($sv4, $ip);
                    } else {
                        array_push($sv6, $ip);
                    }

                }

                foreach ($r->subnet as $subnet) {
                    $i = $c->subnetGet($subnet->ip)->subnet;
                    if (filter_var($subnet->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        array_push($nv4, $subnet->ip . "/" . $subnet->mask);
                    } else {
                        array_push($nv6, $subnet->ip . "/" . $subnet->mask);
                    }

                }

                $cm = new CIDRmatch();
                foreach ($_POST['rdns'] as $ip => $rdns) {
                    if ($ip == "newv4") {
                        if (empty($rdns)) {
                            continue;
                        }

                        if (empty($_POST['ip']['newv4'])) {
                            continue;
                        }

                        $ip = $_POST['ip']['newv4'];
                    } else if ($ip == "newv6") {
                        if (empty($rdns)) {
                            continue;
                        }

                        if (empty($_POST['ip']['newv6'])) {
                            continue;
                        }

                        $ip = $_POST['ip']['newv6'];
                    }

                    $match = false;
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        foreach ($sv4 as $s) {
                            if ($s == $ip) {
                                $match = true;
                            }
                        }

                        foreach ($nv4 as $s) {
                            if ($cm->match($ip, $s)) {
                                $match = true;
                            }
                        }

                    } else {
                        foreach ($sv6 as $s) {
                            if ($s == $ip) {
                                $match = true;
                            }
                        }

                        foreach ($nv6 as $s) {
                            if ($cm->match($ip, $s)) {
                                $match = true;
                            }
                        }

                    }
                    if (!$match) {
                        continue;
                    }

                    if (empty($rdns)) {
                        $c->rdnsDelete($ip);
                    } else {
                        $c->rdnsUpdate($ip, $rdns);
                    }

                }
            }

            try {
                $r = $c->rdnsGetAll($c->serverGet($this->getOption("server_id"))->server->server_ip);
            } catch (RobotClientException $ex) {
                return false;
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

              <?php foreach ($r as $ip) {if (!filter_var($ip->rdns->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                continue;
            }
                ?>
              <tr>
                <td style="vertical-align: middle;"><?=$ip->rdns->ip;?></td>
                <td style="vertical-align: middle;"><input type="text" name="rdns[<?=$ip->rdns->ip;?>]" class="form-control input-sm" value="<?=$ip->rdns->ptr;?>"></td>
              </tr>
              <?php }?>

              <tr>
                <td style="vertical-align: middle;"><input type="text" name="ip[newv4]" class="form-control input-sm" placeholder="<?=$this->getLang("NEWV4");?>"></td>
                <td style="vertical-align: middle;"><input type="text" name="rdns[newv4]" class="form-control input-sm" placeholder="<?=$this->getLang("NEWPTR");?>"></td>
              </tr>
            </table>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <tr>
                <th width="40%"><?=$this->getLang("IPV6");?></th>
                <th><?=$this->getLang("PTR");?></th>
              </tr>

              <?php foreach ($r as $ip) {if (!filter_var($ip->rdns->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                continue;
            }
                ?>
              <tr>
                <td style="vertical-align: middle;"><?=$ip->rdns->ip;?></td>
                <td style="vertical-align: middle;"><input type="text" name="rdns[<?=$ip->rdns->ip;?>]" class="form-control input-sm" value="<?=$ip->rdns->ptr;?>"></td>
              </tr>
              <?php }?>

              <tr>
                <td style="vertical-align: middle;"><input type="text" name="ip[newv6]" class="form-control input-sm" placeholder="<?=$this->getLang("NEWV6");?>"></td>
                <td style="vertical-align: middle;"><input type="text" name="rdns[newv6]" class="form-control input-sm" placeholder="<?=$this->getLang("NEWPTR");?>"></td>
              </tr>
            </table>
          </div>
          <div class="row">
            <div class="col-md-6">
					    <input type="submit" class="btn btn-primary btn-block" value="<?$this->getLang("SAVERDNS");?>" />
            </div>
            <div class="col-md-6">
					    <a href="<?php unset($_GET['task']);
            echo isset($GLOBALS['adminInfo']) ? "?" . http_build_query($_GET) : "./";?>" class="btn btn-default btn-block"><?=$this->getLang("BACK");?></a>
            </div>
          </div>
				</form>
			  </div>
			</div>
			<?php
} else if (!empty($this->getOption("server_id"))) {
            $r = $c->serverGet($this->getOption("server_id"))->server;
            if ($r->throttled) {
                ?>
    <div style="text-align: justify;" class="alert alert-info"><?=$this->getLang("DROSSEL");?></div>
    <?php }?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("IPAS");?></div>
		  <div class="panel-body">
			  <?php
$name = $this->getLang("CONTRACT") . " $id";
            if ($r->server_name != $name) {
                $c->servernameUpdate($r->server_ip, $name);
            }

            echo "<b>{$this->getLang("SINGLEIP")}</b>";
            foreach ($r->ip as $ip) {
                $i = $c->ipGet($ip)->ip;
                echo "<br /><i class='fa fa-spinner fa-spin server_status' data-ip='$ip'></i> &nbsp;$ip ({$this->getLang("GATEWAY")}: {$i->{gateway}}" . ($i->seperate_mac ? ", {$this->getLang("MAC")}: " . $i->seperate_mac : "") . ")";
            }

            if (count($r->subnet) > 0) {
                echo "<br /><br /><b>{$this->getLang("subnets")}</b>";
                foreach ($r->subnet as $subnet) {
                    $i = $c->subnetGet($subnet->ip)->subnet;
                    echo "<br />{$subnet->ip}/{$subnet->mask} ({$this->getLang("gateway")}: {$i->gateway})";
                }
            }
            ?>
        <script>
        $(".server_status").each(function(){
          var t = $(this);
          $.post("<?php $_GET['task'] = "ping";
            echo isset($GLOBALS['adminInfo']) ? "?" . http_build_query($_GET) : $CFG['PAGEURL'] . "hosting/" . $id . "/ping";?>", {
			"ip": t.data("ip"),
			"csrf_token": "<?=CSRF::raw();?>",
          }, function(r){
            if(r == "ok") t.removeClass().addClass("fa fa-circle").css("color", "green");
            else t.removeClass().addClass("fa fa-circle").css("color", "red");
          });
        });
        </script>
		  </div>
		</div>
    <?php if (!empty($this->getData("robot_user"))) {?>
    <form method="POST" action="https://robot.your-server.de/login/check" target="_blank">
      <input type="hidden" name="user" value="<?=$this->getData("robot_user");?>">
      <input type="hidden" name="password" value="<?=$this->getData("robot_password");?>">
      <input type="submit" class="btn btn-primary btn-block" value="<?=$this->getLang("LOGIN");?>" style="margin-top: -10px;">
    </form>
		<?php
}
        } else {
            echo '<div class="alert alert-info" style="margin-bottom: 0;">' . $this->getLang("NOSERVER") . '</div>';
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
        try {
            $c = $this->client();
            $r = $c->serverGet($this->getOption("server_id"))->server;
        } catch (RobotClientException $ex) {}

        $actions = array();
        if ($r->wol) {
            $actions["wol"] = $this->getLang("ACTWOL");
        }

        if ($r->reset) {
            $actions["swr"] = $this->getLang("ACTSWR");
            $actions["hwr"] = $this->getLang("ACTHWR");
        }
        $actions["manr"] = $this->getLang("ACTMR");
        $actions["rdns"] = $this->getLang("ACTRDNS");
        return $actions;
    }
}
