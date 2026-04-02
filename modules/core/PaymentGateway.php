<?php
// Abstract class for payment gateways

abstract class PaymentGateway
{
    protected $name;
    protected $lang;
    protected $settings;
    protected $options;
    protected $log = false;
    protected $language;
    protected $global;
    protected $payment_handler = false;
    protected $cashbox = false;
    protected $refunds = false;

    public function __construct($name)
    {
        global $gateways;

        $this->name = $name;
        $this->settings = $gateways->getSettings($name);
    }

    public function feesAllowed()
    {
        return !(isset($this->no_fees) && $this->no_fees);
    }

    public function canSee($user)
    {
        if (!is_object($user) || !($user instanceof User) || !$this->isActive()) {
            return false;
        }

        $verification = array_key_exists("system_verification", $this->settings) ? intval($this->settings["system_verification"]) : 0;
        $scoring = array_key_exists("system_scoring_show", $this->settings) ? intval($this->settings["system_scoring_show"]) : 0;

        if ($verification > 1 && !$user->get()['verified']) {
            return false;
        }

        if ($scoring > $user->getScore()) {
            return false;
        }

        $current = explode(",", array_key_exists("allowed_cgroups", $this->settings) ? $this->settings['allowed_cgroups'] : "");
        $current = array_filter($current, function ($v) {return $v !== '';});

        if (count($current) && !in_array($user->get()['cgroup'], $current)) {
            return false;
        }

        return true;
    }

    public function canPay($user)
    {
        if (!$this->canSee($user)) {
            return false;
        }

        $verification = array_key_exists("system_verification", $this->settings) ? intval($this->settings["system_verification"]) : 0;
        $scoring = array_key_exists("system_scoring_pay", $this->settings) ? intval($this->settings["system_scoring_pay"]) : 0;

        if ($verification > 0 && !$user->get()['verified']) {
            return false;
        }

        if ($scoring > $user->getScore()) {
            return false;
        }

        return true;
    }

    public function activate()
    {
        global $db, $CFG;

        $activeGateways = unserialize($CFG['ACTIVE_GATEWAYS']);
        if (in_array($this->name, $activeGateways)) {
            return false;
        }

        if (!$activeGateways || !is_array($activeGateways)) {
            $activeGateways = array();
        }

        array_push($activeGateways, $this->name);
        $CFG['ACTIVE_GATEWAYS'] = $activeGateways = serialize($activeGateways);
        $res = $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($activeGateways) . "' WHERE `key` = 'active_gateways' LIMIT 1");

        $this->options["order"] = array("type" => "order", "default" => "0");
        $this->options["fix"] = array("type" => "fees", "default" => "0");
        $this->options["percent"] = array("type" => "fees", "default" => "0");
        $this->options["excl"] = array("type" => "fees", "default" => "0");
        if (is_array($this->options)) {
            foreach ($this->options as $k => $info) {
                $db->query("INSERT INTO gateway_settings (`gateway`, `setting`, `value`) VALUES ('" . $db->real_escape_string($this->name) . "', '" . $db->real_escape_string($k) . "', '" . (isset($info['default']) ? encrypt($db->real_escape_string($info['default'])) : "") . "')");
            }
        }

        $this->__construct($this->language);
        return (bool) $res;
    }

    public function addFees($amount, $curCur = false)
    {
        global $var;

        if ($this->settings["excl"] == 1) {
            $fix = $this->settings['fix'];
            if ($curCur && isset($var['myCurrency'])) {
                try {
                    $curObj = new Currency($var['myCurrency']);
                    $fix = $curObj->convertBack($fix);
                } catch (CurrencyException $ex) {}
            }

            $amount += ($amount / 100) * $this->settings['percent'];
            $amount += $fix;
        }
        return ceil($amount * 100) / 100;
    }

    public function deductFees($amount)
    {
        $amount -= $this->settings['fix'];
        $amount = ceil(($amount * 100) / (100 + $this->settings['percent']) * 100) / 100;
        return $amount;
    }

    public function getFees($amount)
    {
        return ($this->deductFees($amount) - $amount) / -1;
    }

    public function getFeeString()
    {
        global $cur, $nfo;
        if (empty($this->settings['percent']) && empty($this->settings['fix'])) {
            return "";
        }

        if (empty($this->settings['fix'])) {
            return $nfo->format($this->settings['percent'], 2) . "%";
        }

        if (empty($this->settings['percent'])) {
            return $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $this->settings['fix']), 2));
        }

        return $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $this->settings['fix']), 2)) . " + " . $nfo->format($this->settings['percent'], 2) . "%";
    }

    public function deactivate()
    {
        global $db, $CFG;

        $activeGateways = unserialize($CFG['ACTIVE_GATEWAYS']);
        if ($activeGateways !== false && is_array($activeGateways)) {
            if (!in_array($this->name, $activeGateways)) {
                return false;
            }

            foreach ($activeGateways as $k => $v) {
                if (trim($v) == $this->name) {
                    unset($activeGateways[$k]);
                }
            }

            $CFG['ACTIVE_GATEWAYS'] = $activeGateways = serialize($activeGateways);
            $res = $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($activeGateways) . "' WHERE `key` = 'active_gateways' LIMIT 1");
        }

        $db->query("DELETE FROM gateway_settings WHERE `gateway` = '" . $db->real_escape_string($this->name) . "'");
        $this->__construct($this->language);
        return (bool) $res;
    }

    final public function isActive()
    {
        global $db, $CFG;

        if ($this instanceof UnallowedGateway) {
            return true;
        }

        $activeGateways = unserialize($CFG['ACTIVE_GATEWAYS']);
        if (!$activeGateways || !is_array($activeGateways)) {
            return false;
        }

        return (bool) in_array($this->name, $activeGateways);
    }

    public function getVersion()
    {
        return "1.0";
    }

    final public function cashbox()
    {return (bool) $this->cashbox;}

    final public function haveLog()
    {
        return (bool) $this->log;
    }

    final public function havePaymentHandler()
    {
        return (bool) $this->payment_handler;
    }

    public function setOption($k, $v)
    {
        global $db, $CFG;

        $k = $db->real_escape_string($k);
        $v = $db->real_escape_string(encrypt($v));

        $sql = $db->query("SELECT 1 FROM gateway_settings WHERE `gateway` = '" . $db->real_escape_string($this->name) . "' AND `setting` = '$k'");
        if ($sql->num_rows == 0) {
            $db->query("INSERT INTO gateway_settings (`value`, `gateway`, `setting`) VALUES ('$v', '" . $db->real_escape_string($this->name) . "', '$k')");
        } else {
            $db->query("UPDATE gateway_settings SET `value` = '$v' WHERE `gateway` = '" . $db->real_escape_string($this->name) . "' AND `setting` = '$k'");
        }

        $res = $db->affected_rows;

        $this->__construct($this->language);
        return $res;
    }

    final public function getGlobalNotification()
    {
        return $this->global !== null ? $this->global : false;
    }

    abstract public function getPaymentForm($amount = null);
    abstract public function getPaymentHandler();
    abstract public function getIpnHandler();

    public function cancelAutoPayment(User $user)
    {
        $user->set([
            'auto_payment_provider' => '',
            'auto_payment_credentials' => '',
        ]);

        return true;
    }

    public function getLang($str = "", $lang = "")
    {
        $s = "frontend_name";
        if (strtolower($str) == $s && isset($this->$s)) {
            return $this->$s;
        }

        if ($lang != "" && file_exists(__DIR__ . "/../../languages/" . basename($lang) . ".php")) {
            $oldLanguage = $this->language;
            $this->__construct($lang);
        }

        $return = !empty($str) && isset($this->lang[strtoupper($str)]) ? $this->lang[strtoupper($str)] : $this->lang;
        if (isset($oldLanguage)) {
            $this->__construct($oldLanguage);
        }

        return $return;
    }

    public function getReqOptions()
    {return $this->options;}
    public function getSettings()
    {return $this->settings;}
    public function isLogActive()
    {return (bool) $this->log;}

    public function log($data, $log)
    {
        global $db, $CFG;

        return (bool) $db->query("INSERT INTO gateway_logs (`data`, `log`, `time`, `gateway`) VALUES ('" . $db->real_escape_string($data) . "', '" . $db->real_escape_string($log) . "', " . time() . ", '" . $db->real_escape_string($this->name) . "')");
    }

    public function getJavaScript()
    {
        return "";
    }

    public function canRefund()
    {
        return $this->refunds;
    }
}

// Class for handling payment gateways
class PaymentGatewayHandler
{
    protected $gateways = array();
    protected $settings = array();

    public function __construct()
    {
        global $var, $CFG, $db;
        $this->language = isset($var['language']) ? $var['language'] : $CFG['LANG'];

        $sql = $db->query("SELECT * FROM gateway_settings");
        if ($sql) {
            while ($row = $sql->fetch_object()) {
                if (!array_key_exists($row->gateway, $this->settings) || !is_array($this->settings[$row->gateway])) {
                    $this->settings[$row->gateway] = [];
                }

                $this->settings[$row->gateway][$row->setting] = decrypt($row->value);
            }
        }
    }

    public function getSettings($g)
    {
        return array_key_exists($g, $this->settings) ? $this->settings[$g] : [];
    }

    public function loadGateways()
    {
        $gatewayHandle = opendir(__DIR__ . '/../payment/');
        while ($f = readdir($gatewayHandle)) {
            if (is_dir(__DIR__ . '/../payment/' . $f) && substr($f, 0, 1) != ".") {
                require_once __DIR__ . '/../payment/' . $f . '/' . $f . '.php';
            }
        }

        closedir($gatewayHandle);

        $order = $name = array();
        foreach (get_declared_classes() as $class) {
            if (get_parent_class($class) == "PaymentGateway" && $class != "UnallowedGateway") {
                $this->gateways[$class::$shortName] = new $class($this->language);
                if ($this->gateways[$class::$shortName]->isActive()) {
                    $order[$class::$shortName] = intval($this->gateways[$class::$shortName]->getSettings()['order']);
                } else {
                    $order[$class::$shortName] = 1000;
                }

                $name[$class::$shortName] = $this->gateways[$class::$shortName]->getLang('name');
            }
        }

        array_multisort($order, SORT_ASC, $this->gateways);
    }

    public function makePayment()
    {
        foreach ($this->gateways as $gateway) {
            if (method_exists($gateway, "makePayment")) {
                $gateway->makePayment();
            }
        }

    }

    public function get($autoPaymentRequired = false)
    {
        $g = $this->gateways;

        if ($autoPaymentRequired) {
            foreach ($g as $k => $obj) {
                if (!method_exists($obj, "setupAutoPayment") || !method_exists($obj, "makeAutoPayment") || !method_exists($obj, "cancelAutoPayment")) {
                    unset($g[$k]);
                }
            }
        }

        return $g;
    }

    public function getActivated($u, $hard = false)
    {
        global $db, $CFG;

        $user = User::getInstance($u, "ID");

        $sql = $db->query("SELECT disabled_payment FROM clients WHERE ID = " . intval($u));
        if ($sql->num_rows != 1) {
            return false;
        }

        $d = explode(",", $sql->fetch_object()->disabled_payment);

        $g = $this->get();
        foreach ($g as $k => $v) {
            if (in_array($k, $d) || !$v->canSee($user) || ($hard && !$v->canPay($user))) {
                unset($g[$k]);
            } else if ($v->canSee($user) && !$v->canPay($user)) {
                $g[$k] = new UnallowedGateway;
                $g[$k]->frontend_name = is_string($v->getLang("frontend_name")) ? $v->getLang("frontend_name") : $v->getLang("name");
            }
        }

        return $g;
    }

    public function getGlobalNotification()
    {
        $global = false;
        foreach ($this->gateways as $obj) {
            if ($obj->getGlobalNotification() !== false) {
                $global = $obj->getGlobalNotification();
            }
        }

        return $global;
    }

    public function insertJavaScript()
    {
        global $var;

        $js = [];

        foreach ($this->get() as $gateway) {
            if ($gateway->isActive()) {
                $r = $gateway->getJavaScript();
                if (is_array($r)) {
                    foreach ($r as $j) {
                        if (!in_array('<script src="' . $j . '"></script>', $js)) {
                            array_push($js, '<script src="' . $j . '"></script>');
                        }
                    }

                } else {
                    $var['paymentJS'] .= $r;
                }
            }
        }

        $var['paymentJS'] .= implode("", $js);
    }

}

$gateways = new PaymentGatewayHandler;
$gateways->loadGateways();

// Class for unallowed gateways (display error message)

class UnallowedGateway extends PaymentGateway
{
    public static $shortName = "unallowed_gateway";

    public function __construct()
    {
        $this->settings = [];
    }

    public function getPaymentForm($amount = null)
    {
        global $lang;
        return '<div class="alert alert-info" style="margin-bottom: 0;">' . $lang['CREDIT']['NOT_AVAILABLE'] . '</div>';
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
