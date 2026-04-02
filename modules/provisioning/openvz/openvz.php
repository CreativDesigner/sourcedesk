<?php

class OVZProv extends Provisioning
{
    protected $name = "OpenVZ";
    protected $short = "openvz";
    protected $lang;
    protected $options = array();
    protected $ssh;
    protected $serverMgmt = true;

    public function loadOptions($id, $pd = false)
    {
        parent::loadOptions($id, $pd);

        foreach (["cpus", "ram", "hdd"] as $o) {
            if (!is_numeric($this->options[$o]) && array_key_exists($this->options[$o], $this->cf)) {
                $this->options[$o] = $this->cf[$o];
            }
        }

    }

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['ssh_host'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $os = $this->osList($_POST['ssh_host'], $_POST['ssh_password']);
            if (!$os) {
                die('<div class="alert alert-danger">' . $this->getLang("CONNFAIL") . '</div>');
            }

            if (count($os) == 0) {
                die('<div class="alert alert-danger">' . $this->getLang("NOOS") . '</div>');
            }

            $html = '<div class="form-group"><label>' . $this->getLang("DEFOS") . '</label><select data-setting="default_os" class="form-control prov_settings">';

            foreach ($os as $p) {
                if (!empty($this->getOption("default_os")) && $this->getOption("default_os") == $p) {
                    $html .= "<option value='$p' selected='selected'>$p</option>";
                } else {
                    $html .= "<option value='$p'>$p</option>";
                }

            }

            $html .= "</select></div>";
            die($html);
        }

        ob_start();?>
		<script>$("#ip_tab_btn").show();</script>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("SSHHOST");?></label>
					<input type="text" data-setting="ssh_host" value="<?=$this->getOption("ssh_host");?>" placeholder="vz01.sourceway.de[:923]" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("ROOTPW");?></label>
					<input type="password" data-setting="ssh_password" value="<?=$this->getOption("ssh_password");?>" placeholder="GlMo=1O!" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<div class="row" mgmt="0">
			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("CPUCORES");?></label>
					<input type="text" data-setting="cpus" value="<?=$this->getOption("cpus");?>" placeholder="1" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("RAM");?></label>
					<div class="input-group">
						<input type="text" data-setting="ram" value="<?=$this->getOption("ram");?>" placeholder="1" class="form-control prov_settings" />
						<span class="input-group-addon"><?=$this->getLang("GB");?></span>
					</div>
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("STORAGE");?></label>
					<div class="input-group">
						<input type="text" data-setting="hdd" value="<?=$this->getOption("hdd");?>" placeholder="100" class="form-control prov_settings" />
						<span class="input-group-addon"><?=$this->getLang("GB");?></span>
					</div>
				</div>
			</div>
		</div>

		<div class="alert alert-info" mgmt="0"><?=$this->getLang("WIKI");?></div>

		<a href="#" id="check_conn" mgmt="0" class="btn btn-default btn-block"><?=$this->getLang("GDFS");?></a>

		<script>
        if (!mgmt) {
		var doing = false;
		function request(){
			if(doing) return;
			doing = !doing;
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("DBF");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=openvz", {
				ssh_host: $("[data-setting=ssh_host]").val(),
				ssh_password: $("[data-setting=ssh_password]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("GDFS");?>');
				$("#server_conf").html(r);
			});
		}
		<?php if (!empty($this->getOption("ssh_host"))) {
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

    private function osList($host = false, $password = false)
    {
        $res = $this->ssh("ls -1 /var/lib/vz/template/cache/", $host, $password);
        if (!$res) {
            return false;
        }

        $ex = explode("\n", $res);

        $os = array();
        foreach ($ex as $k => $v) {
            if (empty($v)) {
                continue;
            }

            $ending = substr($v, -7);
            if ($ending != ".tar.gz") {
                continue;
            }

            $os[] = substr($v, 0, strlen($v) - 7);
        }

        return $os;
    }

    private function ssh($command, $host = false, $password = false)
    {
        try {
            if (!$this->ssh) {
                $ex = explode(":", $host === false ? $this->getOption("ssh_host") : $host);
                if (count($ex) == 1) {
                    $host = $ex[0];
                    $port = 22;
                } else if (count($ex) == 2 && intval($ex[1]) == $ex[1]) {
                    $host = $ex[0];
                    $port = intval($ex[1]);
                } else {
                    return false;
                }

                $ssh = new \phpseclib\Net\SSH2($host, $port);
                if (!$ssh->login("root", $password === false ? $this->getOption("ssh_password") : $password)) {
                    return false;
                }

                $this->ssh = $ssh;
            }

            return $this->ssh->exec($command);
        } catch (RuntimeException $ex) {
            return false;
        }
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        $password = $sec->generatePassword(16, false, "lud");
        $os = $this->getOption("default_os");

        $oslist = $this->osList();
        if (false === $oslist) {
            return array(false, $this->getLang("ERRC"));
        }

        if (!in_array($os, $oslist)) {
            return array(false, $this->getLang("ERROS"));
        }

        $cpus = $this->getOption("cpus");
        $ram = $this->getOption("ram");
        $hdd = $this->getOption("hdd");

        if (!is_numeric($cpus) || $cpus <= 0) {
            return array(false, $this->getLang("ERRCPU"));
        }

        if (!is_numeric($ram) || $ram <= 0) {
            return array(false, $this->getLang("ERRRAM"));
        }

        if (!is_numeric($hdd) || $hdd <= 0) {
            return array(false, $this->getLang("ERRHDD"));
        }

        $ram *= 1024;
        $ip = $this->getDedicatedIP();
        if (!$ip) {
            return [false, $this->getLang("ERRIP")];
        }

        $this->ssh("vzctl create $id --ostemplate $os");
        $this->ssh("vzctl set $id --onboot yes --save");
        $this->ssh("vzctl set $id --ipadd $ip --save");

        $this->ssh("vzctl set $id --nameserver 8.8.8.8 --nameserver 8.8.4.4 --save");
        $this->ssh("vzctl set $id --hostname vs$id --save");
        $this->ssh("vzctl set $id --diskspace {$hdd}G:{$hdd}G --save");
        $this->ssh("vzctl set $id --userpasswd root:$password --save");
        $this->ssh("vzctl set $id --ram {$ram}M --save");
        $this->ssh("vzctl set $id --cpus $cpus --save");
        $this->ssh("vzctl start $id");

        return array(true, array(
            "ip" => $ip,
            "password" => $password,
            "os" => $os,
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        if (!$this->ssh("vzctl stop $id")) {
            return array(false, $this->getLang("ERRC"));
        }

        $this->ssh("vzctl destroy $id");

        $this->releaseDedicatedIP();

        return array(true);
    }

    public function Output($id, $task = "")
    {
        global $pars, $sec;
        $this->loadOptions($id);

        ob_start();

        if ($task == "restart" && $this->isRunning($id)) {
            $this->ssh("vzctl restart $id");
            $_SESSION['ovz_html'] = '<div class="alert alert-success">' . $this->getLang("RESOK") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "stop" && $this->isRunning($id)) {
            $this->ssh("vzctl stop $id");
            $_SESSION['ovz_html'] = '<div class="alert alert-success">' . $this->getLang("STOPOK") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "start" && !$this->isRunning($id)) {
            $this->ssh("vzctl start $id");
            $_SESSION['ovz_html'] = '<div class="alert alert-success">' . $this->getLang("STARTOK") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "reset_pw") {
            $this->ssh("vzctl set $id --userpasswd root:{$this->getData('password')} --save");
            $_SESSION['ovz_html'] = '<div class="alert alert-success">' . $this->getLang("RPWOK") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "onboot_off") {
            $this->ssh("vzctl set $id --onboot no --save");
            $_SESSION['ovz_html'] = '<div class="alert alert-success">' . $this->getLang("NOBOOTOK") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "onboot_on") {
            $this->ssh("vzctl set $id --onboot yes --save");
            $_SESSION['ovz_html'] = '<div class="alert alert-success">' . $this->getLang("ONBOOTOK") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if (!empty($_SESSION['ovz_html'])) {
            echo $_SESSION['ovz_html'];
            $_SESSION['ovz_html'] = "";
        }

        if ($task == "reinstall") {
            if (!empty($_POST['os']) && in_array($_POST['os'], $this->osList())) {
                $this->setData("password", $sec->generatePassword(16, false, "lud"));
                $this->setData("os", $_POST['os']);

                $hostname = "vs" . $id;
                $r = $this->ssh("cat /etc/vz/conf/$id.conf");
                $ex = explode("\n", $r);
                $onboot = false;
                foreach ($ex as $v) {
                    $v = trim($v);
                    if (substr($v, 0, 10) != "HOSTNAME=\"") {
                        continue;
                    }

                    $hostname = substr($v, 10, strlen($v) - 11);
                    break;
                }

                $this->ssh("vzctl stop $id");
                $this->ssh("vzctl destroy $id");

                $this->ssh("vzctl create $id --ostemplate " . escapeshellarg($_POST['os']));
                $this->ssh("vzctl set $id --onboot yes --save");
                $this->ssh("vzctl set $id --ipadd {$this->getData('ip')} --save");

                $this->ssh("vzctl set $id --nameserver 8.8.8.8 --nameserver 8.8.4.4 --save");
                $this->ssh("vzctl set $id --hostname " . escapeshellarg($hostname) . " --save");
                $this->ssh("vzctl set $id --userpasswd root:" . escapeshellarg($this->getData("password")) . " --save");
                $this->ChangePackage($id);
                $this->ssh("vzctl start $id");

                $_SESSION['ovz_html'] = '<div class="alert alert-success">' . $this->getLang("REIOK") . '</div>';
                if (isset($pars[1]) && $pars[1] == $task) {
                    header('Location: ./');
                } else {
                    header('Location: ?p=hosting&id=' . $id);
                }

                exit;
            }
            ?>
			<div class="panel panel-default">
			  <div class="panel-heading"><?=$this->getLang("REINSTALLATION");?></div>
			  <div class="panel-body">
				<p style="text-align: justify;"><?=$this->getLang("REII");?></p>

				<form method="POST">
					<select name="os" class="form-control">
						<?php foreach ($this->osList() as $o) {?>
						<option value="<?=$o;?>"<?php if ($this->getData("os") == $o) {
                echo ' selected="selected"';
            }
                ?>><?=$this->getOsName($o);?></option>
						<?php }?>
					</select>

					<input type="submit" class="btn btn-primary btn-block" value="<?=$this->getLang("REIDO");?>" style="margin-top: 10px;" />
				</form>
			  </div>
			</div>
			<?php
} elseif ($task == "hostname") {
            if (!empty($_POST['hostname'])) {
                $this->ssh("vzctl set $id --hostname " . escapeshellarg($_POST['hostname']) . " --save");
                $_SESSION['ovz_html'] = '<div class="alert alert-success">' . $this->getLang("HOSTOK") . '</div>';
                if (isset($pars[1]) && $pars[1] == $task) {
                    header('Location: ./');
                } else {
                    header('Location: ?p=hosting&id=' . $id);
                }

                exit;
            }

            $hostname = "vs" . $id;

            $r = $this->ssh("cat /etc/vz/conf/$id.conf");
            $ex = explode("\n", $r);
            $onboot = false;
            foreach ($ex as $v) {
                $v = trim($v);
                if (substr($v, 0, 10) != "HOSTNAME=\"") {
                    continue;
                }

                $hostname = substr($v, 10, strlen($v) - 11);
                break;
            }

            ?>
			<div class="panel panel-default">
			  <div class="panel-heading"><?=$this->getLang("CHAHO");?></div>
			  <div class="panel-body">
				<p style="text-align: justify;"><?=$this->getLang("CHAHOI");?></p>

				<form method="POST">
					<input type="text" name="hostname" value="<?=$hostname;?>" placeholder="vps.example.com" class="form-control" />
					<input type="submit" class="btn btn-primary btn-block" value="<?=$this->getLang("CHASAVE");?>" style="margin-top: 10px;" />
				</form>
			  </div>
			</div>
			<?php
} else {
            ?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("CRED");?></div>
		  <div class="panel-body">
			<b><?=$this->getLang("IPA");?>:</b> <?=$this->getData("ip");?><br />
			<b><?=$this->getLang("OS");?>:</b> <?=$this->getOsName($this->getData("os"));?><br />
		    <b><?=$this->getLang("ROOTPW");?>:</b> <?=$this->getData("password");?>
		  </div>
		</div>
		<?php
}
        $res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id)
    {
        $this->loadOptions($id);
        if ($this->ssh("vzctl stop $id") === false) {
            return array(false, "User suspension failed");
        }

        $this->ssh("vzctl set $id --onboot no --save");
        return array(true);
    }

    public function Unsuspend($id)
    {
        $this->loadOptions($id);
        if ($this->ssh("vzctl start $id") === false) {
            return array(false, "User unsuspension failed");
        }

        $this->ssh("vzctl set $id --onboot yes --save");
        return array(true);
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        $cpus = $this->getOption("cpus");
        $ram = $this->getOption("ram");
        $hdd = $this->getOption("hdd");

        if (!is_numeric($cpus) || $cpus <= 0) {
            return array(false, $this->getLang("ERRCPU"));
        }

        if (!is_numeric($ram) || $ram <= 0) {
            return array(false, $this->getLang("ERRRAM"));
        }

        if (!is_numeric($hdd) || $hdd <= 0) {
            return array(false, $this->getLang("ERRHDD"));
        }

        $ram *= 1024;

        $this->ssh("vzctl set $id --diskspace {$hdd}G:{$hdd}G --save");
        $this->ssh("vzctl set $id --ram {$ram}M --save");
        $this->ssh("vzctl set $id --cpus $cpus --save");

        return array(true);
    }

    public function AllEmailVariables()
    {
        return array(
            "ip",
            "password",
            "os",
        );
    }

    public function EmailVariables($id)
    {
        $this->loadOptions($id);

        return array(
            "ip" => $this->getData("ip"),
            "password" => $this->getData("password"),
            "os" => $this->getOsName($this->getData("os")),
        );
    }

    public function ContractInfo($id)
    {
        $this->loadOptions($id);

        return [
            $this->getLang("IPA") => $this->getData("ip"),
            $this->getLang("OS") => $this->getData("os"),
        ];
    }

    private function isRunning($id)
    {
        if (strpos($this->ssh("vzctl status $id"), "running") !== false) {
            return true;
        }

        return false;
    }

    public function OwnFunctions($id)
    {
        $this->loadOptions($id);

        $actions = array();

        if ($this->isRunning($id)) {
            $actions["restart"] = $this->getLang("ACTREB");
            $actions["stop"] = $this->getLang("ACTSTOP");
        } else {
            $actions["start"] = $this->getLang("ACTSTART");
        }

        $actions["hostname"] = $this->getLang("ACTHOS");
        $actions["reinstall"] = $this->getLang("ACTREI");
        $actions["reset_pw"] = $this->getLang("ACTRESPW");

        return $actions;
    }

    public function AdminFunctions($id)
    {
        $this->loadOptions($id);

        $r = $this->ssh("cat /etc/vz/conf/$id.conf");
        $ex = explode("\n", $r);
        $onboot = false;
        foreach ($ex as $v) {
            $v = trim($v);
            if (substr($v, 0, 6) != "ONBOOT") {
                continue;
            }

            if (strpos($v, "yes") !== false) {
                $onboot = true;
            }

            break;
        }

        if ($onboot) {
            return array("onboot_off" => $this->getLang("ACTNOB"));
        } else {
            return array("onboot_on" => $this->getLang("ACTONB"));
        }

    }

    public function getOsName($os)
    {
        $names = array(
            "debian-7.0-x86_64-minimal" => "Debian 7 (minimal)",
        );

        return isset($names[$os]) ? $names[$os] : $os;
    }

    public function ApiTasks($id)
    {
        $actions = $this->OwnFunctions($id);

        $my = array();
        if (in_array("restart", $actions)) {
            $my["RebootServer"] = "";
        }

        if (in_array("stop", $actions)) {
            $my["StopServer"] = "";
        }

        if (in_array("hostname", $actions)) {
            $my["SetHostname"] = "fqdn";
        }

        if (in_array("reinstall", $actions)) {
            $my["ReinstallServer"] = "os";
        }

        if (in_array("reset_pw", $actions)) {
            $my["SetRootPassword"] = "pwd";
        }

        if (in_array("start", $actions)) {
            $my["StartServer"] = "";
        }

        $my["GetOSList"] = "";
        return $my;
    }

    public function RebootServer($id, $req)
    {
        $this->loadOptions($id);
        $this->ssh("vzctl restart $id");
        die(json_encode(array("code" => "100", "message" => "Server restarted.", "data" => array())));
    }

    public function StopServer($id, $req)
    {
        $this->loadOptions($id);
        $this->ssh("vzctl stop $id");
        die(json_encode(array("code" => "100", "message" => "Server stopped.", "data" => array())));
    }

    public function SetHostname($id, $req)
    {
        $this->loadOptions($id);
        $this->ssh("vzctl set $id --hostname {$req['fqdn']} --save");
        die(json_encode(array("code" => "100", "message" => "Hostname set.", "data" => array())));
    }

    public function ReinstallServer($id, $req)
    {
        global $sec;
        $this->loadOptions($id);

        if (!in_array($req['os'], $this->osList())) {
            die(json_encode(array("code" => "810", "message" => "OS not found.", "data" => array())));
        }

        $this->setData("password", $sec->generatePassword(16, false, "lud"));
        $this->setData("os", $req['os']);

        $hostname = "vs" . $id;
        $r = $this->ssh("cat /etc/vz/conf/$id.conf");
        $ex = explode("\n", $r);
        $onboot = false;
        foreach ($ex as $v) {
            $v = trim($v);
            if (substr($v, 0, 10) != "HOSTNAME=\"") {
                continue;
            }

            $hostname = substr($v, 10, strlen($v) - 11);
            break;
        }

        $this->ssh("vzctl stop $id");
        $this->ssh("vzctl destroy $id");

        $this->ssh("vzctl create $id --ostemplate " . escapeshellarg($_POST['os']));
        $this->ssh("vzctl set $id --onboot yes --save");
        $this->ssh("vzctl set $id --ipadd {$this->getData('ip')} --save");

        $this->ssh("vzctl set $id --nameserver 8.8.8.8 --nameserver 8.8.4.4 --save");
        $this->ssh("vzctl set $id --hostname " . escapeshellarg($hostname) . " --save");
        $this->ssh("vzctl set $id --userpasswd root:" . escapeshellarg($this->getData("password")) . " --save");
        $this->ChangePackage($id);
        $this->ssh("vzctl start $id");

        die(json_encode(array("code" => "100", "message" => "Installation done.", "data" => array("pwd" => $this->getData("password")))));
    }

    public function SetRootPassword($id, $req)
    {
        $this->loadOptions($id);
        $this->ssh("vzctl set $id --userpasswd root:{$req['pwd']} --save");
        die(json_encode(array("code" => "100", "message" => "Root password set.", "data" => array())));
    }

    public function StartServer($id, $req)
    {
        $this->loadOptions($id);
        $this->ssh("vzctl start $id");
        die(json_encode(array("code" => "100", "message" => "Server started.", "data" => array())));
    }

    public function GetOSList($id, $req)
    {
        $this->loadOptions($id);
        $os = $this->osList();
        if (false === $os) {
            die(json_encode(array("code" => "810", "message" => "Technical error occured.", "data" => array())));
        }

        die(json_encode(array("code" => "100", "message" => "OS list fetched.", "data" => array("os" => $os))));
    }
}

?>