<?php

class GamepanelIo extends Provisioning
{
    protected $name = "GamePanel.io";
    protected $short = "gamepanel_io";
    protected $lang;
    protected $options = array();
    protected $ssh;
    protected $token;
    protected $serverMgmt = true;
    protected $usernameMgmt = true;

    private function call($action, $data = null, $method = null)
    {
        $ch = curl_init("https://" . $this->getOption("host") . "/api/v1/" . $action);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->getOption("token"),
        ]);
        if (is_array($data) && count($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        if ($method) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $res;
    }

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if ($this->getData("server_id") === false) {
            $this->setData("server_id", "");
        }

        if (isset($_POST['host'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $this->options["host"] = $_POST['host'];
            $this->options["token"] = $_POST['token'];

            $res = $this->call("plans?500");
            if (!is_array($res) || $res['error']) {
                if ($res['message']) {
                    die("<div class='alert alert-danger'>{$res['message']}</div>");
                } else {
                    die("<div class='alert alert-danger'>Connection Error</div>");
                }
            }

            if (!count($res)) {
                die("<div class='alert alert-danger'>" . $this->getLang("NOPLANS") . "</div>");
            }

            $plans = [];
            foreach ($res as $p) {
                $plans[$p['id']] = $p['name'];
            }

            $res = $this->call("games?500");
            if (!is_array($res) || !count($res)) {
                die("<div class='alert alert-danger'>" . $this->getLang("NOGAMES") . "</div>");
            }

            $games = [];
            foreach ($res as $g) {
                array_push($games, $g['name']);
            }
            ?>
            <div class="row">
                <div class="col-md-4">
                    <label><?=$this->getLang("PLAN");?></label>
                    <select data-setting="plan" class="form-control prov_settings">
                        <?php foreach ($plans as $i => $p) {?>
                        <option value="<?=$i;?>"<?=$i == $this->getOption("plan") ? ' selected="selected"' : '';?>><?=$p;?></option>
                        <?php }?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label><?=$this->getLang("game");?></label>
                    <select data-setting="game" class="form-control prov_settings">
                        <?php foreach ($games as $g) {?>
                        <option value="<?=$g;?>"<?=$g == $this->getOption("game") ? ' selected="selected"' : '';?>><?=$g;?></option>
                        <?php }?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label><?=$this->getLang("IPA");?></label>
                    <select data-setting="ip" class="form-control prov_settings">
                        <option value="auto"<?="auto" == $this->getOption("ip") ? ' selected="selected"' : '';?>><?=$this->getLang("auto");?></option>
                        <option value="dedicated"<?="dedicated" == $this->getOption("ip") ? ' selected="selected"' : '';?>><?=$this->getLang("DEDICATED");?></option>
                    </select>
                </div>
            </div><br />
            <?php
exit;
        }

        ob_start();?>

        <input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

        <div class="row" mgmt="1">
            <div class="col-md-6">
                <div class="form-group">
                    <label><?=$this->getLang("HOSTNAME");?></label>
                    <input type="text" data-setting="host" value="<?=$this->getOption("host");?>" placeholder="sourceway.mypanel.io" class="form-control prov_settings" />
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label><?=$this->getLang("PAT");?></label>
                    <input type="password" data-setting="token" value="<?=$this->getOption("token");?>" placeholder="<?=$this->getLang("SECRET");?>" class="form-control prov_settings" />
                </div>
            </div>
        </div>

		<a href="#" mgmt="0" id="check_conn" class="btn btn-default btn-block"><?=$this->getLang("CHECKCON");?></a><br />

		<script>
        if (!mgmt) {
		var doing = false;
		function request(){
			if(doing) return;
			doing = !doing;
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("DBF");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=gamepanel_io", {
                host: $("[data-setting=host]").val(),
				token: $("[data-setting=token]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("CHECKCON");?>');
				$("#server_conf").html(r);
			});
		}
		<?php if (!empty($this->getOption("token"))) {
            ?>request();<?php
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
        $u = $this->getClient($id);

        $res = $this->call("users", [
            "username" => $username = $this->getUsername($id),
            "fullName" => $u->get()['name'],
            "email" => $u->get()['mail'],
            "password" => $pwd = $sec->generatePassword(12, false, "lud"),
        ], "POST");

        if ($res['username'] != $username) {
            return [false, $res['message']];
        }

        $uid = $res["id"];

        $res = $this->call("servers", [
            "name" => "Server " . $id,
            "user" => $uid,
            "game" => $this->getOption("game"),
            "plan" => $this->getOption("plan"),
            "allocation" => $this->getOption("ip"),
        ], "POST");

        if ($res['message']) {
            return [false, $res['message']];
        }

        return [true, [
            "username" => $username,
            "sid" => $res['id'],
            "fid" => $res['friendlyId'],
            "uid" => $res['user'],
            "pwd" => $pwd,
            "ip" => $res['ip'],
            "port" => $res['port'],
        ]];
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $res = $this->call("servers/" . $this->getData("sid"), null, "DELETE");
        if ($res['message']) {
            return [false, $res['message']];
        }

        $res = $this->call("users/" . $this->getData("uid"), null, "DELETE");
        if ($res['message']) {
            return [false, $res['message']];
        }

        return [true];
    }

    public function Suspend($id, $locked = true)
    {
        $this->loadOptions($id);

        $res = $this->call("servers/" . $this->getData("sid"), [
            "suspended" => $locked,
        ], "PATCH");
        if ($res['message']) {
            return [false, $res['message']];
        }

        $res = $this->call("users/" . $this->getData("uid"), [
            "locked" => $locked,
        ], "PATCH");
        if ($res['message']) {
            return [false, $res['message']];
        }

        return [true];
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, false);
    }

    public function Output($id, $task = "")
    {
        global $pars;

        $this->loadOptions($id);

        ob_start();
        ?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("SERVER");?></div>
		  <div class="panel-body">
            <?php
echo "<b>" . $this->getLang("IP") . ":</b> " . htmlentities($this->getData("ip")) . "<br />";
        echo "<b>" . $this->getLang("PORT") . ":</b> " . htmlentities($this->getData("port")) . "<br /><br />";

        echo "<b>" . $this->getLang("PANELURL") . ":</b> <a href='https://" . $this->getOption("host") . "/' target='_blank'>" . $this->getOption("host") . "</a><br />";
        echo "<b>" . $this->getLang("username") . ":</b> " . ($this->getData("username") ?: "c$id") . "<br />";
        echo "<b>" . $this->getLang("Password") . ":</b> " . htmlentities($this->getData("pwd"));
        ?>
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
