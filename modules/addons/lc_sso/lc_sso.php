<?php
// Addon for SSO login to LiveConfig

class LiveConfigSSO extends Addon
{
    public static $shortName = "lc_sso";

    public function __construct($language)
    {
        $this->language = $language;
        $this->name = self::$shortName;
        parent::__construct();

        if (!include (__DIR__ . "/language/$language.php")) {
            throw new ModuleException();
        }

        if (!is_array($addonlang) || !isset($addonlang["NAME"])) {
            throw new ModuleException();
        }

        $this->lang = $addonlang;

        $this->info = array(
            'name' => $this->getLang("NAME"),
            'version' => "1.0",
            'company' => "sourceWAY.de",
            'url' => "https://sourceway.de/",
        );
    }

    public function delete()
    {
        return $this->deleteDir(realpath(__DIR__));
    }

    public function hooks()
    {
        return array(
            array("AdminSSO", "sso", 0),
        );
    }

    public function adminPages()
    {
        return array(
            "lc_sso" => "admin",
        );
    }

    public function admin()
    {
        global $CFG;

        $user = $this->options["username"];
        $pass = $this->options["password"];
        $func = "SessionCreate";
        $ts = gmdate("Y-m-d") . "T" . gmdate("H:i:s") . ".000Z";
        $token = base64_encode(hash_hmac('sha1', 'LiveConfig' . $user . $func . $ts, $pass, true));
        $auth = array('login' => $user, 'timestamp' => $ts, 'token' => $token);
        $url = rtrim($this->options["url"], "/") . '/liveconfig/soap?wsdl' . '&l=' . urlencode($this->options["username"]) . '&p=' . urlencode($this->options["password"]);
        $client = new SoapClient($url, array('style' => SOAP_DOCUMENT, 'use' => SOAP_LITERAL));

        try {
            $res = $client->SessionCreate([
                "auth" => $auth,
                "login" => $this->options["username"],
                "exiturl" => rtrim($CFG['PAGEURL'], "/") . "/admin",
            ]);

            alog("general", "liveconfig_sso");

            header('Location: ' . rtrim($this->options["url"], "/") . '/liveconfig/login/sso?token=' . $res->token);
            exit;
        } catch (SoapException $ex) {
            die($ex->getMessage());
        } catch (SoapFault $ex) {
            die($ex->getMessage());
        }
    }

    public function sso()
    {
        if (!$this->haveAccess()) {
            return [];
        }

        return [
            "?p=lc_sso" => '<i class="fa fa-fw fa-server"></i> ' . $this->getLang("LC"),
        ];
    }

    public function getSettings()
    {
        return array(
            "url" => array("placeholder" => "https://panel.sourceway.de/", "label" => $this->getLang("URL"), "type" => "text"),
            "username" => array("placeholder" => "admin", "label" => $this->getLang("USERNAME"), "type" => "text"),
            "password" => array("placeholder" => $this->getLang("PASSWORDH"), "label" => $this->getLang("PASSWORD"), "type" => "password"),
        );
    }
}
