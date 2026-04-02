<?php

class ownCloudProv extends Provisioning
{
    protected $name = "ownCloud";
    protected $short = "owncloud";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $version = "1.2";

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);
        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("OCURL");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://files.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("OCUSR");?></label>
					<input type="text" data-setting="user" value="<?=$this->getOption("user");?>" placeholder="admin" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("OCPWD");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="GlMo=1O!" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<div class="form-group" mgmt="0">
			<label><?=$this->getLang("STORAGE");?></label>
			<div class="input-group">
				<input type="text" data-setting="limit" value="<?=$this->getOption("limit");?>" placeholder="10" class="form-control prov_settings" />
				<span class="input-group-addon"><?=$this->getLang("gb");?></span>
			</div>
		</div>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function loadOptions($id, $pd = false)
    {
        parent::loadOptions($id, $pd);

        foreach (["limit"] as $o) {
            if (!is_numeric($this->options[$o]) && array_key_exists($this->options[$o], $this->cf)) {
                $this->options[$o] = $this->cf[$o];
            }
        }

    }

    private function curl($url, $credentials = "", array $post = array(), $method = "POST")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (count($post) > 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }

        if ($method != "POST") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if (!empty($credentials)) {
            curl_setopt($ch, CURLOPT_USERPWD, $credentials);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "OCS-APIRequest: true",
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        $username = str_replace([" ", "ß", "ä", "ü", "ö"], ["-", "ss", "ae", "ue", "oe"], strtolower($u->get()['lastname'])) . "-" . $id;
        $password = $sec->generatePassword(12, false, "lud");

        $r = $this->curl($this->getOption("url") . "/ocs/v1.php/cloud/users", $this->getOption("user") . ":" . $this->getOption("password"), array("userid" => $username, "password" => $password));

        $xml = simplexml_load_string($r);
        $res = $xml->xpath('/ocs/meta');
        $result = $res[0]->status;

        if ($result != "ok") {
            return array(false, $res[0]->message);
        }

        return array(true, array(
            "username" => $username,
            "password" => $password,
        ), "ChangePackage");
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $r = $this->curl($this->getOption("url") . "/ocs/v1.php/cloud/users/" . urlencode($this->getData("username")), $this->getOption("user") . ":" . $this->getOption("password"), array(), "DELETE");

        $xml = simplexml_load_string($r);
        $res = $xml->xpath('/ocs/meta');
        $result = $res[0]->status;

        if ($res[0]->statuscode == "101") {
            $res[0]->message = "User not found";
        }

        if ($result != "ok") {
            return array(false, $res[0]->message);
        }

        return array(true);
    }

    public function Output($id, $task = "")
    {
        $this->loadOptions($id);

        ob_start();

        if ($task == "reset_pw") {
            $r = $this->curl($this->getOption("url") . "/ocs/v1.php/cloud/users/" . urlencode($this->getData("username")), $this->getOption("user") . ":" . $this->getOption("password"), array("key" => "password", "value" => $this->getData("password")), "PUT");

            $xml = simplexml_load_string($r);
            $res = $xml->xpath('/ocs/meta');
            $result = $res[0]->status;

            if ($result == "ok") {
                echo '<div class="alert alert-success">' . $this->getLang("PWCHANGED") . '</div>';
            } else {
                echo '<div class="alert alert-danger">' . $this->getLang("TECERR") . '</div>';
            }

        }
        ?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("CRED");?></div>
		  <div class="panel-body">
		    <b><?=$this->getLang("URL");?>:</b> <a href="<?=$this->getOption("url");?>" target="_blank"><?=$this->getOption("url");?></a><br />
		    <b><?=$this->getLang("USERNAME");?>:</b> <?=$this->getData("username");?><br />
		    <b><?=$this->getLang("PASSWORD");?>:</b> <?=$this->getData("password");?>
		  </div>
		</div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        $r = $this->curl($this->getOption("url") . "/ocs/v1.php/cloud/users/" . urlencode($this->getData("username")), $this->getOption("user") . ":" . $this->getOption("password"), array("key" => "quota", "value" => $this->getOption("limit") * 1073741824), "PUT");

        $xml = simplexml_load_string($r);
        $res = $xml->xpath('/ocs/meta');
        $result = $res[0]->status;

        if ($result != "ok") {
            return array(false, $res[0]->message);
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
            "user" => $this->getData("username"),
            "password" => $this->getData("password"),
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

    public function ApiTasks($id)
    {
        return array(
            "SetUserPassword" => "pwd",
        );
    }

    public function SetUserPassword($id, $req)
    {
        $this->loadOptions($id);

        $r = $this->curl($this->getOption("url") . "/ocs/v1.php/cloud/users/" . urlencode($this->getData("username")), $this->getOption("user") . ":" . $this->getOption("password"), array("key" => "password", "value" => $req['pwd']), "PUT");

        if (!$r) {
            die(json_encode(array("code" => "810", "message" => "Technical error occured.", "data" => array())));
        }

        $xml = simplexml_load_string($r);
        $res = $xml->xpath('/ocs/meta');
        $result = $res[0]->status;

        if ($result == "ok") {
            $this->setData("password", $req['pwd']);
            die(json_encode(array("code" => "100", "message" => "Password changed successfully.", "data" => array())));
        }

        die(json_encode(array("code" => "811", "message" => "Changing password failed. Maybe not complex enough?", "data" => array())));
    }
}

?>