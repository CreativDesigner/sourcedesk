<?php

class ProFTPdProv extends Provisioning
{
    protected $name = "ProFTPd";
    protected $short = "proftpd";
    protected $lang;
    protected $options = array();
    protected $ssh;
    protected $db;
    protected $serverMgmt = true;
    protected $usernameMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("hostname");?></label>
					<input type="text" data-setting="hostname" value="<?=$this->getOption("hostname");?>" placeholder="fs01.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("SSHPORT");?></label>
					<input type="text" data-setting="ssh_port" value="<?=$this->getOption("ssh_port") ?: "22";?>" placeholder="22" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<div class="row" mgmt="1">
			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("rootpw");?></label>
					<input type="password" data-setting="ssh_password" value="<?=$this->getOption("ssh_password");?>" placeholder="GlMo=1O!" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("sqlpw");?></label>
					<input type="password" data-setting="mysql_password" value="<?=$this->getOption("mysql_password");?>" placeholder="GlMo=1O!" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<div class="row" mgmt="0">
			<div class="col-md-12">
				<div class="form-group">
					<label><?=$this->getLang("DISKSPACE");?></label>
					<div class="input-group">
						<input type="text" data-setting="quota" value="<?=$this->getOption("quota");?>" placeholder="1024" class="form-control prov_settings" />
						<span class="input-group-addon"><?=$this->getLang("MB");?></span>
					</div>
				</div>
			</div>
		</div>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function loadOptions($id, $pd = false)
    {
        parent::loadOptions($id, $pd);

        foreach (["quota"] as $o) {
            if (!is_numeric($this->options[$o]) && array_key_exists($this->options[$o], $this->cf)) {
                $this->options[$o] = $this->cf[$o];
            }
        }

    }

    private function db($query)
    {
        if (!$this->db) {
            $this->db = mysqli_init();
            mysqli_options($this->db, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
            if (!mysqli_real_connect($this->db, $this->getOption("hostname"), "root", $this->getOption("mysql_password"), "proftpd", 3306, null, MYSQLI_CLIENT_SSL)) {
                die(mysqli_connect_error($this->db));
            }

            $r = $this->db->query($query);
        } else {
            $r = $this->db->query($query);
            if (!$r) {
                $this->db = false;
                return $this->db($query);
            }
        }

        return $r;
    }

    private function ssh($command)
    {
        try {
            if (!$this->ssh) {
                $ssh = new \phpseclib\Net\SSH2($this->getOption("hostname"), $this->getOption("ssh_port"));
                if (!$ssh->login("root", $this->getOption("ssh_password"))) {
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

        $username = $this->getUsername($id);
        $password = $sec->generatePassword(16, false, "lud");
        $quota = $this->getOption("quota") * 1048576;

        $this->ssh("mkdir -p /var/ftp/" . escapeshellarg($username));
        $this->ssh("chown -R ftpuser:ftpgroup /var/ftp/" . escapeshellarg($username));

        $this->db("INSERT INTO `ftpquotalimits` (`name`, `quota_type`, `per_session`, `limit_type`, `bytes_in_avail`, `bytes_out_avail`, `bytes_xfer_avail`, `files_in_avail`, `files_out_avail`, `files_xfer_avail`) VALUES ('$username', 'user', 'false', 'hard', $quota, 0, 0, 0, 0, 0)");

        $this->db("INSERT INTO `ftpuser` (`userid`, `passwd`, `uid`, `gid`, `homedir`, `shell`, `count`, `accessed`, `modified`) VALUES ('$username', '$password', 2001, 2001, '/var/ftp/$username', '/usr/sbin/nologin', 0, '', '')");

        $members = $this->db("SELECT members FROM ftpgroup LIMIT 1")->fetch_object()->members;
        $ex = explode(",", $members);
        if (!in_array($username, $ex)) {
            $members .= ",$username";
            $this->db("UPDATE ftpgroup SET members = '$members' LIMIT 1");
        }

        return array(true, array(
            "username" => $username,
            "password" => $password,
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);
        $this->db("SELECT members FROM ftpgroup LIMIT 1");

        $username = $this->getData("username");
        $this->db("DELETE FROM `ftpquotalimits` WHERE `name` = '$username'");
        $this->db("DELETE FROM `ftpuser` WHERE `userid` = '$username'");
        $this->ssh("rm -Rf /var/ftp/" . escapeshellarg($username));

        $members = $this->db("SELECT members FROM ftpgroup LIMIT 1")->fetch_object()->members;
        $ex = explode(",", $members);
        if (in_array($username, $ex)) {
            $members = str_replace(",$username", "", $members);
            $this->db("UPDATE ftpgroup SET members = '$members' LIMIT 1");
        }

        return array(true);
    }

    public function Output($id, $task = "")
    {
        global $pars, $sec;
        $this->loadOptions($id);

        ob_start();

        if ($task == "new_pw") {
            $password = $sec->generatePassword(16, false, "lud");
            $this->db("UPDATE `ftpuser` SET `passwd` = '" . $password . "' WHERE `userid` = 's$id'");
            $this->setData("password", $password);

            $_SESSION['proftpd_html'] = '<div class="alert alert-success">' . $this->getLang("NEWPWSET") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if (!empty($_SESSION['proftpd_html'])) {
            echo $_SESSION['proftpd_html'];
            $_SESSION['proftpd_html'] = "";
        }

        if (isset($_POST['action']) && $_POST['action'] == "storage") {
            $r = $this->ssh("du -sh /var/ftp/s" . intval($id) . "/");
            die(explode("\t", $r)[0]);
        }

        ?>
		<div class="panel panel-default">
			<div class="panel-heading"><?=$this->getLang("FTPCRED");?></div>
			<div class="panel-body">
				<?=$this->getLang("hostname");?>: <?=$this->getOption("hostname");?> (<?=$this->getLang("PORT");?> 21)<br />
				<?=$this->getLang("username");?>: <?=$this->getData("username");?><br />
				<?=$this->getLang("password");?>: <?=$this->getData("password");?><br /><br />
				<?=$this->getLang("useddisk");?>: <span id="storage"><i class="fa fa-spinner fa-spin"></i> <?=$this->getLang("PW");?></span>
			</div>
		</div>

		<script>
		$(document).ready(function(){
			$.post("", {
				action: "storage",
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				$("#storage").html(r);
			});
		});
		</script>
		<?php

        $res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id)
    {
        $this->loadOptions($id);

        $username = $this->getData("username");
        $this->db("UPDATE `ftpuser` SET `passwd` = '' WHERE `userid` = '$username'");

        return array(true);
    }

    public function Unsuspend($id)
    {
        $this->loadOptions($id);

        $username = $this->getData("username");
        $this->db("UPDATE `ftpuser` SET `passwd` = '" . $this->getData("password") . "' WHERE `userid` = '$username'");

        return array(true);
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        $quota = $this->getOption("quota") * 1048576;

        $this->db("UPDATE `ftpquotalimits` SET `bytes_in_avail` = $quota WHERE `name` = 's$id'");

        return array(true);
    }

    public function AllEmailVariables()
    {
        return array(
            "hostname",
            "username",
            "password",
        );
    }

    public function EmailVariables($id)
    {
        $this->loadOptions($id);

        return array(
            "hostname" => $this->getOption("hostname"),
            "username" => $this->getData("username"),
            "password" => $this->getData("password"),
        );
    }

    public function ContractInfo($id)
    {
        $this->loadOptions($id);

        return [
            $this->getLang("USERNAME") => $this->getData("username"),
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
        $actions = array();
        $actions["new_pw"] = $this->getLang("newpw");
        return $actions;
    }

    public function AdminFunctions($id)
    {
        $actions = array();
        $actions["new_pw"] = $this->getLang("newpw");
        return $actions;
    }

    public function ApiTasks($id)
    {
        return array(
            "GenerateNewPassword" => "",
        );
    }

    public function SetUserPassword($id, $req)
    {
        global $sec;
        $this->loadOptions($id);

        $password = $sec->generatePassword(16, false, "lud");
        $this->db("UPDATE `ftpuser` SET `passwd` = '" . $password . "' WHERE `userid` = 's$id'");
        $this->setData("password", $password);

        die(json_encode(array("code" => "100", "message" => "New password set.", "data" => array("pwd" => $password))));
    }
}