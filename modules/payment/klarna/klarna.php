<?php
// Class for paying with Klarna

class KlarnaPG extends PaymentGateway
{
    public static $shortName = "klarna";

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
            "username" => array("type" => "text", "name" => $this->getLang('username')),
            "password" => array("type" => "text", "name" => $this->getLang('password')),
        );
        $this->log = true;
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

        $code = "<p><form method=\"POST\" class=\"form-inline\" action=\"" . $CFG['PAGEURL'] . "credit/pay/klarna\">
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

    public function getPaymentHandler()
    {
        global $var, $user, $CFG, $nfo, $lang, $title, $tpl;

        if (!$this->canPay($user)) {
            return;
        }

        if (!empty($_GET['oid'])) {
            if ($this->getIpnHandler()) {
                header("Location: " . $CFG['PAGEURL'] . "credit?okay");
            } else {
                header("Location: " . $CFG['PAGEURL'] . "credit?cancel");
            }
            exit;
        }

        $api = Klarna\Rest\Transport\Connector::create(
            $this->settings["username"],
            $this->settings["password"],
            Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL
        );

        $order = [
            "order_id" => $oid = Security::generatePassword(24, true, "ld"),
            "purchase_country" => $user->get()['country_alpha2'],
            "purchase_currency" => $var['myCurrency'],
            "locale" => array_shift($lang['LANG_CODES']),
            "order_amount" => intval(strval($this->addFees($nfo->phpize($_POST['amount']), true) * 100)),
            "order_tax_amount" => "0",
            "order_lines" => [
                [
                    "type" => "digital",
                    "name" => $this->getLang("subject"),
                    "reference" => $user->get()['ID'],
                    "quantity" => "1",
                    "unit_price" => intval(strval($this->addFees($nfo->phpize($_POST['amount']), true) * 100)),
                    "tax_rate" => "0",
                    "total_amount" => intval(strval($this->addFees($nfo->phpize($_POST['amount']), true) * 100)),
                    "total_tax_amount" => "0",
                ],
            ],
            "billing_address" => [
                "organization_name" => $user->get()['company'],
                "given_name" => $user->get()['firstname'],
                "family_name" => $user->get()['lastname'],
                "title" => $user->get()['salutation'] == "FEMALE" ? "Frau" : "Herr",
                "email" => $user->get()['mail'],
                "postal_code" => $user->get()['postcode'],
                "street_address" => $user->get()['street'] . " " . $user->get()['street_number'],
                "city" => $user->get()['city'],
                "country" => $user->get()['country_alpha2'],
            ],
            "merchant_urls" => [
                "terms" => $CFG['PAGEURL'] . "terms",
                "checkout" => $CFG['PAGEURL'] . "credit/pay/klarna?oid=" . $oid . "&cid=" . $user->get()['ID'] . "&amount=" . $this->addFees($nfo->phpize($_POST['amount']), true) * 100 . "&currency=" . $var['myCurrency'],
                "confirmation" => $CFG['PAGEURL'] . "credit/pay/klarna?oid=" . $oid . "&cid=" . $user->get()['ID'] . "&amount=" . $this->addFees($nfo->phpize($_POST['amount']), true) * 100 . "&currency=" . $var['myCurrency'],
                "push" => $CFG['PAGEURL'] . "ipn/klarna?oid=" . $oid . "&cid=" . $user->get()['ID'] . "&amount=" . $this->addFees($nfo->phpize($_POST['amount']), true) * 100 . "&currency=" . $var['myCurrency'],
                "notification" => $CFG['PAGEURL'] . "ipn/klarna?oid=" . $oid . "&cid=" . $user->get()['ID'] . "&amount=" . $this->addFees($nfo->phpize($_POST['amount']), true) * 100 . "&currency=" . $var['myCurrency'],
            ],
        ];

        try {
            $checkout = new Klarna\Rest\Checkout\Order($api);
            $checkout->create($order);

            if (empty($_SESSION['klarna_map']) || !is_array($_SESSION['klarna_map'])) {
                $_SESSION['klarna_map'] = [
                    $oid => $checkout["order_id"],
                ];
            } else {
                $_SESSION['klarna_map'][$oid] = $checkout["order_id"];
            }

            $tpl = __DIR__ . "/templates/payment.tpl";
            $var['widget'] = $checkout['html_snippet'];
            $var['gateLang'] = $this->getLang();
            $title = $this->getLang("name");
        } catch (Exception $e) {
            $title = $lang['ERROR']['TITLE'];
            $tpl = "error";
            $var['error'] = $e->getMessage();
        }
    }

    public function getIpnHandler()
    {
        global $CFG, $db, $transactions, $cur, $maq, $raw_cfg, $nfo;

        $log = "System gestartet\n";

        try {
            $oid = $_GET['oid'] ?? "";
            $cid = $_GET['cid'] ?? "";
            $amount = ($_GET['amount'] ?? 0) / 100;
            $currency = $_GET['currency'] ?? "";

            if (empty($oid) || empty($cid) || empty($amount) || empty($currency)) {
                throw new Exception($log . "Parameter unvollständig");
            }

            $api = Klarna\Rest\Transport\Connector::create(
                $this->settings["username"],
                $this->settings["password"],
                Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL
            );

            if (is_array($_SESSION['klarna_map']) && array_key_exists($oid, $_SESSION['klarna_map'])) {
                $oid = $_SESSION['klarna_map'][$oid];
            }

            $order = new Klarna\Rest\OrderManagement\Order($api, $oid);

            if (count($transactions->get(array("subject" => "klarna|$oid"))) > 0) {
                throw new Exception($log . "Transaktions-ID bereits eingebucht");
            }

            try {
                $curObj = new Currency($currency);
            } catch (CurrencyException $ex) {
                throw new Exception($log . "Unbekannte W&auml;hrung '" . $currency . "'");
            }

            $log .= "Benutzer: $cid\n";

            if (!is_numeric($cid) || $cid < 0 || $db->query("SELECT ID FROM clients WHERE ID = '" . $db->real_escape_string($cid) . "' LIMIT 1")->num_rows != 1) {
                throw new Exception($log . "Benutzer unbekannt");
            }

            if (!$this->canPay(User::getInstance($cid, "ID"))) {
                throw new Exception($log . "Benutzer nicht berechtigt");
            }

            $order->createCapture([
                "captured_amount" => $amount * 100,
                "description" => $this->getLang("credit_applied"),
            ]);

            $payment_amount = $payment_amount_orig = $amount;
            $payment_amount = $curObj->convertFrom($payment_amount);
            $fees = $this->getFees($payment_amount);

            // Update the user credit and insert the transaction into the database
            $db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($payment_amount) . "' WHERE ID = '" . $db->real_escape_string($cid) . "' LIMIT 1");
            $transactions->insert("klarna", $oid, $payment_amount, $cid, "", 1);

            $log .= "Guthaben aktualisiert\nTransaktion eingef&uuml;gt\n";

            // Gather user information and send a notification email
            $userInfo = $db->query("SELECT ID, firstname, lastname, mail FROM clients WHERE ID = '" . $db->real_escape_string($cid) . "' LIMIT 1")->fetch_object();
            $uI = User::getInstance($userInfo->mail);
            $sendLang = $uI->getLanguage();

            $mtObj = new MailTemplate("Guthabenaufladung");
            $title = $mtObj->getTitle($sendLang);
            $mail = $mtObj->getMail($sendLang, $userInfo->firstname . " " . $userInfo->lastname);
            
            $maq->enqueue([
                "amount" => $curObj->getPrefix() . $payment_amount_orig . $curObj->getSuffix(),
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
            $data = print_r(array_map([$db, "real_escape_string"], $_GET), true);
            $log = $db->real_escape_string($log . "E-Mail gesendet");

            $db->query("INSERT INTO gateway_logs (`data`, `log`, `time`, `gateway`) VALUES ('$data', '$log', " . time() . ", 'klarna')");
            return true;
        } catch (Exception $ex) {
            // Write the log into the database
            $data = print_r(array_map([$db, "real_escape_string"], $_GET), true);
            $log = $db->real_escape_string($ex->getMessage());

            $db->query("INSERT INTO gateway_logs (`data`, `log`, `time`, `gateway`) VALUES ('$data', '$log', " . time() . ", 'klarna')");
            return false;
        }
    }

}
