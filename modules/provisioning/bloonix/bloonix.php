<?php

class BloonixProv extends Provisioning
{
    protected $name = "Bloonix";
    protected $short = "bloonix";
    protected $lang;
    protected $options = array();
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
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://monitor.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("user");?></label>
					<input type="text" data-setting="user" value="<?=$this->getOption("user");?>" placeholder="admin" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("pw");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="GlMo=1O!" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<div class="form-group" mgmt="0">
			<label><?=$this->getLang("hosts");?></label>
			<input type="text" data-setting="hosts" value="<?=$this->getOption("hosts");?>" placeholder="10" class="form-control prov_settings" />
			<p class="help-block"><?=$this->getLang("uh");?></p>
		</div>

		<div class="form-group" mgmt="0">
			<label><?=$this->getLang("services");?></label>
			<input type="text" data-setting="services" value="<?=$this->getOption("services");?>" placeholder="10" class="form-control prov_settings" />
			<p class="help-block"><?=$this->getLang("uh");?></p>
		</div>

		<div class="form-group" mgmt="0">
			<label><?=$this->getLang("users");?></label>
			<input type="text" data-setting="users" value="<?=$this->getOption("users");?>" placeholder="10" class="form-control prov_settings" />
		</div>

		<div class="form-group" mgmt="0">
			<label><?=$this->getLang("sms");?></label>
			<input type="text" data-setting="sms" value="<?=$this->getOption("sms");?>" placeholder="10" class="form-control prov_settings" />
			<p class="help-block"><?=$this->getLang("uh");?></p>
		</div>

		<div class="form-group" mgmt="0">
			<label><?=$this->getLang("domain");?></label>
			<input type="text" data-setting="domain" value="<?=$this->getOption("domain");?>" placeholder="domain.de" class="form-control prov_settings" />
			<p class="help-block"><?=$this->getLang("domainh");?></p>
		</div>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function loadOptions($id, $pd = false)
    {
        parent::loadOptions($id, $pd);

        foreach (["hosts", "services", "users", "sms"] as $o) {
            if (!is_numeric($this->options[$o]) && array_key_exists($this->options[$o], $this->cf)) {
                $this->options[$o] = $this->cf[$o];
            }
        }

    }

    private function curl($url, array $post = array(), $method = "POST", array $header = array())
    {
        $header = array_merge($header, array("Content-Type: application/json"));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (count($post) > 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        }

        if ($method != "POST") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        return $res;
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        $r = $this->curl($this->getOption("url") . "/login", array("username" => $this->getOption("user"), "password" => $this->getOption("password")));
        $sid = $r->data->sid;

        if (strlen($sid) < 100) {
            return array(false, "Session ID not obtained");
        }

        $r = $this->curl($this->getOption("url") . "/token/csrf", array(), "POST", array("Cookie: sid=$sid"));
        if (empty($r->data)) {
            return array(false, "Token not obtained");
        }

        $csrf = $r->data;

        $host_reg_authkey = $sec->generatePassword(128, false, "lud");

        $url = $this->getOption("url") . "/administration/companies/create/";
        $data = array(
            "active" => "1",
            "address1" => "",
            "address2" => "",
            "alt_company_id" => $id,
            "city" => "",
            "comment" => "",
            "company" => $id,
            "country" => "",
            "data_retention" => "3650",
            "email" => $u->get()['mail'],
            "fax" => "",
            "host_reg_allow_from" => "all",
            "host_reg_authkey" => $host_reg_authkey,
            "host_reg_enabled" => "1",
            "max_chart_views_per_user" => "50",
            "max_charts_per_user" => "1000",
            "max_contactgroups" => "100",
            "max_contacts" => "100",
            "max_dashboards_per_user" => "50",
            "max_dashlets_per_dashboard" => "50",
            "max_dependencies_per_host" => "100",
            "max_downtimes_per_host" => "1000",
            "max_groups" => "100",
            "max_hosts" => $this->getOption("hosts"),
            "max_hosts_in_reg_queue" => "1000",
            "max_metrics_per_chart" => "50",
            "max_services" => $this->getOption("services"),
            "max_services_per_host" => "500",
            "max_sms" => $this->getOption("sms"),
            "max_templates" => "1000",
            "max_timeperiods" => "1000",
            "max_timeslices_per_object" => "200",
            "max_users" => $this->getOption("users"),
            "name" => $u->get()['lastname'],
            "phone" => "",
            "sla" => "0",
            "sms_enabled" => "1",
            "state" => "",
            "surname" => $u->get()['firstname'],
            "title" => "",
            "zipcode" => "",
            "token" => $csrf,
        );
        $r = $this->curl($url, $data, "POST", array("Cookie: sid=$sid"));
        if ($r->status != "ok") {
            return array(false, "Company creation failed");
        }

        $companyid = $r->data->id;

        $r = $this->curl($this->getOption("url") . "/token/csrf", array(), "POST", array("Cookie: sid=$sid"));
        if (empty($r->data)) {
            return array(false, "Token not obtained");
        }

        $csrf = $r->data;

        $password = $sec->generatePassword(16, false, "lud");
        $myUserName = $this->getUsername($id);

        $url = $this->getOption("url") . "/administration/users/create/";
        $data = array(
            "locked" => "0",
            "company_id" => $companyid,
            "authentication_key" => "",
            "comment" => "",
            "allow_from" => "all",
            "manage_contacts" => "1",
            "manage_templates" => "1",
            "name" => $u->get()['name'],
            "password" => $password,
            "password_changed" => "0",
            "phone" => "",
            "role" => "operator",
            "timezone" => "Europe/Berlin",
            "token" => $csrf,
            "username" => $myUserName . "@" . $this->getOption("domain"),
        );
        $r = $this->curl($url, $data, "POST", array("Cookie: sid=$sid"));
        if ($r->status != "ok") {
            return array(false, "User creation failed");
        }

        $userid = $r->data->id;

        return array(true, array(
            "username" => $myUserName . "@" . $this->getOption("domain"),
            "password" => $password,
            "userid" => $userid,
            "companyid" => $companyid,
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $r = $this->curl($this->getOption("url") . "/login", array("username" => $this->getOption("user"), "password" => $this->getOption("password")));
        $sid = $r->data->sid;

        if (strlen($sid) < 100) {
            return array(false, "Session ID not obtained");
        }

        $r = $this->curl($this->getOption("url") . "/token/csrf", array(), "POST", array("Cookie: sid=$sid"));
        if (empty($r->data)) {
            return array(false, "Token not obtained");
        }

        $csrf = $r->data;

        $url = $this->getOption("url") . "/administration/companies/" . $this->getData("companyid") . "/delete/";
        $data = array(
            "token" => $csrf,
        );
        $r = $this->curl($url, $data, "POST", array("Cookie: sid=$sid"));
        if ($r->status != "ok") {
            return array(false, "Company deletion failed");
        }

        return array(true);
    }

    public function Output($id, $task = "")
    {
        $this->loadOptions($id);

        ob_start();

        if ($task == "reset_pw") {
            $r = $this->curl($this->getOption("url") . "/login", array("username" => $this->getOption("user"), "password" => $this->getOption("password")));
            $sid = $r->data->sid;

            if (strlen($sid) < 100) {
                echo '<div class="alert alert-danger">' . $this->getLang("tecerr") . '</div>';
            } else {
                $r = $this->curl($this->getOption("url") . "/token/csrf", array(), "POST", array("Cookie: sid=$sid"));
                if (empty($r->data)) {
                    return array(false, "Token not obtained");
                }

                $csrf = $r->data;

                $url = $this->getOption("url") . "/administration/users/" . $this->getData("userid") . "/update";
                $data = array(
                    "password" => $this->getData("password"),
                    "password_changed" => "0",
                    "token" => $csrf,
                );
                $r = $this->curl($url, $data, "POST", array("Cookie: sid=$sid"));
                if ($r->status != "ok") {
                    echo '<div class="alert alert-danger">' . $this->getLang("tecerr") . '</div>';
                } else {
                    echo '<div class="alert alert-success">' . $this->getLang("PWC") . '</div>';
                }

            }
        }

        if ($task == "reset_mail") {
            $r = $this->curl($this->getOption("url") . "/login", array("username" => $this->getOption("user"), "password" => $this->getOption("password")));
            $sid = $r->data->sid;

            if (strlen($sid) < 100) {
                echo '<div class="alert alert-danger">' . $this->getLang("tecerr") . '</div>';
            } else {
                $r = $this->curl($this->getOption("url") . "/token/csrf", array(), "POST", array("Cookie: sid=$sid"));
                if (empty($r->data)) {
                    return array(false, "Token not obtained");
                }

                $csrf = $r->data;

                $url = $this->getOption("url") . "/administration/users/" . $this->getData("userid") . "/update";
                $data = array(
                    "username" => $this->getData("username"),
                    "token" => $csrf,
                );
                $r = $this->curl($url, $data, "POST", array("Cookie: sid=$sid"));
                if ($r->status != "ok") {
                    echo '<div class="alert alert-danger">' . $this->getLang("tecerr") . '</div>';
                } else {
                    echo '<div class="alert alert-success">' . $this->getLang("emc") . '</div>';
                }

            }
        }

        if ($task == "reset_rights") {
            $r = $this->curl($this->getOption("url") . "/login", array("username" => $this->getOption("user"), "password" => $this->getOption("password")));
            $sid = $r->data->sid;

            if (strlen($sid) < 100) {
                echo '<div class="alert alert-danger">' . $this->getLang("tecerr") . '</div>';
            } else {
                $r = $this->curl($this->getOption("url") . "/token/csrf", array(), "POST", array("Cookie: sid=$sid"));
                if (empty($r->data)) {
                    return array(false, "Token not obtained");
                }

                $csrf = $r->data;

                $url = $this->getOption("url") . "/administration/users/" . $this->getData("userid") . "/update";
                $data = array(
                    "role" => "operator",
                    "token" => $csrf,
                );
                $r = $this->curl($url, $data, "POST", array("Cookie: sid=$sid"));
                if ($r->status != "ok") {
                    echo '<div class="alert alert-danger">' . $this->getLang("tecerr") . '</div>';
                } else {
                    echo '<div class="alert alert-success">' . $this->getLang("rc") . '</div>';
                }

            }
        }
        ?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("cred");?></div>
		  <div class="panel-body">
		    <b><?=$this->getLang("url");?>:</b> <a href="<?=$this->getOption("url");?>" target="_blank"><?=$this->getOption("url");?></a><br />
		    <b><?=$this->getLang("username");?>:</b> <?=$this->getData("username");?><br />
		    <b><?=$this->getLang("password");?>:</b> <?=$this->getData("password");?>
		  </div>
		</div>

		<form method="POST" action="<?=$this->getOption("url");?>/login/" target="_blank">
			<input type="hidden" name="username" value="<?=$this->getData("username");?>" />
			<input type="hidden" name="password" value="<?=$this->getData("password");?>" />
			<input type="submit" class="btn btn-primary btn-block" value="<?=$this->getLang("login");?>" />
		</form>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $status = 1)
    {
        $this->loadOptions($id);

        $r = $this->curl($this->getOption("url") . "/login", array("username" => $this->getOption("user"), "password" => $this->getOption("password")));
        $sid = $r->data->sid;

        if (strlen($sid) < 100) {
            return array(false, "Session ID not obtained");
        }

        $r = $this->curl($this->getOption("url") . "/token/csrf", array(), "POST", array("Cookie: sid=$sid"));
        if (empty($r->data)) {
            return array(false, "Token not obtained");
        }

        $csrf = $r->data;

        $url = $this->getOption("url") . "/administration/users/" . $this->getData("userid") . "/update";
        $data = array(
            "locked" => $status,
            "token" => $csrf,
        );
        $r = $this->curl($url, $data, "POST", array("Cookie: sid=$sid"));
        if ($r->status != "ok") {
            return array(false, "User suspension failed");
        }

        return array(true);
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, 0);
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        $r = $this->curl($this->getOption("url") . "/login", array("username" => $this->getOption("user"), "password" => $this->getOption("password")));
        $sid = $r->data->sid;

        if (strlen($sid) < 100) {
            return array(false, "Session ID not obtained");
        }

        $r = $this->curl($this->getOption("url") . "/token/csrf", array(), "POST", array("Cookie: sid=$sid"));
        if (empty($r->data)) {
            return array(false, "Token not obtained");
        }

        $csrf = $r->data;

        $url = $this->getOption("url") . "/administration/companies/" . $this->getData("companyid") . "/update";
        $data = array(
            "max_hosts" => $this->getOption("hosts"),
            "max_services" => $this->getOption("services"),
            "max_sms" => $this->getOption("sms"),
            "max_users" => $this->getOption("users"),
            "token" => $csrf,
        );
        $r = $this->curl($url, $data, "POST", array("Cookie: sid=$sid"));
        if ($r->status != "ok") {
            return array(false, "Company edit failed");
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

    public function ContractInfo($id)
    {
        $this->loadOptions($id);

        return [
            $this->getLang("USERNAME") => $this->getData("username"),
        ];
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

    public function OwnFunctions($id)
    {
        return array(
            "reset_mail" => $this->getLang("RESMAIL"),
            "reset_pw" => $this->getLang("RESPW"),
            "reset_rights" => $this->getLang("RESR"),
        );
    }

    public function AdminFunctions($id)
    {
        return array(
            "reset_mail" => $this->getLang("RESMAIL"),
            "reset_pw" => $this->getLang("RESPW"),
            "reset_rights" => $this->getLang("RESR"),
        );
    }

    public function ApiTasks($id)
    {
        return array(
            "SetUserPassword" => "pwd",
            "ResetUserRights" => "",
            "ResetUserEmail" => "",
        );
    }

    public function SetUserPassword($id, $req)
    {
        $this->loadOptions($id);

        $r = $this->curl($this->getOption("url") . "/login", array("username" => $this->getOption("user"), "password" => $this->getOption("password")));
        $sid = $r->data->sid;

        if (strlen($sid) < 100) {
            die(json_encode(array("code" => "810", "message" => "Technical error occured.", "data" => array())));
        }

        $r = $this->curl($this->getOption("url") . "/token/csrf", array(), "POST", array("Cookie: sid=$sid"));
        if (empty($r->data)) {
            die(json_encode(array("code" => "811", "message" => "Technical error occured.", "data" => array())));
        }

        $csrf = $r->data;

        $url = $this->getOption("url") . "/administration/users/" . $this->getData("userid") . "/update";
        $data = array(
            "password" => $req['pwd'],
            "password_changed" => "1",
            "token" => $csrf,
        );
        $r = $this->curl($url, $data, "POST", array("Cookie: sid=$sid"));
        if ($r->status != "ok") {
            die(json_encode(array("code" => "812", "message" => "Password change failed.", "data" => array())));
        }

        die(json_encode(array("code" => "100", "message" => "Password changed successfully.", "data" => array())));
    }

    public function ResetUserRights($id, $req)
    {
        $this->loadOptions($id);

        $r = $this->curl($this->getOption("url") . "/login", array("username" => $this->getOption("user"), "password" => $this->getOption("password")));
        $sid = $r->data->sid;

        if (strlen($sid) < 100) {
            die(json_encode(array("code" => "810", "message" => "Technical error occured.", "data" => array())));
        }

        $r = $this->curl($this->getOption("url") . "/token/csrf", array(), "POST", array("Cookie: sid=$sid"));
        if (empty($r->data)) {
            die(json_encode(array("code" => "811", "message" => "Technical error occured.", "data" => array())));
        }

        $csrf = $r->data;

        $url = $this->getOption("url") . "/administration/users/" . $this->getData("userid") . "/update";
        $data = array(
            "role" => "operator",
            "token" => $csrf,
        );
        $r = $this->curl($url, $data, "POST", array("Cookie: sid=$sid"));
        if ($r->status != "ok") {
            die(json_encode(array("code" => "812", "message" => "Role reset failed.", "data" => array())));
        }

        die(json_encode(array("code" => "100", "message" => "Role reset successful.", "data" => array())));
    }

    public function ResetUserEmail($id, $req)
    {
        $this->loadOptions($id);

        $r = $this->curl($this->getOption("url") . "/login", array("username" => $this->getOption("user"), "password" => $this->getOption("password")));
        $sid = $r->data->sid;

        if (strlen($sid) < 100) {
            die(json_encode(array("code" => "810", "message" => "Technical error occured.", "data" => array())));
        }

        $r = $this->curl($this->getOption("url") . "/token/csrf", array(), "POST", array("Cookie: sid=$sid"));
        if (empty($r->data)) {
            die(json_encode(array("code" => "811", "message" => "Technical error occured.", "data" => array())));
        }

        $csrf = $r->data;

        $url = $this->getOption("url") . "/administration/users/" . $this->getData("userid") . "/update";
        $data = array(
            "username" => $this->getData("username"),
            "token" => $csrf,
        );
        $r = $this->curl($url, $data, "POST", array("Cookie: sid=$sid"));
        if ($r->status != "ok") {
            die(json_encode(array("code" => "812", "message" => "Email reset failed.", "data" => array())));
        }

        die(json_encode(array("code" => "100", "message" => "Email reset successful.", "data" => array())));
    }
}

?>