<?php

class XenoPanelProv extends Provisioning
{
    protected $name = "XenoPanel";
    protected $short = "xenopanel";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);
        ob_start();
        ?>
		<div class="row">
		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

			<div class="col-md-4" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("xpurl");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://xenopanel.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("apiuser");?></label>
					<input type="text" data-setting="api_user" value="<?=$this->getOption("api_user");?>" placeholder="<?=$this->getLang("SECRET");?>" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("apikey");?></label>
					<input type="password" data-setting="api_key" value="<?=$this->getOption("api_key");?>" placeholder="<?=$this->getLang("SECRET");?>" class="form-control prov_settings" />
				</div>
            </div>

            <div class="col-md-12" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("PLANID");?></label>
					<input type="text" data-setting="plan" value="<?=$this->getOption("plan");?>" placeholder="Admin Dashboard > API > Module Plans Manager" class="form-control prov_settings" />
				</div>
			</div>
		</div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    private function call($method, $data = null)
    {
        if (!is_array($data)) {
            $data = [];
        }

        $data['api_key'] = $this->getOption("api_key");
        $data['api_user'] = $this->getOption("api_user");

        $ch = curl_init($this->getOption("url") . "/api/v2/admin/" . $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $res = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        if (strpos($res, "success") === false) {
            throw new Exception($data);
        }

        return json_decode($res, true);
    }

    public function Create($id)
    {
        $this->loadOptions($id);
        $u = $this->getClient($id);

        try {
            $res = $this->call("create_server", [
                "product_id" => $id,
                "first_name" => $u->get()['firstname'],
                "last_name" => $u->get()['lastname'],
                "email_address" => $u->get()['mail'],
                "user_id" => $u->get()['ID'],
                "plan_id" => $this->getOption("plan"),
            ]);

            return [true, [
                "usr" => $res['username'],
                "pwd" => $res['password'],
                "ipp" => $res['ip_address'] . ":" . $res['port'],
                "sid" => $res['server_id'],
            ]];
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }
    }

    public function Delete($id)
    {
        return $this->Suspend($id, "delete_server");
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
                <b><?=$this->getLang("url");?>:</b> <a href="<?=$this->getOption("url");?>" target="_blank"><?=$this->getOption("url");?></a><br />
                <b><?=$this->getLang("username");?>:</b> <?=$this->getData("usr");?><br />
                <b><?=$this->getLang("password");?>:</b> <?=$this->getData("pwd");?><br /><br />
                <b><?=$this->getLang("sip");?>:</b> <?=$this->getData("ipp");?>
            </div>
        </div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $task = "suspend_server")
    {
        $this->loadOptions($id);
        $u = $this->getClient($id);

        try {
            $res = $this->call($task, [
                "product_id" => $id,
                "first_name" => $u->get()['firstname'],
                "last_name" => $u->get()['lastname'],
                "email_address" => $u->get()['mail'],
                "user_id" => $u->get()['ID'],
                "plan_id" => $this->getOption("plan"),
            ]);

            return [true];
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, "unsuspend_server");
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