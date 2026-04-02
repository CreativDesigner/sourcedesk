<?php
// Addon for SupportPal integration

class KayakoIntegration extends Addon
{
    public static $shortName = "kayako";

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

    public function getSettings()
    {
        return array(
            "url" => array("placeholder" => "https://sourceway.kayako.com", "label" => $this->getLang("URL"), "type" => "text", "help" => $this->getLang("URLH")),
            "sso_secret" => array("placeholder" => "39BN25GYuvf8mkXi4xD7Hqoe", "label" => $this->getLang("SSOKEY"), "type" => "password"),
            "identifier" => array("placeholder" => "SD", "label" => $this->getLang("IDENT"), "type" => "text"),
            "replace" => array("default" => "1", "label" => $this->getLang("REPLACE"), "type" => "checkbox"),
        );
    }

    public function delete()
    {
        return $this->deleteDir(realpath(__DIR__));
    }

    public function hooks()
    {
        if ($this->getOption("replace") == "1") {
            return array(
                array("AdminSidebarSupportLink", "adminSSOLink", 0),
                array("AdminClientProfileTicketLink", "adminTicketLink", 0),
            );
        }
    }

    public function clientPages()
    {
        return array(
            "supportarea" => "clientSSOLink",
        );
    }

    public function adminPages()
    {
        return array(
            "kayakosso" => "doAdminSSO",
            "kayakoprofile" => "openProfile",
        );
    }

    public function adminSSOLink()
    {
        ob_start();
        ?>
		<a href="?p=kayakosso" target="_blank"><i class="fa fa-envelope fa-fw"></i> <?=$this->getLang("SUPPORT");?></a>
		<?php
$html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    public function adminTicketLink($params)
    {
        $u = $params['user'];
        return '<a class="list-group-item" href="?p=kayakoprofile&uid=' . $u->get()['ID'] . '" target="_blank">' . $this->getLang("TICKETS") . '</a>';
    }

    public function clientSSOLink()
    {
        global $user, $tpl;
        User::status();

        if (!empty($_GET['returnto'])) {
            $payload = [
                "iat" => $time = time(),
                "jti" => md5($this->options['sso_secret'] . ":" . $time),
                "email" => $user->get()['mail'],
                "name" => $user->get()['name'],
                "role" => "customer",
                "external_id" => $this->options['identifier'] . $user->get()['ID'],
            ];

            $token = \Firebase\JWT\JWT::encode($payload, $this->options['sso_secret'], 'HS256');

            header('Location: ' . $_GET['returnto'] . '&jwt=' . $token);
            exit;
        }

        header('Location: ' . $this->options['url'] . '/login');
        exit;
    }

    public function openProfile()
    {
        global $tpl;

        $user = User::getInstance(intval($_GET['uid']), "ID");

        if (!$user) {
            $tpl = "error";
        } else {
            $payload = [
                "iat" => $time = time(),
                "jti" => md5($this->options['sso_secret'] . ":" . $time),
                "email" => $user->get()['mail'],
                "name" => $user->get()['name'],
                "role" => "customer",
                "external_id" => $this->options['identifier'] . $user->get()['ID'],
            ];

            $token = \Firebase\JWT\JWT::encode($payload, $this->options['sso_secret'], 'HS256');

            $ch = curl_init($this->options['url'] . "/api/v1/session.json");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/json",
                "Content-Type: application/json",
                "Authorization: Bearer $token",
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $res = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if ($res["status"] != 200 || empty($res["data"]["user"]["id"])) {
                $tpl = "error";
            } else {
                $this->doAdminSSO("/agent/users/" . $res["data"]["user"]["id"]);
            }
        }

    }

    public function doAdminSSO($url = "/agent")
    {
        global $adminInfo;

        if (empty($_GET['returnto'])) {
            header('Location: ' . $this->options['url'] . $url);
            exit;
        }

        $payload = [
            "iat" => $time = time(),
            "jti" => md5($this->options['sso_secret'] . ":" . $time),
            "email" => $adminInfo->email,
            "name" => $adminInfo->name,
            "role" => "agent",
        ];

        $token = \Firebase\JWT\JWT::encode($payload, $this->options['sso_secret'], 'HS256');

        alog("general", "kayako_sso", $url);

        header('Location: ' . $_GET['returnto'] . '&jwt=' . $token);
        exit;
    }
}
