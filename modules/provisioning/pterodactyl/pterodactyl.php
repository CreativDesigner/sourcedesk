<?php

class PterodactylProv extends Provisioning
{
    protected $name = "Pterodactyl";
    protected $short = "pterodactyl";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;
    protected $version = "1.1";

    private function call($action, $data, $method = "POST")
    {
        $ch = curl_init(rtrim($this->options["url"], "/") . "/api/application/" . $action);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->options["key"],
        ]);

        if (is_array($data) && count($data) > 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $res = @json_decode(curl_exec($ch), true);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        switch ($code) {
            case 401:
                $err = $this->getLang("AF");
                break;

            case 403:
                $err = $this->getLang("AF");
                break;

            case 404:
                $err = $this->getLang("URLF");
                break;

            case 500:
                $err = $this->getLang("INTER");
                break;
        }

        if (!empty($err)) {
            die("<div class='alert alert-danger'>$err</div>");
        }

        return $res;
    }

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);
        ob_start();

        if (isset($_POST['url'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $this->options["url"] = $_POST['url'];
            $this->options["key"] = $_POST['key'];

            $res = $this->call("nodes", []);
            if (empty($res['object']) || $res['object'] != "list") {
                die("<div class='alert alert-danger'>{$this->getLang("SERR")}</div>");
            }

            $nodes = [];
            foreach ($res["data"] as $r) {
                if ($r['object'] != "node") {
                    continue;
                }

                $nodes[$r['attributes']['id']] = $r['attributes']['name'];
            }

            if (!count($nodes)) {
                die("<div class='alert alert-danger'>{$this->getLang("NONODES")}</div>");
            }

            $res = $this->call("nests", []);

            $nests = [];
            foreach ($res["data"] as $r) {
                if ($r['object'] != "nest") {
                    continue;
                }

                $nests[$r['attributes']['id']] = $r['attributes']['name'];
            }

            if (!count($nests)) {
                die("<div class='alert alert-danger'>{$this->getLang("NONESTS")}</div>");
            }

            ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?=$this->getLang("NODE");?></label>
                        <select data-setting="node" class="form-control prov_settings">
                        <?php
foreach ($nodes as $id => $name) {
                $name = htmlentities($name);
                $sel = $this->getOption("node") == $id ? " selected=''" : "";
                echo "<option value='$id'$sel>$name</option>";
            }
            ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label><?=$this->getLang("NEST");?></label>
                        <select data-setting="nest" class="form-control prov_settings">
                        <?php
foreach ($nests as $id => $name) {
                $name = htmlentities($name);
                $sel = $this->getOption("nest") == $id ? " selected=''" : "";
                echo "<option value='$id'$sel>$name</option>";
            }
            ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label><?=$this->getLang("EGGID");?></label>
                        <input type="text" data-setting="egg" value="<?=$this->getOption("egg");?>" placeholder="<?=$this->getLang("MMN");?>" class="form-control prov_settings" />
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label><?=$this->getLang("DBS");?></label>
                        <input type="text" data-setting="dbs" value="<?=$this->getOption("dbs") ?: "0";?>" placeholder="<?=$this->getLang("UNLIMITED");?>" class="form-control prov_settings" />
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label><?=$this->getLang("alocs");?></label>
                        <input type="text" data-setting="allocations" value="<?=$this->getOption("allocations") ?: "0";?>" placeholder="<?=$this->getLang("unlimited");?>" class="form-control prov_settings" />
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label><?=$this->getLang("MEMMB");?></label>
                        <input type="text" data-setting="ram" value="<?=$this->getOption("ram") ?: "";?>" placeholder="<?=$this->getLang("UNLIMITED");?>" class="form-control prov_settings" />
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label><?=$this->getLang("SWAPMB");?></label>
                        <input type="text" data-setting="swap" value="<?=$this->getOption("swap") ?: "0";?>" placeholder="<?=$this->getLang("UNLIMITED");?>" class="form-control prov_settings" />
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label><?=$this->getLang("DISKMB");?></label>
                        <input type="text" data-setting="storage" value="<?=$this->getOption("storage") ?: "1024";?>" placeholder="<?=$this->getLang("UNLIMITED");?>" class="form-control prov_settings" />
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label><?=$this->getLang("CPU");?></label>
                        <input type="text" data-setting="cpu" value="<?=$this->getOption("cpu") ?: "";?>" placeholder="<?=$this->getLang("UNLIMITED");?>" class="form-control prov_settings" />
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label><?=$this->getLang("IO");?></label>
                        <input type="text" data-setting="io" value="<?=$this->getOption("io") ?: "500";?>" placeholder="<?=$this->getLang("UNLIMITED");?>" class="form-control prov_settings" />
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label><?=$this->getLang("PORT");?></label>
                        <input type="text" data-setting="port" value="<?=$this->getOption("port") ?: "";?>" placeholder="<?=$this->getLang("PORTH");?>" class="form-control prov_settings" />
                    </div>
                </di>
            </div>
            <?php
exit;
        }

        ?>
        <script>$("#ip_tab_btn").show();</script>
		<div class="row" mgmt="1">
		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("PTERURL");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://ptero.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("APIKEY");?></label>
					<input type="password" data-setting="key" value="<?=$this->getOption("key");?>" placeholder="<?=$this->getLang("SECRET");?>" class="form-control prov_settings" />
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
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=pterodactyl", {
				url: $("[data-setting=url]").val(),
				key: $("[data-setting=key]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("CHECKCONN");?>');
				$("#server_conf").html(r);
			});
		}

        <?=!empty($this->getOption("url")) ? 'request();' : '';?>

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
        $u = $this->getClient($id);

        $pwd = $sec->generatePassword(12, false, "lud");

        $nest = [];
        $res = $this->call("nests", []);
        foreach ($res["data"] as $r) {
            if ($r['object'] != "nest") {
                continue;
            }

            if ($r['attributes']['id'] != $this->getOption("nest")) {
                continue;
            }

            $nest = $r['attributes'];
        }

        if (!count($nest)) {
            return [false, "Unknown nest"];
        }

        $res = $this->call("users", [
            "email" => $u->get()['mail'],
            "username" => $username = $this->getUsername($id),
            "first_name" => $u->get()['firstname'],
            "last_name" => $u->get()['lastname'],
            "password" => $pwd,
        ], "POST");

        if (!is_array($res) || empty($res['object']) || $res['object'] != "user") {
            return [false, "Could not create user"];
        }

        $uid = $res['attributes']['id'];

        $data = [
            "name" => $username,
            "user" => $uid,
            "nest" => $this->getOption("nest"),
            "egg" => $this->getOption("egg"),
            "limits" => [
                "memory" => $this->getOption("ram") ?: "0",
                "swap" => $this->getOption("swap") ?: "-1",
                "disk" => $this->getOption("storage") ?: "-1",
                "io" => $this->getOption("io") ?: "1000",
                "cpu" => $this->getOption("cpu") ?: "0",
            ],
        ];

        if ($this->getOption("port")) {
            $ip = $this->getDedicatedIP();
            if (!$ip) {
                return array(false, "No free IP addresses");
            }

            $data["deploy"] = [
                "port_range" => [$this->getOption("port"), $this->getOption("port")],
                "dedicated_ip" => $ip,
            ];
        }

        $this->call("servers", $data, "POST");

        return [true, [
            "username" => $username,
            "uid" => $uid,
            "pwd" => $pwd,
        ]];
    }

    public function Output($id, $task = "")
    {
        global $pars, $sec;
        $this->loadOptions($id);

        ob_start();
        ?>
        <div class="panel panel-default">
            <div class="panel-heading"><?=$this->getLang("CRED");?></div>
            <div class="panel-body">
                <b><?=$this->getLang("URL");?>:</b> <a href="<?=$this->getOption("url");?>" target="_blank"><?=$this->getOption("url");?></a><br />
                <b><?=$this->getLang("USERNAME");?>:</b> <?=$this->getData("username") ?: "s$id";?><br />
                <b><?=$this->getLang("PASSWORD");?>:</b> <?=$this->getData("pwd");?>
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