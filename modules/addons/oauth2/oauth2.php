<?php
// Addon for having an OAuth2 server

class OAuthTwoServer extends Addon
{
    public static $shortName = "oauth2";

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

    public function activate()
    {
        global $db;
        parent::activate();
        $db->query("CREATE TABLE oauth_clients (client_id VARCHAR(80) NOT NULL, client_secret VARCHAR(80), redirect_uri VARCHAR(2000) NOT NULL, grant_types VARCHAR(80), scope VARCHAR(100), user_id VARCHAR(80), CONSTRAINT clients_client_id_pk PRIMARY KEY (client_id));");
        $db->query("CREATE TABLE oauth_access_tokens (access_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT access_token_pk PRIMARY KEY (access_token));");
        $db->query("CREATE TABLE oauth_authorization_codes (authorization_code VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), redirect_uri VARCHAR(2000), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT auth_code_pk PRIMARY KEY (authorization_code));");
        $db->query("CREATE TABLE oauth_refresh_tokens (refresh_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT refresh_token_pk PRIMARY KEY (refresh_token));");
        $db->query("CREATE TABLE oauth_users (username VARCHAR(255) NOT NULL, password VARCHAR(2000), first_name VARCHAR(255), last_name VARCHAR(255), CONSTRAINT username_pk PRIMARY KEY (username));");
        $db->query("CREATE TABLE oauth_scopes (scope TEXT, is_default BOOLEAN);");
        $db->query("CREATE TABLE oauth_jwt (client_id VARCHAR(80) NOT NULL, subject VARCHAR(80), public_key VARCHAR(2000), CONSTRAINT jwt_client_id_pk PRIMARY KEY (client_id));");
    }

    public function deactivate()
    {
        global $db;
        parent::deactivate();
        $db->query("DROP TABLE oauth_clients;");
        $db->query("DROP TABLE oauth_access_tokens;");
        $db->query("DROP TABLE oauth_authorization_codes;");
        $db->query("DROP TABLE oauth_refresh_tokens;");
        $db->query("DROP TABLE oauth_users;");
        $db->query("DROP TABLE oauth_scopes;");
        $db->query("DROP TABLE oauth_jwt;");
    }

    public function delete()
    {
        return $this->deleteDir(realpath(__DIR__));
    }

    public function hooks()
    {
        return array(
            array("MaintenanceAllowedPages", "maintenance", 0),
            array("SkipCSRF", "csrf", 0),
        );
    }

    public function csrf($p)
    {
        return $p["page"] == "oauth2";
    }

    public function maintenance()
    {
        return ["oauth2", "oauth2/user"];
    }

    public function adminPages()
    {
        return array("oauth2" => "admin");
    }

    public function clientPages()
    {
        return array("oauth2" => "client");
    }

    public function getSettings()
    {
        return array();
    }

    public function client()
    {
        global $pars, $db, $CFG;
        require_once __DIR__ . "/config.php";

        if (empty($_SERVER['HTTP_AUTHORIZATION']) && !empty($_POST['access_token'])) {
            $_SERVER['HTTP_AUTHORIZATION'] = $_POST['access_token'];
        }

        if (isset($pars[0]) && $pars[0] == "user" && !empty($_SERVER['HTTP_AUTHORIZATION']) && explode(" ", $_SERVER['HTTP_AUTHORIZATION'])[0] == "Bearer") {
            $code = explode(" ", $_SERVER['HTTP_AUTHORIZATION'])[1];
            $sql = $db->query("SELECT user_id FROM oauth_access_tokens WHERE access_token = '" . $db->real_escape_string($code) . "' AND expires > '" . date("Y-m-d H:i:s") . "'");
            if ($sql->num_rows != 1) {
                die(json_encode(["error" => "Bearer invalid"]));
            }

            $sql = $db->query("SELECT name, username, email FROM admins WHERE ID = " . intval($sql->fetch_object()->user_id));
            if ($sql->num_rows != 1) {
                die(json_encode(["error" => "User invalid"]));
            }

            die(json_encode($sql->fetch_assoc()));
        }

        $data = OAuth2\Request::createFromGlobals();
        $server->handleTokenRequest($data)->send();
        exit;
    }

    public function admin()
    {
        global $adminInfo;
        require_once __DIR__ . "/config.php";
        $request = OAuth2\Request::createFromGlobals();
        $response = new OAuth2\Response();

        if (!$server->validateAuthorizeRequest($request, $response)) {
            alog("general", "oauth2_auth_fail");
            $response->send();
            exit;
        }

        alog("general", "oauth2_auth_success");

        $server->handleAuthorizeRequest($request, $response, true, $adminInfo->ID);
        $response->send();
    }
}
