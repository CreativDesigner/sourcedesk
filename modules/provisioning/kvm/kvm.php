<?php

class KVMProv extends Provisioning
{
    protected $name = "KVM";
    protected $short = "kvm";
    protected $lang;
    protected $options = array();
    protected $ssh;
    protected $serverMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['ssh_host'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $iso = $this->isoList($_POST['ssh_host'], $_POST['ssh_password']);
            if (!$iso) {
                die('<div class="alert alert-danger">' . $this->getLang("CONFAIL") . '</div>');
            }

            die('<div class="alert alert-success">' . $this->getLang("CONOK") . '</div>');
        }

        ob_start();?>
		<script>$("#ip_tab_btn").show();</script>

		<div class="row" mgmt="1">
		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">
			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("SSHHOST");?></label>
					<input type="text" data-setting="ssh_host" value="<?=$this->getOption("ssh_host");?>" placeholder="vm01.sourceway.de[:923]" class="form-control prov_settings" />
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
					<label><?=$this->getLang("HDD");?></label>
					<div class="input-group">
						<input type="text" data-setting="hdd" value="<?=$this->getOption("hdd");?>" placeholder="100" class="form-control prov_settings" />
						<span class="input-group-addon"><?=$this->getLang("GB");?></span>
					</div>
				</div>
			</div>
		</div>

		<div class="alert alert-info" mgmt="0"><?=$this->getLang("DOC");?>	</div>

		<a href="#" id="check_conn" mgmt="0" class="btn btn-default btn-block"><?=$this->getLang("CHECKCONN");?></a>

		<script>
        if (!mgmt) {
		var doing = false;
		function request(){
			if(doing) return;
			doing = !doing;
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("CHECKINGCONN");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=kvm", {
				ssh_host: $("[data-setting=ssh_host]").val(),
				ssh_password: $("[data-setting=ssh_password]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("CHECKCONN");?>');
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

    public function loadOptions($id, $pd = false)
    {
        parent::loadOptions($id, $pd);

        foreach (["cpus", "ram", "hdd"] as $o) {
            if (!is_numeric($this->options[$o]) && array_key_exists($this->options[$o], $this->cf)) {
                $this->options[$o] = $this->cf[$o];
            }
        }

    }

    private function isoList($host = false, $password = false)
    {
        $res = $this->ssh("ls -1 /home/iso/", $host, $password);
        if (!$res) {
            return false;
        }

        $ex = explode("\n", $res);

        $os = array();
        foreach ($ex as $k => $v) {
            if (empty($v)) {
                continue;
            }

            $ending = substr($v, -4);
            if ($ending != ".iso") {
                continue;
            }

            $os[] = substr($v, 0, strlen($v) - 4);
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

        $vnc = $sec->generatePassword(16, false, "lud");

        $cpus = $this->getOption("cpus");
        $ram = $this->getOption("ram");
        $hdd = $this->getOption("hdd");

        if (!is_numeric($cpus) || $cpus <= 0) {
            return array(false, "CPU not configured");
        }

        if (!is_numeric($ram) || $ram <= 0) {
            return array(false, "RAM not configured");
        }

        if (!is_numeric($hdd) || $hdd <= 0) {
            return array(false, "HDD not configured");
        }

        if (!($uuid = $this->ssh("uuid"))) {
            return array(false, "Connection failed");
        }

        $uuid = trim($uuid);
        $ram *= 1048576;
        $mac = "00:00:" . implode(':', str_split(substr(md5(mt_rand()), 0, 8), 2));

        $xml = "<domain type='kvm'>
		  <name>kvm$id</name>
		  <uuid>$uuid</uuid>
		  <memory unit='KiB'>$ram</memory>
		  <currentMemory unit='KiB'>$ram</currentMemory>
		  <vcpu placement='static'>$cpus</vcpu>
		  <resource>
		    <partition>/machine</partition>
		  </resource>
		  <os>
		    <type arch='x86_64' machine='pc-i440fx-2.1'>hvm</type>
		    <boot dev='hd'/>
		  </os>
		  <features>
		    <acpi/>
		  </features>
		  <clock offset='utc'/>
		  <on_poweroff>destroy</on_poweroff>
		  <on_reboot>restart</on_reboot>
		  <on_crash>destroy</on_crash>
		  <devices>
		    <emulator>/usr/bin/kvm</emulator>
		    <disk type='file' device='disk'>
		      <driver name='qemu' type='raw'/>
		      <source file='/home/hdd/kvm$id.img'/>
		      <backingStore/>
		      <target dev='vda' bus='virtio'/>
		      <alias name='virtio-disk0'/>
		      <address type='pci' domain='0x0000' bus='0x00' slot='0x04' function='0x0'/>
		    </disk>
		    <disk type='block' device='cdrom'>
		      <driver name='qemu' type='raw'/>
		      <backingStore/>
		      <target dev='hdc' bus='ide' tray='open'/>
		      <readonly/>
		      <alias name='ide0-1-0'/>
		      <address type='drive' controller='0' bus='1' target='0' unit='0'/>
		    </disk>
		    <controller type='ide' index='0'>
		      <alias name='ide0'/>
		      <address type='pci' domain='0x0000' bus='0x00' slot='0x01' function='0x1'/>
		    </controller>
		    <controller type='usb' index='0'>
		      <alias name='usb0'/>
		      <address type='pci' domain='0x0000' bus='0x00' slot='0x01' function='0x2'/>
		    </controller>
		    <controller type='pci' index='0' model='pci-root'>
		      <alias name='pci.0'/>
		    </controller>
		    <interface type='bridge'>
		      <mac address='$mac'/>
		      <source bridge='br0'/>
		      <target dev='vnet0'/>
		      <model type='rtl8139'/>
		      <alias name='net0'/>
		      <address type='pci' domain='0x0000' bus='0x00' slot='0x03' function='0x0'/>
		    </interface>
		    <serial type='pty'>
		      <source path='/dev/pts/1'/>
		      <target port='0'/>
		      <alias name='serial0'/>
		    </serial>
		    <console type='pty' tty='/dev/pts/1'>
		      <source path='/dev/pts/1'/>
		      <target type='serial' port='0'/>
		      <alias name='serial0'/>
		    </console>
		    <input type='mouse' bus='ps2'/>
		    <input type='keyboard' bus='ps2'/>
		    <graphics type='vnc' port='-1' autoport='yes' passwd='$vnc' listen='0.0.0.0'/>
		    <video>
		      <model type='cirrus' vram='9216' heads='1'/>
		      <alias name='video0'/>
		      <address type='pci' domain='0x0000' bus='0x00' slot='0x02' function='0x0'/>
		    </video>
		    <memballoon model='virtio'>
		      <alias name='balloon0'/>
		      <address type='pci' domain='0x0000' bus='0x00' slot='0x05' function='0x0'/>
		    </memballoon>
		  </devices>
		</domain>";

        $ip = $this->getDedicatedIP();
        if (!$ip) {
            return array(false, "No free IP addresses");
        }

        $this->ssh("qemu-img create -f raw /home/hdd/kvm$id.img {$hdd}G");
        $this->ssh("cat <<EOF  > /home/xml/kvm$id.xml
$xml
EOF");
        $this->ssh("virsh define /home/xml/kvm$id.xml");
        $this->ssh("virsh start kvm$id");
        $this->ssh("virsh autostart kvm$id");

        return array(true, array(
            "ip" => $ip,
            "vnc" => $vnc,
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        if (!$this->ssh("virsh destroy kvm$id")) {
            return array(false, "Connection failed");
        }

        $this->ssh("rm -Rf /home/hdd/kvm$id.img");
        $this->ssh("rm -Rf /home/xml/kvm$id.xml");

        $this->releaseDedicatedIP();

        return array(true);
    }

    public function Output($id, $task = "")
    {
        global $pars, $sec;
        $this->loadOptions($id);

        ob_start();

        if ($task == "restart" && $this->isRunning($id)) {
            $this->ssh("virsh reboot kvm$id");
            $_SESSION['kvm_html'] = '<div class="alert alert-success">' . $this->getLang("REBOOTOK") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "stop" && $this->isRunning($id)) {
            $this->ssh("virsh shutdown kvm$id");
            $_SESSION['kvm_html'] = '<div class="alert alert-success">' . $this->getLang("SHUTDOWNOK") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "destroy" && $this->isRunning($id)) {
            $this->ssh("virsh destroy kvm$id");
            $_SESSION['kvm_html'] = '<div class="alert alert-success">' . $this->getLang("DESTROYOK") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "start" && !$this->isRunning($id)) {
            $this->ssh("virsh define /home/xml/kvm$id.xml");
            $this->ssh("virsh start kvm$id");
            $this->ssh("virsh autostart kvm$id");
            $_SESSION['kvm_html'] = '<div class="alert alert-success">' . $this->getLang("STARTOK") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "remove_iso" && $this->isIso($id)) {
            $this->ssh('virsh attach-disk kvm' . $id . ' "" hdc --driver qemu --type cdrom --mode readonly');
            $xml = $this->ssh("virsh dumpxml kvm$id");
            $xml = str_replace("type='vnc'", "type='vnc' passwd='" . $this->getData("vnc") . "'", $xml);
            $xml = str_replace("<boot dev='cdrom'/>", "<boot dev='hd'/>", $xml);
            $this->ssh("cat <<EOF  > /home/xml/kvm$id.xml
$xml
EOF");
            $this->ssh("virsh destroy kvm$id");
            $this->ssh("virsh define /home/xml/kvm$id.xml");
            $this->ssh("virsh start kvm$id");
            $this->ssh("virsh autostart kvm$id");

            $_SESSION['kvm_html'] = '<div class="alert alert-success">' . $this->getLang("ISOREMOK") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if ($task == "reset_pw" && $this->isRunning($id)) {
            $old = $this->getData("vnc");
            $new = $sec->generatePassword(16, false, "lud");
            $this->setData("vnc", $new);

            $this->ssh("sed -i -- s/passwd=\'$old\'/passwd=\'$new\'/g /home/xml/kvm$id.xml");
            $this->ssh("rm -f /home/xml/kvm$id.xml.bak");

            $_SESSION['kvm_html'] = '<div class="alert alert-success">' . $this->getLang("VNCPWOK") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        if (!empty($_SESSION['kvm_html'])) {
            echo $_SESSION['kvm_html'];
            $_SESSION['kvm_html'] = "";
        }

        if ($task == "attach_iso") {
            if (!empty($_POST['iso']) && in_array($_POST['iso'], $this->isoList())) {
                $this->ssh('virsh attach-disk kvm' . $id . ' "/home/iso/' . escapeshellcmd($_POST['iso']) . '.iso" hdc --driver qemu --type cdrom --mode readonly');
                $xml = $this->ssh("virsh dumpxml kvm$id");
                $xml = str_replace("type='vnc'", "type='vnc' passwd='" . $this->getData("vnc") . "'", $xml);
                $xml = str_replace("<boot dev='hd'/>", "<boot dev='cdrom'/>", $xml);
                $this->ssh("cat <<EOF  > /home/xml/kvm$id.xml
$xml
EOF");
                $this->ssh("virsh destroy kvm$id");
                $this->ssh("virsh define /home/xml/kvm$id.xml");
                $this->ssh("virsh start kvm$id");
                $this->ssh("virsh autostart kvm$id");

                $_SESSION['kvm_html'] = '<div class="alert alert-success">' . $this->getLang("ISOADDOK") . '</div>';
                if (isset($pars[1]) && $pars[1] == $task) {
                    header('Location: ./');
                } else {
                    header('Location: ?p=hosting&id=' . $id);
                }

                exit;
            }
            ?>
			<div class="panel panel-default">
			  <div class="panel-heading"><?=$this->getLang("ISOADD");?></div>
			  <div class="panel-body">
				<p style="text-align: justify;"><?=$this->getLang("ISOADDI");?></p>

				<form method="POST">
					<select name="iso" class="form-control">
						<?php foreach ($this->isoList() as $o) {?>
						<option><?=$o;?></option>
						<?php }?>
					</select>

					<input type="submit" class="btn btn-primary btn-block" value="<?=$this->getLang("ISOADD");?>" style="margin-top: 10px;" />
				</form>
			  </div>
			</div>
			<?php
} else {
            ?>
		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-default">
				  <div class="panel-heading"><?=$this->getLang("VSD");?></div>
				  <div class="panel-body">
					<b><?=$this->getLang("IPA");?>:</b> <?=$this->getData("ip");?>
				  </div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="panel panel-default">
				  <div class="panel-heading"><?=$this->getLang("VNCCRED");?></div>
				  <div class="panel-body">
					<b><?=$this->getLang("HOST");?>:</b> <?=$this->getVncHost($id);?><br />
					<b><?=$this->getLang("password");?>:</b> <?=$this->getData("vnc");?>
				  </div>
				</div>
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
        if ($this->ssh("virsh destroy kvm$id") === false) {
            return array(false, "User suspension failed");
        }

        return array(true);
    }

    public function Unsuspend($id)
    {
        $this->loadOptions($id);
        if ($this->ssh("virsh define /home/xml/kvm$id.xml") === false) {
            return array(false, "User unsuspension failed");
        }

        $this->ssh("virsh start kvm$id");
        $this->ssh("virsh autostart kvm$id");
        return array(true);
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        $cpus = $this->getOption("cpus");
        $ram = $this->getOption("ram");
        $hdd = $this->getOption("hdd");

        if (!is_numeric($cpus) || $cpus <= 0) {
            return array(false, "CPU not configured");
        }

        if (!is_numeric($ram) || $ram <= 0) {
            return array(false, "RAM not configured");
        }

        if (!is_numeric($hdd) || $hdd <= 0) {
            return array(false, "HDD not configured");
        }

        $ram *= 1048576;

        $xml = $this->ssh("virsh dumpxml kvm$id");
        $xml = str_replace("type='vnc'", "type='vnc' passwd='" . $this->getData("vnc") . "'", $xml);
        if (!$xml) {
            return array(false, "Connection failed");
        }

        $start = "<memory unit='KiB'>";
        $end = "</memory>";

        $startpos = strpos($xml, $start);
        $endpos = strpos($xml, $end);
        if ($startpos !== false && $endpos !== false) {
            $xml = substr($xml, 0, $startpos - 1) . $ram . substr($xml, $endpos);
        }

        $start = "<currentMemory unit='KiB'>";
        $end = "</currentMemory>";

        $startpos = strpos($xml, $start);
        $endpos = strpos($xml, $end);
        if ($startpos !== false && $endpos !== false) {
            $xml = substr($xml, 0, $startpos - 1) . $ram . substr($xml, $endpos);
        }

        $start = "<vcpu placement='static'>";
        $end = "</vcpu>";

        $startpos = strpos($xml, $start);
        $endpos = strpos($xml, $end);
        if ($startpos !== false && $endpos !== false) {
            $xml = substr($xml, 0, $startpos - 1) . $cpus . substr($xml, $endpos);
        }

        $start = "<vcpu placement='static'>";
        $end = "</vcpu>";

        $startpos = strpos($xml, $start);
        $endpos = strpos($xml, $end);
        if ($startpos !== false && $endpos !== false) {
            $xml = substr($xml, 0, $startpos - 1) . $cpus . substr($xml, $endpos);
        }

        $this->ssh("qemu-img resize /home/hdd/kvm$id.img {$hdd}G");

        $this->ssh("cat <<EOF  > /home/xml/kvm$id.xml
$xml
EOF");
        $this->ssh("virsh destroy kvm$id");
        $this->ssh("virsh define /home/xml/kvm$id.xml");
        $this->ssh("virsh start kvm$id");
        $this->ssh("virsh autostart kvm$id");
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

    private function isRunning($id)
    {
        if (strpos($this->ssh("virsh dominfo kvm$id"), "running") !== false) {
            return true;
        }

        return false;
    }

    public function OwnFunctions($id)
    {
        $this->loadOptions($id);

        $actions = array();

        if ($this->isRunning($id)) {
            $actions["restart"] = $this->getLang("actres");
            $actions["stop"] = $this->getLang("actstop");
            $actions["destroy"] = $this->getLang("actdes");

            if (!$this->isIso($id)) {
                $actions["attach_iso"] = $this->getLang("actisoadd");
            } else {
                $actions["remove_iso"] = $this->getLang("actisorem");
            }

            $actions["reset_pw"] = $this->getLang("actvncpw");
        } else {
            $actions["start"] = $this->getLang("actstart");
        }

        return $actions;
    }

    public function AdminFunctions($id)
    {
        return array();
    }

    private function isIso($id)
    {
        $this->loadOptions($id);
        $r = $this->ssh("virsh dumpxml kvm$id");
        if (strpos($r, "<boot dev='cdrom'/>") !== false) {
            return true;
        }

        return false;
    }

    private function getVncHost($id)
    {
        $this->loadOptions($id);
        $port = trim($this->ssh("virsh vncdisplay kvm$id"));
        if (substr($port, 0, 1) != ":") {
            return "<i>- {$this->getLang("VNCINACT")} -</i>";
        }

        $offset = intval(substr($port, 1));
        return $this->getOption("ssh_host") . ":" . (5900 + $offset);
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

        if (in_array("destroy", $actions)) {
            $my["ShutdownServer"] = "";
        }

        if (in_array("attach_iso", $actions)) {
            $my["InsertISO"] = "iso";
        }

        if (in_array("remove_iso", $actions)) {
            $my["RemoveISO"] = "";
        }

        if (in_array("reset_pw", $actions)) {
            $my["SetVNCPassword"] = "pwd";
        }

        if (in_array("reset_pw", $actions)) {
            $my["GetVNCData"] = "";
        }

        if (in_array("start", $actions)) {
            $my["StartServer"] = "";
        }

        $my["GetISOList"] = "";
        return $my;
    }

    public function RebootServer($id, $req)
    {
        $this->loadOptions($id);
        $this->ssh("virsh reboot kvm$id");
        die(json_encode(array("code" => "100", "message" => "ACPI restart request submitted.", "data" => array())));
    }

    public function StopServer($id, $req)
    {
        $this->loadOptions($id);
        $this->ssh("virsh destroy kvm$id");
        die(json_encode(array("code" => "100", "message" => "Server was stopped hard.", "data" => array())));
    }

    public function ShutdownServer($id, $req)
    {
        $this->loadOptions($id);
        $this->ssh("virsh shutdown kvm$id");
        die(json_encode(array("code" => "100", "message" => "ACPI shutdown request submitted.", "data" => array())));
    }

    public function InsertISO($id, $req)
    {
        $this->loadOptions($id);

        if (!in_array($req['iso'], $this->isoList())) {
            die(json_encode(array("code" => "810", "message" => "ISO file not known.", "data" => array())));
        }

        $this->ssh('virsh attach-disk kvm' . $id . ' "/home/iso/' . escapeshellcmd($req['iso']) . '.iso" hdc --driver qemu --type cdrom --mode readonly');
        $xml = $this->ssh("virsh dumpxml kvm$id");
        $xml = str_replace("type='vnc'", "type='vnc' passwd='" . $this->getData("vnc") . "'", $xml);
        $xml = str_replace("<boot dev='hd'/>", "<boot dev='cdrom'/>", $xml);
        $this->ssh("cat <<EOF  > /home/xml/kvm$id.xml
$xml
EOF");
        $this->ssh("virsh destroy kvm$id");
        $this->ssh("virsh define /home/xml/kvm$id.xml");
        $this->ssh("virsh start kvm$id");
        $this->ssh("virsh autostart kvm$id");

        die(json_encode(array("code" => "100", "message" => "ISO inserted and server restarted.", "data" => array())));
    }

    public function RemoveISO($id, $req)
    {
        $this->loadOptions($id);

        $this->ssh('virsh attach-disk kvm' . $id . ' "" hdc --driver qemu --type cdrom --mode readonly');
        $xml = $this->ssh("virsh dumpxml kvm$id");
        $xml = str_replace("type='vnc'", "type='vnc' passwd='" . $this->getData("vnc") . "'", $xml);
        $xml = str_replace("<boot dev='cdrom'/>", "<boot dev='hd'/>", $xml);
        $this->ssh("cat <<EOF  > /home/xml/kvm$id.xml
$xml
EOF");
        $this->ssh("virsh destroy kvm$id");
        $this->ssh("virsh define /home/xml/kvm$id.xml");
        $this->ssh("virsh start kvm$id");
        $this->ssh("virsh autostart kvm$id");

        die(json_encode(array("code" => "100", "message" => "ISO removed and server restarted.", "data" => array())));
    }

    public function SetVNCPassword($id, $req)
    {
        $this->loadOptions($id);

        $old = $this->getData("vnc");
        $new = $req['pwd'];
        $this->setData("vnc", $new);

        $this->ssh("sed -i -- s/passwd=\'$old\'/passwd=\'$new\'/g /home/xml/kvm$id.xml");
        $this->ssh("rm -f /home/xml/kvm$id.xml.bak");

        die(json_encode(array("code" => "100", "message" => "VNC password set and valid after server restart.", "data" => array())));
    }

    public function GetVNCData($id, $req)
    {
        $vnc = $this->getVncHost($id);
        if (substr($vnc, 0, 4) == "<i>-") {
            die(json_encode(array("code" => "810", "message" => "VNC not active.", "data" => array())));
        }

        die(json_encode(array("code" => "100", "message" => "VNC data retrieved.", "data" => array("vnc" => $vnc))));
    }

    public function StartServer($id, $req)
    {
        $this->loadOptions($id);
        $this->ssh("virsh define /home/xml/kvm$id.xml");
        $this->ssh("virsh start kvm$id");
        $this->ssh("virsh autostart kvm$id");
        die(json_encode(array("code" => "100", "message" => "Server started.", "data" => array())));
    }

    public function GetISOList($id, $req)
    {
        $this->loadOptions($id);
        $iso = $this->isoList();
        if (false === $iso) {
            die(json_encode(array("code" => "810", "message" => "Fetching ISO list failed.", "data" => array())));
        }

        die(json_encode(array("code" => "100", "message" => "ISO list fetched.", "data" => array("files" => $iso))));
    }
}