<?php
// Class for making cash payments

class CashPG extends PaymentGateway
{
    public static $shortName = "cash";

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
            "address" => array("type" => "textarea", "name" => $this->getLang('address'), "help" => $this->getLang('address_hint')),
            "prefix" => array("type" => "text", "name" => $this->getLang('prefix'), "help" => $this->getLang('prefix_hint'), "default" => $this->getLang('default_prefix')),
            "in_person" => array("type" => "checkbox", "description" => $this->getLang('in_person')),
        );
        $this->log = false;
        $this->no_fees = true;
        $this->cashbox = true;
    }

    public function getPaymentForm($amount = null)
    {
        global $user, $cur;

        $in_person = $this->settings['in_person'] == "true" ? "<br /><br />" . $this->getLang('make_in_person') : "";

        if ($amount > 0) {
            return "<p style=\"text-align: justify;\">" . str_replace(array("%a", "%t"), array($cur->infix($amount), $this->settings['prefix'] . $user->get()['ID']), $this->getLang('intro_amount')) . "<br />
				<center>" . nl2br($this->settings['address']) . "</center><br />
				{$this->getLang('hint')}{$in_person}</p>";
        }

        return "<p style=\"text-align: justify;\">" . str_replace("%t", $this->settings['prefix'] . $user->get()['ID'], $this->getLang('intro')) . "<br />
				<center>" . nl2br($this->settings['address']) . "</center><br />
				{$this->getLang('hint')}{$in_person}</p>";
    }

    public function makeCashboxPayment($hash)
    {
        global $tpl, $nfo, $CFG, $cur, $var;
        $tpl = __DIR__ . "/templates/cashbox.tpl";
        $var['amount'] = $cur->infix($nfo->format($nfo->phpize($_POST['amount'])));

        $in_person = $this->settings['in_person'] == "true" ? "<br /><br />" . $this->getLang('make_in_person') : "";

        $var['instructions'] = "<center>" . nl2br($this->settings['address']) . "</center><br />
				{$this->getLang('hint')}{$in_person}";
        $var['gateLang'] = $this->getLang();
        $var['text'] = $CFG['CASHBOX_PREFIX'] . $hash;
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
