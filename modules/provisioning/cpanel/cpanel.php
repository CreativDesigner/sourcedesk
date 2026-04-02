<?php

class CPanelProv extends Provisioning
{
    protected $name = "cPanel";
    protected $short = "cpanel";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;
    protected $version = "1.1";

    public function Config($id, $product = true)
    {
        global $lang;
        $this->loadOptions($id, $product);

        if (isset($_POST['url'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $res = $this->Call("listpkgs", [], $_POST);

            if (!$res) {
                die('<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $this->getLang("CONNERR") . '</div>');
            }

            if (!empty($res->cpanelresult->error)) {
                die('<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . htmlentities($res->cpanelresult->error) . '</div>');
            }

            $pkgs = $res->data->pkg;

            if (count($pkgs) == 0) {
                die('<div class="alert alert-warning">' . $this->getLang("noplans") . '</div>');
            }

            $html = '<div class="form-group"><label>' . $this->getLang("plan") . '</label><select data-setting="plan" class="form-control prov_settings">';

            foreach ($pkgs as $p) {
                $p = $p->name;
                if (!empty($this->getOption("plan")) && $this->getOption("plan") == $p) {
                    $html .= "<option selected='selected'>$p</option>";
                } else {
                    $html .= "<option>$p</option>";
                }

            }

            $html .= "</select></div>";
            die($html);
        }

        ob_start();?>

		<div class="row" mgmt="1">
		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("whmurl");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://webspacepanel.de:2087" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("WHMUSER");?></label>
					<input type="text" data-setting="user" value="<?=$this->getOption("user");?>" placeholder="root" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("WHMPW");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="L957fZviq6" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<a href="#" mgmt="0" id="check_conn" class="btn btn-default btn-block"><?=$this->getLang("GDFS");?></a>

		<script>
        if (!mgmt) {
		var doing = false;
		function request(){
			if(doing) return;
			doing = !doing;
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("JGD");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=cpanel", {
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				url: $("[data-setting=url]").val(),
				user: $("[data-setting=user]").val(),
				password: $("[data-setting=password]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("GDFS");?>');
				$("#server_conf").html(r);
			});
		}
		<?php if (!empty($this->getOption("url"))) {
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

    private function Call($func, $data = [], $opt = false)
    {
        if (!is_array($opt)) {
            $opt = $this->options;
        }

        $data = is_array($data) && count($data) > 0 ? "&" . http_build_query($data) : "";

        $ch = curl_init(rtrim($opt["url"], "/") . "/json-api/" . $func . "?api.version=1" . $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic " . base64_encode($opt["user"] . ":" . $opt["password"]) . "\n\r",
        ]);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        return is_object($res) ? $res : false;
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);

        $res = $this->Call("createacct", [
            "username" => $username = strtolower($this->getUsername($id)),
            "domain" => "$id.web",
            "password" => $pwd = $sec->generatePassword(16, false, "lud"),
            "plan" => $this->getOption("plan"),
        ]);

        if ($res->metadata->result) {
            return [true, [
                "username" => $username,
                "password" => $pwd,
            ]];
        } else {
            return [false, $res->metadata->reason];
        }
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $res = $this->Call("removeacct", [
            "username" => strtolower($this->getData("username") ?: "web$id"),
        ]);

        if ($res->metadata->result) {
            return [true];
        } else {
            return [false, $res->metadata->reason];
        }
    }

    public function Output($id, $task = "")
    {
        global $pars, $raw_cfg, $CFG;
        $this->loadOptions($id);

        ob_start();

        $org = $this->getOption("url");
        $host = array_shift(explode(":", rtrim(substr(array_pop(explode(":", $org, 2)), 2), "/")));
        $url = "https://$host:2083/";

        ?>
		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-default">
				  <div class="panel-heading"><?=$this->getLang("CRED");?></div>
				  <div class="panel-body">
				    <b><?=$this->getLang("URL");?>:</b> <a target="_blank" href="<?=$url;?>"><?=$url;?></a><br />
				    <b><?=$this->getLang("USERNAME");?>:</b> <?=strtolower($this->getData("username") ?: "web$id");?><br />
				    <b><?=$this->getLang("PASSWORD");?>:</b> <?=$this->getData("password");?></a>
				  </div>
				</div>
			</div>

			<div class="col-md-6">
				<div class="panel panel-default">
				  <div class="panel-heading"><?=$this->getLang("FTPCRED");?></div>
				  <div class="panel-body">
				    <b><?=$this->getLang("HOST");?>:</b> <?=$host;?><br />
				    <b><?=$this->getLang("username");?>:</b> <?=strtolower($this->getData("username") ?: "web$id");?><br />
				    <b><?=$this->getLang("PASSWORD");?>:</b> <?=$this->getData("password");?>
				  </div>
				</div>
			</div>
		</div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $do = "")
    {
        $this->loadOptions($id);

        $res = $this->Call($do . "suspendacct", [
            "user" => strtolower($this->getData("username") ?: "web$id"),
        ]);

        if ($res->metadata->result) {
            return [true];
        } else {
            return [false, $res->metadata->reason];
        }
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, "un");
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        $res = $this->Call("changepackage", [
            "user" => strtolower($this->getData("username") ?: "web$id"),
            "pkg" => $this->getOption("plan"),
        ]);

        if ($res->metadata->result) {
            return [true];
        } else {
            return [false, $res->metadata->reason];
        }
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
        $u = $this->getClient($id);

        return array(
            "url" => $raw_cfg['PAGEURL'] . "/hosting/" . $id,
        );
    }
}