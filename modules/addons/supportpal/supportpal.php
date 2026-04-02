<?php
// Addon for SupportPal integration

class SupportPalIntegration extends Addon
{
    public static $shortName = "supportpal";

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
            "url" => array("placeholder" => "https://support.sourceway.de", "label" => $this->getLang("URL"), "type" => "text", "help" => $this->getLang("URLH")),
            "simpleauth" => array("placeholder" => "Jbu61LGHk3e4su4Ie+xJW##q78qqI6iN", "label" => $this->getLang("SA"), "type" => "password"),
            "api_token" => array("placeholder" => "Jbu61LGHk3e4su4Ie+xJW##q78qqI6iN", "label" => $this->getLang("AK"), "type" => "password"),
            "customfield_id" => array("placeholder" => "1", "label" => $this->getLang("CFID"), "type" => "text"),
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
                array("ClientDataChanged", "syncClientData", 10),
                array("AdminClientProfileTicketLink", "adminTicketLink", 0),
            );
        } else {
            return array(
                array("ClientDataChanged", "syncClientData", 10),
            );
        }
    }

    public function clientPages()
    {
        return array(
            "supportarea" => "clientSSOLink",
            "supportlogin" => "clientSSOForce",
        );
    }

    public function adminPages()
    {
        return array(
            "supportpalsso" => "doAdminSSO",
            "supportpalsync" => "syncAllClients",
            "supportpaldate" => "syncRegDates",
            "supportpalprofile" => "openProfile",
            "supportpalcount" => "countOpenTickets",
        );
    }

    public function countOpenTickets()
    {
        die((String) json_decode($this->apiCall("ticket/ticket", ["status" => "1", "priority" => "2,3"]))->count);
    }

    public function adminSSOLink()
    {
        ob_start();
        ?>
		<a href="?p=supportpalsso" target="_blank"><i class="fa fa-envelope fa-fw"></i> <?=$this->getLang("SUPPORT");?></a>
		<?php
$html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    public function adminTicketLink($params)
    {
        $u = $params['user'];

        if (isset($_GET['sp_ticket_count'])) {
            $id = $this->syncClientData($params, true);
            die((String) json_decode($this->apiCall("ticket/ticket", ["user" => $id]))->count ?: "0");
        }

        return '<a class="list-group-item" href="?p=supportpalprofile&uid=' . $u->get()['ID'] . '" target="_blank">' . $this->getLang("TICKETS") . '</a>';
    }

    public function syncRegDates()
    {
        global $CFG, $db;

        @$sp = new MySQLi($_GET['hst'], $_GET['usr'], $_GET['pwd'], $_GET['dbn']);
        if ($sp->connect_errno) {
            die("MySQLi error: <i>" . $sp->connect_error . "</i>");
        }

        $sql = $sp->query("SELECT user_id, value FROM user_customfield_value WHERE field_id = " . $this->options['customfield_id']);
        while ($row = $sql->fetch_object()) {
            $u = User::getInstance($row->value, "ID");
            if (!$u) {
                continue;
            }

            $sp->query("UPDATE user SET created_at = " . $u->get()['registered'] . " WHERE id = " . $row->user_id);
        }

        alog("general", "supportpal_sync_reg_dates");

        die("Date sync finished");
    }

    public function syncAllClients()
    {
        global $CFG, $db;
        $offset = 0;
        if (isset($_GET['offset']) && is_numeric($_GET['offset'])) {
            $offset = abs(intval($_GET['offset']));
        }

        $sql = $db->query("SELECT ID FROM clients WHERE ID > $offset");
        while ($row = $sql->fetch_object()) {
            $u = new User($row->ID, "ID");
            $u->saveChanges();
        }

        alog("general", "supportpal_sync_all_clients");

        die("Full client sync finished");
    }

    public function syncClientData($params, $id = false)
    {
        global $sec, $CFG;

        $req = $params['user']->get()['mail'];
        if (!empty($params['old']['mail'])) {
            $req = $params['old']['mail'];
        }

        $res = json_decode($this->apiCall("user/user", ["email" => $req]));
        if ($res->count == 0) {
            $data = array(
                'password' => $sec->generatePassword(16, false, "luds"),
                'email' => $params['user']->get()['mail'],
            );
            $res = json_decode($this->apiCall('user/user', $data, 'POST'));
            $id = $res->data->id;
            $new = true;
        } else {
            $id = $res->data[0]->id;
            $new = false;
        }

        if ($id) {
            return $id;
        }

        $data = array(
            'firstname' => $params['user']->get()['firstname'],
            'lastname' => $params['user']->get()['lastname'],
            'email' => $params['user']->get()['mail'],
            'country' => $params['user']->get()['country_alpha2'],
            'confirmed' => $params['user']->get()['locked'] != 1 && ($CFG['USER_CONFIRMATION'] == 0 || $params['user']->get()['confirmed'] == "1") ? "1" : "0",
            'customfield' => array($this->options['customfield_id'] => $params['user']->get()['ID']),
        );

        if (!empty($params['user']->get()['company'])) {
            $data['organisation'] = $params['user']->get()['company'];
        } else {
            $data['organisation_id'] = "0";
        }

        if ($new) {
            $data['organisation_access_level'] = "0";
            $data['organisation_notifications'] = "1";
        }

        alog("general", "supportpal_sync_client_data", $id);

        $this->apiCall('user/user/' . $id, $data, 'PUT');
    }

    public function clientSSOLink($force = false)
    {
        global $var, $user;
        if ($force) {
            User::status();
        }

        if ($var['logged_in']) {
            $baseUrl = $this->options['url'];
            $redirectUrl = $this->options['url'];
            $simpleAuthKey = trim($this->options['simpleauth']);

            $token = array(
                'exp' => time() + 60,
                'email' => $user->get()['mail'],
                'jti' => uniqid() . mt_rand(100000, 999999),
            );

            $jwt = Firebase\JWT\JWT::encode($token, $simpleAuthKey);
            $loginUrl = rtrim($baseUrl, '/') . '/simpleauth';
            $request = $loginUrl . '?token=' . $jwt . '&redirect=' . urlencode($redirectUrl);
            header("Location: $request");
            exit;
        } else {
            header('Location: ' . $this->options['url']);
            exit;
        }
    }

    public function clientSSOForce()
    {
        $this->clientSSOLink(true);
    }

    public function openProfile()
    {
        global $tpl;
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
        } else {
            $id = $this->syncClientData(["user" => User::getInstance($_GET['uid'], 'ID')]);
        }

        if (empty($id)) {
            $tpl = "error";
        } else {
            $this->doAdminSSO("/admin/user/manage/" . $id . "/ticket");
        }

    }

    public function doAdminSSO($url = "/admin/dashboard")
    {
        global $adminInfo;

        $baseUrl = $this->options['url'];
        $redirectUrl = $baseUrl . $url;
        if (!empty($_POST['intended']) && substr(base64_decode($_POST['intended']), 0, strlen($baseUrl)) == $baseUrl) {
            $redirectUrl = base64_decode($_POST['intended']);
        }

        $simpleAuthKey = trim($this->options['simpleauth']);

        $token = array(
            'exp' => time() + 60,
            'email' => $adminInfo->email,
            'jti' => uniqid() . mt_rand(100000, 999999),
        );

        $jwt = Firebase\JWT\JWT::encode($token, $simpleAuthKey);
        $loginUrl = rtrim($baseUrl, '/') . '/admin/simpleauth';
        $request = $loginUrl . '?token=' . $jwt . '&redirect=' . urlencode($redirectUrl);

        alog("general", "supportpal_sso");

        header("Location: $request");
        exit;
    }

    private function apiCall($apiCall, $data = array(), $method = 'GET')
    {
        // Variables
        $baseUrl = $this->options['url'] . '/api/';
        $apiToken = trim($this->options['api_token']);

        // Start cURL
        $c = curl_init();
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($c, CURLOPT_USERPWD, $apiToken . ":x");

        // Start building the URL
        $apiCall = $baseUrl . $apiCall;

        // Check what type of API call we are making
        if ($method == 'GET') {
            // Add the array of data to the URL
            if (is_array($data)) {
                $apiCall .= "?";
                foreach ($data as $key => $value) {
                    if (isset($value)) {
                        if (is_array($value)) {
                            // In case we have an array for a value, add each item of array
                            foreach ($value as $arrayKey => $arrayValue) {
                                $apiCall .= $key . '[' . $arrayKey . ']' . "=" . $arrayValue . "&";
                            }
                        } else {
                            $apiCall .= $key . "=" . $value . "&";
                        }
                    }
                }

                // Remove the final &
                $apiCall = rtrim($apiCall, '&');
            }
        } elseif ($method == 'PUT' || $method == 'DELETE') {
            // PUT and DELETE require an $id variable to be appended to the URL
            if (isset($data['id'])) {
                $apiCall .= "/" . $data['id'];
            }

            // Setup the remainder of the cURL request
            curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($c, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: ' . $method));
        } else {
            // Setup the remainder of the cURL request
            curl_setopt($c, CURLOPT_POST, true);
            curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        // Set the URL
        curl_setopt($c, CURLOPT_URL, $apiCall);

        // Execute the API call and return the response
        $result = curl_exec($c);
        curl_close($c);

        // Return the results of the API call
        return $result;
    }
}
