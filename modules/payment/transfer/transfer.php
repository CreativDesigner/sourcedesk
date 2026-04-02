<?php
// Class for making normal bank transfers

class TransferPG extends PaymentGateway
{
    public static $shortName = "transfer";

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
            "account_holder" => array("type" => "text", "name" => $this->getLang('account_holder')),
            "iban" => array("type" => "text", "name" => $this->getLang('iban')),
            "bic" => array("type" => "text", "name" => $this->getLang('bic')),
            "bank" => array("type" => "text", "name" => $this->getLang('bank')),
            "prefix" => array("type" => "text", "name" => $this->getLang('prefix'), "help" => $this->getLang('prefix_hint'), "default" => $this->getLang('default_prefix')),
            "code_gateway" => array("type" => "checkbox", "description" => $this->getLang("code_gateway"), "default" => false),
            "code_invoice" => array("type" => "checkbox", "description" => $this->getLang("code_invoice"), "default" => false),
        );
        $this->log = false;
        $this->no_fees = true;
        $this->cashbox = true;
    }

    public function getPaymentForm($amount = null)
    {
        global $user, $cur, $nfo;

        $qr = "";
        if ($this->settings['code_gateway'] === "true" && file_exists(__DIR__ . "/../../../lib/phpqrcode/phpqrcode.php")) {
            require_once __DIR__ . "/../../../lib/phpqrcode/phpqrcode.php";
            $qrContent = "bank://singlepaymentsepa?name=" . urlencode(strtoupper($this->settings['account_holder'])) . "&reason=" . $this->settings['prefix'] . $user->get()['ID'] . "&iban=" . str_replace(" ", "", $this->settings['iban']) . "&bic=" . str_replace(" ", "", $this->settings['bic']);
            if ($amount > 0) {
                $qrContent .= "&amount=" . $nfo->phpize($amount);
            }

            ob_start();
            QRcode::png($qrContent, null);
            $qrCode = base64_encode(ob_get_contents());
            ob_end_clean();

            $qr = "<img src='data:image/png;base64,$qrCode' alt='$qrContent' title='$qrContent' style='float: right;' />";
        }

        return "<p>{$this->getLang('intro')}$qr<br /><br />
				{$this->getLang('account_holder')}: {$this->settings['account_holder']}<br/>
				{$this->getLang('iban')}: {$this->settings['iban']}<br />
				{$this->getLang('bic')}: {$this->settings['bic']}<br />
				{$this->getLang('bank')}: {$this->settings['bank']}<br />" . ($amount > 0 ? "{$this->getLang('amount')}: " . $cur->infix($amount) . "<br />" : "") . "
				{$this->getLang('subject')}: {$this->settings['prefix']}{$user->get()['ID']}<br /><br />
				<b>{$this->getLang('subject_hint')}</b></p>";
    }

    public function makeCashboxPayment($hash)
    {
        global $tpl, $nfo, $CFG, $cur, $var;
        $tpl = __DIR__ . "/templates/cashbox.tpl";
        $var['amount'] = $cur->infix($nfo->format($nfo->phpize($_POST['amount'])));

        if ($this->settings['code_gateway'] === "true" && file_exists(__DIR__ . "/../../../lib/phpqrcode/phpqrcode.php")) {
            require_once __DIR__ . "/../../../lib/phpqrcode/phpqrcode.php";
            $qrContent = "bank://singlepaymentsepa?name=" . urlencode(strtoupper($this->settings['account_holder'])) . "&reason=" . $CFG['CASHBOX_PREFIX'] . $hash . "&iban=" . str_replace(" ", "", $this->settings['iban']) . "&bic=" . str_replace(" ", "", $this->settings['bic']) . "&amount=" . urlencode($nfo->phpize($_POST['amount']));

            ob_start();
            QRcode::png($qrContent, null);
            $qrCode = base64_encode(ob_get_contents());
            ob_end_clean();

            $var['qr'] = "<img src='data:image/png;base64,$qrCode' alt='$qrContent' title='$qrContent' style='float: right;' />";
        }

        $var['instructions'] = "{$this->getLang('account_holder')}: {$this->settings['account_holder']}<br/>
								{$this->getLang('iban')}: {$this->settings['iban']}<br />
								{$this->getLang('bic')}: {$this->settings['bic']}<br />
								{$this->getLang('bank')}: {$this->settings['bank']}<br />
								{$this->getLang('subject')}: " . $CFG['CASHBOX_PREFIX'] . $hash . "<br /><br />
								<b>{$this->getLang('subject_hint')}</b>";
        $var['gateLang'] = $this->getLang();
    }

    public function getPaymentHandler()
    {
        return false;
    }

    public function getIpnHandler()
    {
        return false;
    }

}
