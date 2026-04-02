<?php

class VMManagerProv extends Provisioning
{
    protected $name = "VMmanager";
    protected $short = "vmmanager";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;

    private function loadClasses()
    {
        spl_autoload_register(function ($c) {
            if (substr($c, 0, 7) != 'IspApi\\') {
                return;
            }

            $c = substr($c, 7);
            $c = str_replace('\\', DIRECTORY_SEPARATOR, $c);

            $file = __DIR__ . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . $c . ".php";
            if (file_exists($file) && realpath($file) == $file) {
                require_once $file;
            }
        });
    }

    private function client()
    {
        $server = new \IspApi\Server\Server($this->options['host'], $this->options['port']);
        $credentials = new \IspApi\Credentials\Credentials($this->options['user'], $this->options['password']);
        $format = new \IspApi\Format\JsonFormat;
        $client = new \IspApi\HttpClient\CurlClient;

        $ispManager = new \IspApi\IspManager;
        return $ispManager->setServer($server)->setCredentials($credentials)->setHttpClient($client)->setFormat($format);
    }

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['host'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $this->loadClasses();
            foreach (["host", "port", "user", "password"] as $k) {
                $this->options[$k] = $_POST[$k];
            }

            try {
                $func = new \IspApi\Func\System\GetPlans;
                $res = $this->client()->setFunc($func)->execute();

                if (!array_key_exists("doc", $res)) {
                    die('<div class="alert alert-danger">Internal Server Error</div>');
                }

                $res = $res['doc'];
                if (!array_key_exists("elem", $res)) {
                    die('<div class="alert alert-danger">' . $this->getLang("AFNP") . '</div>');
                }
                $res = $res['elem'];

                $plans = [];

                foreach ($res as $plan) {
                    $plans[$plan['id']['$']] = $plan['name']['$'];
                }
            } catch (Exception $ex) {
                die('<div class="alert alert-danger">' . $ex->getMessage() . '</div>');
            }

            try {
                $func = new \IspApi\Func\System\GetNodes;
                $res = $this->client()->setFunc($func)->execute();

                if (!array_key_exists("doc", $res)) {
                    die('<div class="alert alert-danger">Internal Server Error</div>');
                }

                $res = $res['doc'];
                if (!array_key_exists("elem", $res)) {
                    die('<div class="alert alert-danger">' . $this->getLang("NONODES") . '</div>');
                }
                $res = $res['elem'];

                $nodes = [];

                foreach ($res as $node) {
                    $nodes[$node['id']['$']] = $node['name']['$'];
                }
            } catch (Exception $ex) {
                die('<div class="alert alert-danger">' . $ex->getMessage() . '</div>');
            }

            try {
                $func = new \IspApi\Func\System\GetTemplates;
                $res = $this->client()->setFunc($func)->execute();

                if (!array_key_exists("doc", $res)) {
                    die('<div class="alert alert-danger">Internal Server Error</div>');
                }

                $res = $res['doc'];
                if (!array_key_exists("elem", $res)) {
                    die('<div class="alert alert-danger">' . $this->getLang("noost") . '</div>');
                }
                $res = $res['elem'];

                $templates = [];

                foreach ($res as $template) {
                    if ($template["installed"]['$'] != "off") {
                        $templates[] = $template['name']['$'];
                    }
                }

                if (count($templates) == 0) {
                    die('<div class="alert alert-danger">' . $this->getLang("noost") . '</div>');
                }
            } catch (Exception $ex) {
                die('<div class="alert alert-danger">' . $ex->getMessage() . '</div>');
            }

            $html = '<div class="row">';

            $html .= '<div class="col-md-4"><div class="form-group"><label>' . $this->getLang("PLAN") . '</label><select data-setting="plan" class="form-control prov_settings">';

            foreach ($plans as $k => $p) {
                if (!empty($this->getOption("plan")) && $this->getOption("plan") == $k) {
                    $html .= "<option value='$k' selected='selected'>$p</option>";
                } else {
                    $html .= "<option value='$k'>$p</option>";
                }

            }

            $html .= "</select></div></div>";

            $html .= '<div class="col-md-4"><div class="form-group"><label>' . $this->getLang("SERVER") . '</label><select data-setting="node" class="form-control prov_settings">';

            foreach ($nodes as $k => $p) {
                if (!empty($this->getOption("node")) && $this->getOption("node") == $k) {
                    $html .= "<option value='$k' selected='selected'>$p</option>";
                } else {
                    $html .= "<option value='$k'>$p</option>";
                }

            }

            $html .= "</select></div></div>";

            $html .= '<div class="col-md-4"><div class="form-group"><label>' . $this->getLang("DEFOS") . '</label><select data-setting="template" class="form-control prov_settings">';

            foreach ($templates as $p) {
                if (!empty($this->getOption("template")) && $this->getOption("template") == $p) {
                    $html .= "<option value='$p' selected='selected'>$p</option>";
                } else {
                    $html .= "<option value='$p'>$p</option>";
                }

            }

            $html .= "</select></div></div>";

            $html .= "</div>";
            die($html);
        }

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-8">
				<div class="form-group">
					<label><?=$this->getLang("HOSTNAME");?></label>
					<input type="text" data-setting="host" value="<?=$this->getOption("host");?>" placeholder="web.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("PORT");?></label>
					<input type="text" data-setting="port" value="<?=$this->getOption("port") ?: "1500";?>" placeholder="1500" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("username");?></label>
					<input type="text" data-setting="user" value="<?=$this->getOption("user");?>" placeholder="admin" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("password");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="GlMo=1O!" class="form-control prov_settings" />
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
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("DBF");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=vmmanager", {
				host: $("[data-setting=host]").val(),
				port: $("[data-setting=port]").val(),
				user: $("[data-setting=user]").val(),
				password: $("[data-setting=password]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("GDFS");?>');
				$("#server_conf").html(r);
			});
		}
		<?=!empty($this->getOption("host")) ? 'request();' : '';?>

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
        global $sec, $CFG;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        $pwd = $sec->generatePassword(12, false, "lud");
        $this->loadClasses();

        try {
            $func = new \IspApi\Func\User\Add;

            $func->setName($username = $this->getUsername($id));
            $func->setPassword($pwd);
            $res = $this->client()->setFunc($func)->execute();

            if (!array_key_exists("doc", $res)) {
                return [false, "Internal Server Error"];
            }

            $res = $res['doc'];
            if (array_key_exists("error", $res)) {
                $err = $res['error']['msg']['$'];
                return [false, $err];
            }

            if ($res['tparams']['name']['$'] != $username) {
                return [false, "Internal Server Error"];
            }

            $uid = $res['id'][0]['$'];
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }

        try {
            $func = new \IspApi\Func\Vm\Add;

            $func->setName("s" . $id);
            $func->setHostnode($this->getOption("node"));
            $func->setUser($uid);
            $func->setDomain("s" . $id . ".example");
            $func->setPassword($pwd);
            $func->setOsTemplate($this->getOption("template"));
            $func->setPreset($this->getOption("plan"));
            $res = $this->client()->setFunc($func)->execute();

            if (!array_key_exists("doc", $res)) {
                return [false, "Internal Server Error"];
            }

            $res = $res['doc'];
            if (array_key_exists("error", $res)) {
                $err = $res['error']['msg']['$'];
                return [false, $err];
            }

            if ($res['tparams']['domain']['$'] != "s" . $id . ".example") {
                return [false, "Internal Server Error"];
            }

            $cid = $res['id'][0]['$'];
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }

        return [true, [
            "username" => $username,
            "pwd" => $pwd,
            "uid" => $uid,
            "cid" => $cid,
        ]];
    }

    public function Delete($id)
    {
        return $this->Suspend($id, "Delete");
    }

    public function Output($id, $task = "")
    {
        $this->loadOptions($id);

        ob_start();
        ?>
		<div class="panel panel-default">
			<div class="panel-heading"><?=$this->getLang("Cred");?></div>
			<div class="panel-body">
			<b><?=$this->getLang("url");?>:</b> <a href="<?=$this->getUrl();?>" target="_blank"><?=$this->getUrl();?></a><br />
			<b><?=$this->getLang("username");?>:</b> <?=$this->getData("username") ?: ("c" . $id);?><br />
			<b><?=$this->getLang("password");?>:</b> <?=$this->getData("pwd");?>
			</div>
		</div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $pClass = "Suspend")
    {
        $this->loadOptions($id);

        try {
            $this->loadClasses();
            $class = "\\IspApi\\Func\\Vm\\$pClass";
            $func = new $class($this->getData("cid"));
            if ($pClass == "Delete") {
                $func->force();
            }
            $res = $this->client()->setFunc($func)->execute();

            if (!array_key_exists("doc", $res)) {
                return [false, "Internal Server Error"];
            }

            $res = $res['doc'];
            if (array_key_exists("error", $res)) {
                $err = $res['error']['msg']['$'];
                return [false, $err];
            }
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }

        try {
            $this->loadClasses();
            $class = "\\IspApi\\Func\\User\\$pClass";
            $func = new $class($this->getData("username") ?: ("c" . $id));
            $res = $this->client()->setFunc($func)->execute();

            if (!array_key_exists("doc", $res)) {
                return [false, "Internal Server Error"];
            }

            $res = $res['doc'];
            if (array_key_exists("error", $res)) {
                $err = $res['error']['msg']['$'];
                return [false, $err];
            }

            return [array_key_exists("ok", $res)];
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, "Resume");
    }

    public function AllEmailVariables()
    {
        return array(
            "url",
            "user",
            "password",
        );
    }

    private function getUrl()
    {
        return "https://" . $this->getOption("host") . ($this->getOption("port") != 443 ? ":" . $this->getOption("port") : "");
    }

    public function EmailVariables($id)
    {
        $this->loadOptions($id);

        return array(
            "url" => $this->getUrl(),
            "user" => $this->getData("username") ?: ("c" . $id),
            "password" => $this->getData("pwd"),
        );
    }

    public function ContractInfo($id)
    {
        $this->loadOptions($id);

        return [
            $this->getLang("USERNAME") => $this->getData("username") ?: "c" . $id,
        ];
    }
}