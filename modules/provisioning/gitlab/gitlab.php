<?php

class GitlabProv extends Provisioning
{
    protected $name = "GitLab";
    protected $short = "gitlab";
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
					<label><?=$this->getLang("URL");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://git.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("Token");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="Ndk92KloP90nKe8Q-8m1" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-12" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("PROJ");?></label>
					<input type="text" data-setting="projects" value="<?=$this->getOption("projects");?>" placeholder="10" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<div class="checkbox" mgmt="0">
			<label>
				<input type="checkbox" class="prov_check" data-setting="groups" value="yes"<?=$this->getOption("groups") == "yes" ? ' checked="checked"' : '';?>>
				<?=$this->getLang("GROUPS");?>
			</label>
		</div>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function loadOptions($id, $pd = false)
    {
        parent::loadOptions($id, $pd);

        foreach (["projects"] as $o) {
            if (!is_numeric($this->options[$o]) && array_key_exists($this->options[$o], $this->cf)) {
                $this->options[$o] = $this->cf[$o];
            }
        }

    }

    public function Create($id)
    {
        return array(true, array(
            "username" => "",
        ));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $ch = curl_init($this->getOption("url") . "/api/v3/users");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("PRIVATE-TOKEN: " . $this->getOption("password")));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        $userinfo = false;
        foreach ($res as $u) {
            if ($u->username == $this->getData("username")) {
                $userinfo = $u;
            }
        }

        if ($userinfo === false) {
            return array(false, $this->getLang("UNF"));
        }

        $ch = curl_init($this->getOption("url") . "/api/v3/users/{$userinfo->id}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("PRIVATE-TOKEN: " . $this->getOption("password")));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, array());
        curl_exec($ch);
        curl_close($ch);

        return array(true);
    }

    public function Output($id, $task = "")
    {
        $this->loadOptions($id);

        if (empty($this->getData("username")) && !empty($_POST['user'])) {
            $ch = curl_init($this->getOption("url") . "/api/v3/users");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("PRIVATE-TOKEN: " . $this->getOption("password")));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $res = json_decode(curl_exec($ch));
            curl_close($ch);

            $userinfo = false;
            foreach ($res as $u) {
                if ($u->username == $_POST['user']) {
                    $userinfo = $u;
                }
            }

            if ($userinfo === false) {
                $err = $this->getLang("UNF");
            } else if ($userinfo->state != "active") {
                $err = $this->getLang("UNA");
            } else if ($userinfo->projects_limit > 0) {
                $err = $this->getLang("UAA");
            } else {
                $ch = curl_init($this->getOption("url") . "/api/v3/users/{$userinfo->id}");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("PRIVATE-TOKEN: " . $this->getOption("password")));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, array("projects_limit" => $this->getOption("projects"), "external" => "false", "can_create_group" => $this->getOption("groups") == "yes" ? "true" : "false"));
                $res = json_decode(curl_exec($ch));
                curl_close($ch);

                if ($res->projects_limit == $this->getOption("projects")) {
                    $suc = $this->getLang("SUC");
                    $this->setData("username", $userinfo->username);
                } else {
                    $err = $this->getLang("TECERR");
                }
            }
        }

        if (!empty($this->getData("username")) && isset($_POST['remove'])) {
            $ch = curl_init($this->getOption("url") . "/api/v3/users");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("PRIVATE-TOKEN: " . $this->getOption("password")));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $res = json_decode(curl_exec($ch));
            curl_close($ch);

            $userinfo = false;
            foreach ($res as $u) {
                if ($u->username == $this->getData("username")) {
                    $userinfo = $u;
                }
            }

            if ($userinfo === false) {
                $err = $this->getLang("UNF");
            } else {
                $ch = curl_init($this->getOption("url") . "/api/v3/users/{$userinfo->id}");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("PRIVATE-TOKEN: " . $this->getOption("password")));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, array("projects_limit" => 0, "external" => "true", "can_create_group" => "false"));
                $res = json_decode(curl_exec($ch));
                curl_close($ch);

                if ($res->projects_limit == 0) {
                    $suc = $this->getLang("SUC2");
                    $this->setData("username", "");
                } else {
                    $err = $this->getLang("TECERR");
                }
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
		  	<div class="panel-heading"><?=$this->getLang("ACTIVATION");?></div>
		  	<div class="panel-body">
				<p style="text-align: justify;"><?=str_replace("%u", $this->getOption("url"), $this->getLang("ACTIN"));?></p>

				<?php if (empty($this->getData("username"))) {?>
				<form method="POST">
					<div class="form-group">
						<label><?=$this->getLang("GLUSER");?></label>
						<input type="text" name="user" placeholder="<?=$this->getLang("GLUSERP");?>" value="<?=isset($_POST['user']) ? $_POST['user'] : "";?>" class="form-control">
					</div>

					<input type="submit" class="btn btn-primary btn-block" value="<?=$this->getLang("DONOW");?>">
				</form>
				<?php } else {?>
				<p style="text-align: justify;"><?=str_replace("%u", $this->getData("username"), $this->getLang("DEAIN"));?></p>
				<form method="POST">
					<input type="hidden" name="remove" value="yes" />
					<input type="submit" class="btn btn-warning btn-block" value="<?=$this->getLang("DEANOW");?>">
				</form>
				<?php }?>
			</div>
		</div>

		<?php if (!empty($this->getData("username"))) {?><a href="<?=$this->getOption("url");?>" target="_blank" class="btn btn-primary btn-block"><?=$this->getLang("TOGL");?></a><?php }?>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Suspend($id, $status = 0)
    {
        $this->loadOptions($id);

        $ch = curl_init($this->getOption("url") . "/api/v3/users");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("PRIVATE-TOKEN: " . $this->getOption("password")));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        $userinfo = false;
        foreach ($res as $u) {
            if ($u->username == $this->getData("username")) {
                $userinfo = $u;
            }
        }

        if ($userinfo === false) {
            return array(false, $this->getLang("UNF"));
        }

        $ch = curl_init($this->getOption("url") . "/api/v3/users/{$userinfo->id}/" . ($status == 1 ? "un" : "") . "block");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("PRIVATE-TOKEN: " . $this->getOption("password")));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, array());
        curl_exec($ch);
        curl_close($ch);

        return array(true);
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, 1);
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        $ch = curl_init($this->getOption("url") . "/api/v3/users");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("PRIVATE-TOKEN: " . $this->getOption("password")));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        $userinfo = false;
        foreach ($res as $u) {
            if ($u->username == $this->getData("username")) {
                $userinfo = $u;
            }
        }

        if ($userinfo === false) {
            return array(false, $this->getLang("UNF"));
        }

        $ch = curl_init($this->getOption("url") . "/api/v3/users/{$userinfo->id}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("PRIVATE-TOKEN: " . $this->getOption("password")));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, array("projects_limit" => $this->getOption("projects"), "external" => "false", "can_create_group" => $this->getOption("groups") == "yes" ? "true" : "false"));
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        if ($res->projects_limit == $this->getOption("projects")) {
            return array(true);
        } else {
            return array(false, $this->getLang("TECERR"));
        }

    }

    public function AllEmailVariables()
    {
        return array(
            "gitlab_url",
            "config_url",
        );
    }

    public function EmailVariables($id)
    {
        global $raw_cfg;
        $this->loadOptions($id);

        return array(
            "gitlab_url" => $this->getOption("url"),
            "config_url" => $raw_cfg['PAGEURL'] . "/hosting/" . $id,
        );
    }

    public function ApiTasks($id)
    {
        return array(
            "AssignUsername" => "usr",
        );
    }

    public function AssignUsername($id, $req)
    {
        $this->loadOptions($id);

        if (!empty($this->getData("username"))) {
            $ch = curl_init($this->getOption("url") . "/api/v3/users");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("PRIVATE-TOKEN: " . $this->getOption("password")));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $res = json_decode(curl_exec($ch));
            curl_close($ch);

            $userinfo = false;
            foreach ($res as $u) {
                if ($u->username == $this->getData("username")) {
                    $userinfo = $u;
                }
            }

            if ($userinfo === false) {
                die(json_encode(array("code" => "810", "message" => "Technical error occured.", "data" => array())));
            } else {
                $ch = curl_init($this->getOption("url") . "/api/v3/users/{$userinfo->id}");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("PRIVATE-TOKEN: " . $this->getOption("password")));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, array("projects_limit" => 0, "external" => "true", "can_create_group" => "false"));
                $res = json_decode(curl_exec($ch));
                curl_close($ch);

                if ($res->projects_limit == 0) {
                    $this->setData("username", "");
                } else {
                    die(json_encode(array("code" => "811", "message" => "Technical error occured.", "data" => array())));
                }
            }
        }

        $ch = curl_init($this->getOption("url") . "/api/v3/users");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("PRIVATE-TOKEN: " . $this->getOption("password")));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        $userinfo = false;
        foreach ($res as $u) {
            if ($u->username == $req['usr']) {
                $userinfo = $u;
            }
        }

        if ($userinfo === false) {
            die(json_encode(array("code" => "812", "message" => "User not found.", "data" => array())));
        } else if ($userinfo->state != "active") {
            die(json_encode(array("code" => "813", "message" => "User is inactive", "data" => array())));
        } else if ($userinfo->projects_limit > 0) {
            die(json_encode(array("code" => "814", "message" => "User already assigned to abonnement.", "data" => array())));
        } else {
            $ch = curl_init($this->getOption("url") . "/api/v3/users/{$userinfo->id}");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("PRIVATE-TOKEN: " . $this->getOption("password")));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, array("projects_limit" => $this->getOption("projects"), "external" => "false", "can_create_group" => $this->getOption("groups") == "yes" ? "true" : "false"));
            $res = json_decode(curl_exec($ch));
            curl_close($ch);

            if ($res->projects_limit == $this->getOption("projects")) {
                $this->setData("username", $userinfo->username);
                die(json_encode(array("code" => "100", "message" => "User assigned to abonnement.", "data" => array())));
            } else {
                die(json_encode(array("code" => "815", "message" => "Technical error occured.", "data" => array())));
            }
        }
    }
}

?>