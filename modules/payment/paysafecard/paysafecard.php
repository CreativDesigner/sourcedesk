<?php
// Class for making paysafecard payments

class PaysafecadPG extends PaymentGateway
{
    public static $shortName = "paysafecard";

    public function __construct($language)
    {
        parent::__construct(self::$shortName);
        $this->language = $language;

        if (!include (__DIR__ . "/language/$language.php")) {
            throw new ModuleException();
        }

        if (!is_array($addonlang) || !isset($addonlang["NAME"])) {
            throw new ModuleException();
        }

        $this->lang = $addonlang;
        $this->options = array(
            "key" => array("type" => "text", "name" => $this->getLang('key'), "help" => $this->getLang('key_hint')),
            "test" => array("type" => "checkbox", "description" => $this->getLang('test')),
        );
        $this->log = true;
        $this->cashbox = true;
        $this->payment_handler = true;

        if (!empty($_GET['psc_id'])) {
            $this->capturePayment();
        }

    }

    public function capturePayment()
    {
        global $user, $db, $CFG, $maq, $raw_cfg, $transactions, $cur, $nfo;

        if (!$user || !$this->canPay($user)) {
            return;
        }

        $header = array(
            "Content-Type: application/json",
            "Authorization: Basic " . base64_encode($this->settings['key']),
        );

        $ch = curl_init("https://api" . ($this->settings['test'] == "true" ? "test" : "") . ".paysafecard.com/v1/payments/" . urlencode($_GET['psc_id']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        if (!empty($res->status) && $res->status == "AUTHORIZED") {
            $ch = curl_init("https://api" . ($this->settings['test'] == "true" ? "test" : "") . ".paysafecard.com/v1/payments/" . urlencode($_GET['psc_id']) . "/capture");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_POST, 1);
            $res = json_decode(curl_exec($ch));
            curl_close($ch);

            $amount = $res->amount;
            $status = $res->status;
            $currency = $res->currency;
            $cust = $res->customer->id;

            if (!empty($res->status) && $res->status == "SUCCESS") {
                $fees = $this->getFees($amount);

                $db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($amount) . "' WHERE ID = '" . $db->real_escape_string($user->get()['ID']) . "' LIMIT 1");
                $transactions->insert("paysafecard", $_GET['psc_id'], $amount, $user->get()['ID'], "", 1);

                $userInfo = $db->query("SELECT ID, firstname, lastname, mail, language FROM clients WHERE ID = '" . $db->real_escape_string($user->get()['ID']) . "' LIMIT 1")->fetch_object();
                $uI = User::getInstance($userInfo->ID, "ID");
                $sendLang = $uI->getLanguage();

                $mtObj = new MailTemplate("Guthabenaufladung");
                $title = $mtObj->getTitle($sendLang);
                $mail = $mtObj->getMail($sendLang, $userInfo->firstname . " " . $userInfo->lastname);
                
                $maq->enqueue([
					"amount" => $cur->infix($nfo->format($amount), $currency),
					"processor" => $this->getLang("name"),
				], $mtObj, $userInfo->mail, $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $userInfo->ID, true, 0, 0, $mtObj->getAttachments($sendLang));

                if (($ntf = AdminNotification::getInstance("IPN-Gutschrift")) !== false) {
                    $ntf->set("amount", $cur->infix($nfo->format($amount), $cur->getBaseCurrency()));
                    $ntf->set("fees", $cur->infix($nfo->format($fees), $cur->getBaseCurrency()));
                    $ntf->set("gateway", $this->getLang("name"));
                    $ntf->set("customer", $userInfo->firstname . " " . $userInfo->lastname);
                    $ntf->set("clink", $raw_cfg['PAGEURL'] . "admin/?p=customers&edit=" . $userInfo->ID);
                    $ntf->send();
                }

                if ($fees > 0) {
                    $uI->invoiceFees($fees);
                }

                $uI->applyCredit();
            } else {
                unset($_GET['okay']);
                $_GET['cancel'] = 1;
            }
        } else if ($res->status != "SUCCESS") {
            unset($_GET['okay']);
            $_GET['cancel'] = 1;
        }
    }

    public function getPaymentForm($amount = null)
    {
        global $cur, $CFG, $nfo, $lang, $var, $raw_cfg;

        ?>
		<img src="<?=$raw_cfg['PAGEURL'];?>modules/payment/paysafecard/logo.png" alt="paysafecard" title="paysafecard" width="320px" /><br /><br />
		<?php

        $fees = $addon = "";
        if ($this->settings['excl'] == 1) {
            $addon = "_EXCL";
        }

        if ($this->settings['fix'] != 0 && $this->settings['percent'] != 0) {
            $fees = " " . str_replace(array("%p", "%a"), array($nfo->format($this->settings['percent'], 2, true), $cur->infix($nfo->format($cur->convertAmount(null, $this->settings['fix']), 2))), $lang['CREDIT']['FEES_BOTH' . $addon]);
        } else if ($this->settings['fix'] != 0) {
            $fees = " " . str_replace(array("%p", "%a"), array($nfo->format($this->settings['percent'], 2, true), $cur->infix($nfo->format($cur->convertAmount(null, $this->settings['fix']), 2))), $lang['CREDIT']['FEES_FIX' . $addon]);
        } else if ($this->settings['percent'] != 0) {
            $fees = " " . str_replace(array("%p", "%a"), array($nfo->format($this->settings['percent'], 2, true), $cur->infix($nfo->format($cur->convertAmount(null, $this->settings['fix']), 2))), $lang['CREDIT']['FEES_PERCENT' . $addon]);
        }

        $code = "<p><form method=\"POST\" class=\"form-inline\" action=\"" . $CFG['PAGEURL'] . "credit/pay/paysafecard\">
				<div class=\"input-group\">";

        $myCur = $var['currencyObj'];

        if (!empty($myCur->getPrefix())) {
            $code .= "<span class=\"input-group-addon\">{$myCur->getPrefix()}</span>";
        }

        $code .= "<input type=\"text\" name=\"amount\" value=\"" . ($amount !== null ? $amount : '') . "\" placeholder=\"{$this->getLang('amount')}\" style=\"max-width:80px\" class=\"form-control\">";

        if (!empty($myCur->getSuffix())) {
            $code .= "<span class=\"input-group-addon\">{$myCur->getSuffix()}</span>";
        }

        $code .= "</div>
  				<input type=\"submit\" class=\"btn btn-primary\" value=\"{$this->getLang('submit')}" . $fees . "\">
    			</form><p class=\"help-block\">" . $this->getLang("max") . "</p></p>";

        return $code;
    }

    public function getPaymentHandler()
    {
        global $var, $user, $CFG, $nfo, $lang, $title, $tpl;

        if (!$this->canPay($user)) {
            return;
        }

        $amount = $this->addFees($nfo->phpize($_POST['amount']), true);
        if ($amount < 0.01 || $amount > 1000) {
            $title = $lang['ERROR']['TITLE'];
            $tpl = "error";
            $var['error'] = $this->getLang('err2');
        }

        $header = array(
            "Content-Type: application/json",
            "Authorization: Basic " . base64_encode($this->settings['key']),
        );

        $data = array(
            "type" => "PAYSAFECARD",
            "amount" => $amount,
            "currency" => $var['myCurrency'],
            "redirect" => array(
                "success_url" => $CFG['PAGEURL'] . "credit?okay&psc_id={payment_id}",
                "failure_url" => $CFG['PAGEURL'] . "credit?cancel",
            ),
            "notification_url" => $CFG['PAGEURL'] . "ipn/paysafecard?id={payment_id}",
            "customer" => array("id" => $user->get()['ID']),
            "shop_id" => "sourceway",
        );

        $ch = curl_init("https://api" . ($this->settings['test'] == "true" ? "test" : "") . ".paysafecard.com/v1/payments");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $res = json_decode(curl_exec($ch));

        if (!empty($res->redirect->auth_url)) {
            header('Location: ' . $res->redirect->auth_url);
            exit;
        } else {
            $title = $lang['ERROR']['TITLE'];
            $tpl = "error";
            $var['error'] = $this->getLang('err1');
            if (!empty($res->message)) {
                $var['debug'] = $res->message;
            }

        }
    }

    public function makeCashboxPayment($hash, $gate = false)
    {
        global $cur, $CFG, $user, $var, $lang, $nfo, $title, $tpl;

        $header = array(
            "Content-Type: application/json",
            "Authorization: Basic " . base64_encode($this->settings['key']),
        );

        $suc = $CFG['PAGEURL'] . 'cashbox/' . $_REQUEST['user'] . '/' . $_REQUEST['hash'] . '?okay&amount=' . urlencode($_POST['amount']) . '&payment_method=' . urlencode($_POST['payment_method']) . '&subject=' . urlencode($_POST['subject']);
        $err = $CFG['PAGEURL'] . 'cashbox/' . $_REQUEST['user'] . '/' . $_REQUEST['hash'] . '?cancel&amount=' . urlencode($_POST['amount']) . '&payment_method=' . urlencode($_POST['payment_method']) . '&subject=' . urlencode($_POST['subject']);

        if ($gate) {
            $suc = $CFG['PAGEURL'] . 'psc_gate?payment={payment_id}';
            $err = $CFG['PAGEURL'] . 'psc_gate';
        }

        $data = array(
            "type" => "PAYSAFECARD",
            "amount" => $gate ? $_POST['amount'] : $this->addFees($nfo->phpize($_POST['amount']), true),
            "currency" => $var['myCurrency'],
            "redirect" => array(
                "success_url" => $suc,
                "failure_url" => $err,
            ),
            "notification_url" => $CFG['PAGEURL'] . "ipn/paysafecard?id={payment_id}",
            "customer" => array("id" => $CFG['CASHBOX_PREFIX'] . $hash),
            "shop_id" => "sourceway",
        );

        $ch = curl_init("https://api" . ($this->settings['test'] == "true" ? "test" : "") . ".paysafecard.com/v1/payments");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $res = json_decode(curl_exec($ch));

        if (!empty($res->redirect->auth_url)) {
            if ($gate) {
                return $res->redirect->auth_url;
            }

            header('Location: ' . $res->redirect->auth_url);
            exit;
        } else {
            if ($gate) {
                return false;
            }

            $title = $lang['ERROR']['TITLE'];
            $tpl = "error";
            $var['error'] = $this->getLang('err1');
        }
    }

    public function getIpnHandler()
    {
        global $CFG, $db, $transactions, $cur, $maq, $raw_cfg, $nfo, $dfo;

        $log = "System gestartet\n";

        try {
            if (empty($_GET['id'])) {
                throw new Exception($log . "Keine Transaktions-ID mitgegeben");
            }

            $header = array(
                "Content-Type: application/json",
                "Authorization: Basic " . base64_encode($this->settings['key']),
            );

            $ch = curl_init("https://api" . ($this->settings['test'] == "true" ? "test" : "") . ".paysafecard.com/v1/payments/" . urlencode($_GET['id']) . "/capture");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_POST, 1);
            $res = json_decode(curl_exec($ch));
            curl_close($ch);

            $amount = $res->amount;
            $status = $res->status;
            $currency = $res->currency;
            $cust = $res->customer->id;
            $txn_id = $_GET['id'];

            if (!empty($res->status) && $res->status == "SUCCESS") {
                $log .= "paysafecard hat die Zahlung best&auml;tigt\n";

                if (count($transactions->get(array("subject" => "paysafecard|$txn_id"))) > 0) {
                    throw new Exception($log . "Transaktions-ID bereits eingebucht");
                }

                try {
                    $curObj = new Currency($currency);
                    $amount = $cur->convertAmount($currency, $amount, $cur->getBaseCurrency());
                } catch (CurrencyException $ex) {
                    throw new Exception($log . "Unbekannte W&auml;hrung");
                }

                if (strlen($CFG['CASHBOX_PREFIX']) >= 2 && $CFG['CASHBOX_ACTIVE'] && stripos($cust, $CFG['CASHBOX_PREFIX'] . "C") !== false) {
                    $log .= "Cashbox-Transaktion\n";
                    $user = "";

                    $cashboxHash = substr($cust, stripos($cust, $CFG['CASHBOX_PREFIX'] . "C") + strlen($CFG['CASHBOX_PREFIX']), 8);
                    $sql = $db->query("SELECT * FROM `cashbox` WHERE `hash` = '" . $db->real_escape_string($cashboxHash) . "'");
                    if ($sql->num_rows != 1) {
                        throw new Exception($log . "Transaktion nicht gefunden");
                    }

                    $cashboxInfo = $sql->fetch_object();
                    $user = $cashboxInfo->user;
                } else {
                    $user = str_replace($this->settings['prefix'], "", $cust);
                }
                $log .= "Benutzer: $user\n";

                if (!is_numeric($user) || $user < 0 || $db->query("SELECT ID FROM clients WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1")->num_rows != 1) {
                    throw new Exception($log . "Benutzer unbekannt");
                }

                if (!$this->canPay(User::getInstance($user, "ID"))) {
                    throw new Exception("Benutzer nicht berechtigt");
                }

                $fees = $this->getFees($amount);

                $db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($amount) . "' WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1");
                $transactions->insert("paysafecard", $txn_id, $amount, $user, isset($cashboxInfo) ? trim($cashboxInfo->subject) : "", 1);

                $log .= "Guthaben aktualisiert\nTransaktion eingef&uuml;gt\n";

                // Gather user informationen and send a notification email
                $userInfo = $db->query("SELECT ID, firstname, lastname, mail, language FROM clients WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1")->fetch_object();
                $uI = User::getInstance($userInfo->ID, "ID");
                $sendLang = $uI->getLanguage();

                $mtObj = new MailTemplate("Guthabenaufladung");
                $title = $mtObj->getTitle($sendLang);
                $mail = $mtObj->getMail($sendLang, $userInfo->firstname . " " . $userInfo->lastname);
                
                $maq->enqueue([
					"amount" => $cur->infix($nfo->format($amount), $currency),
					"processor" => $this->getLang("name"),
				], $mtObj, $userInfo->mail, $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $userInfo->ID, true, 0, 0, $mtObj->getAttachments($sendLang));

                // Send admin notification
                if (($ntf = AdminNotification::getInstance("IPN-Gutschrift")) !== false) {
                    $ntf->set("amount", $cur->infix($nfo->format($amount), $cur->getBaseCurrency()));
                    $ntf->set("fees", $cur->infix($nfo->format($fees), $cur->getBaseCurrency()));
                    $ntf->set("gateway", $this->getLang("name"));
                    $ntf->set("customer", $userInfo->firstname . " " . $userInfo->lastname);
                    $ntf->set("clink", $raw_cfg['PAGEURL'] . "admin/?p=customers&edit=" . $userInfo->ID);
                    $ntf->send();
                }

                // Invoice fees
                if ($fees > 0) {
                    $uI->invoiceFees($fees);
                    $log .= "Geb&uuml;hren gebucht\n";
                }

                $uI->applyCredit();

                throw new Exception($log . "E-Mail gesendet");
            } else {
                throw new Exception($log . "Zahlung nicht erfolgreich");
            }

        } catch (Exception $ex) {
            $data = $db->real_escape_string(print_r($res, true));
            $log = $db->real_escape_string($ex->getMessage());

            $db->query("INSERT INTO gateway_logs (`data`, `log`, `time`, `gateway`) VALUES ('$data', '$log', " . time() . ", 'paysafecard')");
        }
    }

}

?>