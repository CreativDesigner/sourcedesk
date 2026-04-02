<?php

class SpamExpertsProv extends Provisioning
{
    protected $name = "SpamExperts";
    protected $short = "spamexperts";
    protected $lang;
    protected $options = array();
    protected $ssh;
    protected $db;
    protected $serverMgmt = true;
    protected $usernameMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("url");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://login.antispamcloud.com" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("username");?></label>
					<input type="text" data-setting="username" value="<?=$this->getOption("username");?>" placeholder="mail@example.com" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("password");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="GlMo=1O!" class="form-control prov_settings" />
				</div>
			</div>
        </div>
        <div class="row" mgmt="0">
			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("domains");?></label>
					<input type="text" data-setting="domains" value="<?=$this->getOption("domains");?>" placeholder="10" class="form-control prov_settings" />
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("products");?></label>
					<select data-setting="products" class="form-control prov_settings">
						<option value="100"<?=$this->getOption("products") == "100" ? ' selected=""' : '';?>><?=$this->getLang("p100");?></option>
						<option value="010"<?=$this->getOption("products") == "010" ? ' selected=""' : '';?>><?=$this->getLang("p010");?></option>
						<option value="001"<?=$this->getOption("products") == "001" ? ' selected=""' : '';?>><?=$this->getLang("p001");?></option>
						<option value="110"<?=$this->getOption("products") == "110" ? ' selected=""' : '';?>><?=$this->getLang("p110");?></option>
						<option value="101"<?=$this->getOption("products") == "101" ? ' selected=""' : '';?>><?=$this->getLang("p101");?></option>
						<option value="011"<?=$this->getOption("products") == "011" ? ' selected=""' : '';?>><?=$this->getLang("p011");?></option>
						<option value="111"<?=$this->getOption("products") == "111" ? ' selected=""' : '';?>><?=$this->getLang("p111");?></option>
					</select>
				</div>
			</div>
		</div>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);

        $username = $this->getData("username");
        $password = $sec->generatePassword(16, false, "lud");
        $email = urlencode($this->getClient($id)->get()['mail']);

        $url = $this->getOption("url") . "/api/admin/add/format/json/username/$username/password/$password/email/$email/domainslimit/" . $this->getOption("domains") . "/api_usage/1";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->getOption("username") . ":" . $this->getOption("password"));
        $res = json_decode(curl_exec($ch));

        $m = $res->messages;
        if (!empty($m->error)) {
            return array(false, $m->error[0]);
        }

        if (empty($m->success)) {
            return array(false, "Unknown error");
        }

        $inbound = substr($this->getOption("products"), 0, 1);
        $outbound = substr($this->getOption("products"), 1, 1);
        $archive = substr($this->getOption("products"), 2, 1);

        $url = $this->getOption("url") . "/api/admin/setproducts/format/json/username/$username/incoming/" . $inbound . "/outgoing/" . $outbound . "/archiving/" . $archive;

        curl_setopt($ch, CURLOPT_URL, $url);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        $m = $res->messages;
        if (!empty($m->error)) {
            return array(false, $m->error[0]);
        }

        if (empty($m->success)) {
            return array(false, "Unknown error");
        }

        return array(true, array(
            "username" => $username,
            "password" => $password,
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $username = $this->getData("username");
        $url = $this->getOption("url") . "/api/admin/remove/format/json/username/$username";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->getOption("username") . ":" . $this->getOption("password"));
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        $m = $res->messages;
        if (!empty($m->error)) {
            return array(false, $m->error[0]);
        }

        if (empty($m->success)) {
            return array(false, "Unknown error");
        }

        return array(true);
    }

    public function Output($id, $task = "")
    {
        global $pars, $sec;
        $this->loadOptions($id);

        ob_start();

        if ($task == "new_pw") {
            $password = $sec->generatePassword(16, false, "lud");
            $username = $this->getData("username");
            $url = $this->getOption("url") . "/api/admin/update/format/json/username/$username/password/$password";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->getOption("username") . ":" . $this->getOption("password"));
            curl_exec($ch);
            curl_close($ch);

            $this->setData("password", $password);

            $_SESSION['spamexperts_html'] = '<div class="alert alert-success">' . $this->getLang("resok") . '</div>';
            if (isset($pars[1]) && $pars[1] == $task) {
                header('Location: ./');
            } else {
                header('Location: ?p=hosting&id=' . $id);
            }

            exit;
        }

        ?>
		<?php if (!empty($_SESSION['spamexperts_html'])) {echo $_SESSION['spamexperts_html'];
            $_SESSION['spamexperts_html'] = "";}?>
		<div class="panel panel-default">
			<div class="panel-heading"><?=$this->getLang("CRED");?></div>
			<div class="panel-body">
				<b><?=$this->getLang("url");?>:</b> <a href="<?=$this->getOption("url");?>" target="_blank"><?=$this->getOption("url");?></a><br />
        <b><?=$this->getLang("username");?>:</b> <?=$this->getData("username");?><br />
        <b><?=$this->getLang("password");?>:</b> <?=$this->getData("password");?><br /><br />
        <?php
$url = $this->getOption("url") . "/api/authticket/create/format/json/username/" . $this->getData("username");

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->getOption("username") . ":" . $this->getOption("password"));
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        $link = rtrim($this->getOption("url"), "/") . "/?authticket=" . $res->result;
        ?>
        <a href="<?=$link;?>" target="_blank" class="btn btn-primary btn-block"><?=$this->getLang("LTC");?></a>
        <small><?=$this->getLang("UCS");?></small>
			</div>
		</div>

    <div class="panel panel-default">
			<div class="panel-heading"><?=$this->getLang("COND");?></div>
			<div class="panel-body">
				<?=$this->getLang("domainss");?>: <?=$this->getOption("domains");?><br />
        <?=$this->getLang("ICF");?>: <?=substr($this->getOption("products"), 0, 1) ? "<font color='green'><b>{$this->getLang("YES")}</b></font>" : "<font color='red'><b>{$this->getLang("NO")}</b></font>";?><br />
        <?=$this->getLang("OGF");?>: <?=substr($this->getOption("products"), 1, 1) ? "<font color='green'><b>{$this->getLang("YES")}</b></font>" : "<font color='red'><b>{$this->getLang("NO")}</b></font>";?><br />
        <?=$this->getLang("EMA");?>: <?=substr($this->getOption("products"), 2, 1) ? "<font color='green'><b>{$this->getLang("YES")}</b></font>" : "<font color='red'><b>{$this->getLang("NO")}</b></font>";?><br /><br />

        <p style="text-align: justify; margin-bottom: 0;"><?=$this->getLang("CCS");?></p>
			</div>
		</div>
		<?php

        $res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $status = "inactive")
    {
        $this->loadOptions($id);

        $username = $this->getData("username");
        $url = $this->getOption("url") . "/api/admin/update/format/json/username/$username/status/$status";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->getOption("username") . ":" . $this->getOption("password"));
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        $m = $res->messages;
        if (!empty($m->error)) {
            return array(false, $m->error[0]);
        }

        if (empty($m->success)) {
            return array(false, "Unknown error");
        }

        return array(true);
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, "active");
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        $username = $this->getData("username");
        $url = $this->getOption("url") . "/api/admin/update/format/json/username/$username/domainslimit/" . $this->getOption("domains");

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->getOption("username") . ":" . $this->getOption("password"));
        $res = json_decode(curl_exec($ch));

        $m = $res->messages;
        if (!empty($m->error)) {
            return array(false, $m->error[0]);
        }

        if (empty($m->success)) {
            return array(false, "Unknown error");
        }

        $inbound = substr($this->getOption("products"), 0, 1);
        $outbound = substr($this->getOption("products"), 1, 1);
        $archive = substr($this->getOption("products"), 2, 1);

        $url = $this->getOption("url") . "/api/admin/setproducts/format/json/username/$username/incoming/" . $inbound . "/outgoing/" . $outbound . "/archiving/" . $archive;

        curl_setopt($ch, CURLOPT_URL, $url);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        $m = $res->messages;
        if (!empty($m->error)) {
            return array(false, $m->error[0]);
        }

        if (empty($m->success)) {
            return array(false, "Unknown error");
        }

        return array(true);
    }

    public function AllEmailVariables()
    {
        return array(
            "url",
            "username",
            "password",
            "config_url",
        );
    }

    public function EmailVariables($id)
    {
        global $raw_cfg;
        $this->loadOptions($id);

        return array(
            "hostname" => $this->getOption("url"),
            "username" => $this->getData("username"),
            "password" => $this->getData("password"),
            "config_url" => $raw_cfg['PAGEURL'] . "/hosting/" . $id,
        );
    }

    public function ContractInfo($id)
    {
        $this->loadOptions($id);

        return [
            $this->getLang("USERNAME") => $this->getData("username"),
        ];
    }

    public function OwnFunctions($id)
    {
        $actions = array();
        $actions["new_pw"] = $this->getLang("SETPW");
        return $actions;
    }

    public function AdminFunctions($id)
    {
        $actions = array();
        $actions["new_pw"] = $this->getLang("SETPW");
        return $actions;
    }
}
