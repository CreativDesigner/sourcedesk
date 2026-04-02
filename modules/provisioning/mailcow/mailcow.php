<?php

class MailcowProv extends Provisioning
{
    protected $name = "Mailcow";
    protected $short = "mailcow";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['url'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            $this->options["url"] = $_POST['url'];
            $this->options["key"] = $_POST['key'];

            $res = $this->Call("get/domain/all");

            if (!$res) {
                die('<div class="alert alert-danger">' . $this->getLang("CONNFAIL") . '</div>');
            }

            if (!empty($res['type']) && $res['type'] == "error") {
                die('<div class="alert alert-danger">' . $res['msg'] . '</div>');
            }

            $domains = [];
            foreach ($res as $domain) {
                if (array_key_exists("domain_name", $domain)) {
                    array_push($domains, $domain['domain_name']);
                }
            }

            if (!count($domains)) {
                die('<div class="alert alert-danger">' . $this->getLang("NODOMS") . '</div>');
            }

            $html = '<div class="form-group"><label>' . $this->getLang("domain") . '</label><select data-setting="domain" class="form-control prov_settings">';

            foreach ($domains as $p) {
                if (!empty($this->getOption("domain")) && $this->getOption("domain") == $p) {
                    $html .= "<option value='$p' selected='selected'>$p</option>";
                } else {
                    $html .= "<option value='$p'>$p</option>";
                }

            }

            $html .= "</select></div>";

            $html .= '<div class="form-group"><label>' . $this->getLang("space") . '</label><input data-setting="space" class="form-control prov_settings" value="' . intval($this->getOption("space")) . '"></div>';
            die($html);
        }

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("MCURL");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://mail.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("MCKEY");?></label>
					<input type="password" data-setting="key" value="<?=$this->getOption("key");?>" placeholder="DFA20D-80EE3E-0DAE45-44CDA4-A2074A" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<a href="#" id="check_conn" mgmt="0" class="btn btn-default btn-block"><?=$this->getLang("GDFS");?></a>

		<script>
        if (!mgmt) {
		var doing = false;
		function request(){
			if(doing) return;
			doing = !doing;
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("DBF");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=mailcow", {
				url: $("[data-setting=url]").val(),
				key: $("[data-setting=key]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("GDFS");?>');
				$("#server_conf").html(r);
			});
		}
		<?php if (!empty($this->getOption("url"))) {
            ?>request();<?php
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

    private function Call($action, $method = "GET", $data = [])
    {
        $ch = curl_init(rtrim($this->getOption("url"), "/") . "/api/v1/" . ltrim($action, "/"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = [
            "X-API-Key: " . $this->getOption("key"),
        ];

        if ($method != "GET") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (is_array($data) && count($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        } else {
            array_push($headers, "Content-Type: application/json");
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $res = @curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        $res = @json_decode($res, true);
        if (!is_array($res)) {
            return false;
        }

        return $res;
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        $res = $this->Call("add/mailbox", "POST", ["attr" => json_encode([
            "local_part" => "c$id",
            "domain" => $this->getOption("domain"),
            "name" => $u->get()['name'],
            "quota" => $this->getOption("space"),
            "password" => $pwd = $sec->generatePassword(12, false, "lud"),
            "password2" => $pwd,
            "active" => true,
        ])]);

        if (array_key_exists("type", $res) && $res['type'] == "error") {
            return [false, $res['msg']];
        }

        if (!is_array($res) || count($res) != 1 || !array_key_exists(0, $res) || $res[0]['type'] != "success") {
            return [false, $res[0]['msg'][0] ?? ""];
        }

        return array(true, array(
            "password" => $pwd,
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $res = $this->Call("delete/mailbox", "POST", [
            "items" => json_encode(["c$id@" . $this->getOption("domain")]),
        ]);

        if (array_key_exists("type", $res) && $res['type'] == "error") {
            return [false, $res['msg']];
        }

        return array(true);
    }

    public function Output($id, $task = "")
    {
        $this->loadOptions($id);

        ob_start();

        if ($task == "reset_pw") {
            $res = $this->Call("edit/mailbox", "POST", [
                "items" => json_encode(["c$id@" . $this->getOption("domain")]),
                "attr" => json_encode([
                    "password" => $this->getData("password"),
                    "password2" => $this->getData("password"),
                ]),
            ]);

            if (array_key_exists("type", $res) && $res['type'] == "error") {
                echo '<div class="alert alert-danger">' . $this->getLang("TECERR") . '</div>';
            } else {
                echo '<div class="alert alert-success">' . $this->getLang("RESOK") . '</div>';
            }
        }

        ?>
		<div class="panel panel-default">
            <div class="panel-heading"><?=$this->getLang("CRED");?></div>
            <div class="panel-body">
                <b><?=$this->getLang("URL");?>:</b> <a href="<?=$this->getOption("url");?>" target="_blank"><?=$this->getOption("url");?></a><br />
                <b><?=$this->getLang("email");?>:</b> c<?=$id;?>@<?=$this->getOption("domain");?><br />
                <b><?=$this->getLang("password");?>:</b> <?=$this->getData("password");?>
            </div>
        </div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $active = false)
    {
        $this->loadOptions($id);

        $res = $this->Call("edit/mailbox", "POST", [
            "items" => json_encode(["c$id@" . $this->getOption("domain")]),
            "attr" => json_encode([
                "active" => $active,
            ]),
        ]);

        if (array_key_exists("type", $res) && $res['type'] == "error") {
            return [false, $res['msg']];
        }

        return array(true);
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, true);
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        $res = $this->Call("edit/mailbox", "POST", [
            "items" => json_encode(["c$id@" . $this->getOption("domain")]),
            "attr" => json_encode([
                "quota" => $this->getOption("space"),
            ]),
        ]);

        if (array_key_exists("type", $res) && $res['type'] == "error") {
            return [false, $res['msg']];
        }

        return array(true);
    }

    public function AllEmailVariables()
    {
        return array(
            "url",
            "user",
            "password",
        );
    }

    public function EmailVariables($id)
    {
        $this->loadOptions($id);

        return array(
            "url" => $this->getOption("url"),
            "user" => "c$id@" . $this->getOption("domain"),
            "password" => $this->getData("password"),
        );
    }

    public function ContractInfo($id)
    {
        $this->loadOptions($id);

        return [
            $this->getLang("EMAIL") => "c$id@" . $this->getOption("domain"),
        ];
    }

    public function OwnFunctions($id)
    {
        return array(
            "reset_pw" => $this->getLang("RESPW"),
        );
    }

    public function AdminFunctions($id)
    {
        return array(
            "reset_pw" => $this->getLang("RESPW"),
        );
    }
}