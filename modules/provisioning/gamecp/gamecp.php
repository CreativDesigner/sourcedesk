<?php

class GameCpProv extends Provisioning
{
    protected $name = "GameCP";
    protected $short = "gamecp";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);
        ob_start();
        ?>
		<div class="row">
		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

			<div class="col-md-6" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("GURL");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://gamecp.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("PASSPHRASE");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="Ndk92KloP90nKe8Q-8m1" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("GAMEID");?></label>
					<input type="text" data-setting="gameid" value="<?=$this->getOption("gameid");?>" placeholder="10" class="form-control prov_settings" />
				</div>
            </div>

            <div class="col-md-6" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("slots");?></label>
					<input type="text" data-setting="slots" value="<?=$this->getOption("slots");?>" placeholder="50" class="form-control prov_settings" />
				</div>
			</div>
		</div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    private function call($data)
    {
        $url = $this->getOption("url") . "/billing/mb/index.php?passphrase=" . urlencode($this->getOption("password")) . "&connector=ce";
        if (is_array($data) && count($data)) {
            $url .= "&" . http_build_query($data);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        $pwd = $sec->generatePassword(12, false, "lud");
        $rconpwd = $sec->generatePassword(12, false, "lud");
        $privpwd = $sec->generatePassword(12, false, "lud");

        $res = $this->call([
            "action" => "create",
            "function" => "createacct",
            "username" => $this->getUsername($id),
            "password" => $pwd,
            "customerid" => $u->get()['ID'],
            "packageid" => $id,
            "email_server" => "no",
            "start_server" => "yes",
            "mark_ip_used" => "no",
            "emailaddr" => $u->get()['mail'],
            "firstname" => $u->get()['firstname'],
            "lastname" => $u->get()['lastname'],
            "address" => $u->get()['street'] . " " . $u->get()['street_nr'],
            "city" => $u->get()['city'],
            "state" => "",
            "country" => $u->get()['country_alpha2'],
            "zipcode" => $u->get()['postcode'],
            "phonenum" => $u->get()['telephone'],
            "game_id" => $this->getOption("gameid"),
            "max_players" => $this->getOption("slots"),
            "pub_priv" => "yes",
            "login_path" => "yes",
            "hostname" => "s" . $id . ".com",
            "motd" => "",
            "rcon_password" => $rconpwd,
            "priv_password" => $privpwd,
        ]);

        if (strpos($res, "Command Execution Result: Ok") === false) {
            return [false, $res];
        }

        return [true, [
            "pwd" => $pwd,
            "rconpwd" => $rconpwd,
            "privpwd" => $privpwd,
            "username" => $this->getUsername($id),
        ]];
    }

    public function Delete($id)
    {
        return $this->Suspend($id, "delete");
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
                <b><?=$this->getLang("USERNAME");?>:</b> <?=$this->getData("username") ?: "s$id";?><br />
                <b><?=$this->getLang("PASSWORD");?>:</b> <?=$this->getData("pwd");?><br /><br />
                <b><?=$this->getLang("SPW");?>:</b> <?=$this->getData("privpwd");?><br />
                <b><?=$this->getLang("RCONPW");?>:</b> <?=$this->getData("rconpwd");?>
            </div>
        </div>

        <form method="POST" target="_blank" action="<?=$this->getOption("url");?>">
            <input type="hidden" name="user" value="<?=$this->getData("username") ?: "s$id";?>" />
            <input type="hidden" name="pass" value="<?=$this->getData("pwd");?>" />
            <input type="submit" name="sublogin" value="<?=$this->getLang("MANAGE");?>" class="btn btn-primary btn-block" />
        </form>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $task = "suspendgame")
    {
        $this->loadOptions($id);
        $u = $this->getClient($id);

        $res = $this->call([
            "action" => $task,
            "customerid" => $u->get()['ID'],
            "packageid" => $id,
        ]);

        if (strpos($res, "Command Execution Result: Ok") === false) {
            return [false, $res];
        }

        return [true];
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, "unsuspendgame");
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