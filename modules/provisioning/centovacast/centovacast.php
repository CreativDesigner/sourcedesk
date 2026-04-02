<?php
class CentovaCastProv extends Provisioning
{
    protected $name = "CentovaCast";
    protected $short = "centovacast";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['url'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $this->options["url"] = $_POST['url'];
            $this->options["username"] = $_POST['username'];
            $this->options["password"] = $_POST['password'];

            $res = $this->call("system.listhosts");
            if (!$res || empty($res->type) || !in_array($res->type, ["error", "success"])) {
                die('<div class="alert alert-danger">' . $this->getLang("conerr") . '</div>');
            }

            if (!empty($res->type) && $res->type == "error") {
                die('<div class="alert alert-danger">' . htmlentities($res->response->message) . '</div>');
            }

            $nodes = $res->response->data;

            if (count($nodes) == 0) {
                die('<div class="alert alert-danger">' . $this->getLang("NOHOSTS") . '</div>');
            }
            ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?=$this->getLang("host");?></label>
                        <select data-setting="host" class="form-control prov_settings">
                            <?php
foreach ($nodes as $s) {?>
                            <option <?php if ($this->getOption('host') == $s->id) {
                echo ' selected=""';
            }
                ?> value="<?=$s->id;?>"><?=htmlentities($s->parameters->title);?></option>
                            <?php }?>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label><?=$this->getLang("template");?></label>
                        <input type="text" data-setting="template" value="<?=$this->getOption("template");?>" placeholder="<?=$this->getLang("PEN");?>" class="form-control prov_settings" />
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
					<label><?=$this->getLang("URL");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="http://shout.sourceway.de:2199" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("username");?></label>
					<input type="text" data-setting="username" value="<?=$this->getOption("username");?>" placeholder="admin" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("PASSWORD");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="<?=$this->getLang("passwordh");?>" class="form-control prov_settings" />
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
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=centovacast", {
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				url: $("[data-setting=url]").val(),
				username: $("[data-setting=username]").val(),
				password: $("[data-setting=password]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("CHECKCONN");?>');
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

    private function call($method, $data = [])
    {
        $data = array_merge([
            "a" => [
                "username" => $this->getOption("username"),
                "password" => $this->getOption("password"),
            ],
            "f" => "json",
        ], $data);

        $ch = curl_init($this->getOption("url") . "/api.php?xm=$method");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) {
            return false;
        }

        return json_decode($res);
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $c = $this->getClient($id);

        $pwd = $sec->generatePassword(12, false, "lud");
        $source_pwd = $sec->generatePassword(12, false, "lud");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <centovacast>
            <request class="system" method="provision">
                <password>' . $this->getOption("username") . '|' . $this->getOption("password") . '</password>
                <username>' . ($username = $this->getUsername($id)) . '</username>
                <hostname>auto</hostname>
                <ipaddress>auto</ipaddress>
                <port>auto</port>
                <rpchostid>' . $this->getOption("host") . '</rpchostid>
                <adminpassword>' . $pwd . '</adminpassword>
                <email>' . htmlentities($c->get()["mail"]) . '</email>
                <sourcepassword>' . $source_pwd . '</sourcepassword>
                <title>Radio ' . $id . '</title>
                <organization>' . htmlentities($c->get()['company'] ?: $c->get()['name']) . '</organization>
                <introfile></introfile>
                <fallbackfile></fallbackfile>
                <template>' . $this->getOption("template") . '</template>
            </request>
        </centovacast>';

        $ch = curl_init($this->getOption("url") . "/api.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $xml = @simplexml_load_string($res);
        if (!$xml || !isset($xml->response)) {
            return [false, $res];
        }

        if ($xml->response->message != "Account created") {
            return [false, $xml->response->message];
        }

        $xml = $xml->response;

        return [true, [
            "username" => $username,
            "pwd" => $pwd,
            "source_pwd" => $source_pwd,
            "ip" => strval($xml->data->account->ipaddress),
            "port" => strval($xml->data->account->port),
        ]];
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <centovacast>
            <request class="system" method="terminate">
                <password>' . $this->getOption("username") . '|' . $this->getOption("password") . '</password>
                <username>' . ($this->getData("username") ?: "r$id") . '</username>
            </request>
        </centovacast>';

        $ch = curl_init($this->getOption("url") . "/api.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $xml = @simplexml_load_string($res);
        if (!$xml || !isset($xml->response)) {
            return [false, $res];
        }

        if ($xml->response->message != "Account removed") {
            return [false, $xml->response->message];
        }

        return [true];
    }

    public function Output($id, $task = "")
    {
        global $pars, $sec;
        $this->loadOptions($id);

        ob_start();
        $url = $this->getOption("url");
        ?>
        <div class="panel panel-default">
            <div class="panel-heading"><?=$this->getLang("CRED");?></div>
            <div class="panel-body">
                <b><?=$this->getLang("SRV");?>:</b> <?=$this->getData("ip") . ":" . $this->getData("port");?><br />
                <b><?=$this->getLang("URL");?>:</b> <a href="<?=$url;?>" target="_blank"><?=$url;?></a><br />
                <b><?=$this->getLang("USERNAME");?>:</b> <?=$this->getData("username") ?: "r$id";?>
                <br /><b><?=$this->getLang("RPW");?>:</b> <?=$this->getData("pwd");?>
            </div>
        </div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $status = "disabled")
    {
        $this->loadOptions($id);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <centovacast>
            <request class="system" method="setstatus">
                <password>' . $this->getOption("username") . '|' . $this->getOption("password") . '</password>
                <username>' . ($this->getData("username") ?: "r$id") . '</username>
                <status>' . $status . '</status>
            </request>
        </centovacast>';

        $ch = curl_init($this->getOption("url") . "/api.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $xml = @simplexml_load_string($res);
        if (!$xml || !isset($xml->response)) {
            return [false, $res];
        }

        if ($xml->response->message != "Account status updated") {
            return [false, $xml->response->message];
        }

        return [true];
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, "enabled");
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