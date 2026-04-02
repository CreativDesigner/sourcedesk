<?php

class OnAppProv extends Provisioning
{
    protected $name = "OnApp";
    protected $short = "onapp";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;
    protected $version = "1.3";

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['host'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $this->options["host"] = $_POST['host'];
            $this->options["username"] = $_POST['username'];
            $this->options["api_key"] = $_POST['api_key'];

            $res = $this->call("version");

            if (is_string($res)) {
                die('<div class="alert alert-danger">' . htmlentities($res) . '</div>');
            }

            if (!array_key_exists("version", $res)) {
                die('<div class="alert alert-danger">' . $this->getLang("AUTHFAIL") . '</div>');
            }

            $packages = $this->call("instance_packages");
            if (!is_array($packages) || !count($packages)) {
                die('<div class="alert alert-danger">' . $this->getLang("NOIP") . '</div>');
            }

            $templates = $this->call("templates");
            if (!is_array($templates) || !count($templates)) {
                die('<div class="alert alert-danger">' . $this->getLang("NOTP") . '</div>');
            }

            $isos = $this->call("template_isos");
            $ovas = $this->call("template_ovas");

            $buckets = $this->call("billing/buckets");
            if (!is_array($buckets) || !count($buckets)) {
                die('<div class="alert alert-danger">' . $this->getLang("NOBC") . '</div>');
            }
            ?>
			<div class="form-group">
				<label><?=$this->getLang("package");?></label>
				<select data-setting="package" class="form-control prov_settings">
					<?php
foreach ($packages as $p) {?>
					<option value="<?=$p['instance_package']['id'];?>" <?php if ($this->getOption('package') == $p['instance_package']['id']) {
                echo ' selected=""';
            }
                ?>><?=htmlentities($p['instance_package']['label']);?></option>
					<?php }?>
				</select>
			</div>

			<div class="form-group">
				<label><?=$this->getLang("template");?></label>
				<select data-setting="template" class="form-control prov_settings">
					<?php
foreach ($templates as $p) {?>
					<option value="<?=$p['image_template']['id'];?>" <?php if ($this->getOption('template') == $p['image_template']['id']) {
                echo ' selected=""';
            }
                ?>><?=htmlentities($p['image_template']['label']);?></option>
					<?php }?>
                    <?php
foreach ($isos as $p) {?>
					<option value="<?=$p['image_template_iso']['id'];?>" <?php if ($this->getOption('template') == $p['image_template_iso']['id']) {
                echo ' selected=""';
            }
                ?>><?=htmlentities($p['image_template_iso']['label']);?></option>
					<?php }?>
                    <?php
foreach ($ovas as $p) { ?>
					<option value="<?=$p['image_template_ova']['id'];?>" <?php if ($this->getOption('template') == $p['image_template_ova']['id']) {
                echo ' selected=""';
            }
                ?>><?=htmlentities($p['image_template_ova']['label']);?></option>
					<?php }?>
				</select>
			</div>

            <div class="form-group">
				<label><?=$this->getLang("bucket");?></label>
				<select data-setting="bucket" class="form-control prov_settings">
					<?php
foreach ($buckets as $p) {?>
					<option value="<?=$p['bucket']['id'];?>" <?php if ($this->getOption('bucket') == $p['bucket']['id']) {
                echo ' selected=""';
            }
                ?>><?=htmlentities($p['bucket']['label']);?></option>
					<?php }?>
				</select>
			</div>
			<?php
exit;
        }

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row">
			<div class="col-md-4" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("HOST");?></label>
					<input type="text" data-setting="host" value="<?=$this->getOption("host");?>" placeholder="cp.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

            <div class="col-md-4" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("USERNAME");?></label>
					<input type="text" data-setting="username" value="<?=$this->getOption("username");?>" placeholder="mail@sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("API_KEY");?></label>
					<input type="password" data-setting="api_key" value="<?=$this->getOption("api_key");?>" placeholder="734e216e8a119a4bc25b642ee2cf5aacdf160a01" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<a href="#" id="check_conn" mgmt="0" class="btn btn-default btn-block"><?=$this->getLang("CHECKCONN");?></a>

		<script>
        if (!mgmt) {
		var doing = false;
		function request(){
			if(doing) return;
			doing = !doing;
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("CHECKINGCONN");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=onapp", {
				host: $("[data-setting=host]").val(),
				username: $("[data-setting=username]").val(),
				api_key: $("[data-setting=api_key]").val(),
				_mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("CHECKCONN");?>');
				$("#server_conf").html(r);
			});
		}
		<?php if (!empty($this->getOption("host"))) {
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

    private function call($action, $data = [], $method = "GET", $pars = "")
    {
        $ch = curl_init("https://" . rtrim(trim($this->options["host"]), "/") . "/$action.json$pars");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->options["username"] . ":" . $this->options["api_key"]);

        if (is_array($data) && count($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
            ]);
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $res = @json_decode(curl_exec($ch), true);

        if (curl_error($ch)) {
            $res = curl_error($ch);
        }

        curl_close($ch);

        return $res;
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $c = $this->getClient($id);

        $data = [
            "user" => [
                "login" => $username = $this->getUsername($id),
                "password" => $password = $sec->generatePassword(12, false, "lud"),
                "email" => $c->get()['mail'],
                "first_name" => $c->get()['firstname'],
                "last_name" => $c->get()['lastname'],
                "bucket_id" => $this->getOption("bucket"),
            ],
        ];

        $res = $this->call("users", $data, "POST");
        $uid = $res['user']['id'];

        $data = [
            "virtual_machine" => [
                "template_id" => $this->getOption("template"),
                "label" => "s" . $id,
                "hostname" => "s" . $id,
                "instance_package_id" => $this->getOption("package"),
                "required_ip_address_assignment" => "1",
                "required_virtual_machine_build" => "1",
                "required_virtual_machine_startup" => "1",
            ],
        ];

        $res = $this->call("virtual_machines", $data, "POST");
        if (!$res || ($res['errors'] ?? "") || ($res['error'] ?? "")) {
            if ($res['errors'] ?? "") {
                $res['error'] = implode(",", array_map(function ($v) {return strval($v[0]);}, $res['errors']));
            }

            return [false, $res['error'] ?? ""];
        }

        $vid = $res['virtual_machine']['id'];

        $this->call("virtual_machines/$vid/change_owner", [], "POST", "?user_id=$uid&custom_recipes_action=copy&backups_action=move");

        return array(true, array(
            "username" => $username,
            "password" => $password,
            "vid" => $vid,
            "uid" => $uid,
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $res = $this->call("virtual_machines/" . $this->getData("vid"), [], "DELETE");
        if (!$res || ($res['errors'] ?? "") || ($res['error'] ?? "")) {
            if ($res['errors'] ?? "") {
                $res['error'] = implode(",", array_map(function ($v) {return strval($v[0]);}, $res['errors']));
            }

            return [false, $res['error'] ?? ""];
        }

        return array(true);
    }

    public function Output($id, $task = "")
    {
        global $pars, $sec;
        $this->loadOptions($id);

        ob_start();
        ?>
		<div class="row">
			<div class="col-md-12">
				<?php $url = "https://" . $this->getOption("host");?>
				<div class="panel panel-default" style="margin-bottom: 0;">
					<div class="panel-heading"><?=$this->getLang("MNGT");?></div>
					<div class="panel-body">
						<b><?=$this->getLang("URL");?>:</b> <a href="<?=$url;?>" target="_blank"><?=$url;?></a><br />
						<b><?=$this->getLang("username");?>:</b> <?=$this->getData("username");?><br />
						<b><?=$this->getLang("Password");?>:</b> <?=$this->getData("password");?>
					</div>
				</div>
			</div>
		</div>

        <form method="POST" action="<?=$url;?>/users/sign_in" target="_blank" style="margin-top: 10px;">
            <input type="hidden" name="user[login]" value="<?=$this->getData("username");?>">
            <input type="hidden" name="user[password]" value="<?=$this->getData("password");?>">
            <input type="hidden" name="user[remember_me]" value="0">
            <input type="hidden" name="commit" value="Log In">
            <input type="submit" class="btn btn-primary btn-block" value="<?=$this->getLang("LOGIN");?>">
        </form>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id)
    {
        $this->loadOptions($id);

        $res = $this->call("virtual_machines/" . $this->getData("vid") . "/suspend", [], "POST");
        if (!$res || ($res['errors'] ?? "") || ($res['error'] ?? "")) {
            if ($res['errors'] ?? "") {
                $res['error'] = implode(",", array_map(function ($v) {return strval($v[0]);}, $res['errors']));
            }

            return [false, $res['error'] ?? ""];
        }

        return array(true);
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id);
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        $res = $this->call("virtual_machines/" . $this->getData("vid"), [
            "instance_package_id" => $this->getOption("package"),
        ], "POST");
        if (!$res || ($res['errors'] ?? "") || ($res['error'] ?? "")) {
            if ($res['errors'] ?? "") {
                $res['error'] = implode(",", array_map(function ($v) {return strval($v[0]);}, $res['errors']));
            }

            return [false, $res['error'] ?? ""];
        }

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
}
