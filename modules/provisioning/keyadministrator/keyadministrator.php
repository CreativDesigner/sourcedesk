<?php

class KeyadministratorProv extends Provisioning
{
    protected $name = "Keyadministrator";
    protected $short = "keyadministrator";
    protected $version = "1.2";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['user'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            try {
                $r = $this->getApiClient($_POST['user'], $_POST['password'])->getAvailableLicenses();

                $licenses = array();
                foreach ($r as $k => $v) {
                    if (isset($v['name'])) {
                        $licenses[$k] = $v['name'];
                        continue;
                    }

                    foreach ($v as $k2 => $v2) {
                        if (isset($v2['name'])) {
                            $licenses[$k2] = $v2['name'];
                        }
                    }

                }

                ?>
				<div class="form-group">
					<label><?=$this->getLang("LTYPE");?></label>
					<select data-setting="license" class="form-control prov_settings">
					<?php foreach ($licenses as $k => $n) {?>
					<option value="<?=$k;?>"<?php if (!empty($this->getOption("license")) && $this->getOption("license") == $k) {
                    echo ' selected="selected"';
                }
                    ?>><?=$n;?></option>
					<?php }?>
					</select>
				</div>
				<?php
exit;
            } catch (LicenseException $ex) {
                die('<div class="alert alert-danger">' . $ex->getMessage() . '</div>');
            }
        }

        ob_start();?>

		<div class="row" mgmt="1">
		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("KAUSER");?></label>
					<input type="text" data-setting="user" value="<?=$this->getOption("user");?>" placeholder="sourceway" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("KAPASS");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="GlMo=1O!" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<a href="#" id="check_conn" mgmt="0" class="btn btn-default btn-block"><?=$this->getLang("GAL");?></a>

		<script>
        if (!mgmt) {
		var doing = false;
		function request(){
			if(doing) return;
			doing = !doing;
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("PW");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=keyadministrator", {
				user: $("[data-setting=user]").val(),
				password: $("[data-setting=password]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("GAL");?>');
				$("#server_conf").html(r);
			});
		}
		<?php if (!empty($this->getOption("user"))) {
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

    private function getApiClient($user, $pw)
    {
        require_once __DIR__ . "/lib/xmlrpc.inc";
        require_once __DIR__ . "/license_client.php";
        return new LicenseClient($user, $pw, "cwhadmin.cyberwebhosting.de", "/rpc/license_service.php", 443);
    }

    public function Create($id)
    {
        $this->loadOptions($id);

        if (!empty($this->getData("license"))) {
            return array(false, "There is a license ID already. Clear the module data first.");
        }

        try {
            $r = $this->getApiClient($this->getOption("user"), $this->getOption("password"))->orderLicense($this->getOption("license"));
            if (isset($r['license'])) {
                return array(true, array("license" => $r['license']));
            } else {
                return array(false, "License ID not found");
            }

        } catch (LicenseException $ex) {
            return array(false, $ex->getMessage());
        }
    }

    public function Cancellation($id, $date = "0000-00-00")
    {
        $this->loadOptions($id);

        if ($date == "0000-00-00") {
            $this->getApiClient($this->getOption("user"), $this->getOption("password"))->deleteLicense($this->getData("license"), $date);
        } else {
            $time = strtotime($date);
            while (true) {
                try {
                    $this->getApiClient($this->getOption("user"), $this->getOption("password"))->deleteLicense($this->getData("license"), date("Y-m-d", $time));
                    break;
                } catch (LicenseException $ex) {
                    $time += 86400;
                }
            }
        }
    }

    public function Output($id, $task = "")
    {
        $this->loadOptions($id);

        try {
            if ($task == "download") {
                $license_file = $this->getApiClient($this->getOption("user"), $this->getOption("password"))->retrieveLicense($this->getData("license"));
                if (!empty($license_file) && is_string($license_file)) {
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="license.xml"');
                    echo $license_file;
                    exit;
                }
            }

            if ($task == "reset") {
                $this->getApiClient($this->getOption("user"), $this->getOption("password"))->resetLicense($this->getData("license"));
                $suc = $this->getLang("RESOK");
            }

            $info = $this->getApiClient($this->getOption("user"), $this->getOption("password"))->viewLicense($this->getData("license"));

            ob_start();
            if (isset($err)) {
                echo '<div class="alert alert-danger">' . $err . '</div>';
            }

            if (isset($suc)) {
                echo '<div class="alert alert-success">' . $suc . '</div>';
            }

            if (!empty($_POST['plesk_ip']) && $this->hasPleskBinding() && filter_var($_POST['plesk_ip'], FILTER_VALIDATE_IP)) {
                $this->getApiClient($this->getOption("user"), $this->getOption("password"))->setBindState($this->getData("license"), $_POST['plesk_ip']);
                echo '<div class="alert alert-success">' . $this->getLang("OKAY") . '</div>';
            }

            ?>
            <div class="panel panel-default">
            <div class="panel-heading"><?=$this->getLang("LICENSEDATA");?></div>
            <div class="panel-body">
                <b><?=$this->getLang("LICENSEKEY");?>:</b> <?=$info['key'];?><br />
                <b><?=$this->getLang("USED");?>:</b> <?=$info['used'] ? $this->getLang("YES") : $this->getLang("NO");?>
                <?php if (!empty($info['ip']) && !$this->hasPleskBinding()) {?>
                <br /><b><?=$this->getLang("IPA");?>:</b> <?=$info['ip'];?>
                <?php }?>
                <?php if (!empty($info['mac'])) {?>
                <br /><b><?=$this->getLang("MAC");?>:</b> <?=$info['mac'];?>
                <?php }?>

                <?php if ($this->hasPleskBinding()) {
                $pip = $this->getApiClient($this->getOption("user"), $this->getOption("password"))->getBindState($this->getData("license"))['ip'];
                ?>
                <form method="POST" class="form-inline">
                    <input type="text" name="plesk_ip" value="<?=$pip;?>" class="form-control" placeholder="<?=$this->getLang("IPA");?>">
                    <input type="submit" class="btn btn-default" value="<?=$this->getLang("SET");?>">
                </form>
                <?php }?>
            </div>
            </div>
            <?php
$res = ob_get_contents();
            ob_end_clean();
            return $res;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function AllEmailVariables()
    {
        return array(
            "config_url",
        );
    }

    public function EmailVariables($id)
    {
        global $raw_cfg;
        $this->loadOptions($id);

        return array(
            "config_url" => $raw_cfg['PAGEURL'] . "/hosting/" . $id,
        );
    }

    public function OwnFunctions($id)
    {
        $this->loadOptions($id);
        $license_file = $this->getApiClient($this->getOption("user"), $this->getOption("password"))->retrieveLicense($this->getData("license"));

        $actions = array();

        if (!empty($license_file) && is_string($license_file)) {
            $actions["download"] = $this->getLang("DL");
        }

        $actions["reset"] = $this->getLang("RESET");

        return $actions;
    }

    public function AdminFunctions($id)
    {
        return $this->OwnFunctions($id);
    }

    public function ApiTasks($id)
    {
        $actions = $this->OwnFunctions($id);

        $my = array(
            "ResetLicense" => "",
        );

        if ($this->hasPleskBinding()) {
            $my["SetIP"] = "ip";
        }

        if (in_array("download", $actions)) {
            $my["GetLicenseFile"] = "";
        }

        return $my;
    }

    public function ResetLicense($id, $req)
    {
        $this->loadOptions($id);
        $this->getApiClient($this->getOption("user"), $this->getOption("password"))->resetLicense($this->getData("license"));
        die(json_encode(array("code" => "100", "message" => "License reset successful.", "data" => array())));
    }

    public function SetIP($id, $req)
    {
        $this->loadOptions($id);
        $this->getApiClient($this->getOption("user"), $this->getOption("password"))->setBindState($this->getData("license"), $req['ip']);
        die(json_encode(array("code" => "100", "message" => "IP set successful.", "data" => array())));
    }

    public function GetLicenseFile($id, $req)
    {
        $this->loadOptions($id);

        $license_file = $this->getApiClient($this->getOption("user"), $this->getOption("password"))->retrieveLicense($this->getData("license"));
        if (empty($license_file) || !is_string($license_file)) {
            die(json_encode(array("code" => "810", "message" => "No license file available.", "data" => array())));
        }

        die(json_encode(array("code" => "100", "message" => "License file retrieved.", "data" => array("file" => "license.xml", "content" => $license_file))));
    }

    protected function hasPleskBinding()
    {
        return date("Y-m-d") >= "2020-07-01" && substr($this->getOption("license"), 0, 6) == "PLSK_1";
    }
}