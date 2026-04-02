<?php
class WHMSonicProv extends Provisioning
{
    protected $name = "WHMSonic";
    protected $short = "whmsonic";
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

            $url = $_POST['url'];
            $usr = $_POST['username'];
            $pwd = $_POST['password'];

            $ch = curl_init($url . "/whmsonic/modules/api2.php");
            curl_setopt($ch, CURLAUTH_BASIC, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_USERPWD, "$usr:$pwd");
            curl_setopt($ch, CURLOPT_POSTFIELDS, "cmd=packs&owner=$usr");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $res = curl_exec($ch);
            $rec = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                die('<div class="alert alert-danger">' . $err . '</div>');
            }

            if ($rec == 401) {
                die('<div class="alert alert-danger">' . $this->getLang("CREDERR") . '</div>');
            }

            if (empty($res)) {
                die('<div class="alert alert-danger">' . $this->getLang("noplans") . '</div>');
            }
            ?>
            <div class="form-group">
                <label><?=$this->getLang("template");?></label>
                <select data-setting="template" class="form-control prov_settings">
                    <?php
foreach (explode(",", $res) as $s) {?>
                    <option <?php if ($this->getOption('template') == $s) {
                echo ' selected=""';
            }
                ?>><?=htmlentities($s);?></option>
                    <?php }?>
                </select>
            </div>
			<?php
exit;
        }

        ob_start();?>
		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-3">
				<div class="form-group">
					<label><?=$this->getLang("WHMURL");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://whm.sourceway.de:2087" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-3">
				<div class="form-group">
					<label><?=$this->getLang("SIP");?></label>
					<input type="text" data-setting="ip" value="<?=$this->getOption("ip");?>" placeholder="5.9.7.9" class="form-control prov_settings" />
				</div>
			</div>

            <div class="col-md-3">
				<div class="form-group">
					<label><?=$this->getLang("USERNAME");?></label>
					<input type="text" data-setting="username" value="<?=$this->getOption("username");?>" placeholder="root" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-3">
				<div class="form-group">
					<label><?=$this->getLang("password");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="<?=$this->getLang("secret");?>" class="form-control prov_settings" />
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
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=whmsonic", {
				url: $("[data-setting=url]").val(),
				ip: $("[data-setting=ip]").val(),
                username: $("[data-setting=username]").val(),
				password: $("[data-setting=password]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
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

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $c = $this->getClient($id);

        $pwd = $sec->generatePassword(12, false, "lud");

        $data = [
            "cmd" => "setup",
            "stype" => "External",
            "ip" => $this->getOption("ip"),
            "cemail" => $c->get()['mail'],
            "rad_username" => $username = $this->getUsername($id),
            "c_pass" => $pwd,
            "owner" => $this->getOption("username"),
            "package" => $this->getOption("template"),
            "esend" => "yes",
        ];

        $ch = curl_init($this->getOption("url") . "/whmsonic/modules/api2.php");
        curl_setopt($ch, CURLAUTH_BASIC, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->getOption("username")}:{$this->getOption("password")}");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        $rec = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return [false, $err];
        }

        if ($rec == 401) {
            return [false, $this->getLang("AUTHFAIL")];
        }

        if ($res != "Complete" && $res != "") {
            return [false, $res];
        }

        return [true, [
            "username" => $username,
            "pwd" => $pwd,
        ]];
    }

    public function Delete($id)
    {
        return $this->Suspend($id, "terminate");
    }

    public function Output($id, $task = "")
    {
        global $pars, $sec;
        $this->loadOptions($id);

        ob_start();
        $url = str_replace("2087", "2083", $this->getOption("url"));
        ?>
        <div class="panel panel-default">
            <div class="panel-heading"><?=$this->getLang("CRED");?></div>
            <div class="panel-body">
            <b><?=$this->getLang("URL");?>:</b> <a href="<?=$url;?>" target="_blank"><?=$url;?></a><br />
            <b><?=$this->getLang("USERNAME");?>:</b> <?=$this->getData("username") ?: "sc_$id";?>
            <br /><b><?=$this->getLang("PASSWORD");?>:</b> <?=$this->getData("pwd");?>
            </div>
        </div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $task = "suspend")
    {
        $this->loadOptions($id);

        $data = [
            "cmd" => $task,
            "rad_username" => $this->getData("username") ?: ("sc_" . $id),
        ];

        $ch = curl_init($this->getOption("url") . "/whmsonic/modules/api2.php");
        curl_setopt($ch, CURLAUTH_BASIC, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->getOption("username")}:{$this->getOption("password")}");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        $rec = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return [false, $err];
        }

        if ($rec == 401) {
            return [false, $this->getLang("authfail")];
        }

        if ($res != "Complete") {
            return [false, $res];
        }

        return array(true);
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, "unsuspend");
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