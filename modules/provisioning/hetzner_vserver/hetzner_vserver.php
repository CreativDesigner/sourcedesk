<?php

class HetznerVserver extends Provisioning
{
    protected $name = "Hetzner vServer";
    protected $short = "hetzner_vserver";
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

                die('<div class="form-group"><label>' . $this->getLang("SIP") . '</label><input type="text" class="form-control prov_settings" data-setting="server_id" value="' . htmlentities($this->getOption("server_id")) . '"></div>');
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
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=hetzner_vserver", {
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
        return [false];
    }

    public function Output($id, $task = "")
    {
        global $pars, $sec, $CFG;
        $this->loadOptions($id);
        $c = $this->client();

        ob_start();

        if ($task == "start") {
            $c->vServerStart($this->getOption("server_id"));
            $_SESSION['htz_html'] = '<div class="alert alert-success">' . $this->getLang("STARTOK") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "stop") {
            $c->vServerStop($this->getOption("server_id"));
            $_SESSION['htz_html'] = '<div class="alert alert-success">' . $this->getLang("STOPOK") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "shutdown") {
            $c->vServerShutdown($this->getOption("server_id"));
            $_SESSION['htz_html'] = '<div class="alert alert-success">' . $this->getLang("SHUTDOWNOK") . '</div>';
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

        ?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("IPAS");?></div>
		  <div class="panel-body">
			  <?php
              $ip = $this->getOption("server_id");
              echo "<i class='fa fa-spinner fa-spin server_status' data-ip='$ip'></i> &nbsp;$ip";
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

    public function OwnFunctions($id)
    {
        return [
            "start" => $this->getLang("START"),
            "stop" => $this->getLang("STOP"),
            "shutdown" => $this->getLang("SHUTDOWN"),
        ];
    }
}
