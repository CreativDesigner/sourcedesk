<?php
global $var, $session, $db, $CFG, $maq, $lang, $captcha, $val;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$CFG['ALLOW_REG']) {

    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";
    $var['error'] = $lang['REGISTER']['DISABLED'];

} else if ($var['logged_in'] != 0) {

    // User should not be logged in
    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";

} else if ($var['ca_disabled']) {

    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";
    $var['error'] = $lang['GENERAL']['BLOCKED'];

} else {

    $title = $lang['REGISTER']['BTITLE'];
    $tpl = "register";

    if (isset($_POST['firstname'])) {
        // Check if the captcha is correct
        if ($captcha->verify() || $session->get('captcha_solved_reg') == 1) {
            // The client only have to solve one captcha within one registration
            $session->set('captcha_solved_reg', 1);

            // The name of the client should not be empty
            if (empty($_POST['firstname']) || empty($_POST['lastname'])) {
                $var['error'] = $lang['REGISTER']['NAME_ERROR'];
            } else {
                // Check email address and MX records
                if (!isset($_POST['email']) || !$val->email($_POST['email'])) {
                    $var['error'] = $lang['REGISTER']['EMAIL_ERROR'];
                } else {
                    // Check if email address is already in use
                    $q = $db->query("SELECT * FROM clients WHERE mail = '" . $db->real_escape_string($_POST['email']) . "'");
                    if ($q->num_rows > 0) {
                        $var['error'] = $lang['REGISTER']['ALREG'];
                    } else {
                        // Send confirmation email with hash and timestamp for limitation
                        $ts = time() + 60 * 60 * 48;

                        $headers = array();
                        $headers[] = "MIME-Version: 1.0";
                        $headers[] = "Content-type: text/plain; charset=utf-8";
                        $headers[] = "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">";

                        $mtObj = new MailTemplate("Neuregistrierung");
                        $titlex = $mtObj->getTitle($CFG['LANG']);
                        $mail = $mtObj->getMail($CFG['LANG'], $_POST['firstname'] . " " . $_POST['lastname']);
                        $maq->enqueue([
                            "link" => $CFG['PAGEURL'] . "confirm/" . urlencode($_POST['firstname']) . "/" . urlencode($_POST['lastname']) . "/" . urlencode(str_replace(".", ",", $_POST['email'])) . "/" . $ts . "/" . md5($CFG['HASH'] . $_POST['firstname'] . $_POST['lastname'] . $_POST['email'] . $ts),
                        ], $mtObj, $_POST['email'], $titlex, $mail, implode("\r\n", $headers), 0, true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

                        // Reset captcha flag and go to the next step
                        $session->set('captcha_solved_reg', 0);
                        $var['steptwo'] = 1;
                    }
                }
            }
        } else {
            // Reset captcha flag and display error, if captcha is submitted wrong
            $session->set('captcha_solved_reg', 0);
            $var['error'] = $lang['REGISTER']['CAPTCHA_ERROR'];
        }
    }

    // Generate captcha and save it into session
    $myCaptcha = $captcha->get();
    if ($session->get('captcha_solved_reg') == 1) {
        unset($myCaptcha);
    }

    if (is_array($myCaptcha) && isset($myCaptcha['type']) && $myCaptcha['type'] == "text") {
        $var['captchaText'] = $myCaptcha['value'];
    } else if (isset($myCaptcha['type']) && $myCaptcha['type'] == "modal") {
        $var['captchaModal'] = $myCaptcha['value'];
    } else if (isset($myCaptcha['type']) && $myCaptcha['type'] == "code") {
        $var['captchaCode'] = $myCaptcha['value'];
    }

    if (isset($myCaptcha['exec'])) {
        $var['captchaExec'] = $myCaptcha['exec'];
    }
}
