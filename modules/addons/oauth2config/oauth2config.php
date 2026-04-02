<?php
// Addon for having an OAuth2 configuration

class OAuthConfiguration extends Addon
{
    public static $shortName = "oauth2config";

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

    public function adminPages()
    {
        return array("oauth2config" => "admin");
    }

    public function adminMenu()
    {
        return array("OAuth2" => "oauth2config");
    }

    public function getSettings()
    {
        return array();
    }

    public function admin()
    {
        global $tpl, $var, $CFG, $db, $sec;
        $tpl = __DIR__ . "/templates/admin.tpl";
        $var['secret'] = $sec->generatePassword(32, false, "ld");
        $var['clients'] = array();

        if (isset($_GET['d'])) {
            $db->query("DELETE FROM oauth_clients WHERE client_id = '" . $db->real_escape_string($_GET['d']) . "'");
            alog("general", "oauth2_delete", $_GET['d']);
        }

        if (!empty($_POST['client_id']) && !empty($_POST['client_secret']) && strlen($_POST['client_secret']) == 32 && !empty($_POST['redirect_uri'])) {
            $db->query("INSERT INTO oauth_clients (client_id, client_secret, redirect_uri) VALUES ('" . $db->real_escape_string($_POST['client_id']) . "', '" . $db->real_escape_string($_POST['client_secret']) . "', '" . $db->real_escape_string($_POST['redirect_uri']) . "')");
            alog("general", "oauth2_add", $_POST['client_id']);
        }

        $sql = $db->query("SELECT * FROM oauth_clients");
        while ($row = $sql->fetch_array()) {
            array_push($var['clients'], $row);
        }

        $var['l'] = $this->getLang();
    }
}
