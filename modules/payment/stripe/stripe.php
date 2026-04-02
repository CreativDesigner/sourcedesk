<?php
// Class for making stripe payments (CC)

class StripePG extends PaymentGateway
{
    public static $shortName = "stripe";

    public function getVersion()
    {
        return "1.1";
    }

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
            "public_key" => array("type" => "text", "name" => $this->getLang('public_key')),
            "private_key" => array("type" => "text", "name" => $this->getLang('private_key')),
        );
        $this->log = true;
        $this->payment_handler = true;
        $this->cashbox = true;
    }

    public function makePayment()
    {
        global $db, $user, $CFG, $transactions, $var, $nfo, $cur, $raw_cfg, $maq;

        if (!$this->canPay($user)) {
            return;
        }

        if ($this->isActive() && in_array("okay", array_keys($_GET))) {
            require __DIR__ . "/lib/init.php";
            \Stripe\Stripe::setApiKey($this->settings['private_key']);

            try {
                $events = \Stripe\Event::all([
                    'type' => 'checkout.session.completed',
                    'created' => [
                        // Check for events created in the last 24 hours.
                        'gte' => time() - 24 * 60 * 60,
                    ],
                ]);
            } catch (Exception $ex) {
                return;
            }

            foreach ($events->autoPagingIterator() as $event) {
                $session = $event->data->object;

                $tid = $session->id;

                // Check if the payment is not already inserted in the system
                if (count($transactions->get(array("subject" => "stripe|$tid"))) > 0) {
                    continue;
                }

                // Check if the currency code is correct
                try {
                    $curObj = new Currency($session->display_items[0]->currency);
                } catch (CurrencyException $ex) {
                    continue;
                }

                // Get payment subject and try to extract the user ID
                $user2 = $session->display_items[0]->custom->description;

                // Check if the extracted user exists
                if (!is_numeric($user2) || $user2 < 0 || $db->query("SELECT ID FROM clients WHERE ID = '" . $db->real_escape_string($user2) . "' LIMIT 1")->num_rows != 1) {
                    continue;
                }

                if (!$this->canPay(User::getInstance($user2, "ID"))) {
                    continue;
                }

                // Get the payment amount (deduct any refunded amount for security reasons)
                $payment_amount_orig = $session->display_items[0]->amount / 100;
                $payment_amount = $curObj->convertFrom($payment_amount_orig);
                $fees = $this->getFees($payment_amount);

                // Update the user credit and insert the transaction into the database
                $db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($payment_amount) . "' WHERE ID = '" . $db->real_escape_string($user2) . "' LIMIT 1");
                $transactions->insert("stripe", $tid, $payment_amount, $user2, "", 1);

                // Gather user information and send a notification email
                $userInfo = $db->query("SELECT ID, firstname, lastname, mail FROM clients WHERE ID = '" . $db->real_escape_string($user2) . "' LIMIT 1")->fetch_object();
                $uI = User::getInstance($userInfo->mail);
                $sendLang = $uI->getLanguage();

                $mtObj = new MailTemplate("Guthabenaufladung");
                $title = $mtObj->getTitle($sendLang);
                $mail = $mtObj->getMail($sendLang, $userInfo->firstname . " " . $userInfo->lastname);

                $maq->enqueue([
                    "amount" => $curObj->getPrefix() . $nfo->format($payment_amount_orig) . $curObj->getSuffix(),
                    "processor" => $this->getLang("frontend_name"),
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
                }

                $uI->applyCredit();
            }
        }
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

        $code = "<p><form method=\"POST\" class=\"form-inline\" action=\"" . $CFG['PAGEURL'] . "credit/pay/stripe\">
				<div class=\"input-group\">";

        $myCur = $var['currencyObj'];

        if (!empty($myCur->getPrefix())) {
            $code .= "<span class=\"input-group-addon\">{$myCur->getPrefix()}</span>";
        }

        $code .= "<input type=\"text\" name=\"amount\" value=\"" . ($amount !== null ? $nfo->format($amount) : '') . "\" placeholder=\"{$this->getLang('amount')}\" style=\"max-width:80px\" class=\"form-control\">";

        if (!empty($myCur->getSuffix())) {
            $code .= "<span class=\"input-group-addon\">{$myCur->getSuffix()}</span>";
        }

        $code .= "</div>
  				<input type=\"submit\" class=\"btn btn-primary\" value=\"{$this->getLang('pay')}" . $fees . "\">
    			</form></p>";

        return $code;
    }

    public function getPaymentHandler()
    {
        global $var, $user, $CFG, $nfo, $lang, $title, $tpl, $maq;

        if (!$this->canPay($user)) {
            return;
        }

        require __DIR__ . "/lib/init.php";
        \Stripe\Stripe::setApiKey($this->settings['private_key']);

        try {
            $session = \Stripe\Checkout\Session::create(array(
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'name' => $CFG['PAGENAME'],
                    'description' => $user->get()['ID'],
                    'images' => [],
                    'amount' => max(50, $this->addFees($nfo->phpize($_POST['amount']), true) * 100),
                    'currency' => $var['myCurrency'],
                    'quantity' => 1,
                ]],
                'success_url' => $CFG['PAGEURL'] . 'credit?okay',
                'cancel_url' => $CFG['PAGEURL'] . 'credit?cancel',
            ));
        } catch (Exception $ex) {
            return false;
        }

        $session = $session->id;

        ?>
		<script src="https://js.stripe.com/v3/"></script>
		<script>
		var stripe = new Stripe("<?=$this->settings['public_key'];?>");

		stripe.redirectToCheckout({
			sessionId: '<?=$session;?>'
		}).then(function(r) {});
		</script>
		<?php

        exit;
    }

    public function getIpnHandler()
    {
        return false;
    }

    public function getJavaScript()
    {
        return ["https://js.stripe.com/v3/"];
    }

}