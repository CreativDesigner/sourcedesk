<?php

class LiveconfigLicensingProv extends Provisioning
{
    protected $name = "LiveConfig Lizenzen";
    protected $short = "liveconfig_licensing";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);
        ob_start();?>

		<div class="row">
		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

			<div class="col-md-6" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("username");?></label>
					<input type="text" data-setting="user" value="<?=$this->getOption("user");?>" placeholder="sourceway" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("password");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="GlMo=1O!" class="form-control prov_settings" />
				</div>
			</div>

            <div class="col-md-12" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("LICENSE");?></label>
					<select data-setting="license" class="form-control prov_settings">
                        <option value="0">Basic</option>
                        <option value="1"<?=$this->getOption("license") == "1" ? ' selected=""' : "";?>>Standard</option>
                        <option value="2"<?=$this->getOption("license") == "2" ? ' selected=""' : "";?>>Business</option>
                    </select>
				</div>
			</div>
		</div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    protected function call($function, $data)
    {
        $url = "https://www.liveconfig.com/soap.wsdl";
        $user = $this->getOption("user");
        $pw = $this->getOption("password");

        $ts = gmdate("Y-m-d") . "T" . gmdate("H:i:s") . "000Z";
        $token = base64_encode(hash_hmac('sha256', 'LC_Partner_API' . $user . $function . $ts, md5($pw), true));
        $auth = array(
            'login' => $user,
            'timestamp' => $ts,
            'token' => $token,
        );
        $data['auth'] = $auth;
        $client = new SoapClient($url, array('style' => SOAP_DOCUMENT, 'use' => SOAP_LITERAL));
        try {
            return $client->$function($data);
        } catch (SoapFault $soapFault) {
            return $soapFault->faultstring;
        }
    }

    public function Delete($id, $reason = 0)
    {
        $this->loadOptions($id);

        $res = $this->call('CancelLicense', array("licenseid" => $this->getData("license"), "reason" => $reason));
        if (is_string($res)) {
            return [false, $res];
        }

        return [true];
    }

    public function Create($id)
    {
        $this->loadOptions($id);

        if (!empty($this->getData("license"))) {
            return array(false, "There is a license ID already. Clear the module data first.");
        }

        $res = $this->call('OrderLicense', array("edition" => $this->getOption("license"), "comment" => "Vertrag " . $id));
        if (is_string($res)) {
            return [false, $res];
        }

        return [true, [
            "license" => $res->licenseid,
            "code" => $res->code,
        ]];
    }

    public function Output($id, $task = "")
    {
        $this->loadOptions($id);

        if ($task == "cancel") {
            $res = $this->Delete($id, 1);
            if ($res[0]) {
                $suc = $this->getLang("RESOK");
            } else {
                $err = $res[1];
            }
        }

        ob_start();
        if (isset($err)) {
            echo '<div class="alert alert-danger">' . $err . '</div>';
        }

        if (isset($suc)) {
            echo '<div class="alert alert-success">' . $suc . '</div>';
        }
        ?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("LICDATA");?></div>
		  <div class="panel-body">
            <b><?=$this->getLang("LICID");?>:</b> <?=$this->getData("license");?><br />
		    <b><?=$this->getLang("LICKEY");?>:</b> <?=$this->getData("code");?>
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
            "config_url",
        );
    }

    public function EmailVariables($id)
    {
        global $raw_cfg;
        $this->loadOptions($id);

        return array(
            "config_url" => $raw_cfg['PAGEURL'] . "/hosting/" . $id,
        );
    }

    public function AdminFunctions($id)
    {
        return [
            "cancel" => $this->getLang("LICCAN"),
        ];
    }
}