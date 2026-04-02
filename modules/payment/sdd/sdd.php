<?php
// Class for making SEPA direct debit payments

class SEPADirectDebitPG extends PaymentGateway
{
    public static $shortName = "sdd";

    public function getVersion()
    {
        return "1.3";
    }

    public function __construct($language)
    {
        global $db, $CFG;
        parent::__construct(self::$shortName);
        $this->language = $language;

        if (!include (__DIR__ . "/language/$language.php")) {
            throw new ModuleException();
        }

        if (!is_array($addonlang) || !isset($addonlang["NAME"])) {
            throw new ModuleException();
        }

        $this->lang = $addonlang;

        $templates = array(
            "0" => $this->getLang("NOPRENOT"),
        );
        $sql = $db->query("SELECT ID, name FROM email_templates WHERE category = 'Eigene' ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            $templates[$row->ID] = $row->name;
        }

        $default = 0;
        $sql = $db->query("SHOW FULL COLUMNS FROM clients");
        while ($row = $sql->fetch_object()) {
            if ($row->Field == "sepa_limit") {
                $default = $row->Default;
            }
        }
        $default = number_format($default, 2, '.', '');

        $this->options = array(
            "ci" => array("type" => "text", "name" => $this->getLang('ci'), "help" => $this->getLang('ci_hint'), "placeholder" => "DE12ZZZ00001234567"),
            "prenotification" => array("type" => "select", "name" => $this->getLang('prenotification'), "default" => "", "options" => $templates, "help" => $this->getLang('prenotification_hint') . '<br />%ci%, %reference%, %iban%, %bic%, %duedate%, %amount%'),
            "prenotification_days" => array("type" => "text", "name" => $this->getLang('prenotification_days'), "help" => $this->getLang('prenotification_days_hint'), "default" => "14", "placeholder" => "14"),
            "subject" => array("type" => "text", "name" => $this->getLang('subject'), "default" => strtoupper($CFG['PAGENAME']) . " SAGT DANKE", "placeholder" => strtoupper($CFG['PAGENAME']) . " SAGT DANKE"),
            "sender" => array("type" => "text", "name" => $this->getLang('sender'), "default" => $CFG['PAGENAME'], "placeholder" => $CFG['PAGENAME']),
            "iban" => array("type" => "text", "name" => $this->getLang('iban'), "default" => "", "placeholder" => "DE12 3456 7890 1234 5678 90"),
            "bic" => array("type" => "text", "name" => $this->getLang('bic'), "default" => "", "placeholder" => "ABCDEFGHXXX"),
            "minimum" => array("type" => "text", "name" => $this->getLang('minimum'), "default" => "10.00", "placeholder" => "2.50"),
            "deflimit" => array("type" => "text", "name" => $this->getLang('deflimit'), "default" => "100.00", "placeholder" => "100.00", "value" => $default),
            "verification" => array("type" => "select", "name" => $this->getLang('verification'), "default" => "pdf", "options" => ["pdf" => $this->getLang("pdf"), "checkbox" => $this->getLang("checkbox")]),
            "autpay" => array("type" => "select", "name" => $this->getLang('autpay'), "default" => "no", "options" => ["no" => $this->getLang("no"), "yes" => $this->getLang("yes")]),
        );
        $this->log = false;
        $this->no_fees = true;
        $this->cashbox = false;
        $this->payment_handler = true;
    }

    public function setOption($k, $v)
    {
        global $db, $CFG;

        if ($k == "deflimit") {
            $default = doubleval($v);
            return (bool) $db->query("ALTER TABLE clients ALTER sepa_limit SET DEFAULT $default;");
        } else {
            parent::setOption($k, $v);
        }
    }

    public function setupAutoPayment(User $user)
    {
        ob_start();

        if (!empty($_GET['deactivate_sddap'])) {
            $user->cancelAutoPayment();
        }

        if (!empty($_GET['activate_sddap']) && SepaDirectDebit::mandateByClient($user->get()['ID'])) {
            $user->set([
                'auto_payment_provider' => "sdd",
                'auto_payment_credentials' => "",
            ]);
        }

        if ($user->get()['auto_payment_provider'] == "sdd") {
            ?>
			<div class="alert alert-success"><?=$this->getLang('autopay_active');?></div>

			<a href="?automated_gateway=sdd&deactivate_sddap=1" class="btn btn-primary btn-block"><?=$this->getLang('autopay_deactivate');?></a>
			<?php
} else if (SepaDirectDebit::mandateByClient($user->get()['ID'])) {
            ?>
			<div class="alert alert-warning"><?=$this->getLang('autopay_inactive');?></div>

			<a href="?automated_gateway=sdd&activate_sddap=1" class="btn btn-primary btn-block"><?=$this->getLang('autopay_activate');?></a>
			<?php
} else {
            ?>
			<div class="alert alert-warning"><?=$this->getLang('autopay_inactive_nofav');?></div>
			<?php
}

        $res = ob_get_contents();
        ob_end_clean();

        return $res;
    }

    public function makeAutoPayment(User $user, float $amount, bool $force = false)
    {
        global $db, $CFG, $paymentReference;

        if ($user->get()['auto_payment_provider'] != "sdd") {
            return false;
        }

        $id = $user->get()['sepa_fav'];
        $mandate = SepaDirectDebit::mandateByClient($user->get()['ID']);

        if (!$mandate) {
            return false;
        }

        $daily = $db->query("SELECT SUM(amount) AS s FROM client_transactions WHERE user = " . $user->get()['ID'] . " AND subject LIKE 'sdd|%' AND `time` >= " . strtotime("-24 hours"))->fetch_object()->s;
        if ($daily + $amount > $user->get()['sepa_limit'] && !$force) {
            return false;
        }

        return SepaDirectDebit::create($mandate, $amount, $paymentReference ?? "");
    }

    public function getPaymentForm($amount = null)
    {
        global $user, $cur, $CFG, $var;

        $status = SepaDirectDebit::clientMandates($user->get()['ID']);

        ob_start();
        ?>

		<?php
if ($status === "expired") {
            echo '<div class="alert alert-warning" style="text-align: justify;">' . $this->getLang('expired') . '</div>';
        }

        if (false === $status) {
            echo '<div class="alert alert-info" style="text-align: justify; margin-bottom: 0;">' . str_replace(array("%u1", "%u2"), array("<a href=\"{$CFG['PAGEURL']}credit/pay/sdd\">", "</a>"), $this->getLang('NO_MANDATE')) . '</div>';
        } else {
            echo '<p style="text-align: justify;">' . $this->getLang('existing') . ' ' . str_replace(array("%d"), array($this->settings['prenotification_days']), $this->getLang('what')) . '</p>';

            $code = "<p><form method=\"POST\" class=\"form-inline\" action=\"" . $CFG['PAGEURL'] . "credit/pay/sdd\">
					<div class=\"input-group\">";

            $myCur = $var['currencyObj'];

            if (!empty($myCur->getPrefix())) {
                $code .= "<span class=\"input-group-addon\">{$myCur->getPrefix()}</span>";
            }

            $code .= "<input type=\"text\" name=\"amount\" value=\"" . ($amount !== null ? $amount : '') . "\" placeholder=\"{$this->getLang('amount')}\" style=\"max-width:80px\" class=\"form-control\">";

            if (!empty($myCur->getSuffix())) {
                $code .= "<span class=\"input-group-addon\">{$myCur->getSuffix()}</span>";
            }

            $code .= "</div><input type=\"hidden\" name=\"action\" value=\"payment\">
	  				<input type=\"submit\" class=\"btn btn-primary\" value=\"{$this->getLang('submit')}\">
	    			</form></p>";

            echo $code;

            echo '<a href="' . $CFG['PAGEURL'] . 'credit/pay/sdd">' . $this->getLang('manage') . '</a></small></form>';
        }
        ?>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function getPaymentHandler()
    {
        global $tpl, $title, $var, $user, $lang, $nfo, $cur, $db, $CFG, $val, $pars;

        if (!$this->canPay($user)) {
            return;
        }

        $var['sdd_verification'] = $this->settings['verification'];
        $var['sdd_days'] = $this->settings['prenotification_days'];
        $var['sdd_ci'] = $this->settings['ci'];

        if (isset($_POST['amount']) && SepaDirectDebit::clientMandates($user->get()['ID']) === true) {
            $title = $this->getLang('name');
            $tpl = __DIR__ . "/payment.tpl";
            $var['glang'] = $this->getLang();

            $minAmount = doubleval($this->settings['minimum']);

            $amount = $nfo->phpize($_POST['amount']);
            if ((!is_numeric($amount) && !is_double($amount)) || $amount < $minAmount) {
                $title = $lang['ERROR']['TITLE'];
                $tpl = "error";
                $var['error'] = $this->getLang('error');
            }

            $var['accounts'] = array();
            $sql = $db->query("SELECT ID FROM client_sepa WHERE client = " . $user->get()['ID'] . " AND status = 1 ORDER BY ID = " . $user->get()['sepa_fav'] . " DESC, ID DESC");
            while ($row = $sql->fetch_object()) {
                $mandate = SepaDirectDebit::mandate($row->ID);
                if ($mandate->expired()) {
                    continue;
                }

                $var['accounts'][$row->ID] = $mandate->getAccountHolder() . " - " . $mandate->getIBAN() . " - " . $mandate->getBIC();
            }

            if (isset($_POST['account'])) {
                try {
                    if (!array_key_exists($_POST['account'], $var['accounts'])) {
                        throw new Exception($this->getLang('eaccount'));
                    }

                    if (empty($_POST['token']) || $_POST['token'] != $_SESSION['sdd_token']) {
                        throw new Exception($this->getLang('etoken'));
                    }

                    $daily = $db->query("SELECT SUM(amount) AS s FROM client_transactions WHERE user = " . $user->get()['ID'] . " AND subject LIKE 'sdd|%' AND `time` >= " . strtotime("-24 hours"))->fetch_object()->s;
                    if ($daily + $amount > $user->get()['sepa_limit']) {
                        throw new Exception($this->getLang('elimit'));
                    }

                    if (!SepaDirectDebit::create(SepaDirectDebit::mandate($_POST['account']), $amount)) {
                        throw new Exception($this->getLang('etec'));
                    }

                    $user->set(array("credit" => $user->get()['credit'] + $amount));

                    $var['suc'] = 1;
                } catch (Exception $ex) {
                    $var['error'] = $ex->getMessage();
                }
            }

            $var['amount'] = $cur->infix($nfo->format($amount));
            $var['rawamount'] = $nfo->format($amount);
            $var['token'] = $_SESSION['sdd_token'] = md5(uniqid());
        } else {
            $title = $this->getLang('name');
            $tpl = __DIR__ . "/clientarea.tpl";
            $var['glang'] = $glang = $this->getLang();

            $var['accounts'] = array();
            $sql = $db->query("SELECT ID FROM client_sepa WHERE client = " . $user->get()['ID'] . " ORDER BY ID = " . $user->get()['sepa_fav'] . " DESC, ID DESC");
            while ($row = $sql->fetch_object()) {
                $mandate = SepaDirectDebit::mandate($row->ID);
                $var['accounts'][$row->ID] = $mandate;
            }

            if (isset($pars[2]) && $pars[2] == "cancel" && isset($pars[3]) && array_key_exists($pars[3], $var['accounts']) && $var['accounts'][$pars[3]]->getStatus() == 1) {
                $db->query("UPDATE client_sepa SET status = 2 WHERE ID = " . intval($pars[3]));
                $var['suc'] = $glang['DEACTIVATEDSUC'];
                $var['accounts'][$pars[3]] = SepaDirectDebit::mandate($pars[3]);
            }

            if (isset($pars[2]) && $pars[2] == "fav" && isset($pars[3]) && array_key_exists($pars[3], $var['accounts']) && $var['accounts'][$pars[3]]->getStatus() == 1) {
                $user->set(array("sepa_fav" => $pars[3]));
                $var['user']['sepa_fav'] = $pars[3];
            }

            if (isset($pars[2]) && array_key_exists($pars[2], $var['accounts']) && $var['accounts'][$pars[2]]->getStatus() == 0) {
                SepaDirectDebit::mandate($pars[2])->downloadPDF();
            }

            if (isset($_POST['account_holder'])) {
                try {
                    if (empty($_POST['account_holder'])) {
                        throw new Exception($this->getLang("ADDE1"));
                    }

                    if (empty($_POST['iban']) || !$val->iban($_POST['iban'])) {
                        throw new Exception($this->getLang("ADDE2"));
                    }

                    foreach ($var['accounts'] as $a) {
                        if (($a->isActive() || (!$a->expired() && $a->getStatus() == 0)) && strtolower(str_replace(" ", "", $a->getIBAN())) == strtolower(str_replace(" ", "", $_POST['iban']))) {
                            throw new Exception($this->getLang("ADDE5"));
                        }
                    }

                    if (empty($_POST['bic'])) {
                        throw new Exception($this->getLang("ADDE3"));
                    }

                    if (!ctype_alnum($_POST['bic']) || !in_array(strlen($_POST['bic']), array(8, 11))) {
                        throw new Exception($this->getLang('ADDE4'));
                    }

                    $status = 0;
                    if ($this->settings['verification'] == "checkbox") {
                        if (empty($_POST['checkbox'])) {
                            throw new Exception($this->getLang('NEEDCHECK'));
                        }

                        $status = 1;
                    }

                    $sql = $db->prepare("INSERT INTO client_sepa (status, client, iban, bic, account_holder, date) VALUES (?,?,?,?,?,?)");
                    $sql->bind_param("iissss", $status, $a = $user->get()['ID'], $b = strtoupper(str_replace(" ", "", $_POST['iban'])), $c = strtoupper($_POST['bic']), $_POST['account_holder'], $d = date("Y-m-d"));
                    $sql->execute();
                    $new_mid = $db->insert_id;

                    $var['suc'] = str_replace(array("%u1", "%u2"), array('<a href="' . $CFG['PAGEURL'] . 'credit/pay/sdd/' . $new_mid . '" target="_blank">', "</a>"), $this->getLang('ADDOK'));
                    if ($this->settings['verification'] == "checkbox") {
                        $var['suc'] = $this->getLang("ADDOK2");
                    }
                    unset($_POST);

                    $var['accounts'] = array();
                    $sql = $db->query("SELECT ID FROM client_sepa WHERE client = " . $user->get()['ID'] . " ORDER BY ID = " . $user->get()['sepa_fav'] . " DESC, ID DESC");
                    while ($row = $sql->fetch_object()) {
                        $mandate = SepaDirectDebit::mandate($row->ID);
                        $var['accounts'][$row->ID] = $mandate;
                    }

                    if ($this->settings["autpay"] == "yes" && !$user->autoPaymentStatus()) {
                        $user->set([
                            'auto_payment_provider' => "sdd",
                            'auto_payment_credentials' => "",
                        ]);

                        if ($this->settings['verification'] == "checkbox") {
                            $user->set([
                                'sepa_fav' => $new_mid,
                            ]);

                            $user->applyCredit(true);
                            $open = $db->query("SELECT credit FROM clients WHERE ID = " . $user->get()['ID'])->fetch_object()->credit / -1;

                            if ($open > 0) {
                                $user->autoPayment($open);
                            }
                        }
                    }
                } catch (Exception $ex) {
                    $var['err'] = $ex->getMessage();
                }
            }
        }
    }

    public function getIpnHandler()
    {
        return false;
    }
}