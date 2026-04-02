<?php
global $user, $session, $db, $CFG, $sec, $maq, $var, $lang, $f2b, $addons;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

// User should access this site only when he is not logged in
if ($var['logged_in'] == 1) {

    $additional = array();
    foreach ($_GET as $k => $v) {
        if ($k != "p" && $k != "redirect_to" && $k != "add_product" && $k != "add_service") {
            $additional[$k] = $v;
        }
    }

    header('Location: ' . $CFG['PAGEURL'] . (!empty($_REQUEST['redirect_to']) ? urldecode($_REQUEST['redirect_to']) : "dashboard") . rtrim("?" . http_build_query($additional), "?"));
    exit;

} else if ($var['ca_disabled']) {

    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";
    $var['error'] = $lang['GENERAL']['BLOCKED'];

} else {

    $title = $lang['LOGIN']['TITLE'];
    $tpl = "login";

    $var['pwsend'] = 0;

    // Check if user wants a password reset
    if (isset($_POST['pwreset']) && isset($_POST['email'])) {
        // Build a object for the requested user
        $user = new User($_POST['email']);

        // Set alert
        $var['alert'] = "<div class=\"alert alert-success\">" . $lang['LOGIN']['PW_SEND'] . "</div>";

        // Check if the user exists and the last password reset request was not in the last 24 hours
        if ($user !== false && $user->get() != null) {
            if ($user->get()['last_pwreset'] <= time() - 86400) {
                // Get the email address of the user and a random hash
                $mail = $user->get()['mail'];

                if (function_exists("random_bytes")) {
                    $hash = bin2hex(random_bytes(22));
                } else {
                    $hash = bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
                }

                // Send a email for password reset
                $mtObj = new MailTemplate("Passwort angefordert");

                $titlex = $mtObj->getTitle($CFG['LANG']);
                $mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);

                $maq->enqueue([
                    "link" => $CFG['PAGEURL'] . "reset_password?u=" . $user->get()['ID'] . "&h=" . $hash,
                ], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

                // Set password reset hash and last password reset request to users profile
                $user->set(
                    array("reset_hash" => $hash,
                        "last_pwreset" => time())
                );

                // Log action
                $user->log("Passwort-Reset angefordert");
            }

            $var['pwsend'] = 1;
        } else if (trim($_POST['email']) == "" || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $var['alert'] = "<div class=\"alert alert-danger\">" . $lang['LOGIN']['NO_MAIL'] . "</div>";
        }
    } else if (isset($_GET['send_password']) && isset($_GET['h']) && $_GET['h'] == substr(hash("sha512", $CFG['HASH'] . $_GET['send_password']), 0, 5)) {
        // Build a object for the requested user
        $user = new User($_GET['send_password']);

        // Check if the user exists and the last password reset request was not in the last 24 hours
        if ($user !== false && $user->get() != null && $user->get()['last_pwreset'] <= time() - 86400) {
            // Get the email address of the user and a random hash
            $mail = $user->get()['mail'];

            if (function_exists("random_bytes")) {
                $hash = bin2hex(random_bytes(22));
            } else {
                $hash = bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
            }

            // Send a email for password reset
            $mtObj = new MailTemplate("Passwort angefordert");

            $titlex = $mtObj->getTitle($CFG['LANG']);
            $mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);

            $maq->enqueue([
                "link" => $CFG['PAGEURL'] . "reset_password?u=" . $user->get()['ID'] . "&h=" . $hash,
            ], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

            // Set password reset hash and last password reset request to users profile
            $user->set(
                array("reset_hash" => $hash,
                    "last_pwreset" => time())
            );

            // Log action
            $user->log("Passwort-Reset angefordert (Link geklickt)");

            $var['pwsend'] = 1;
            $var['alert'] = "<div class=\"alert alert-success\">" . $lang['LOGIN']['PW_SEND_DONE'] . "</div>";
        } else if ($user->get()['last_pwreset'] > time() - 86400) {
            $var['alert'] = "<div class=\"alert alert-danger\">" . $lang['LOGIN']['ALREADY_REQUESTED'] . "</div>";
        }
    }

    // Check if user submitted the login form
    if (isset($_POST['login']) && isset($_POST['email']) && isset($_POST['password'])) {
        try {
            // Get POST variables into local variables
            $mail = $_POST['email'];
            $pwd = $_POST['password'];

            // Try to build a user object
            $user = new User($mail);

            // Check if any mail was submitted
            if (trim($_POST['email']) == "" || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception($lang['LOGIN']['NO_MAIL']);
            }

            // Check if user exists
            if (!$user || $user->get() == null) {
                $f2b->failedLogin();
                throw new Exception($lang['LOGIN']['WRONG_CREDENTIALS']);
            }

            // Check if the password is correct
            if ($sec->hash($pwd, $user->get()['salt']) != $user->get()['pwd'] && ($CFG['CLIENTSIDE_HASHING'] != 1 || $sec->hash($pwd, $user->get()['salt'], true) != $user->get()['pwd'])) {
                $f2b->failedLogin();
                $user->log("Login-Versuch (Falsches Passwort)");
                $user->set(array("failed_login" => time()));
                throw new Exception($lang['LOGIN']['WRONG_CREDENTIALS']);
            }

            // Check if the user account is locked by an administrator
            if ($user->get()['locked'] == 1) {
                $user->log("Login-Versuch (Benutzer gesperrt)");
                throw new Exception($lang['LOGIN']['LOCKED']);
            }

            // Check if user confirmation is required
            if ($CFG['USER_CONFIRMATION'] == 1 && $user->get()['confirmed'] != 1) {
                $user->log("Login-Versuch (Benutzer noch nicht frei)");
                throw new Exception($lang['LOGIN']['UNFREE']);
            }

            // Set the session
            $session->set('mail', $mail);

            if ($CFG['HASH_METHOD'] == "plain" || $CFG['HASH_METHOD'] == "none" || $CFG['HASH_METHOD'] == "") {
                $session->set('pwd', md5($user->get()['pwd']));
            } else {
                $session->set('pwd', $user->get()['pwd']);
            }

            // Set the last login time to now and log into activity log
            $user->set(array('last_login' => time(), "failed_login" => 0));

            // Check if the user wants a login notification
            if ($user->get()['login_notify']) {
                // If so, send the user an email
                $mtObj = new MailTemplate("Login-Benachrichtigung");
                $userLang = isset($user->get()['language']) && trim($user->get()['language']) != "" && file_exists(__DIR__ . "/../" . $user->get()['language'] . ".php") ? $user->get()['language'] : $CFG['LANG'];

                $titlex = $mtObj->getTitle($userLang);
                $mail = $mtObj->getMail($userLang, $user->get()['name']);

                $maq->enqueue([], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($userLang));
            }

            // Log action
            $user->log("Hat sich eingeloggt");

            $addons->runHook("CustomerLogin", [
                "user" => $user,
                "source" => "clientarea",
            ]);

            // Check if the user wants to set a cookie
            if (isset($_POST['cookie'])) {
                // If so, generate a key and save the credentials into database
                $key = "";
                for ($i = 0; $i < 5; $i++) {
                    if (function_exists("random_bytes")) {
                        $key .= bin2hex(random_bytes(22));
                    } else {
                        $key .= bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
                    }
                }

                $valid = time() + 60 * 60 * 24 * 30;

                $db->query("INSERT INTO client_cookie (`string`, `valid`, `user`, `auth`) VALUES ('" . hash("sha512", $key) . "', $valid, " . $user->get()['ID'] . ", '" . $user->get()['pwd'] . "')");

                // Save key on clients local computer
                setcookie("auth", $key, $valid, "/", null, $CFG['EXPLICIT_SSL'] || $CFG['HSTS'], true);
            }

            // Import cart contents
            $cart = new Cart($user->get()['ID']);
            $cart->importSession();

            // Redirect the user to the start page
            $additional = array();
            foreach ($_GET as $k => $v) {
                if ($k != "p" && $k != "redirect_to" && $k != "add_product" && $k != "add_service") {
                    $additional[$k] = $v;
                }
            }

            header('Location: ' . $CFG['PAGEURL'] . (!empty($_REQUEST['redirect_to']) ? urldecode($_REQUEST['redirect_to']) : "dashboard") . rtrim("?" . http_build_query($additional), "?"));
            exit;
        } catch (Exception $ex) {
            // If any error occured, display it
            $var['alert'] = "<div class=\"alert alert-danger\">" . $ex->getMessage() . "</div>";
        }

    }

}
