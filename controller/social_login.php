<?php
global $pars, $CFG, $session, $var;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if ($var['logged_in']) {
    header('Location: ' . $CFG['PAGEURL']);
    exit;
}

if ($var['ca_disabled']) {

    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";
    $var['error'] = $lang['GENERAL']['BLOCKED'];

} else {

    function do_social_login($firstname, $lastname, $email, $service)
    {
        global $db, $CFG, $val, $session, $sec, $maq;

        if (!$val->email($email)) {
            return;
        }

        $sql = $db->query("SELECT * FROM clients WHERE mail = '" . $db->real_escape_string($email) . "'");
        if ($sql->num_rows == 1) {
            $info = $sql->fetch_object();

            if ($info->social_login != "1") {
                return;
            }

            if ($info->locked == "1") {
                return;
            }

            if ($CFG['USER_CONFIRMATION'] == "1" && $info->confirmed != "1") {
                return;
            }

            $session->set('mail', $info->mail);

            if ($CFG['HASH_METHOD'] == "plain" || $CFG['HASH_METHOD'] == "none" || $CFG['HASH_METHOD'] == "") {
                $session->set('pwd', md5($info->pwd));
            } else {
                $session->set('pwd', $info->pwd);
            }

            $uI = User::getInstance($info->ID, "ID");
            $uI->log("Login per $service durchgeführt");
            $uI->set(array('last_login' => time()));

            if ($info->login_notify) {
                $mtObj = new MailTemplate("Login-Benachrichtigung");
                $userLang = $uI->getLanguage();

                $titlex = $mtObj->getTitle($userLang);
                $mail = $mtObj->getMail($userLang, $uI->get()['name']);

                $maq->enqueue([], $mtObj, $uI->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $uI->get()['ID'], true, 0, 0, $mtObj->getAttachments($userLang));
            }
        } else {
            // Get client IP address
            $ip = ip();

            // Get standard newsletter
            $sql = $db->query("SELECT ID FROM newsletter_categories WHERE standard = 1");
            $nl = array();
            while ($row = $sql->fetch_object()) {
                array_push($nl, $row->ID);
            }

            $nl = $db->real_escape_string(implode("|", $nl));

            $cgroup = 0;
            if ($CFG['DEFAULT_CGROUP'] && $db->query("SELECT 1 FROM client_groups WHERE ID = " . intval($CFG['DEFAULT_CGROUP']))->num_rows) {
                $cgroup = intval($CFG['DEFAULT_CGROUP']);
            }

            // If all is correct, we insert the new user into database and get the new ID for further use
            $pwd = $sec->generatePassword(12, false, "lud");
            $salt = $sec->generateSalt();
            $limit = doubleval($CFG['POSTPAID_DEF']);
            $db->query("INSERT INTO clients (`cgroup`, `mail`, `firstname`, `lastname`, `pwd`, `salt`, `registered`, `last_login`, `last_ip`, newsletter, postpaid) VALUES ($cgroup, '" . $db->real_escape_string($email) . "', '" . $db->real_escape_string($firstname) . "', '" . $db->real_escape_string($lastname) . "', '" . $db->real_escape_string($sec->hash($pwd, $salt)) . "', '" . $db->real_escape_string($salt) . "', '" . time() . "', '" . time() . "', '" . $db->real_escape_string($ip) . "', '$nl', $limit)");
            $newID = $db->insert_id;
            $db->query("INSERT INTO ip_logs (time, user, ip) VALUES (" . time() . ", " . $newID . ", '" . $db->real_escape_string($ip) . "')");

            // We will assign all mail sent before to the customers email
            $db->query("UPDATE client_mails SET user = $newID WHERE recipient = '" . $db->real_escape_string($email) . "'");

            $hash = $sec->hash($pwd, $salt, false);
            if ($CFG['HASH_METHOD'] == "plain" || $CFG['HASH_METHOD'] == "none" || $CFG['HASH_METHOD'] == "") {
                $hash = md5($pwd);
            }

            // Get customer informationen into template
            $user = new User($email);
            $cart = new Cart($user->get()['ID']);
            $cart->importSession();

            if ($CFG['USER_CONFIRMATION'] != 1) {
                $var['logged_in'] = 1;
                $var['user'] = $user->get();
                $var['cart'] = $cart->get();
                $var['cart_count'] = $cart->count();
            }

            // Insert into log and enqueue a mail to new customer
            $mtObj = new MailTemplate("Registrierung per sozialem Login");

            $titlex = $mtObj->getTitle($CFG['LANG']);
            $mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);

            $maq->enqueue([
                "service" => $service,
                "email" => $email,
                "password" => $pwd,
            ], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

            $user->log("Hat sich per $service registriert");

            // Login the customer if no user confirmation is required
            if ($CFG['USER_CONFIRMATION'] != 1) {
                $session->set('mail', $user->get()['mail']);
                if ($CFG['HASH_METHOD'] == "plain" || $CFG['HASH_METHOD'] == "none" || $CFG['HASH_METHOD'] == "") {
                    $session->set('pwd', md5($user->get()['pwd']));
                } else {
                    $session->set('pwd', $user->get()['pwd']);
                }

            }

            // Send admin notification/s
            if (($ntf = AdminNotification::getInstance("Neuer Kunde")) !== false) {
                $ntf->set("name", $user->get()['name']);
                $ntf->set("email", $user->get()['mail']);
                $ntf->set("cid", $user->get()['ID']);
                $ntf->send();
            }
        }

        header('Location: ' . $CFG['PAGEURL'] . '#');
        exit;
    }

    if (isset($pars[0])) {
        switch ($pars[0]) {
            case 'facebook':
                if (!$CFG['FACEBOOK_LOGIN']) {
                    break;
                }

                Facebook\FacebookSession::setDefaultApplication($CFG['FACEBOOK_ID'], $CFG['FACEBOOK_SECRET']);
                $helper = new Facebook\FacebookRedirectLoginHelper($CFG['PAGEURL'] . 'social_login/facebook');

                try {
                    $fbsession = $helper->getSessionFromRedirect();
                } catch (FacebookRequestException $ex) {
                } catch (Exception $ex) {}

                if (isset($fbsession)) {
                    $request = new Facebook\FacebookRequest($fbsession, 'GET', '/me', array("fields" => "email, first_name, last_name"));
                    $response = $request->execute();
                    $graphObject = $response->getGraphObject();

                    do_social_login($graphObject->getProperty('first_name'), $graphObject->getProperty('last_name'), $graphObject->getProperty('email'), "Facebook");
                } else {
                    $loginUrl = $helper->getLoginUrl(array('scope' => 'email'));
                    header('Location: ' . $loginUrl);
                    exit;
                }

                break;

            case 'twitter':
                if (!$CFG['TWITTER_LOGIN']) {
                    break;
                }

                if (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier'])) {
                    $connection = new Twitter\TwitterOAuth($CFG['TWITTER_ID'], $CFG['TWITTER_SECRET'], $session->get('twitter_token'), $session->get('twitter_secret'));
                    $access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

                    if ($access_token) {
                        $connection = new Twitter\TwitterOAuth($CFG['TWITTER_ID'], $CFG['TWITTER_SECRET'], $access_token['oauth_token'], $access_token['oauth_token_secret']);
                        $content = $connection->get('account/verify_credentials', array("include_entities" => "false", "include_email" => "true"));

                        if ($content && isset($content->name)) {
                            $ex = explode(" ", $content->name);
                            do_social_login(array_shift($ex), implode(" ", $ex), $content->email, "Twitter");
                        }
                    }
                } else {
                    $connection = new Twitter\TwitterOAuth($CFG['TWITTER_ID'], $CFG['TWITTER_SECRET']);
                    $request_token = $connection->getRequestToken($CFG['PAGEURL'] . "social_login/twitter");

                    if ($request_token) {
                        $session->set('twitter_token', $request_token['oauth_token']);
                        $session->set('twitter_secret', $request_token['oauth_token_secret']);

                        if ($connection->http_code == "200") {
                            header('Location: ' . $connection->getAuthorizeURL($request_token['oauth_token']));
                            exit;
                        }
                    }
                }

                break;
        }
    }

    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";

}
