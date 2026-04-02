<?php

class MattermostProv extends Provisioning
{
    protected $name = "Mattermost";
    protected $short = "mattermost";
    protected $lang;
    protected $options = array();
    protected $ssh;
    protected $serverMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        ob_start();?>

		<input style="opacity: 0; position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row">
            <div class="col-md-4" mgmt="1">
				<div class="form-group">
                    <label><?=$this->getLang("MURL");?></label>
                    <input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://chat.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4" mgmt="1">
				<div class="form-group">
                    <label><?=$this->getLang("USERNAME");?></label>
                    <input type="text" data-setting="user" value="<?=$this->getOption("user");?>" placeholder="api" class="form-control prov_settings" />
				</div>
            </div>

            <div class="col-md-4" mgmt="1">
				<div class="form-group">
                    <label><?=$this->getLang("PASSWORD");?></label>
                    <input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="<?=$this->getLang("SECRET");?>" class="form-control prov_settings" />
				</div>
            </div>

            <div class="col-md-6" mgmt="0">
				<div class="form-group">
                    <label><?=$this->getLang("MUID");?></label>
                    <input type="text" data-setting="uid" value="<?=$this->getOption("uid");?>" placeholder="n8wgdz387vd3ytdt59" class="form-control prov_settings" />
				</div>
            </div>

            <div class="col-md-6" mgmt="0">
				<div class="form-group">
                    <label><?=$this->getLang("DEFDOM");?></label>
                    <input type="text" data-setting="domain" value="<?=$this->getOption("domain");?>" placeholder="sourcechat.de" class="form-control prov_settings" />
				</div>
            </div>
		</div>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    private function bearer()
    {
        $ch = curl_init(rtrim($this->getOption("url"), "/") . "/api/v4/users/login");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["login_id" => $this->getOption("user"), "password" => $this->getOption("password")]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $res = curl_exec($ch);
        curl_close($ch);

        $pos = strpos($res, "\nToken: ");
        if ($pos === false) {
            return "";
        }

        return substr($res, $pos + 8, 26);
    }

    public function Create($id)
    {
        $this->loadOptions($id);
        $user = $this->getClient($id);

        if (!is_object($user)) {
            return [false, "Customer not found"];
        }

        $name = Security::generatePassword(8, false, "ld");
        $password = Security::generatePassword(12, false, "lud");

        $data = [
            "email" => $name . "@" . $this->getOption("domain"),
            "email_verified" => true,
            "username" => $name,
            "first_name" => $user->get()['firstname'],
            "last_name" => $user->get()['lastname'],
            "password" => $password,
        ];

        $ch = curl_init(rtrim($this->getOption("url"), "/") . "/api/v4/users");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $this->bearer()]);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        if (empty($res->id)) {
            return [false, "Failed to create user"];
        }

        $uid = $res->id;

        $data = [
            "name" => $name,
            "display_name" => $name,
            "type" => "I",
        ];

        $ch = curl_init(rtrim($this->getOption("url"), "/") . "/api/v4/teams");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $this->bearer()]);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        if (empty($res->id)) {
            return [false, "Failed to create team"];
        }

        $tid = $res->id;

        $ch = curl_init(rtrim($this->getOption("url"), "/") . "/api/v4/teams/$tid/members/" . $this->getOption("uid"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $this->bearer()]);
        curl_exec($ch);
        curl_close($ch);

        $data = [
            "team_id" => $tid,
            "user_id" => $uid,
        ];

        $ch = curl_init(rtrim($this->getOption("url"), "/") . "/api/v4/teams/$tid/members");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $this->bearer()]);
        curl_exec($ch);
        curl_close($ch);

        $data = [
            "roles" => "team_user team_admin",
        ];

        $ch = curl_init(rtrim($this->getOption("url"), "/") . "/api/v4/teams/$tid/members/$uid/roles");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $this->bearer()]);
        json_decode(curl_exec($ch));
        curl_close($ch);

        return array(true, array(
            "name" => $name,
            "password" => $password,
            "team_id" => $tid,
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $tid = $this->getData("team_id");

        $ch = curl_init(rtrim($this->getOption("url"), "/") . "/api/v4/teams/$tid");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["permanent" => true]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $this->bearer()]);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        return array($res->status == "OK");
    }

    public function Output($id, $task = "")
    {
        global $dfo;
        $this->loadOptions($id);

        ob_start();
        ?>
		<div class="panel panel-default">
			<div class="panel-heading"><?=$this->getLang("CRED");?></div>
			<div class="panel-body">
                <b><?=$this->getLang("URL");?>:</b> <a href="<?=$this->getOption("url");?>" target="_blank"><?=$this->getOption("url");?></a><br />
                <b><?=$this->getLang("USERNAME");?>:</b> <?=$this->getData("name");?><br />
                <b><?=$this->getLang("PASSWORD");?>:</b> <?=$this->getData("password");?>
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
            "url",
            "username",
            "password",
        );
    }

    public function EmailVariables($id)
    {
        global $raw_cfg;

        $this->loadOptions($id);

        return array(
            "config_url" => $raw_cfg["PAGEURL"] . "hosting/" . $id,
            "url" => $this->getOption("url"),
            "username" => $this->getData("name"),
            "password" => $this->getData("password"),
        );
    }

    public function ContractInfo($id)
    {
        $this->loadOptions($id);

        return [
            $this->getLang("USERNAME") => $this->getData("name"),
        ];
    }
}