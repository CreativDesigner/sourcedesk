<?php

class VirtualizorProv extends Provisioning
{
    protected $name = "Virtualizor";
    protected $short = "virtualizor";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;
    protected $version = "1.2";

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['host'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            require_once __DIR__ . "/sdk.php";
            $v = new Virtualizor_Admin_API($_POST['host'], $_POST['api_id'], $_POST['api_password']);
            $s = $v->servers(1, 100000)['servs'];
            $p = $v->plans(1, 100000)['plans'];

            if (!is_array($p) || count($p) == 0) {
                die('<div class="alert alert-danger">' . $this->getLang("CFNP") . '</div>');
            }

            ?>
			<div class="form-group">
				<label><?=$this->getLang("plan");?></label>
				<select data-setting="plan" class="form-control prov_settings">
					<?php foreach ($p as $plan) {?>
					<option value="<?=$plan['plid'];?>"<?php if ($this->getOption('plan') == $plan['plid']) {
                echo ' selected=""';
            }
                ?>><?=$plan['plan_name'];?></option>
					<?php }?>
				</select>
			</div>

			<div class="form-group">
				<label><?=$this->getLang("server");?></label>
				<select data-setting="server" class="form-control prov_settings">
					<?php foreach ($s as $server) {?>
					<option value="<?=$plan['serid'];?>"<?php if ($this->getOption('server') == $plan['serid']) {
                echo ' selected=""';
            }
                ?>><?=$server['server_name'];?></option>
					<?php }?>
				</select>
			</div>

			<div class="row">
				<div class="col-md-4">
					<div class="form-group">
						<label><?=$this->getLang("OWRAM");?></label>
						<input type="text" data-setting="ram" value="<?=$this->getOption("ram");?>" placeholder="<?=$this->getLang("FNO");?>" class="form-control prov_settings" />
					</div>
				</div>

				<div class="col-md-4">
					<div class="form-group">
						<label><?=$this->getLang("OWSPACE");?></label>
						<input type="text" data-setting="space" value="<?=$this->getOption("space");?>" placeholder="<?=$this->getLang("FNO");?>" class="form-control prov_settings" />
					</div>
				</div>

				<div class="col-md-4">
					<div class="form-group">
						<label><?=$this->getLang("OWCORES");?></label>
						<input type="text" data-setting="cores" value="<?=$this->getOption("cores");?>" placeholder="<?=$this->getLang("FNO");?>" class="form-control prov_settings" />
					</div>
				</div>
			</div>
			<?php
exit;
        }

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("hostname");?></label>
					<input type="text" data-setting="host" value="<?=$this->getOption("host");?>" placeholder="vm01.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("APIID");?></label>
					<input type="password" data-setting="api_id" value="<?=$this->getOption("api_id");?>" placeholder="oyaiztmjo92xmxp6be7afwo53trpgohr" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("APIPW");?></label>
					<input type="password" data-setting="api_password" value="<?=$this->getOption("api_password");?>" placeholder="5xyypj1lymnyvydfk14aqekhaiklebng" class="form-control prov_settings" />
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
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=virtualizor", {
				host: $("[data-setting=host]").val(),
				api_id: $("[data-setting=api_id]").val(),
				api_password: $("[data-setting=api_password]").val(),
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

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        require_once __DIR__ . "/sdk.php";
        $v = new Virtualizor_Admin_API($this->getOption("host"), $this->getOption("api_id"), $this->getOption("api_password"));

        if (!empty($this->getData("vpsid"))) {
            return [false, $this->getLang("VSEXIST")];
        }

        $p = $v->plans(1, 100000)['plans'];
        $f = false;
        foreach ($p as $plan) {
            if ($plan['plid'] != $this->getOption("plan")) {
                continue;
            }

            $f = true;
            break;
        }

        if (!$f) {
            return [false, $this->getLang("PNF")];
        }

        $ram = $this->getOption("ram");
        if (!empty($ram) && isset($this->cf[$ram])) {
            $plan['ram'] = $this->cf[$ram] * 1024;
        }

        $space = $this->getOption("space");
        if (!empty($space) && isset($this->cf[$space])) {
            $plan['space'] = $this->cf[$space];
        }

        $cores = $this->getOption("cores");
        if (!empty($cores) && isset($this->cf[$cores])) {
            $plan['cores'] = $this->cf[$cores];
        }

        $vnc_pwd = $sec->generatePassword(16, false, "lud");
        $root_pw = $sec->generatePassword(16, false, "lud");

        $post = array();
        $post['serid'] = $this->getOption("server");
        $post['virt'] = $plan['virt'];
        $post['user_email'] = "v" . $id . '@' . $this->getOption("host");
        $post['user_pass'] = $root_pw;
        $post['plid'] = $this->getOption("plan");
        $post['osid'] = $plan['osid'];
        $post['hostname'] = "v" . $id . '.' . $this->getOption("host");
        $post['rootpass'] = $root_pw;
        $post['vnc'] = 1;
        $post['vncpass'] = $vnc_pwd;
        $post['space'] = $plan['space'];
        $post['ram'] = $plan['ram'];
        $post['bandwidth'] = $plan['bandwidth'];
        $post['network_speed'] = $plan['network_speed'];
        $post['cores'] = $plan['cores'];
        $post['priority'] = $plan['priority'];
        $post['cpu'] = $plan['cpu'];
        $post['burst'] = $plan['burst'];
        $post['cpu_percent'] = $plan['cpu_percent'];
        $post['num_ips6'] = $plan['num_ips6'];

        $output = $v->addvs($post);
        if (!empty($output['error'])) {
            return array(false, $output['error'][0]);
        }

        $output = $output['vs_info'];
        return array(true, array(
            "uid" => $output['uid'],
            "vpsid" => $output['vpsid'],
            "vnc_pwd" => $vnc_pwd,
            "username" => "v" . $id . '@' . $this->getOption("host"),
            "password" => $root_pw,
            "ipv4" => isset($output['ips'][0]) ? $output['ips'][0] : "",
            "ipv6" => isset($output['ipv6'][0]) ? $output['ipv6'][0] : "",
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);
        require_once __DIR__ . "/sdk.php";
        $v = new Virtualizor_Admin_API($this->getOption("host"), $this->getOption("api_id"), $this->getOption("api_password"));
        $r = $v->delete_vs($this->getData("vpsid"));
        $v->delete_users(["delete" => $this->getData("uid")]);

        $this->setData("uid", $output['uid']);
        $this->setData("vpsid", $output['vpsid']);
        $this->setData("vnc_pwd", $vnc_pwd);
        $this->setData("username", "v" . $id . '@' . $this->getOption("host"));
        $this->setData("password", $root_pw);
        $this->setData("ipv4", isset($output['ips'][0]) ? $output['ips'][0] : "");
        $this->setData("ipv6", isset($output['ipv6'][0]) ? $output['ipv6'][0] : "");

        return array($r['done']);
    }

    private function getVncHost($id)
    {
        $this->loadOptions($id);
        require_once __DIR__ . "/sdk.php";
        $v = new Virtualizor_Admin_API($this->getOption("host"), $this->getOption("api_id"), $this->getOption("api_password"));

        $output = $v->listvs(1, 1, ["vpsid" => $this->getData("vpsid")]);
        if (!empty($output[$this->getData("vpsid")]['vncport'])) {
            return $this->getOption("host") . ":" . $output[$this->getData("vpsid")]['vncport'];
        }

        return "<i>{$this->getLang("NOTACTIVE")}</i>";
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
				  <div class="panel-heading"><?=$this->getLang("VSD");?></div>
				  <div class="panel-body">
					<?php if (!empty($this->getData("ipv4"))) {?><b><?=$this->getLang("v4a");?>:</b> <?=$this->getData("ipv4");?><?php }?>
					<?php if (!empty($this->getData("ipv6"))) {?><?php if (!empty($this->getData("ipv4"))) {?><br /><?php }?><b><?=$this->getLang("v6a");?>:</b> <?=$this->getData("ipv6");?></a><?php }?>
					<br /><b><?=$this->getLang("password");?>:</b> <?=$this->getData("password");?>
				  </div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="panel panel-default">
				  <div class="panel-heading"><?=$this->getLang("VNCCRED");?></div>
				  <div class="panel-body">
					<b><?=$this->getLang("host");?>:</b> <?=$this->getVncHost($id);?><br />
					<b><?=$this->getLang("password");?>:</b> <?=$this->getData("vnc_pwd");?>
				  </div>
				</div>
			</div>
		</div>

		<?php $url = "https://" . $this->getOption("host");?>
		<div class="panel panel-default" style="margin-bottom: 0;">
			<div class="panel-heading"><?=$this->getLang("MNGT");?></div>
			<div class="panel-body">
				<b><?=$this->getLang("URl");?>:</b> <a href="<?=$url;?>" target="_blank"><?=$url;?></a><br />
				<b><?=$this->getLang("username");?>:</b> <?=$this->getData("username");?><br />
				<b><?=$this->getLang("password");?>:</b> <?=$this->getData("password");?>
			</div>
		</div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id)
    {
        $this->loadOptions($id);
        require_once __DIR__ . "/sdk.php";
        $v = new Virtualizor_Admin_API($this->getOption("host"), $this->getOption("api_id"), $this->getOption("api_password"));
        $r = $v->suspend($this->getData("vpsid"));
        return array((bool) $r['done']);
    }

    public function Unsuspend($id)
    {
        $this->loadOptions($id);
        require_once __DIR__ . "/sdk.php";
        $v = new Virtualizor_Admin_API($this->getOption("host"), $this->getOption("api_id"), $this->getOption("api_password"));
        $r = $v->unsuspend($this->getData("vpsid"));
        return array((bool) $r['done']);
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);
        require_once __DIR__ . "/sdk.php";
        $v = new Virtualizor_Admin_API($this->getOption("host"), $this->getOption("api_id"), $this->getOption("api_password"));

        $p = $v->plans(1, 100000)['plans'];
        $f = false;
        foreach ($p as $plan) {
            if ($plan['plid'] != $this->getOption("plan")) {
                continue;
            }

            $f = true;
            break;
        }

        if (!$f) {
            return [false, $this->getLang("PNF")];
        }

        $ram = $this->getOption("ram");
        if (!empty($ram) && isset($this->cf[$ram])) {
            $plan['ram'] = $this->cf[$ram] * 1024;
        }

        $space = $this->getOption("space");
        if (!empty($space) && isset($this->cf[$space])) {
            $plan['space'] = $this->cf[$space];
        }

        $cores = $this->getOption("cores");
        if (!empty($cores) && isset($this->cf[$cores])) {
            $plan['cores'] = $this->cf[$cores];
        }

        $post = array();
        $post['uid'] = $this->getData("uid");
        $post['vpsid'] = $this->getData("vpsid");
        $post['virt'] = $plan['virt'];
        $post['plid'] = $this->getOption("plan");
        $post['osid'] = $plan['osid'];
        $post['hostname'] = "v" . $id . '.' . $this->getOption("host");
        $post['rootpass'] = $root_pw;
        $post['vnc'] = 1;
        $post['vncpass'] = $vnc_pwd;
        $post['space'] = $plan['space'] + 1;
        $post['ram'] = $plan['ram'];
        $post['bandwidth'] = $plan['bandwidth'];
        $post['network_speed'] = $plan['network_speed'];
        $post['cores'] = $plan['cores'];
        $post['priority'] = $plan['priority'];
        $post['cpu'] = $plan['cpu'];
        $post['burst'] = $plan['burst'];
        $post['cpu_percent'] = $plan['cpu_percent'];
        $post['num_ips6'] = $plan['num_ips6'];

        $output = $v->editvs($post);
        if (!empty($output['error'])) {
            return array(false, $output['error'][0]);
        }

        return array(true);
    }

    public function AllEmailVariables()
    {
        return array(
            "hostname",
            "username",
            "password",
            "url",
        );
    }

    public function EmailVariables($id)
    {
        global $raw_cfg;
        $this->loadOptions($id);

        return array(
            "hostname" => $this->getOption("host"),
            "username" => $this->getData("username"),
            "password" => $this->getData("password"),
            "url" => $raw_cfg['PAGEURL'] . "/hosting/" . $id,
        );
    }

    public function ContractInfo($id)
    {
        $this->loadOptions($id);

        return [
            $this->getLang("USERNAME") => $this->getData("username"),
        ];
    }
}
