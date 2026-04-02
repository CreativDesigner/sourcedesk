<?php
// Class to handle mail queue

// Require PHPmailer class and SMTP extension
require __DIR__ . "/phpmailer/class.phpmailer.php";
require __DIR__ . "/phpmailer/class.smtp.php";

class MailQueue
{

    // Function to enqueue a new mail
    // Uses mail() syntax and give the possibility to assign an user
    public static function enqueue($vars, $tpl, $to, $subject, $text, $headers, $user = 0, $autosend = true, $newsletter = 0, $wait = 0, $attachments = array(), $time = 0)
    {
        // Global a few variables for security reasons
        global $db, $CFG, $raw_cfg, $dfo;

        // Replace variables
        if (!is_array($vars)) {
            $vars = [];
        }

        $vars["pagename"] = $CFG['PAGENAME'];
        $vars["pageurl"] = $raw_cfg['PAGEURL'];

        if ($user) {
            $uI = User::getInstance($user, "ID");
            if ($uI) {
                $vars["salutation"] = $uI->getSalutation();
            }
        } else {
            $salutation = "";
            $fallback = @unserialize($CFG['FALLBACK_SALUTATION']);
            if (is_array($fallback)) {
                if (array_key_exists($CFG['LANG'], $fallback)) {
                    $salutation = $fallback[$CFG['LANG']];
                } elseif (count($fallback)) {
                    $salutation = array_values($fallback)[0];
                }
            }

            $vars["salutation"] = $salutation;
        }

        $vars["clientid"] = $user;
        $vars["clientnr"] = $CFG['CNR_PREFIX'] . $user;

        $dateFormat = $user ? $uI->getDateFormat() : null;

        if (!array_key_exists("date", $vars)) {
            $vars["date"] = $dfo->format(time(), false, false, "", $dateFormat);
        }

        foreach (["firstname", "lastname", "company", "street", "streetnumber", "street_number", "postcode", "city", "telephone", "mail"] as $var) {
            $vars[$var] = $user ? $uI->get()[$var] : "";
            $vars["c_" . $var] = $user ? $uI->get()[$var] : "";
        }

        $vars["country"] = $user ? $uI->get()["country_alpha2"] : "";
        $vars["c_country"] = $user ? $uI->get()["country_alpha2"] : "";

        // Smarty
        if (!class_exists("Smarty")) {
            require __DIR__ . "/smarty/Smarty.class.php";
        }

        $smarty = new Smarty;
        $smarty->setCompileDir(__DIR__ . "/../templates/compiled/");

        foreach ($vars as $k => $v) {
            $text = str_replace("%" . $k . "%", $v, $text);
            $subject = str_replace("%" . $k . "%", $v, $subject);
            $smarty->assign($k, $v);
        }

        if (!empty($uI) && ($uI instanceof User)) {
            $smarty->assign("user", $uI);
        }

        $text = $smarty->fetch("string:" . $text);

        // Escape all given parameters for database insert
        // If a parameter is missing, exit function
        $arr = array("to", "subject", "text", "headers", "user", "newsletter", "wait");
        foreach ($arr as $var) {
            if (!isset($$var)) {
                return false;
            } else {
                $$var = $db->real_escape_string($$var);
            }

        }

        // Template ID
        $tpl_id = 0;
        if (is_object($tpl) && $tpl instanceof MailTemplate) {
            $tpl_id = $tpl->getID();
        }

        // Check if template is active
        if ($tpl_id) {
            if (!$db->query("SELECT active FROM email_templates WHERE ID = " . intval($tpl_id))->fetch_object()->active) {
                return false;
            }
        }

        // Insert mail into queue
        $db->query("INSERT INTO client_mails (recipient, subject, text, headers, time, user, newsletter, wait, template_id) VALUES ('$to', '$subject', '$text', '$headers', " . ($time ?: time()) . ", '$user', '$newsletter', '$wait', $tpl_id)");

        // If database insert was successful, return insert ID
        // Otherwise return false
        if ($db->affected_rows > 0) {
            $iid = $db->insert_id;

            // Add any attachments
            if (is_array($attachments) && count($attachments) > 0) {
                mkdir(__DIR__ . "/../files/emails/" . basename($iid));
                foreach ($attachments as $name => $attachment) {
                    if (file_exists($attachment) && is_file($attachment)) {
                        $file = $attachment;
                        if (stripos($file, "files/email_templates/") !== false) {
                            $ex = explode(".", basename($attachment));
                            if (array_pop(array_values($ex)) == "txt") {
                                array_pop($ex);
                            }

                            $file = implode(".", $ex);
                        }

                        $to = str_replace(",", "-", basename($file));
                        if (!is_numeric($name)) {
                            $to = $name;
                        }

                        if (!empty($to)) {
                            copy($attachment, __DIR__ . "/../files/emails/" . basename($iid) . "/" . $to);
                        }

                    }
                }
            }

            if ($CFG['MAILQUEUE_AUTO'] && $autosend) {
                self::send(1, $iid);
            }

            return $iid;
        }
        return false;
    }

    // Function to send mail(s)
    // @var n sets the maximal number of mails to send
    // @var i sets the ID of a specific mail

    public static function send($n = -1, $i = 0, $force = false, $html = true)
    {
        global $db, $CFG, $addons;

        // Send limit (higher for SES)
        if ($n == -1) {
            $n = $CFG['MAIL_TYPE'] == "ses" ? 200 : 10;
        }

        try {
            // Check if cronjob for sending mails is active or a specific mail is wanted to send
            if ($i > 0 || $force || $db->query("SELECT ID FROM cronjobs WHERE `key` = 'queue' AND active = 1 LIMIT 1")->num_rows == 1) {
                // If a specific mail is set, set the maximal number of mails to send to 1
                if (is_numeric($i) && $i != 0) {
                    $n = 1;
                }

                // Check possibility of @var n
                if (!is_numeric($n) || $n < 1) {
                    return false;
                }

                // Select mail(s) from queue
                if (is_numeric($i) && $i > 0) {
                    $sql = $db->query("SELECT * FROM client_mails WHERE sent = 0 AND ID = $i ORDER BY time ASC LIMIT $n");
                } else {
                    $sql = $db->query("SELECT * FROM client_mails WHERE sent = 0 AND time <= " . time() . " ORDER BY time ASC LIMIT $n");
                }

                // If a specific mail is not found
                if ($sql->num_rows == 0 && is_numeric($i) && $i > 0) {
                    return "false";
                }

                // Send each selected mail
                while ($r = $sql->fetch_object()) {
                    // Check if mail should be sent manually
                    if (($r->wait == 1 || $r->time > time()) && $i == 0 && !$force) {
                        continue;
                    }

                    // Run hook
                    $addons->runHook("MailPreSend", [
                        "id" => $r->ID,
                    ]);

                    $hookSql = $db->query("SELECT * FROM client_mails WHERE ID = " . $r->ID);
                    if ($hookSql->num_rows != 1) {
                        continue;
                    }
                    $r = $hookSql->fetch_object();

                    // Rate limiting (implemented to protect users from spam walls...)
                    // Limit to 30 emails per 10 minutes
                    $sql = $db->query("SELECT COUNT(*) c FROM client_mails WHERE user = {$r->user} AND sent >= " . (time() - 600));
                    if ($sql->num_rows >= 30) {
                        Telegram::sendMessage("Processing of email #{$r->ID} stopped: Potential system fault due to many emails to this client");
                        continue;
                    }

                    // Create an new object of PHPmailer class
                    $mail = new PHPMailer(true);

                    // Set charset as UTF-8 for some special chars like euro
                    $mail->CharSet = 'UTF-8';

                    // Extract email address from "From"-header and set the @property from of @object mail
                    $mail->SetFrom(trim(trim(self::getBetween("<", ">", $r->headers)), "<>") ?: $CFG['MAIL_SENDER'], trim(self::getBetween("From:", "<", $r->headers)) ?: $CFG['PAGENAME']);

                    // Get recipients
                    $rec = [];

                    try {
                        if ($r->template_id <= 0) {
                            throw new Exception;
                        }

                        if ($r->user <= 0) {
                            throw new Exception;
                        }

                        $uI = User::getInstance($r->user, "ID");
                        if (!$uI) {
                            throw new Exception;
                        }

                        $ex = explode(",", $uI->get()['exclude_mail_templates']);
                        if (!in_array($r->template_id, $ex)) {
                            $rec[] = $uI->get()['mail'];
                        }

                        foreach ($uI->getContacts($r->template_id) as $c) {
                            $rec[] = $c->get("mail");
                        }
                    } catch (Exception $ex) {
                        $rec = [$r->recipient];
                    }

                    if (!count($rec)) {
                        $db->query("UPDATE client_mails SET sent = " . time() . " WHERE ID = " . $r->ID . " AND sent = 0 LIMIT 1");
                        continue;
                    }

                    // Add recipients
                    $mail->AddAddress(array_shift($rec));

                    while (count($rec)) {
                        $mail->AddCC(array_shift($rec));
                    }

                    // Set header for newsletters
                    if ($r->newsletter > 0) {
                        $mail->addCustomHeader("Precedence: bulk");
                    }

                    // Set subject for @object mail
                    $mail->Subject = $r->subject;

                    // Set body for @object mail
                    if ($r->newsletter > 0) {
                        $userLang = $db->query("SELECT language FROM clients WHERE ID = " . intval($r->user))->fetch_object()->language;
                        $language = !empty($userLang) && file_exists(__DIR__ . "/../languages/" . basename($userLang) . ".php") ? $userLang : $CFG['LANG'];

                        if ($r->user) {
                            $stop_url = $CFG['PAGEURL'] . "stop_newsletter/" . $r->user . "/" . substr(hash("sha512", $CFG['HASH'] . $r->user . $r->recipient), 0, 10);
                        } else {
                            $stop_url = $CFG['PAGEURL'] . "newsletter";
                        }

                        $legalTemplate = new MailTemplate("Newsletter-Disclaimer");
                        $r->text .= "\n\n";
                        $r->text .= str_replace("%stop%", $stop_url, $legalTemplate->getContent($language));
                    }

                    if ($html) {
                        $mail->Body = self::getHTMLBody($r);
                        $mail->IsHTML(true);
                        $mail->AltBody = strip_tags($r->text);
                    } else {
                        $mail->Body = $r->text ?: " ";
                    }

                    // If mails should be send through SMTP, set SMTP data to object
                    if ($CFG['MAIL_TYPE'] == "smtp") {
                        $mail->IsSMTP();
                        $mail->Host = $CFG['SMTP_HOST'];

                        if ($CFG['SMTP_SECURITY'] == "tls" || $CFG['SMTP_SECURITY'] == "ssl") {
                            $mail->SMTPSecure = $CFG['SMTP_SECURITY'];
                        } else {
                            $mail->SMTPSecure = false;
                            $mail->SMTPAuthTLS = false;
                        }

                        // Specify credentials if needed
                        if (!empty($CFG['SMTP_USER']) || !empty($CFG['SMTP_PASSWORD'])) {
                            $mail->SMTPAuth = true;

                            $mail->Username = $CFG['SMTP_USER'];
                            $mail->Password = $CFG['SMTP_PASSWORD'];
                        }
                    }

                    // If mails should be sent through Amazon SES, set SES data to object
                    if ($CFG['MAIL_TYPE'] == "ses") {
                        $mail->isSES();
                        $mail->AddAmazonSESKey($CFG['SES_ID'], $CFG['SES_SECRET']);
                    }

                    // Check for attachments and add them
                    if (is_dir(__DIR__ . "/../files/emails/" . basename($r->ID))) {
                        foreach (glob(__DIR__ . "/../files/emails/" . basename($r->ID) . "/*") as $file) {
                            if (substr(basename($file), 0, 1) == ".") {
                                continue;
                            }

                            $mail->AddAttachment($file);
                        }
                    }

                    // Try to send mail via PHPmailer and set mail as sent
                    if ($mail->Send()) {
                        $db->query("UPDATE client_mails SET sent = " . time() . " WHERE ID = " . $r->ID . " AND sent = 0 LIMIT 1");

                        $addons->runHook("MailPostSend", [
                            "id" => $r->ID,
                        ]);

                        if ($i != 0) {
                            return true;
                        }
                    }
                    if ($i != 0) {
                        return false;
                    }

                }

                return true;
            }

            return false;
        } catch (phpmailerException $ex) {
            return false;
        }
    }

    // Method for getting html mail template

    public static function getHTMLBody($info, $show_attachments = false)
    {
        global $CFG, $raw_cfg, $db;

        require __DIR__ . "/../languages/" . basename($CFG['LANG']) . ".php";
        $userInfo = $db->query("SELECT language FROM clients WHERE ID = " . intval($info->user));
        if ($userInfo->num_rows == 1) {
            $lng = $userInfo->fetch_object()->language ?: $CFG['LANG'];
            if (file_exists(__DIR__ . "/../languages/$lng.php")) {
                require __DIR__ . "/../languages/$lng.php";
            }

        }

        $title = htmlentities($info->subject);
        $pageurl = $raw_cfg['PAGEURL'];
        $pagetitle = $CFG['PAGENAME'];
        $text = $info->text;

        if ($info->newsletter > 0) {
            $cancel_link = $CFG['PAGEURL'] . "stop_newsletter/" . $info->user . "/" . substr(hash("sha512", $CFG['HASH'] . $info->user . $info->recipient), 0, 10);
            $cancel_lang = $lang['EMAIL']['CANCEL'] ?: "Abbestellen";
        }

        $browser_link = $raw_cfg['PAGEURL'] . "email/" . $info->ID . "/" . substr(hash("sha512", "email_view" . $info->ID . $CFG['HASH']), 0, 10);
        $browser_lang = $lang['EMAIL']['WEBVERSION'] ?: "Webversion";
        $login_link = $raw_cfg['PAGEURL'] . "login";
        $login_lang = $lang['EMAIL']['LOGIN'] ?: "Einloggen";
        $attachments_lang = $lang['EMAIL']['ATTACHMENTS'] ?: "Anh&auml;nge";
        $attachments = array();
        if ($show_attachments && is_dir(__DIR__ . "/../files/emails/" . basename($info->ID))) {
            foreach (glob(__DIR__ . "/../files/emails/" . basename($info->ID) . "/*") as $file) {
                $file = basename($file);
                if (substr($file, 0, 1) == ".") {
                    continue;
                }

                $attachments[$file] = $CFG['PAGEURL'] . "email/" . $info->ID . "/" . substr(hash("sha512", "email_view" . $info->ID . $CFG['HASH']), 0, 10) . "/" . str_replace(".", ",", $file);
            }
        }

        ob_start();
        require __DIR__ . "/../templates/email/html.php";
        $res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    // Helper method for extract email address from header

    private static function getBetween($var1 = "", $var2 = "", $pool)
    {
        $temp1 = strpos($pool, $var1) + strlen($var1);
        $result = substr($pool, $temp1, strlen($pool));
        $dd = strpos($result, $var2);

        if ($dd == 0) {
            $dd = strlen($result);
        }

        return substr($result, 0, $dd);
    }

    // Function to resend a mail

    public static function resend($id)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT * FROM client_mails WHERE ID = " . intval($id));
        if ($sql->num_rows != 1) {
            return false;
        }

        $info = $sql->fetch_object();
        unset($info->ID);
        $info->time = time();
        $info->sent = 0;
        $info->resend = 1;
        $info->seen = 0;

        $fields = $values = "";
        foreach ($info as $k => $v) {
            $fields .= "`" . $db->real_escape_string($k) . "`, ";
            $values .= "'" . $db->real_escape_string($v) . "', ";
        }
        $fields = rtrim($fields, ", ");
        $values = rtrim($values, ", ");

        if (!$db->query("INSERT INTO client_mails ($fields) VALUES ($values)")) {
            return false;
        }

        $newID = $db->insert_id;

        // Check for attachments
        if (is_dir(__DIR__ . "/../files/emails/" . basename($id))) {
            symlink(__DIR__ . "/../files/emails/" . basename($id), __DIR__ . "/../files/emails/" . basename($newID));
        }

        return true;
    }

    // Replace mail content

    public static function clean()
    {
        global $CFG, $db;

        // Delete all old mails from database if option is activated
        if ($CFG['MAIL_LEADTIME'] > 0) {
            $db->query("DELETE FROM client_mails WHERE time <= " . (time() - 60 * 60 * 24 * 30 * $CFG['MAIL_LEADTIME']));
        }

    }

    // Delete one mail

    public function replace($id, $arr)
    {
        global $CFG, $db;
        if (!is_array($arr)) {
            return false;
        }

        $sql = $db->query("SELECT `text` FROM client_mails WHERE ID = " . $id . " LIMIT 1");
        if ($sql->num_rows != 1) {
            return false;
        }

        $text = $sql->fetch_object()->text;
        foreach ($arr as $v) {
            $str = "";
            for ($i = 0; $i < strlen($v); $i++) {
                $str .= "*";
            }

            $text = str_replace($v, $str, $text);
        }
        return (bool) $db->query("UPDATE client_mails SET `text` = '" . $db->real_escape_string($text) . "' WHERE ID = " . $id . " LIMIT 1");
    }

    // Static function to delete old mails

    public function delete($id)
    {
        global $CFG, $db;

        if (is_dir(__DIR__ . "/../files/emails/" . basename($id))) {
            foreach (glob(__DIR__ . "/../files/emails/" . basename($id) . "/*") as $f) {
                unlink($f);
            }

            rmdir(__DIR__ . "/../files/emails/" . basename($id));
        }

        return (bool) $db->query("DELETE FROM client_mails WHERE ID = " . intval($id) . " LIMIT 1");
    }

}
