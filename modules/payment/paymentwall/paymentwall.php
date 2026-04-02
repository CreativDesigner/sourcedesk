<?php
// Class for making payments with Paymentwall

class PaymentwallPG extends PaymentGateway
{
    public static $shortName = "paymentwall";

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
            "pk" => array("type" => "text", "name" => $this->getLang('pk')),
            "sk" => array("type" => "text", "name" => $this->getLang('sk')),
        );
        $this->log = true;
        $this->cashbox = true;
        $this->payment_handler = true;
    }

    public function getPaymentForm($amount = null)
    {
        global $cur, $CFG, $nfo, $lang, $var;

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

        $code = "<p><form method=\"POST\" class=\"form-inline\" action=\"" . $CFG['PAGEURL'] . "credit/pay/paymentwall\">
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
    			</form></p>";

        return $code;
    }

    public function getPaymentHandler($ud = array(), $id = "")
    {
        global $var, $user, $CFG, $nfo, $lang, $title, $tpl, $cur;

        if (!$this->canPay($user)) {
            return;
        }

        // Get Paymentwall library
        require_once __DIR__ . "/vendor/paymentwall.php";

        // Set Paymentwall options
        Paymentwall_Config::getInstance()->set(array(
            'api_type' => Paymentwall_Config::API_GOODS,
            'public_key' => $this->settings['pk'],
            'private_key' => $this->settings['sk'],
        ));

        // Init payment
        $widget = new Paymentwall_Widget(
            $id ?: 'User-' . $user->get()['ID'],
            'p10',
            array(
                new Paymentwall_Product(
                    $this->addFees($cur->convertAmount($var['myCurrency'], $nfo->phpize($_POST['amount']), $cur->getBaseCurrency())),
                    $this->addFees($cur->convertAmount($var['myCurrency'], $nfo->phpize($_POST['amount']), $cur->getBaseCurrency())),
                    $cur->getBaseCurrency(),
                    $cur->infix($nfo->format($nfo->phpize($_POST['amount'])), $var['myCurrency']) . ' Guthaben',
                    Paymentwall_Product::TYPE_FIXED
                ),
            ),
            is_array($ud) && count($ud) > 0 ? $ud : array(
                'email' => $user->get()['mail'],
                'customer[firstname]' => $user->get()['firstname'],
                'customer[lastname]' => $user->get()['lastname'],
                'customer[country]' => $user->get()['country_alpha2'],
                'history[registration_email_verified]' => '1',
                'history[registration_date]' => $user->get()['registered'],
            )
        );

        $var['widget'] = $widget->getHtmlCode();
        $var['gateLang'] = $this->getLang();
        $tpl = __DIR__ . "/payment.tpl";
        $title = $this->getLang("NAME");
    }

    public function makeCashboxPayment($hash)
    {
        $this->getPaymentHandler(["customer[firstname]" => $this->getLang("GUEST")], "Cashbox-" . $hash);
    }

    public function getIpnHandler()
    {
        global $CFG, $db, $transactions, $cur, $maq, $raw_cfg, $nfo;

        // Get Paymentwall library
        require_once __DIR__ . "/vendor/paymentwall.php";

        // Set Paymentwall options
        Paymentwall_Config::getInstance()->set(array(
            'api_type' => Paymentwall_Config::API_GOODS,
            'public_key' => $this->settings['pk'],
            'private_key' => $this->settings['sk'],
        ));

        $log = "System gestartet\n";

        try {
            $pingback = new Paymentwall_Pingback($_GET, ip());
            if ($pingback->validate(true/*WHITELIST DISABLED*/)) {
                $log .= "Benachrichtigung ist authentisch\n";

                $userId = $pingback->getUserId(); // User-xxx, Cashbox-xxx
                $paymentId = $pingback->getReferenceId();
                $amount = $_GET['goodsid'];
                $currency = $cur->getBaseCurrency();

                if ($pingback->isDeliverable()) {
                    $log .= "Zahlung ist genehmigt\n";

                    // Check if the payment is not already inserted in the system
                    if (count($transactions->get(array("subject" => "paymentwall|$paymentId"))) > 0) {
                        throw new Exception($log . "Transaktions-ID bereits eingebucht");
                    }

                    // Check if the currency code is correct
                    try {
                        $curObj = new Currency($currency);
                    } catch (CurrencyException $ex) {
                        throw new Exception($log . "Unbekannte W&auml;hrung '" . $currency . "'");
                    }

                    if (substr($userId, 0, 5) == "User-") {
                        $user = substr($userId, 5);
                    } else {
                        $log .= "Cashbox-Transaktion\n";
                        $user = "";

                        $cashboxHash = substr($userId, 8);
                        $sql = $db->query("SELECT * FROM `cashbox` WHERE `hash` = '" . $db->real_escape_string($cashboxHash) . "'");
                        if ($sql->num_rows != 1) {
                            throw new Exception($log . "Transaktion nicht gefunden");
                        }

                        $cashboxInfo = $sql->fetch_object();
                        $user = $cashboxInfo->user;
                    }
                    $log .= "Benutzer: $user\n";

                    // Check if the extracted user exists
                    if (!is_numeric($user) || $user < 0 || $db->query("SELECT ID FROM clients WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1")->num_rows != 1) {
                        throw new Exception($log . "Benutzer unbekannt");
                    }

                    if (!$this->canPay(User::getInstance($user, "ID"))) {
                        throw new Exception("Benutzer nicht berechtigt");
                    }

                    // Get the correct payment amount
                    $payment_amount = $curObj->convertFrom($amount);
                    $fees = $this->getFees($payment_amount);

                    // Update the user credit and insert the transaction into the database
                    $db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($payment_amount) . "' WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1");
                    $transactions->insert("paymentwall", $paymentId, $payment_amount, $user, isset($cashboxInfo) ? trim($cashboxInfo->subject) : "", 1);
                    $log .= "Guthaben aktualisiert\nTransaktion eingef&uuml;gt\n";

                    // Gather user information and send a notification email
                    $userInfo = $db->query("SELECT ID, firstname, lastname, mail FROM clients WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1")->fetch_object();
                    $uI = User::getInstance($userInfo->mail);
                    $sendLang = $uI->getLanguage();

                    $mtObj = new MailTemplate("Guthabenaufladung");
                    $title = $mtObj->getTitle($sendLang);
                    $mail = $mtObj->getMail($sendLang, $userInfo->firstname . " " . $userInfo->lastname);
                    
                    $maq->enqueue([
                        "amount" => $curObj->getPrefix() . $amount . $curObj->getSuffix(),
                        "processor" => $this->getLang("name"),
                    ], $mtObj, $userInfo->mail, $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $userInfo->ID, true, 0, 0, $mtObj->getAttachments($sendLang));

                    // Send admin notification
                    if (($ntf = AdminNotification::getInstance("IPN-Gutschrift")) !== false) {
                        $ntf->set("amount", $cur->infix($nfo->format($payment_amount), $cur->getBaseCurrency()));
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

                    // Throw an exception for log purposes
                    throw new Exception($log . "E-Mail gesendet");
                } else if ($pingback->isCancelable()) {
                    $log .= "Zahlung wurde widerrufen\n";

                    // Check if the payment is not already inserted in the system
                    if (count($transactions->get(array("subject" => "paymentwall|$paymentId"))) <= 0) {
                        throw new Exception($log . "Transaktions-ID nicht gefunden");
                    }

                    // Get the transaction details
                    $t = $transactions->get(array("subject" => "paymentwall|$paymentId"))[0];
                    $t['amount'] -= $this->getFees($t['amount']);

                    // Revert the transaction
                    $db->query("UPDATE clients SET credit = credit - '" . $db->real_escape_string($t['amount']) . "' WHERE ID = '" . $db->real_escape_string($t['user']) . "' LIMIT 1");
                    $transactions->insert("paymentwall", $paymentId, $t['amount'] / -1, $t['user'], $t['cashbox_subject'], 1);
                    throw new Exception($log . "Guthaben zurückgebucht");
                }
            } else {
                throw new Exception($log . "Fehler bei Validierung: " . $pingback->getErrorSummary());
            }
        } catch (Exception $ex) {
            $data = $db->real_escape_string(print_r($_GET, true));
            $log = $db->real_escape_string($ex->getMessage());

            $db->query("INSERT INTO gateway_logs (`data`, `log`, `time`, `gateway`) VALUES ('$data', '$log', " . time() . ", 'paymentwall')");
        }

        echo 'OK';
        exit;
    }
}
