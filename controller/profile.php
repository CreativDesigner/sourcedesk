<?php
global $var, $_POST, $user, $session, $db, $tfa, $CFG, $maq, $sec, $lang, $pars, $addons;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();

$title = $lang['PROFILE']['TITLE'];
$tpl = "profile";

// Avatar
if (isset($_POST['avatar']) && in_array($_POST['avatar'], ["none", ""])) {
    $user->set(["avatar" => $_POST['avatar']]);
}

$var['avatar'] = $user->getAvatar();

if (isset($pars[0]) && $pars[0] == "bdsg") {
    $user->log("BDSG-Auskunft generiert");
    $user->getBDSG()->Output("BDSG-Auskunft-" . str_replace(" ", "-", $user->get()['name']), "I");
    exit;
}

if (isset($_GET['reset_api_key'])) {
    $key = md5(uniqid(mt_rand(), true));
    $db->query("UPDATE clients SET api_key = '$key' WHERE ID = " . $user->get()['ID']);
    die($key);
}

$var['smsverify'] = false;
if ($CFG['SMS_VERIFY'] && is_object($sms = SMSHandler::getDriver()) && array_key_exists($CFG['SMS_VERIFY'], $sms->getTypes())) {
    $var['smsverify'] = true;

    if (isset($_POST['sms_verify'])) {
        if ($user->get()['last_sms_code'] > time() - 300) {
            die($lang['PROFILE']['SMS_CODE_WAIT']);
        }

        $number = $_POST['sms_verify'];
        $code = rand(11111111, 99999999);

        $user->log("SMS-Code beantragt ($number)");
        $user->set([
            "last_sms_code" => time(),
            "sms_code" => $code,
            "sms_code_tries" => "0",
            "sms_code_number" => $number,
        ]);

        $msg = str_replace("%code%", $code, $lang['PROFILE']['SMS_CODE_MSG']);

        if (!$sms->sendMessage($number, $msg, $CFG['SMS_VERIFY'])) {
            die($lang['PROFILE']['SMS_CODE_TECERR']);
        }

        die("ok");
    }

    if (isset($_POST['sms_code'])) {
        if ($user->get()['last_sms_code'] < time() - 600) {
            die($lang['PROFILE']['SMS_CODE_EXPIRED']);
        }

        if ($user->get()['sms_code_tries'] >= 3) {
            die($lang['PROFILE']['SMS_CODE_TRIES']);
        }

        if ($user->get()['sms_code'] != $_POST['sms_code']) {
            $user->log("SMS-Code falsch eingegeben (" . $user->get()['sms_code_number'] . ")");
            $user->set(["sms_code_tries" => $user->get()["sms_code_tries"] + 1]);
            die($lang['PROFILE']['SMS_CODE_FAIL']);
        }

        $user->log("SMS-Code erfolgreich eingegeben (" . $user->get()['sms_code_number'] . ")");
        $user->set(["telephone" => $user->get()['sms_code_number'], "telephone_verified" => 1]);
        $user->saveChanges();

        die("ok");
    }
}

if ($user->get()['telephone_pin_set'] < time() - 300) {
    $pin = $var['pin'] = rand(111111, 999999);
    $user->set(array("telephone_pin" => $pin, "telephone_pin_set" => time()));
} else {
    $pin = $var['pin'] = $user->get()['telephone_pin'];
}

// Print countries to template
$countries = array();
$sql = $db->query("SELECT ID, name FROM client_countries WHERE active = 1 ORDER BY name ASC");
while ($r = $sql->fetch_object()) {
    $countries[$r->ID] = $r->name;
}

$var['countries'] = $countries;

// Fill array with all available newsletter categories
$sql = $db->query("SELECT ID, name FROM newsletter_categories ORDER BY name ASC");
$var['nl'] = array();
while ($row = $sql->fetch_object()) {
    $var['nl'][$row->ID] = $row->name;
}

// Make fields duty or inactive
$arr = $fieldVars = array(
    "salutation" => "Anrede",
    "firstname" => "Vorname",
    "lastname" => "Nachname",
    "company" => "Firma",
    "mail" => "E-Mailadresse",
    "street" => "Straße",
    "street_number" => "Hausnummer",
    "postcode" => "Postleitzahl",
    "city" => "Ort",
    "country" => "Land",
    "telephone" => "Telefonnummer",
    "fax" => "Faxnummer",
    "birthday" => "Geburtstag",
    "vatid" => "USt-IdNr.",
    "website" => "Webseite",
);

$var['fields'] = array();
$var['duty_fields'] = array();
$var['ro_fields'] = array();

foreach ($arr as $db2 => $field) {
    $info = $db->query("SELECT duty, customer FROM client_fields WHERE name = '" . $db->real_escape_string($field) . "' AND active = 1 AND customer > 0 AND system > 0");
    if ($info->num_rows != 1) {
        continue;
    }

    $var['fields'][] = $db2;
    $info = $info->fetch_object();
    if ($info->duty) {
        $var['duty_fields'][] = $db2;
    }

    if ($info->customer == 1) {
        if (!$info->duty || $user->get()[$db2]) {
            $var['ro_fields'][] = $db2;
        }
    }
}

// Custom fields
$var['cf'] = array();
$sql = $db->query("SELECT * FROM client_fields WHERE `system` = 0 AND active = 1 AND customer > 0 ORDER BY position ASC, name ASC, ID ASC");
while ($row = $sql->fetch_object()) {
    $var['cf'][$row->ID] = array($row->name, $user->getField($row->ID), $row->customer == 1, $row->duty == 1, $row->regex);
}

// Check if user has two-factor authentication activated
if ($user->get()['tfa'] == "none") {
    // If TFA is not active, generate a new secret (or get the secret from $_POST, if the user already tried to activate TFA)
    if (isset($_POST['secret']) && $_POST['secret'] != "" && strlen($_POST['secret']) == 32) {
        $secret = $_POST['secret'];
    } else {
        $secret = $tfa->createSecret(32);
    }

    // Generate QR code image URI from Google
    $qrc = $tfa->getQRCodeGoogleUrl($CFG['PAGENAME'], $secret, $user->get()['mail']);

    $var['sec'] = $secret;
    $var['qrc'] = $qrc;

    $var['tfa'] = 0;

    // User try to activate TFA
    if (isset($_POST['activate2fa'])) {
        $code = $db->real_escape_string($_POST['code']);

        if (false === ($secret = $tfa->verifyCode($secret, $code, 2, null, true))) {
            // TFA verification code is wrong
            $var['tfa_alert'] = '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $lang['PROFILE']['WRONG_TFA'] . '</div>';
        } else {
            // TFA verification code is correct, activate TFA for this user and update session
            $user->set(array('tfa' => $secret));
            $user->saveChanges();
            $db->query("INSERT INTO client_tfa (user, code, time) VALUES (" . $user->get()['ID'] . ", '$code', " . time() . ")");
            $var['tfa_alert'] = '<div class="alert alert-success">' . $lang['PROFILE']['TFA_ACTIVATED'] . '</div>';
            $var['tfa'] = 1;
            $session->set('tfa', true);

            // Send notification email
            $mtObj = new MailTemplate("Zwei-Faktor aktiviert");
            $titlex = $mtObj->getTitle($CFG['LANG']);
            $mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);
            $maq->enqueue([], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

            // Log action
            $user->log("2FA aktiviert");
        }
    }
} else {
    // Set TFA activated for template engine
    $var['tfa'] = 1;

    if (isset($_POST['deactivate2fa'])) {
        // User wants to deactivate TFA
        $code = $db->real_escape_string($_POST['code']);

        if (!$tfa->verifyCode($user->get()['tfa'], $code, 2) || $db->query("SELECT * FROM client_tfa WHERE user = " . $user->get()['ID'] . " AND code = '" . $db->real_escape_string($_POST['code']) . "'")->num_rows != 0) {
            // TFA verification code is wrong
            $var['tfa_alert'] = '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $lang['PROFILE']['WRONG_TFA_DEACTIVATE'] . '</div>';
        } else {
            // TFA verification code is correct, deactivate TFA for this user and update session
            $user->set(array('tfa' => "none"));
            $user->saveChanges();
            $db->query("DELETE FROM client_tfa WHERE user = " . $user->get()['ID']);
            $var['tfa_alert'] = '<div class="alert alert-success">' . $lang['PROFILE']['TFA_DEACTIVATED'] . '</div>';

            $session->set('tfa', false);

            // Generate secret and QR code URI for activating TFA again
            if (isset($_POST['secret']) && $_POST['secret'] != "" && strlen($_POST['secret']) == 32) {
                $sec = $_POST['secret'];
            } else {
                $sec = $tfa->createSecret(32);
            }

            $qrc = $tfa->getQRCodeGoogleUrl($CFG['PAGENAME'] . " (" . $user->get()['mail'] . ")", $sec, 170, 170);

            $var['sec'] = $sec;
            $var['qrc'] = $qrc;
            $var['tfa'] = 0;

            // Send notification email
            $mtObj = new MailTemplate("Zwei-Faktor deaktiviert");
            $titlex = $mtObj->getTitle($CFG['LANG']);
            $mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);
            $maq->enqueue([], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

            // Log action
            $user->log("2FA deaktiviert");
        }
    }
}

if (isset($pars[0]) && $pars[0] == "cancel") {
    // User wants to cancel all email changing requests, delete it from database
    $db->query("DELETE FROM client_mailchanges WHERE user = " . $user->get()['ID']);
    $var['p_alert'] = '<div class="alert alert-info">' . $lang['PROFILE']['MAIL_CANCEL'] . '</div>';

    // Send notification email
    $mtObj = new MailTemplate("E-Mailänderung storniert");
    $titlex = $mtObj->getTitle($CFG['LANG']);
    $mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);
    $maq->enqueue([], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

    // Log action
    $user->log("E-Mailänderungswünsche storniert");
} else if (isset($_POST['p_submit'])) {
    // User wants to change his profile information
    try {
        $var['p_alert'] = "";

        $arr = array();

        // User passwords should have at least 8 characters
        $pwchanged = false;
        if (isset($_POST['p_pwd']) && trim($_POST['p_pwd']) != "") {
            if (strlen($_POST['p_pwd']) < 8) {
                throw new Exception($lang['PROFILE']['PASSWORD_LENGTH']);
            }

            // Hash password
            $salt = $sec->generateSalt();
            $arr['pwd'] = $sec->hash($_POST['p_pwd'], $salt, $_POST['password_type'] == "hashed" && $CFG['CLIENTSIDE_HASHING'] == 1);
            $arr['salt'] = $salt;

            $pwchanged = true;
        }

        // User should enter his first name and last name
        if (empty($_POST['p_firstname']) || empty($_POST['p_lastname'])) {
            throw new Exception($lang['PROFILE']['NAME_ERROR']);
        }

        if (!isset($_POST['p_birthday']) || trim($_POST['p_birthday']) == "") {
            $p_birthday = "0000-00-00";
        } else {
            $p_birthday = strtotime($_POST['p_birthday']) === false ? false : date("Y-m-d", strtotime($_POST['p_birthday']));
        }

        if (false === $p_birthday || $p_birthday >= time()) {
            throw new Exception($lang['PROFILE']['BIRTHDAY_ERROR']);
        }

        $arr['salutation'] = isset($_POST['salutation']) && $_POST['salutation'] == "FEMALE" ? "FEMALE" : (isset($_POST['salutation']) && $_POST['salutation'] == "DIVERS" ? "DIVERS" : "MALE");

        $arr['firstname'] = $_POST['p_firstname'];
        $arr['lastname'] = $_POST['p_lastname'];

        // Nickname
        if (empty($user->get()['nickname'])) {
            $nickname = trim($_POST['p_nickname']);
            if (!empty($nickname)) {
                if ($db->query("SELECT 1 FROM clients WHERE nickname LIKE '" . $db->real_escape_string($nickname) . "'")->num_rows) {
                    throw new Exception($lang['PROFILE']['NICKNAMENA']);
                }

                $arr['nickname'] = $nickname;
            }
        }

        // User email address should be valid and not already in use
        $muSql = $db->query("SELECT ID FROM clients WHERE ID != " . $user->get()['ID'] . " AND mail = '" . $db->real_escape_string(trim($_POST['p_mail'])) . "'");
        if (!isset($_POST['p_mail']) || $_POST['p_mail'] == "" || !filter_var($_POST['p_mail'], FILTER_VALIDATE_EMAIL) || !checkdnsrr(explode('@', $_POST['p_mail'])[1], "MX") || $muSql->num_rows > 0) {
            throw new Exception($lang['PROFILE']['MAIL_ERROR']);
        }

        if (empty($_POST['p_street']) && in_array("street", $var['duty_fields'])) {
            throw new Exception($lang['PROFILE']['STREET_ERROR']);
        }

        if (empty($_POST['p_street_number']) && in_array("street_number", $var['duty_fields'])) {
            throw new Exception($lang['PROFILE']['STREET_NUMBER_ERROR']);
        }

        if (empty($_POST['p_postcode']) && in_array("postcode", $var['duty_fields'])) {
            throw new Exception($lang['PROFILE']['POSTCODE_ERROR']);
        }

        if (empty($_POST['p_city']) && in_array("city", $var['duty_fields'])) {
            throw new Exception($lang['PROFILE']['CITY_ERROR']);
        }

        $arr['postcode'] = $_POST['p_postcode'];
        $arr['city'] = $_POST['p_city'];
        $arr['street'] = $_POST['p_street'];
        $arr['street_number'] = $_POST['p_street_number'];
        $arr['birthday'] = $p_birthday;
        if (isset($_POST['p_telephone'])) {
            $arr['telephone'] = $_POST['p_telephone'];
            if ($arr['telephone'] != $user->get()['telephone']) {
                $arr['telephone_verified'] = 0;
            }

        }
        $arr['fax'] = $_POST['p_fax'];

        // User have to choose a valid country
        if ((!is_object($counSql = $db->query("SELECT name, alpha2 FROM client_countries WHERE active = 1 AND ID = '" . $db->real_escape_string($_POST['country']) . "'")) || $counSql->num_rows != 1) && in_array("country", $var['duty_fields'])) {
            throw new Exception($lang['PROFILE']['COUNTRY_ERROR']);
        }

        $counInfo = $counSql->fetch_object();
        $arr['country'] = $_POST['country'];

        if (!empty($arr['postcode']) && !empty($arr['city'])) {
            if ($arr['postcode'] != $user->get()['postcode'] || $arr['city'] != $user->get()['city'] || $arr['street'] != $user->get()['street'] || $arr['street_number'] != $user->get()['street_number'] || $arr['country'] != $user->get()['country']) {
                $loc = GeoLocation::getLocation($_POST['p_street'] . " " . $_POST['p_street_number'] . ", " . $_POST['p_postcode'] . " " . $_POST['p_city'] . ", " . $counInfo->name);

                if ($loc) {
                    $arr['coordinates'] = serialize($loc);
                }
            }
        }

        if (in_array("vatid", $var['fields'])) {
            if ($CFG['TAXES'] && $CFG['EU_VAT'] && !empty($_POST['p_vatid'])) {
                $vid = $_POST['p_vatid'];
                $obj = new EuVAT($vid);

                if (!$obj->isValid()) {
                    throw new Exception($lang['PROFILE']['VATID_E1']);
                }

                if ($obj->getCountry() != $counInfo->alpha2 && in_array("country", $var['duty_fields'])) {
                    throw new Exception($lang['PROFILE']['VATID_E2']);
                }

                $arr['vatid'] = $vid;
            }
        }

        // Custom fields
        $fieldData = array();
        if (isset($_POST['fields']) && is_array($_POST['fields'])) {
            foreach ($_POST['fields'] as $id => $v) {
                if (isset($var['cf'][$id]) && !$var['cf'][$id][2]) {
                    if (!empty($var['cf'][$id][4]) && !preg_match($var['cf'][$id][4], $v)) {
                        throw new Exception(str_replace("%f", $var['cf'][$id][0], $lang['PROFILE']['CF_ERROR']));
                    }

                    $fieldData[$id] = $v;
                }
            }
        }

        if ($_POST['p_mail'] != $user->get()['mail'] && in_array("mail", $var['fields'])) {
            // User wants to change his email, generate a confirmation hash and insert it into the database
            if (function_exists("random_bytes")) {
                $hash = bin2hex(random_bytes(22));
            } else {
                $hash = bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
            }
            $db->query("INSERT INTO client_mailchanges (`user`, `new`, `hash`) VALUES (" . $user->get()['ID'] . ", '" . $db->real_escape_string($_POST['p_mail']) . "', '$hash')");

            // Build confirmation URI
            $url = $CFG['PAGEURL'] . "confirm_email/" . $db->insert_id . "/" . $hash;
            $url2 = $url . "&cancel=1";

            // Send notification to old email address
            $mtObj = new MailTemplate("E-Mailänderung (alte Adresse)");

            $titlex = $mtObj->getTitle($CFG['LANG']);
            $mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);

            $maq->enqueue([
                "old" => $user->get()['mail'],
                "new" => $_POST['p_mail'],
            ], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

            // Send confirmation link to new email address
            $mtObj = new MailTemplate("E-Mailänderung (neue Adresse)");

            $titlex = $mtObj->getTitle($CFG['LANG']);
            $mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);

            $maq->enqueue([
                "old" => $user->get()['mail'],
                "new" => $_POST['p_mail'],
                "link" => $url,
                "link2" => $url2,
            ], $mtObj, $_POST['p_mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", 0, true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

            // Log action
            $user->log("Wunsch auf E-Mailadressänderung: " . $_POST['p_mail']);

            // Display message for email verification
            $var['p_alert'] = '<div class="alert alert-warning">' . $lang['PROFILE']['CHANGE_HINT'] . '</div>';
        }

        // Check if user has activated login notification via email
        $arr['login_notify'] = isset($_POST['login_notify']) ? 1 : 0;

        // Check if the user has activated newsletters
        $arr['newsletter'] = isset($_POST['newsletter']) ? 1 : 0;

        // Social Login status if allowed
        if ($CFG['SOCIAL_LOGIN_TOGGLE']) {
            $arr['social_login'] = isset($_POST['social_login']) ? 1 : 0;
        }

        // Save company name (no validation required)
        $arr['company'] = trim($_POST['p_company']);

        // Save website
        $arr['website'] = trim($_POST['p_website']);

        // Save newsletters
        $nl = array();
        if (isset($_POST['nl']) && is_array($_POST['nl'])) {
            foreach ($_POST['nl'] as $id => $null) {
                array_push($nl, $id);
            }
        }

        $arr['newsletter'] = implode("|", $nl);

        // Delete data without write access
        foreach ($arr as $k => $v) {
            if (array_key_exists($k, $fieldVars) && (!in_array($k, $var['fields']) || in_array($k, $var['ro_fields']))) {
                unset($arr[$k]);
            }
        }

        // Save custom fields
        foreach ($fieldData as $id => $v) {
            $user->setField($id, $v);
        }

        // Save data to database and to session
        $user->set($arr);
        $user->saveChanges();
        $session->set('mail', $user->get()['mail']);
        if ($CFG['HASH_METHOD'] == "plain" || $CFG['HASH_METHOD'] == "none" || $CFG['HASH_METHOD'] == "") {
            $session->set('pwd', md5($user->get()['pwd']));
        } else {
            $session->set('pwd', $user->get()['pwd']);
        }

        if ($pwchanged) {
            $addons->runHook("CustomerChangePassword", [
                "user" => $user,
                "source" => "clientarea",
            ]);
        }

        $addons->runHook("CustomerEdit", [
            "user" => $user,
            "source" => "clientarea",
        ]);

        // Publish new user information to template
        $var['user'] = $user->get();
        $var['avatar'] = $user->getAvatar();

        // Display profile change confirmation to template
        $var['p_alert'] .= '<div class="alert alert-success">' . $lang['PROFILE']['SAVED'] . '</div>';
        unset($_POST);

        // Send notification email
        $mtObj = new MailTemplate("Benutzerdaten geändert");
        $titlex = $mtObj->getTitle($CFG['LANG']);
        $mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);
        $maq->enqueue([], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

        // Log action
        $user->log("Profiländerung");

        // New custom field values
        foreach ($var['cf'] as $id => &$c) {
            $c[1] = $user->getField($id);
        }

        foreach ($fieldVars as $db2 => $field) {
            $info = $db->query("SELECT duty, customer FROM client_fields WHERE name = '" . $db->real_escape_string($field) . "' AND active = 1 AND customer > 0 AND system > 0");
            if ($info->num_rows != 1) {
                continue;
            }

            $info = $info->fetch_object();
            if ($info->customer == 1) {
                if (!$info->duty || $user->get()[$db2]) {
                    $var['ro_fields'][] = $db2;
                }
            }
        }
    } catch (Exception $ex) {
        // If any error occured, display an error message
        $var['p_alert'] = '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
    }

}

// Gather the number of open email change requests for template engine
$var['open'] = $db->query("SELECT * FROM client_mailchanges WHERE user = " . $user->get()['ID'])->num_rows;

// Check if the current country of the user is wrong; if it is, forward this information to template
if ($db->query("SELECT ID FROM client_countries WHERE active = 1 AND ID = " . $user->get()['country'])->num_rows != 1) {
    $var['country_problem'] = true;
}
