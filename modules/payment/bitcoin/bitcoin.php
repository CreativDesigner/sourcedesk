<?php
// Class for making bitcoin transfers

class BitcoinPG extends PaymentGateway
{
    public static $shortName = "bitcoin";

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
            "address" => array("type" => "text", "name" => $this->getLang('address')),
            "api" => array("type" => "text", "name" => $this->getLang('api')),
            "confirmations" => array("type" => "text", "name" => $this->getLang('confirmations'), "help" => $this->getLang('confirmations_hint'), "default" => "6"),
        );
        $this->log = true;
        $this->payment_handler = true;
        $this->cashbox = true;
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

        $myCur = $var['currencyObj'];

        $code = "<p><form method=\"POST\" class=\"form-inline\" action=\"" . $CFG['PAGEURL'] . "credit/pay/bitcoin\">
				<div class=\"input-group\">";

        if (!empty($myCur->getPrefix())) {
            $code .= "<span class=\"input-group-addon\">{$myCur->getPrefix()}</span>";
        }

        $code .= "<input type=\"text\" name=\"amount\" placeholder=\"{$this->getLang('amount')}\" value=\"" . ($amount !== null ? $amount : '') . "\" style=\"max-width:80px\" class=\"form-control\">";

        if (!empty($myCur->getSuffix())) {
            $code .= "<span class=\"input-group-addon\">{$myCur->getSuffix()}</span>";
        }

        $code .= "</div>
  				<input type=\"submit\" class=\"btn btn-primary\" value=\"{$this->getLang('submit')}$fees\">
    			</form></p>";

        return $code;
    }

    public function getPaymentHandler()
    {
        global $var, $user, $CFG, $nfo, $lang, $cur, $nfo, $title, $tpl;

        if (!$this->canPay($user)) {
            return;
        }

        // Convert the amount into PHP readable number
        if (isset($_POST['amount'])) {
            $amount = $this->addFees($cur->convertBack($nfo->phpize($_POST['amount'])));
        }

        try {
            // Amount should be greater than zero
            if (!isset($amount) || !is_numeric($amount) || $amount <= 0) {
                throw new Exception($this->getLang('invalid_amount'));
            }

            // Convert the amount into Bitcoins
            $to_btc = file_get_contents("https://blockchain.info/tobtc?currency=" . $cur->getBaseCurrency() . "&value=" . $this->addFees($cur->convertBack($nfo->phpize($_POST['amount']))));

            // Check if the connection was successful
            if ($to_btc === false) {
                throw new Exception($this->getLang('unreachable'));
            }

            // Check if the Bitcoin amount is greater than 0.01
            if ($to_btc < 0.001) {
                // Get the minimum fiat amount
                $oneeur = file_get_contents("https://blockchain.info/tobtc?currency=" . $var['myCurrency'] . "&value=1");
                $mineur = 0.001 / $oneeur + 0.01;
                throw new Exception(str_replace(array("%m", "%e"), array($nfo->format(0.001, 2, 0), $cur->infix($nfo->format($mineur, 2, 0))), $this->getLang('toLow')));
            }

            // Try to get the bitcoin address
            $ts = time();
            $callback_url = urlencode($CFG['PAGEURL'] . "ipn/bitcoin?id=" . $user->get()['ID'] . "&ts=$ts&h=" . hash('sha512', $CFG['HASH'] . $ts . $user->get()['ID']));
            $receiving_address = $this->settings['address'];

            $ch = curl_init("https://api.blockchain.info/v2/receive?xpub=" . $receiving_address . "&callback=$callback_url&key=" . $this->settings['api']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);

            if ($result === false) {
                throw new Exception($this->getLang('unreachable'));
            }

            $json = json_decode($result);
            $var['address'] = $json->address;
            $var['qrcode'] = "https://chart.googleapis.com/chart?cht=qr&chs=150x150&chl=" . $var['address'] . "&chld=L|0";

            $var['fiat_amount'] = $cur->infix($nfo->format($this->addFees($nfo->phpize($_POST['amount']), true), 2, 1));
            $var['btc_amount'] = $nfo->format($to_btc, 8, 1);
            $var['btc_amount_raw'] = $to_btc;
            $var['confirmations'] = $this->settings['confirmations'];
            $var['gateLang'] = $this->getLang();

            $title = $this->getLang('title');
            $tpl = realpath(__DIR__ . "/templates/summary.tpl");
        } catch (Exception $ex) {
            $title = $lang['ERROR']['TITLE'];
            $tpl = "error";
            $var['error'] = $ex->getMessage();
        }
    }

    public function makeCashboxPayment($hash)
    {
        global $var, $user, $CFG, $nfo, $lang, $cur, $nfo, $title, $tpl;

        // Convert the amount into PHP readable number
        if (isset($_POST['amount'])) {
            $amount = $this->addFees($cur->convertBack($nfo->phpize($_POST['amount']), true));
        }

        try {
            // Amount should be greater than zero
            if (!isset($amount) || !is_numeric($amount) || $amount <= 0) {
                throw new Exception($this->getLang('invalidAmount'));
            }

            // Convert the amount into Bitcoins
            $to_btc = file_get_contents("https://blockchain.info/tobtc?currency=" . $cur->getBaseCurrency() . "&value=" . $this->addFees($cur->convertBack($nfo->phpize($_POST['amount']))));

            // Check if the connection was successful
            if ($to_btc === false) {
                throw new Exception($this->getLang('unreachable'));
            }

            // Check if the Bitcoin amount is greater than 0.01
            if ($to_btc < 0.01) {
                // Get the minimum fiat amount
                $oneeur = file_get_contents("https://blockchain.info/tobtc?currency=" . $var['myCurrency'] . "&value=1");
                $mineur = 0.01 / $oneeur + 0.01;
                throw new Exception(str_replace(array("%m", "%e"), array($nfo->format(0.01, 2, 0), $cur->infix($nfo->format($mineur, 2, 0))), $this->getLang('toLow')));
            }

            // Try to get the bitcoin address
            $ts = time();
            $callback_url = urlencode($CFG['PAGEURL'] . "ipn/bitcoin?cashbox=" . $hash . "&ts=$ts&h=" . hash('sha512', $CFG['HASH'] . $ts . $hash));
            $callback_url = urlencode("https://sourceway.de/ipn.php");
            $receiving_address = $this->settings['address'];

            $ch = curl_init("https://api.blockchain.info/v2/receive?xpub=" . $receiving_address . "&callback=$callback_url&key=" . $this->settings['api']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);

            if ($result === false) {
                throw new Exception($this->getLang('unreachable'));
            }

            $json = json_decode($result);
            $var['address'] = $json->address;
            $var['qrcode'] = "https://chart.googleapis.com/chart?cht=qr&chs=150x150&chl=" . $var['address'] . "&chld=L|0";

            $var['fiat_amount'] = $cur->infix($nfo->format($this->addFees($nfo->phpize($_POST['amount']), true), 2, 1));
            $var['btc_amount'] = $nfo->format($to_btc, 8, 1);
            $var['btc_amount_raw'] = $to_btc;
            $var['confirmations'] = $this->settings['confirmations'];
            $var['gateLang'] = $this->getLang();

            $tpl = realpath(__DIR__ . "/templates/summary.tpl");
        } catch (Exception $ex) {
            $title = $lang['ERROR']['TITLE'];
            $tpl = "error";
            $var['error'] = $ex->getMessage();
        }
    }

    public function getIpnHandler()
    {
        global $CFG, $db, $transactions, $cur, $nfo, $maq, $raw_cfg;

        $log = "System gestartet\n";

        try {
            // Check if the timestamp is accepted
            if (!isset($_GET['ts']) || $_GET['ts'] < time() - 86400) {
                // Print *ok* because this is a permanent error; the payment processor should not call this URI again
                echo "*ok*";
                throw new Exception($log . "Inkorrekter Timestamp");
            }

            // Check if the user is passed
            $cashbox = false;
            if ((!isset($_GET['id']) || !is_numeric($_GET['id'])) && (!isset($_GET['cashbox']) || !$CFG['CASHBOX_ACTIVE'])) {
                // Print *ok* because this is a permanent error; the payment processor should not call this URI again
                echo "*ok*";
                throw new Exception($log . "Benutzer inkorrekt");
            } else if (isset($_GET['cashbox']) && $CFG['CASHBOX_ACTIVE']) {
                $log .= "Cashbox-Transaktion\n";
                $cashbox = true;
            }

            // Check if the callback hash is correct
            $hashparam = $cashbox ? $_GET['cashbox'] : $_GET['id'];
            if ($_GET['h'] != hash('sha512', $CFG['HASH'] . $_GET['ts'] . $hashparam)) {
                // Print *ok* because this is a permanent error; the payment processor should not call this URI again
                echo "*ok*";
                throw new Exception($log . "Authentifizierung fehlgeschlagen");
            }

            // Check if there are enough confirmations for this payment
            if (!isset($_GET['confirmations']) || $_GET['confirmations'] < $this->settings['confirmations']) {
                throw new Exception($log . "Best&auml;tigungen nicht ausreichend");
            }

            $log .= "Best&auml;tigungen ausreichend\n";

            // Check if the transaction is already in the system
            if (count($transactions->get(array("subject" => "bitcoin|" . $_GET['transaction_hash']))) > 0) {
                // Print *ok* because this is a permanent error; the payment processor should not call this URI again
                echo "*ok*";
                throw new Exception($log . "Transaktions-ID bereits eingebucht");
            }

            // Check if the minimum payment amount is reached
            if ($_GET['value'] / 100000000 < 0.01) {
                // Print *ok* because this is a permanent error; the payment processor should not call this URI again
                echo "*ok*";
                throw new Exception($log . "Betrag zu gering");
            }

            // Check for Cashbox transaction
            if ($cashbox) {
                $sql = $db->query("SELECT * FROM `cashbox` WHERE `hash` = '" . $db->real_escape_string($_GET['cashbox']) . "'");
                if ($sql->num_rows != 1) {
                    throw new Exception($log . "Transaktion nicht gefunden");
                }

                $cashboxInfo = $sql->fetch_object();
                $_GET['id'] = $cashboxInfo->user;
            }

            // Check if the user exists
            if (!isset($_GET['id']) || $db->query("SELECT ID FROM clients WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1")->num_rows != 1) {
                // Print *ok* because this is a permanent error; the payment processor should not call this URI again
                echo "*ok*";
                throw new Exception($log . "Benutzer unbekannt (# " . $db->real_escape_string($_GET['id']) . ")");
            }

            if (!$this->canPay(User::getInstance($_GET['id'], "ID"))) {
                throw new Exception("Benutzer nicht berechtigt");
            }

            // Get the amount in Bitcoins and calculate how much this is in the base currency
            $btc_amount = (double) $_GET['value'] / 100000000;
            $oneeur = file_get_contents("https://blockchain.info/tobtc?currency=" . $cur->getBaseCurrency() . "&value=1");
            $payment_amount = $btc_amount / $oneeur;
            $fees = $this->getFees($payment_amount);

            // Update the user credit and insert the transaction in the database
            $db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($payment_amount) . "' WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1");
            $userInfo = $db->query("SELECT ID, firstname, lastname, mail, language, currency FROM clients WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1")->fetch_object();
            $transactions->insert("bitcoin", $_GET['transaction_hash'], $payment_amount, $userInfo->ID, $cashbox ? trim($cashboxInfo->subject) : "", 1);

            $log .= "Guthaben aktualisiert\nTransaktion eingef&uuml;gt\n";

            // Send a notification email
            $uI = User::getInstance($userInfo->ID, "ID");
            $sendLang = $uI->getLanguage();
            $userCurrency = $uI->getCurrency();

            try {
                $curObj = new Currency($userCurrency);
            } catch (CurrencyException $ex) {
                if ($userCurrency == $cur->getBaseCurrency()) {
                    throw new Exception($log . "W&auml;hrungsfehler");
                }

                $userCurrency = $cur->getBaseCurrency();
            }

            $mtObj = new MailTemplate("Guthabenaufladung");
            $title = $mtObj->getTitle($sendLang);
            $mail = $mtObj->getMail($sendLang, $userInfo->firstname . " " . $userInfo->lastname);
            
            $maq->enqueue([
                "amount" => $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $payment_amount, $userCurrency)), $userCurrency),
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

            // Throw an exception for insert the log into the database
            throw new Exception($log . "E-Mail gesendet");
        } catch (Exception $ex) {
            // Insert the log until this moment into the database
            unset($_GET['h']);
            unset($_GET['p']);
            $_GET['userid'] = $_GET['id'];
            $_GET['btc'] = $_GET['value'] / 100000000;
            unset($_GET['id']);
            $data = print_r($_GET, true);

            $log = $db->real_escape_string($ex->getMessage());

            $db->query("INSERT INTO gateway_logs (`data`, `log`, `time`, `gateway`) VALUES ('$data', '$log', " . time() . ", 'bitcoin')");
        }
    }

}
