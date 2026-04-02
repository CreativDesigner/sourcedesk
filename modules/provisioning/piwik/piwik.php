<?php

class PiwikProv extends Provisioning
{
    protected $name = "Matomo";
    protected $short = "piwik";
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

		<div class="row">
			<div class="col-md-6" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("MURL");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url");?>" placeholder="https://stats.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("MTOKEN");?></label>
					<input type="password" data-setting="token" value="<?=$this->getOption("token");?>" placeholder="098f6bcd4621d373cade4e832627b4f6" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-12" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("WEBSITES");?></label>
					<input type="text" data-setting="websites" value="<?=$this->getOption("websites");?>" placeholder="10" class="form-control prov_settings" />
				</div>
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

        foreach (["websites"] as $o) {
            if (!is_numeric($this->options[$o]) && array_key_exists($this->options[$o], $this->cf)) {
                $this->options[$o] = $this->cf[$o];
            }
        }

    }

    private function curl($url)
    {
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

        $username = $this->getUsername($id);
        $password = $sec->generatePassword(12, false, "lud");

        $url = $this->getOption("url") . "/?module=API&method=UsersManager.addUser&userLogin=" . urlencode($username) . "&password=" . urlencode($password) . "&email=" . urlencode($u->get()['mail']) . "&token_auth=" . urlencode($this->getOption("token"));
        $res = $this->curl($url);
        $xml = simplexml_load_string($res);

        if (isset($xml->success)) {
            return array(true, array(
                "username" => $username,
                "password" => $password,
            ));
        }

        return array(false, strval($xml->error['message']) ?: "Error at creation");
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $sites = $this->getSites($id);
        foreach ($sites as $sid => $name) {
            $this->deleteSite($sid);
        }

        $url = $this->getOption("url") . "/?module=API&method=UsersManager.deleteUser&userLogin=" . urlencode($this->getData("username")) . "&token_auth=" . urlencode($this->getOption("token"));
        $res = $this->curl($url);
        $xml = simplexml_load_string($res);

        if (isset($xml->success)) {
            return array(true);
        }

        return array(false, strval($xml->error['message']) ?: "Error at deletion");
    }

    public function Output($id, $task = "")
    {
        $this->loadOptions($id);

        if ($task == "reset_pw") {
            $url = $this->getOption("url") . "/?module=API&method=UsersManager.updateUser&userLogin=" . urlencode($this->getData("username")) . "&password=" . urlencode($this->getData("password")) . "&token_auth=" . urlencode($this->getOption("token"));
            $res = $this->curl($url);
            $xml = simplexml_load_string($res);

            if (isset($xml->success)) {
                $suc = $this->getLang("RESOK");
            } else {
                $err = $this->getLang("TECERR");
            }

        }

        $aid = $id;
        $sites = $this->getSites($id);
        if (isset($_POST['delete_site']) && array_key_exists($_POST['delete_site'], $sites)) {
            if ($this->deleteSite($_POST['delete_site'])) {
                $suc = $this->getLang("DELOK");
                unset($sites[$_POST['delete_site']]);
            } else {
                $err = $this->getLang("TECERR");
            }
        }

        if (isset($_POST['name']) && isset($_POST['url'])) {
            $url = $this->getOption("url") . "/?module=API&method=SitesManager.addSite&siteName=" . urlencode($_POST['name']) . "&urls=" . urlencode($_POST['url']) . "&token_auth=" . urlencode($this->getOption("token"));
            $res = $this->curl($url);
            $id = strval(simplexml_load_string($res));

            if (!is_numeric($id) || $id <= 0) {
                $err = $this->getLang("TECERR");
            } else {
                $url = $this->getOption("url") . "/?module=API&method=UsersManager.setUserAccess&idSites=" . urlencode($id) . "&userLogin=" . urlencode($this->getData("username")) . "&access=admin&token_auth=" . urlencode($this->getOption("token"));
                $res = $this->curl($url);
                $xml = simplexml_load_string($res);

                if (isset($xml->success)) {
                    $suc = $this->getLang("ADDOK");
                } else {
                    $err = $this->getLang("TECERR");
                }

                $sites = $this->getSites($aid);
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
		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-default">
				  	<div class="panel-heading"><?=$this->getLang("CRED");?></div>
				  	<div class="panel-body">
						<b><?=$this->getLang("URL");?>:</b> <a href="<?=$this->getOption("url");?>" target="_blank"><?=$this->getOption("url");?></a><br />
						<b><?=$this->getLang("username");?>:</b> <?=$this->getData("username");?><br />
						<b><?=$this->getLang("password");?>:</b> <?=$this->getData("password");?>
					</div>
				</div>
			</div>

			<div class="col-md-6">
				<div class="panel panel-default">
				  	<div class="panel-heading"><?=$this->getLang("websites");?> (<?=count($sites);?> / <?=$this->getOption("websites");?>)<span class="pull-right"><a href="#" data-toggle="modal" data-target="#addPage"><i class="fa fa-plus"></i></a></span></div>
				  	<div class="panel-body">
						<?php
if (count($sites) == 0) {
            echo "<i>{$this->getLang("NOSITES")}</i>";
        }

        foreach ($sites as $i => $n) {
            echo $n . "<form method='POST' id='pf$i' class='pull-right form-inline'><input type='hidden' name='delete_site' value='$i'><a href='javascript:{}' onclick='document.getElementById(\"pf$i\").submit(); return false;'><i class='fa fa-times'></i></a></form>" . (array_keys(array_reverse($sites))[0] != $i ? "<br />" : "");
        }

        ?>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="addPage" tabindex="-1" role="dialog">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content">
		      <form method="POST"><div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$this->getLang("CANCEL");?>"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title"><?=$this->getLang("ADDWS");?></h4>
		      </div>
		      <div class="modal-body">
		        <div class="form-group">
		        	<label><?=$this->getLang("WSNAME");?></label>
		        	<input type="text" name="name" placeholder="<?=$this->getLang("wsnamep");?>" class="form-control" value="<?=isset($_POST['name']) ? $_POST['name'] : "";?>">
		        </div>

		        <div class="form-group">
		        	<label><?=$this->getLang("WSURL");?></label>
		        	<input type="text" name="url" placeholder="https://example.com" class="form-control" value="<?=isset($_POST['url']) ? $_POST['url'] : "";?>">
		        </div>
		      </div>
		      <div class="modal-footer">
		        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->getLang("CANCEL");?></button>
		        <button type="submit" class="btn btn-primary"><?=$this->getLang("ADDWS");?></button>
		      </div></form>
		    </div>
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
            "url" => $this->getOption("url"),
            "username" => $this->getData("username"),
            "password" => $this->getData("password"),
            "config_url" => $raw_cfg['PAGEURL'] . "/hosting/" . $id,
            "pages_used" => count($this->getSites($id)),
            "pages_max" => $this->getOption("websites"),
        );
    }

    public function ContractInfo($id)
    {
        $this->loadOptions($id);

        return [
            $this->getLang("USERNAME") => $this->getData("username"),
        ];
    }

    private function getSites($id)
    {
        $this->loadOptions($id);

        $url = $this->getOption("url") . "/?module=API&method=UsersManager.getSitesAccessFromUser&userLogin=" . urlencode($this->getData("username")) . "&token_auth=" . urlencode($this->getOption("token"));
        $res = $this->curl($url);
        $xml = simplexml_load_string($res);

        $sites = array();
        foreach ($xml->row as $s) {
            $url = $this->getOption("url") . "/?module=API&method=SitesManager.getSiteFromId&idSite=" . urlencode(intval($s->site)) . "&token_auth=" . urlencode($this->getOption("token"));
            $res = $this->curl($url);
            $xml2 = simplexml_load_string($res);
            $sites[intval($s->site)] = strval($xml2->row->name);
        }

        return $sites;
    }

    private function deleteSite($sid)
    {
        $url = $this->getOption("url") . "/?module=API&method=SitesManager.deleteSite&idSite=" . intval($sid) . "&token_auth=" . urlencode($this->getOption("token"));
        $res = $this->curl($url);
        $xml = simplexml_load_string($res);

        if (isset($xml->success)) {
            return true;
        }

        return false;
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
            "GetUserSites" => "",
            "AddUserSite" => "name,url",
            "DeleteUserSite" => "id",
        );
    }

    public function SetUserPassword($id, $req)
    {
        $this->loadOptions($id);

        $url = $this->getOption("url") . "/?module=API&method=UsersManager.updateUser&userLogin=" . urlencode($this->getData("username")) . "&password=" . urlencode($req['pwd']) . "&token_auth=" . urlencode($this->getOption("token"));
        $res = $this->curl($url);
        $xml = simplexml_load_string($res);

        if (isset($xml->success)) {
            $this->setData("password", $req['pwd']);
            die(json_encode(array("code" => "100", "message" => "Password changed successfully.", "data" => array())));
        }

        die(json_encode(array("code" => "810", "message" => "Technical error occured.", "data" => array())));
    }

    public function GetUserSites($id, $req)
    {
        die(json_encode(array("code" => "100", "message" => "Sites fetched.", "data" => array("sites" => $this->getSites($id)))));
    }

    public function AddUserSite($id, $req)
    {
        $url = $this->getOption("url") . "/?module=API&method=SitesManager.addSite&siteName=" . urlencode($req['name']) . "&urls=" . urlencode($req['url']) . "&token_auth=" . urlencode($this->getOption("token"));
        $res = $this->curl($url);
        $id = strval(simplexml_load_string($res));

        if (!is_numeric($id) || $id <= 0) {
            die(json_encode(array("code" => "810", "message" => "Technical error occured.", "data" => array())));
        } else {
            $url = $this->getOption("url") . "/?module=API&method=UsersManager.setUserAccess&idSites=" . urlencode($id) . "&userLogin=" . urlencode($this->getData("username")) . "&access=admin&token_auth=" . urlencode($this->getOption("token"));
            $res = $this->curl($url);
            $xml = simplexml_load_string($res);

            if (isset($xml->success)) {
                die(json_encode(array("code" => "100", "message" => "Site added.", "data" => array())));
            }

            die(json_encode(array("code" => "811", "message" => "Technical error occured.", "data" => array())));
        }
    }

    public function DeleteUserSite($id, $req)
    {
        $this->loadOptions($id);
        if (!in_array($req['id'], $this->getSites($id))) {
            die(json_encode(array("code" => "810", "message" => "Site not found.", "data" => array())));
        }

        if ($this->deleteSite($req['id'])) {
            die(json_encode(array("code" => "100", "message" => "Site deleted.", "data" => array())));
        }

        die(json_encode(array("code" => "811", "message" => "Technical error occured.", "data" => array())));
    }
}

?>