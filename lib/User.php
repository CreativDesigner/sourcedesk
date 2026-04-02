<?php

// Class for User

class User
{
    // Property to save user information
    private $info = array();
    private $old = array();
    private $new = array();
    private $billDomain = array();

    // Gets information from database
    public function __construct($identifier, $field = "mail", $minimal = false)
    {
        // Global some variables for security reasons
        global $db, $CFG, $dfo, $sec;

        // Get information from database by email address
        $sql = $db->query("SELECT * FROM clients WHERE `" . $db->real_escape_string($field) . "` = '" . $db->real_escape_string($identifier) . "'");

        // Return false if user is not found
        if (!$sql || $sql->num_rows == 0) {
            return false;
        }

        // Save each column info @var info
        $res = $sql->fetch_object();
        foreach ($res as $k => $v) {
            $this->info[$k] = $v;
        }

        if (!$minimal) {
            // Save old information into old array
            foreach ($this->info as $k => $v) {
                if (in_array($k, self::changes())) {
                    $this->old[$k] = $v;
                }
            }

            // Get open invoices amount
            $this->info['open_invoices'] = 0;
            foreach ($this->getInvoices(0) as $i) {
                $this->info['open_invoices'] += $i->getAmount();
            }

            // Get information about products and product IDs into seperate array
            $this->info['products_info'] = array(); // ID, active, license, version, product, date (versioning; formated)
            $this->info['products'] = array(); // Produkt-IDs
            $this->info['software_products'] = array(); // Online software products
            $sql = $db->query("SELECT * FROM client_products WHERE user = " . $this->info['ID'] . " ORDER BY date DESC, ID DESC");
            while ($p = $sql->fetch_object()) {
                array_push($this->info['products'], $p->product);
                if ($p->module == "software") {
                    array_push($this->info['software_products'], $p->product);
                }

                $furtherSQL = $db->query("SELECT * FROM products WHERE ID = " . $p->product);
                if ($furtherSQL->num_rows != 1) {
                    continue;
                }

                $furtherInfo = $furtherSQL->fetch_object();

                $productName = $p->name ?: unserialize($furtherInfo->name)[$CFG['LANG']];

                $this->info['products_info'][$p->ID] = array("ID" => $p->ID, "product" => $p->product, "name" => $productName, "active" => $p->active, "date" => $p->date, "billing" => $p->billing, "price" => $p->price, "cancellation_date" => $p->cancellation_date, "description" => $p->description, "reseller_customer" => $p->reseller_customer);
                if ($p->module == "software") {
                    $this->info['software_products_info'][$p->ID] = array("ID" => $p->ID, "product" => $p->product, "name" => $productName, "active" => $p->active, "date" => $p->date, "billing" => $p->billing, "price" => $p->price, "cancellation_date" => $p->cancellation_date);
                }
            }

            $this->info['fname'] = $this->getfName();

            // Serialize the arrays
            $this->info['products_info'] = serialize($this->info['products_info']);
            $this->info['products'] = serialize($this->info['products']);
            $this->info['software_products'] = serialize($this->info['software_products']);
            $this->info['software_products_info'] = serialize($this->info['software_products_info']);
        }
    }

    // Do auto payment
    public function autoPayment($amount, $force = false)
    {
        global $gateways, $db, $CFG, $transactions;

        if (!$this->autoPaymentStatus()) {
            return false;
        }

        $gateway = $gateways->get(true)[$this->info['auto_payment_provider']];

        if (!$gateway->makeAutoPayment($this, $amount, true)) {
            return false;
        }

        $db->query("UPDATE clients SET credit = credit + " . doubleval($amount) . " WHERE ID = " . $this->get()['ID'] . " LIMIT 1");
        return true;
    }

    // Cancel auto payment
    public function cancelAutoPayment()
    {
        global $gateways;

        if (!$this->autoPaymentStatus()) {
            return false;
        }

        $gateway = $gateways->get(true)[$this->info['auto_payment_provider']];
        if (!$gateway->cancelAutoPayment($this)) {
            return false;
        }

        $this->set([
            'auto_payment_provider' => '',
            'auto_payment_credentials' => '',
        ]);

        return true;
    }

    // Get auto payment status
    public function autoPaymentStatus()
    {
        global $gateways;

        if (empty($this->info['auto_payment_provider'])) {
            return false;
        }

        if (!array_key_exists($this->info['auto_payment_provider'], $gateways->get(true))) {
            return false;
        }

        if (!$gateways->get(true)[$this->info['auto_payment_provider']]->canPay($this)) {
            return false;
        }

        return true;
    }

    // Gives user information as raw array from database
    public static function changes()
    {
        global $CFG;

        $arr = array(
            "mail",
            "salutation",
            "nickname",
            "firstname",
            "lastname",
            "company",
            "postcode",
            "street",
            "street_number",
            "city",
            "country",
            "telephone",
            "telephone_verified",
            "fax",
            "birthday",
            "login_notify",
            "reseller",
            "tfa",
            "newsletter",
            "coordinates",
            "website",
            "verified",
            "birthday_mail",
            "vatid",
            "inv_street",
            "inv_street_number",
            "inv_postcode",
            "inv_city",
            "inv_tthof",
            "inv_due",
        );
        if ($CFG['PASSWORD_HISTORY'] == "1") {
            array_push($arr, "pwd", "salt");
        }

        return $arr;
    }

    // Gives user information as an array
    public static function displayChanges()
    {
        $arr = array(
            "mail",
            "salutation",
            "nickname",
            "firstname",
            "lastname",
            "company",
            "postcode",
            "street",
            "street_number",
            "city",
            "country",
            "telephone",
            "telephone_verified",
            "fax",
            "birthday",
            "login_notify",
            "reseller",
            "tfa",
            "newsletter",
            "website",
            "verified",
            "pwd",
            "birthday_mail",
            "vatid",
            "inv_street",
            "inv_street_number",
            "inv_postcode",
            "inv_city",
            "inv_tthof",
            "inv_due",
        );
        return $arr;
    }

    // You can set new values for columns (requires an array with column => value)
    public static function getInstance($identifier, $field = "mail", $minimal = false)
    {
        $instance = new User($identifier, $field, $minimal);
        return is_array($instance->getRaw()) ? $instance : false;
    }

    // Log an entry to user log (only string required)
    public function getRaw()
    {
        if (count($this->info) == 0) {
            return null;
        }

        return (Array) $this->info;
    }

    // Method to save changes on client profile
    public function set($arr)
    {
        // Global some variables for security reasons
        global $db, $CFG;

        // If an array is passed, save the specified columns
        if (is_array($arr)) {
            foreach ($arr as $k => $v) {
                if (in_array($k, self::changes())) {
                    $this->old[$k] = $this->info[$k];
                    $this->new[$k] = $v;
                }

                $db->query("UPDATE clients SET `$k` = '" . $db->real_escape_string($v) . "' WHERE `mail` = '" . $this->info['mail'] . "' LIMIT 1");
                $this->info[$k] = $v;
                if ($k == "firstname" || $k == "lastname") {
                    $this->info["name"] = $this->info["firstname"] . " " . $this->info["lastname"];
                }

            }
        }
    }

    // Method to reset the changes made
    public function log($log, $ip = false, $force = true)
    {
        // Global some variables for security reasons
        global $db, $session, $_SERVER, $CFG, $addons;

        // Get the IP address of client
        if (!$ip) {
            $ip = ip();
        }

        $ua = $db->real_escape_string($_SERVER['HTTP_USER_AGENT']);

        // Handle admin session
        if ($session->get('admin_login') == 1 || $session->get('admin') == 1) {
            $ip = "Admin";
            $ua = "";
            if (!$force) {
                return false;
            }

        }

        // Escape and insert into database
        $log = $db->real_escape_string($log);
        $ip = $db->real_escape_string($ip);

        $db->query("INSERT INTO client_log (`user`,`time`,`action`,`ip`,`ua`) VALUES ('" . $this->get()['ID'] . "', " . ($t = time()) . ", '$log', '$ip', '$ua')");

        if (!$db->query("SELECT 1 FROM ip_logs WHERE ip = '$ip' AND user = " . $this->get()['ID'])->num_rows) {
            $db->query("INSERT INTO `ip_logs` (`time`, `user`, `ip`) VALUES (" . $t . ", " . $this->get()['ID'] . ", '$ip')");
        }

        // Run hook
        $addons->runHook("UserLogEntry", [
            "user" => $this,
            "log" => $log,
            "ip" => $ip,
        ]);
    }

    // Method returns fields where changes should be logged
    public function get()
    {
        // Global some variables for security reasons
        global $nfo, $cur, $db, $CFG;

        // Check if any data is there
        if (count($this->info) == 0) {
            return null;
        }

        // Also give credit as formated, because Smarty cannot do this easily with @class NumberFormat
        $this->info['credit_formated'] = $nfo->format($this->info['credit'], 2, 0);
        $this->info['credit_converted'] = $cur->infix($nfo->format($cur->convertAmount(null, $this->info['credit'], null), 2, 0));

        $this->info['name'] = $this->info['firstname'] . " " . $this->info['lastname'];

        // Get country alpha2 code
        $sql = $db->query("SELECT * FROM client_countries WHERE ID = " . intval($this->info['country']));
        $this->info['country_alpha2'] = $this->info['country_name'] = "";
        if ($sql->num_rows == 1) {
            $ci = $sql->fetch_object();
            $this->info['country_alpha2'] = $this->info['country_alpha'] = $ci->alpha2;
            $this->info['country_name'] = $ci->name;
        }

        $this->info['streetnumber'] = $this->info['street_number'];

        return (Array) $this->info;
    }

    // Method returns fields which are shown in admin change log
    public function saveChanges($who = "client", $shouldScore = true)
    {
        global $db, $CFG, $addons, $provisioning;

        $old = $this->old;
        $new = $this->new;

        if (!is_array($old) || !is_array($new)) {
            return false;
        }

        foreach ($new as $k => $v) {
            if (!isset($old[$k]) || $v == $old[$k]) {
                unset($new[$k]);
            }
        }

        foreach ($old as $k => $v) {
            if (!isset($new[$k])) {
                unset($old[$k]);
            }
        }

        $diff = array();
        foreach ($new as $k => $v) {
            $diff[$k] = array($old[$k], $v);
        }

        $addons->runHook("ClientDataChanged", ["user" => $this, "old" => $old, "new" => $new, "source" => $who]);

        if (count($diff) == 0) {
            return true;
        }

        foreach (["firstname", "lastname", "companyname", "street", "postcode", "city"] as $field) {
            if (!array_key_exists($field, $diff)) {
                continue;
            }

            if (!$shouldScore) {
                $this->autoScore();
            }
            break;
        }

        $mods = $provisioning->get();
        $prodSql = $db->query("SELECT ID, module FROM client_products WHERE user = {$this->info['ID']} AND active = 1 AND module != ''");
        while ($prodRow = $prodSql->fetch_object()) {
            if (array_key_exists($prodRow->module, $mods)) {
                $mod = $mods[$prodRow->module];
                if (method_exists($mod, "ClientChanged")) {
                    $mod->ClientChanged($prodRow->ID, array_keys($new));
                }
            }
        }

        $diff = $db->real_escape_string(serialize($diff));
        $db->query("INSERT INTO client_changes (`user`, `time`, `diff`, `who`) VALUES ({$this->info['ID']}, " . time() . ", '$diff', '" . ($who == "client" ? 0 : $db->real_escape_string($who)) . "')");
        return true;
    }

    // Method to reset all changes
    public function resetChanges()
    {
        $this->new = array();
    }

    // Method for getting user currency
    public function getCurrency()
    {
        global $cur;

        if (empty($this->info['currency']) || !CurrencyManager::getCurrency($this->info['currency'])) {
            return $cur->getBaseCurrency();
        }

        return $this->info['currency'];
    }

    // Method for getting user language
    public function getLanguage()
    {
        global $raw_cfg;

        if (empty($this->info['language']) || !file_exists(__DIR__ . '/../languages/' . basename($this->info['language']) . '.php')) {
            return $raw_cfg['LANG'];
        }

        return $this->info['language'];
    }

    // Method to load user language
    public function loadLanguage()
    {
        global $lang;
        require __DIR__ . '/../languages/' . basename($this->getLanguage()) . '.php';
    }

    // Method for getting user date format
    public function getDateFormat()
    {
        global $raw_cfg, $CFG;

        $l = $this->getLanguage();
        return !empty($l) && isset($raw_cfg['DATE_FORMAT']) && is_array(unserialize($raw_cfg['DATE_FORMAT'])) && isset(unserialize($raw_cfg['DATE_FORMAT'])[$l]) ? unserialize($raw_cfg['DATE_FORMAT'])[$l] : $CFG['DATE_FORMAT'];
    }

    // Method for getting number format
    public function getNumberFormat()
    {
        global $raw_cfg, $CFG;

        $language = $this->getLanguage();
        $formats = unserialize($raw_cfg['NUMBER_FORMAT']);
        return isset($formats[$language]) ? $formats[$language] : $CFG['NUMBER_FORMAT'];
    }

    // Method to get invoices of this client
    public function getInvoices($status = -1, $draft = false)
    {
        global $db, $CFG;

        $status = $status == -1 ? "" : " AND status = " . intval($status);
        if (!$draft) {
            $status .= " AND status != 3";
        }
        $sql = $db->query("SELECT ID FROM invoices WHERE client = " . $this->info['ID'] . "$status ORDER BY date DESC, ID DESC");
        $arr = [];
        while ($row = $sql->fetch_object()) {
            $obj = new Invoice;
            $obj->load($row->ID);
            array_push($arr, $obj);
        }

        return $arr;
    }

    // Method to get recurring invoices of this client
    public function getRecurringInvoices($status = -1)
    {
        global $db, $CFG;

        $status = $status == -1 ? "" : " AND status = " . intval($status);
        $sql = $db->query("SELECT ID FROM invoice_items_recurring WHERE user = " . $this->info['ID'] . "$status ORDER BY first ASC, ID ASC");
        $arr = [];
        while ($row = $sql->fetch_object()) {
            array_push($arr, new RecurringInvoice($row->ID));
        }

        return $arr;
    }

    // Method to get open invoices
    public function getOpenInvoices()
    {
        return $this->getInvoices("0");
    }

    // Method to send an invoice for payment fees
    public function invoiceFees($amount = 0.00, $credit = true, $send = true)
    {
        global $lang, $CFG;
        if ($amount <= 0) {
            return false;
        }

        if (!isset($lang['TRANSACTIONS']['FEES'])) {
            $oldLang = $lang;
            $this->loadLanguage();
        }

        $item = new InvoiceItem;
        $item->setDescription($lang['TRANSACTIONS']['FEES']);
        $item->setAmount($amount);

        $inv = new Invoice;
        $inv->setDate(date("Y-m-d"));
        $inv->setClient($this->get()['ID']);
        $inv->setDueDate();
        $inv->addItem($item);

        $item->save();

        if (isset($oldLang)) {
            $lang = $oldLang;
        }

        if ($credit) {
            $inv->applyCredit();
        }

        if ($send) {
            $inv->send();
        }

    }

    // Method to get the VAT for a user
    public function getVAT($vatid = null, $date = "")
    {
        global $db, $CFG;

        $b2b = (bool) !empty($this->get()['company']);
        if ($vatid === null) {
            $vatid = $this->get()['vatid'];
        }

        $sql = $db->query("SELECT * FROM client_countries WHERE ID = " . $this->get()['country'] . " AND active = 1");
        if ($sql->num_rows != 1) {
            return false;
        }

        $info = $sql->fetch_object();

        if ($tempVat = TempVat::rate($info->alpha2, $info->percent, $date)) {
            $info->percent = $tempVat;
        }

        if ($b2b) {
            switch ($info->b2b) {
                case "0":
                    return "reverse";
                    break;

                case "1":
                    if (!empty($vatid)) {
                        $obj = new EuVAT($vatid);
                        if ($obj->isValid() && $obj->getCountry() == $info->alpha2) {
                            return "reverse_vatid";
                        }

                    }
                    return array($info->tax, $info->percent, "vatid_missing");
                    break;

                case "2":
                    return array($info->tax, $info->percent);
                    break;

                case "3":
                    return false;
                    break;
            }
        } else {
            switch ($info->b2c) {
                case "0":
                    return "reverse";
                    break;

                case "1":
                    return array($info->tax, $info->percent);
                    break;

                case "2":
                    return false;
                    break;
            }
        }
    }

    // Method for require login in frontend
    public static function status()
    {
        global $var, $CFG, $logout;

        if (empty($var['logged_in']) || $var['logged_in'] != 1) {
            if ($_GET['p'] == "logout") {
                header('Location: ' . $CFG['PAGEURL']);
                exit;
            }

            $additional = array();
            if (!empty($_GET['p']) && $_GET['p'] != "index") {
                $additional['redirect_to'] = $_GET['p'];
            }

            foreach ($_GET as $k => $v) {
                if ($k != "p" && $k != "redirect_to" && $k != "add_product" && $k != "add_service") {
                    $additional[$k] = urlencode($v);
                }
            }

            header('Location: ' . $CFG['PAGEURL'] . 'login' . rtrim("?" . http_build_query($additional), "?"));
            exit;
        }
    }

    // Method to set a new generated password
    public function generatePassword()
    {
        global $sec;

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < 8; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        $data = array();
        $data['salt'] = $sec->generateSalt();
        $data['pwd'] = $sec->hash($result, $data['salt']);

        $this->set($data);
        return $result;
    }

    // Method to apply credit to open invoices
    public function applyCredit($force = false)
    {
        $this->__construct($this->info['mail']);

        $invoices = array();
        foreach ($this->getInvoices(0) as $i) {
            $invoices[$i->getId()] = $i->getAmount();
        }

        arsort($invoices);

        $credit = $this->info['credit'];
        foreach ($invoices as $id => $amount) {
            if ($amount > $credit && !$force) {
                continue;
            }

            $obj = new Invoice;
            $obj->load($id);
            $obj->applyCredit($force);
            $credit -= $amount;
        }

        $this->__construct($this->info['mail']);
    }

    // Method to create a later invoice item
    public function invoiceLater($d, $a, $p = true)
    {
        global $db, $CFG;
        $db->query("INSERT INTO invoicelater (`user`, `description`, `amount`, `paid`) VALUES (" . $this->info['ID'] . ", '" . $db->real_escape_string($d) . "', " . doubleval($a) . ", " . ($p ? 1 : 0) . ")");
    }

    // Method to get later invoice due date
    public function invoiceDue($includeToday = false)
    {
        $d = $this->info['invoicelater'];
        $m = $includeToday ? date("m") : date("m") + 1;
        $y = date("Y");

        if ($m > 12) {
            $m = 1;
            $y++;
        }

        if (!function_exists("cal_days_in_month")) {
            function cal_days_in_month($month, $year)
            {
                return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
            }
        }

        $d = str_pad($d, 2, "0", STR_PAD_LEFT);
        $m = str_pad($m, 2, "0", STR_PAD_LEFT);

        if (date("d") > $d || (date("d") == $d && $this->info['invoicelast'] >= date("Y-m-d"))) {
            if (cal_days_in_month(CAL_GREGORIAN, $m, $y) >= $d) {
                return $y . "-" . $m . "-" . $d;
            }

            return $y . "-" . $m . "-" . cal_days_in_month(CAL_GREGORIAN, $m, $y);
        } else {
            if (cal_days_in_month(CAL_GREGORIAN, date("m"), date("Y")) >= $d) {
                return date("Y") . "-" . date("m") . "-" . $d;
            }

            return date("Y") . "-" . date("m") . "-" . cal_days_in_month(CAL_GREGORIAN, date("m"), date("Y"));
        }
    }

    // Method for doing later invoice business
    public function invoiceNow($f = false)
    {
        global $db, $CFG;

        if (!$f && date("Y-m-d") < $this->invoiceDue(true)) {
            return;
        }

        $sql = $db->query("SELECT * FROM invoicelater WHERE user = {$this->info['ID']} AND paid = 1");
        if ($sql->num_rows > 0) {
            $inv = new Invoice;
            $inv->setDate(date("Y-m-d"));
            $inv->setClient($this->info['ID']);
            $inv->setDueDate();
            $inv->setStatus(1);

            while ($row = $sql->fetch_object()) {
                $item = new InvoiceItem;
                $item->setDescription($row->description);
                $item->setAmount($row->amount);
                $inv->addItem($item);
                $item->save();
            }

            $inv->save();
            $inv->send();

            $sql = $db->query("DELETE FROM invoicelater WHERE user = {$this->info['ID']} AND paid = 1");
        }

        $sql = $db->query("SELECT * FROM invoicelater WHERE user = {$this->info['ID']} AND paid = 0");
        if ($sql->num_rows > 0) {
            $inv = new Invoice;
            $inv->setDate(date("Y-m-d"));
            $inv->setClient($this->info['ID']);
            $inv->setDueDate();
            $inv->setStatus(0);

            while ($row = $sql->fetch_object()) {
                $item = new InvoiceItem;
                $item->setDescription($row->description);
                $item->setAmount($row->amount);
                $inv->addItem($item);
                $item->save();
            }

            $inv->save();
            $inv->send();

            $sql = $db->query("DELETE FROM invoicelater WHERE user = {$this->info['ID']} AND paid = 0");
        }
    }

    // Method for getting paying limit
    public function getLimit()
    {
        global $db, $CFG;

        $available = $this->get()['credit'] + $this->get()['postpaid'];
        $available -= $db->query("SELECT SUM(amount) AS a FROM invoicelater WHERE user = " . $this->get()['ID'] . " AND paid = 0")->fetch_object()->a;

        $inv = $this->getInvoices(0);
        foreach ($inv as $i) {
            $available -= $i->getAmount();
        }

        return $available;
    }

    // Method for getting domain price by user
    public function getDomainPrice($tld, $type = "register")
    {
        global $db, $CFG;

        if ($type == "auth2") {
            $sql = $db->query("SELECT price FROM domain_auth2 WHERE tld = '" . $db->real_escape_string(ltrim($tld, ".")) . "'");
            if ($sql->num_rows != 1) {
                return false;
            }

            return $sql->fetch_object()->price;
        }

        $price = null;

        $sql = $db->query("SELECT * FROM domain_pricing WHERE tld = '" . $db->real_escape_string(ltrim($tld, ".")) . "'");
        if ($row = $sql->fetch_object()) {
            $register = $row->register;
            $transfer = $row->transfer;
            $renew = $row->renew;
            $trade = $row->trade;
            $privacy = $row->privacy;

            $s = $db->query("SELECT * FROM domain_pricing_override WHERE user = " . $this->get()['ID'] . " AND tld = '" . $row->tld . "'");
            if ($s->num_rows == 1 && is_object($row = $s->fetch_object()) && isset($s->$type)) {
                $price = $s->$type;
            }

            if (isset($row->$type)) {
                $price = $row->$type;
            }
        }

        $aSql = $db->query("SELECT `type`, `price` FROM domain_actions WHERE `start` <= '" . date("Y-m-d H:i:s") . "' AND `end` >= '" . date("Y-m-d H:i:s") . "' AND tld = '" . $db->real_escape_string(ltrim($tld, ".")) . "'");
        while ($aRow = $aSql->fetch_object()) {
            $price2 = [
                "REG" => "register",
                "RENEW" => "renew",
                "KK" => "transfer",
            ][$aRow->type];

            if ($price > $aRow->price && $price2 == $type) {
                $price = $aRow->price;
            }
        }

        return $price !== null ? $price : false;
    }

    // Method for getting price with tax
    public function addTax($amount)
    {
        $tax = $this->getVAT();
        if ($tax === false || !is_array($tax) || !isset($tax[1]) || $tax[1] <= 0) {
            return $amount;
        }

        return $amount * (1 + $tax[1] / 100);
    }

    // Destructor (billing outstanding domain tasks)
    public function __destruct()
    {
        if (count($this->billDomain)) {
            $inv = new Invoice;
            $inv->setDate(date("Y-m-d"));
            $inv->setClient($this->get()['ID']);
            $inv->setDueDate();

            foreach ($this->billDomain as $bd) {
                $item = new InvoiceItem;
                $item->setAmount($bd[1]);
                $item->setDescription($bd[0]);
                $inv->addItem($item);
            }

            $inv->save();
            $inv->send();
            $this->tryToClear();
        }
    }

    // Method for billing domains
    public function billDomain($desc, $amount, $domain)
    {
        global $db, $CFG;

        if ($amount == 0) {
            return;
        }

        $this->billDomain[] = [$desc, $amount];
    }

    // Method for getting nameservers
    public function getNS()
    {
        global $CFG;

        $myNs = unserialize($this->get()['dns_server']);
        if ($myNs === false) {
            $ns1 = $CFG['NS1'];
            $ns2 = $CFG['NS2'];
            $ns3 = $CFG['NS3'];
            $ns4 = $CFG['NS4'];
            $ns5 = $CFG['NS5'];
        } else {
            $ns1 = !empty($myNs[0]) ? $myNs[0] : $CFG['NS1'];
            $ns2 = !empty($myNs[1]) ? $myNs[1] : $CFG['NS2'];
            $ns3 = isset($myNs[2]) ? $myNs[2] : $CFG['NS3'];
            $ns4 = isset($myNs[3]) ? $myNs[3] : $CFG['NS4'];
            $ns5 = isset($myNs[4]) ? $myNs[4] : $CFG['NS5'];
        }

        return array($ns1, $ns2, $ns3, $ns4, $ns5);
    }

    // Method for getting group name
    public function getfName()
    {
        global $db, $CFG;

        $group = $this->get()['cgroup'];
        $name = htmlentities($this->get()['name']);

        if ($group == 0) {
            return $name;
        }

        $sql = $db->query("SELECT color FROM client_groups WHERE ID = " . intval($group));
        if ($sql->num_rows == 1) {
            return '<span style="background-color: ' . $sql->fetch_object()->color . ';">' . $name . '</span>';
        }

        return $name;
    }

    // Method for getting contacts
    public function getContacts($email = false)
    {
        global $db, $CFG;

        $contacts = array();

        $sql = $db->query("SELECT ID, mail_templates FROM client_contacts WHERE client = {$this->get()['ID']}");
        while ($row = $sql->fetch_object()) {
            if ($email !== false) {
                $ex = explode(",", $row->mail_templates);
                if (!in_array($email, $ex) || $email == 0) {
                    continue;
                }
            }

            $contacts[$row->ID] = new Contact($row->ID);
        }

        return $contacts;
    }

    // Method to get custom field
    public function getField($f)
    {
        $s = unserialize($this->get()['fields']);
        return $s[$f] ?: "";
    }

    // Method to save custom field
    public function setField($f, $v)
    {
        $this->old["field:$f"] = $this->getField($f);
        $this->new["field:$f"] = $v;

        $s = unserialize($this->get()['fields']);
        if (!is_array($s)) {
            $s = array();
        }

        $s[$f] = $v;
        $this->set(array("fields" => serialize($s)));
    }

    // Automatic rating
    public function autoScore()
    {
        global $db, $CFG;

        $providers = ScoringHandler::getDrivers();
        $sql = $db->query("SELECT provider, setting, value FROM scoring WHERE setting LIKE 'automatic-%'");
        while ($row = $sql->fetch_object()) {
            if (decrypt($row->value) != "1") {
                continue;
            }

            if (!array_key_exists($row->provider, $providers)) {
                continue;
            }

            $this->fetchScore($providers[$row->provider], explode("-", $row->setting, 2)[1]);
        }
    }

    // Get score from provider
    public function fetchScore(Scoring $provider, $method)
    {
        global $db, $CFG;
        if (!method_exists($provider, $method)) {
            return false;
        }

        $r = $provider->$method($this);
        if (is_array($r) && count($r) == 3) {
            $db->query("INSERT INTO client_scoring (time, user, rating, entry, details) VALUES (" . time() . ", " . $this->get()['ID'] . ", '" . $db->real_escape_string($r[1]) . "', '" . $db->real_escape_string($r[0]) . "', '" . $db->real_escape_string($r[2]) . "')");
        }

    }

    // Get score
    public function getScore()
    {
        global $db, $CFG;
        $sql = $db->query("SELECT * FROM client_scoring WHERE user = " . intval($this->get()['ID']) . " ORDER BY entry ASC, time DESC");

        $maxscore = 100;
        $score = 80;
        $lastEntry = "";
        while ($row = $sql->fetch_object()) {
            if ($row->entry == $lastEntry) {
                continue;
            }

            $lastEntry = $row->entry;

            if ($row->rating == "A") {
                $score += 10;
            }

            if ($row->rating == "B") {
                $score += 5;
            }

            if ($row->rating == "C") {
                $score += 3;
            }

            if ($row->rating == "D") {
                $score += 1;
            }

            if ($row->rating == "E") {
                $maxscore = 75;
                $score -= 10;
            }
            if ($row->rating == "F") {
                $maxscore = 60;
                $score -= 50;
            }
        }

        return max(1, min($maxscore, $score));
    }

    // Define database relations
    public static function getRelations($delete = true)
    {
        $rel = [
            "cashbox" => "user",
            "client_calls" => "user",
            "client_cart" => "user",
            "client_changes" => "user",
            "client_contacts" => "client",
            "client_cookie" => "user",
            "client_files" => "user",
            "client_letters" => "client",
            //"client_log" => "user",
            "client_mailchanges" => "user",
            "client_mails" => "user",
            "client_notes" => "user",
            "client_products" => "user",
            "client_quotes" => "client",
            "client_scoring" => "user",
            "client_sepa" => "client",
            "client_tfa" => "user",
            "client_transactions" => "user",
            "credentials" => "user",
            "domains" => "user",
            "invoice_items_recurring" => "user",
            "ip_logs" => "user",
            "vouchers" => "user",
            "wishlist_likes" => "user",
            "wishlist_product_abo" => "user",
            "wishlist_wish_abo" => "user",
        ];

        if (!$delete) {
            $rel = array_merge($rel, [
                "bugtracker" => "user",
                "client_affiliate" => "user",
                "invoices" => "client",
                "projects" => "user",
                "support_tickets" => "customer",
                "testimonials" => "author",
                "wishlist" => "user",
                "wishlist_comments" => "user",
            ]);
        }

        return $rel;
    }

    // Get BDSG document
    public function getBDSG()
    {
        global $db, $CFG, $nfo, $dfo, $cur, $transactions;

        // Initialize PDF
        require_once __DIR__ . "/tcpdf/tcpdf.php";
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetRightMargin(18);
        $pdf->AddPage();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($CFG['PAGENAME']);
        $pdf->SetTitle("BDSG-Auskunft " . $this->get()['name']);

        // PDF header
        $pdf->writeHTML("<h2>Auskunft nach § 34 BDSG für " . $this->get()['name'] . "</h2><br /><br />Nachfolgend finden Sie Ihre angeforderte Auskunft nach § 34 BDSG. Diese Auskunft enthält alle personenbezogenen Daten, die zu Ihrem nachfolgenden Konto bei uns gespeichert sind.<br /><br />Bei Rückfragen kontaktieren Sie uns bitte.<br />", true, false, false, false, '');

        // Profile data
        $table = "<h3>Profildaten</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>Feld</b></th><th><b>Inhalt</b></th></tr>";

        $fields = array(
            "mail" => "E-Mailadresse",
            "firstname" => "Vorname",
            "lastname" => "Nachname",
            "company" => "Firma",
            "vatid" => "UStIdNr.",
            "street" => "Straße",
            "street_number" => "Hausnummer",
            "postcode" => "Postleitzahl",
            "city" => "Stadt",
            "country" => "Land",
            "telephone" => "Telefonnummer",
            "telephone_verified" => "Telefonnummer verifiziert",
            "fax" => "Faxnummer",
            "birthday" => "Geburtstag",
            "website" => "Webseite",
            "language" => "Sprache",
            "currency" => "Währung",
            "pwd" => "Passwort",
            "credit" => "Aktuelles Guthaben",
            "special_credit" => "Davon Sonderguthaben",
            "registered" => "Registrierungs-Datum",
            "last_login" => "Letzter Login",
            "last_active" => "Letzte Aktivität",
            "last_pwreset" => "Letzter Passwortreset-Versuch",
            "locked" => "Konto gesperrt",
            "verified" => "Konto verifiziert",
            "affiliate" => "Affiliate",
            "login_notify" => "Login-Benachrichtigungen",
            "tfa" => "Zwei-Faktor-Authentifizierung",
            "newsletter" => "Newsletter",
        );

        function format_value($k, $v)
        {
            global $dfo, $db, $CFG;

            if (in_array($k, ["telephone_verified", "locked", "verified", "login_notify", "newsletter", "affiliate"])) {
                return $v ? "Ja" : "Nein";
            }

            if ($k == "birthday") {
                return $v == "0000-00-00" ? "" : $dfo->format($v, false);
            }

            if (in_array($k, ["registered", "last_login", "last_active", "last_pwreset"])) {
                return $dfo->format($v);
            }

            if ($k == "country") {
                $sql = $db->query("SELECT name FROM client_countries WHERE ID = " . intval($v));
                if ($sql->num_rows == 1) {
                    return $sql->fetch_object()->name;
                }

            }

            return $v;
        }

        foreach ($this->get() as $k => $v) {
            if (array_key_exists($k, $fields)) {
                $table .= "<tr><td>" . $fields[$k] . "</td><td>" . ($k == "pwd" ? "<i>Hashwert</i>" : ($k == "tfa" ? ($v == "none" ? "Nein" : "Ja") : format_value($k, $v))) . "</td></tr>";
            }
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Data history
        $table = "<h3>Profiländerungen</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>Zeitpunkt</b></th><th><b>Feld</b></th><th><b>Alt</b></th><th><b>Neu</b></th><th><b>Wer?</b></th></tr>";

        $sql = $db->query("SELECT * FROM client_changes WHERE user = " . $this->get()['ID'] . " ORDER BY time DESC");
        while ($row = $sql->fetch_object()) {
            $d = unserialize($row->diff);
            foreach ($d as $k => $v) {
                if ($k != "coordinates" && $k != "pwd" && $k != "salt" && $k != "tfa" && array_key_exists($k, $fields)) {
                    $table .= "<tr><td>" . $dfo->format($row->time) . "</td><td>{$fields[$k]}</td><td>" . format_value($k, $v[0]) . "</td><td>" . format_value($k, $v[1]) . "</td><td>" . ($row->who != "0" ? "Support" : "Kunde") . "</td></tr>";
                }
            }

        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Contacts
        $table = "<h3>Zusätzliche Kontakte</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>Name</b></th><th><b>Details</b></th></tr>";

        $sql = $db->query("SELECT * FROM client_contacts WHERE client = " . $this->get()['ID']);
        while ($row = $sql->fetch_object()) {
            unset($row->ID);
            unset($row->client);
            unset($row->country);
            unset($row->emails_cc);
            $table .= "<tr><td>" . $row->firstname . " " . $row->lastname . "</td><td>" . implode("<br />", (array) $row) . "</td></tr>";
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Products
        $table = "<h3>Verknüpfte Produkte</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>Vertragsdatum</b></th><th><b>Produktname</b></th></tr>";

        $sql = $db->query("SELECT * FROM client_products WHERE user = " . $this->get()['ID']);
        while ($row = $sql->fetch_object()) {
            $name = "<i>Unbekannt</i>";
            $sql2 = $db->query("SELECT name FROM products WHERE ID = " . $row->product);
            if ($sql2->num_rows == 1) {
                $name = unserialize($sql2->fetch_object()->name)[$CFG['LANG']];
            }

            $table .= "<tr><td>" . $dfo->format($row->date, false) . "</td><td>" . $name . "</td></tr>";
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Domains
        $table = "<h3>Verknüpfte Domains</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>Vertragsdatum</b></th><th><b>Domain</b></th></tr>";

        $sql = $db->query("SELECT * FROM domains WHERE user = " . $this->get()['ID']);
        while ($row = $sql->fetch_object()) {
            $table .= "<tr><td>" . $dfo->format($row->created, false) . "</td><td>" . $row->domain . "</td></tr>";
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Projects
        $table = "<h3>Verknüpfte Projekte</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>Fälligkeitsdatum</b></th><th><b>Bezeichnung</b></th></tr>";

        $sql = $db->query("SELECT * FROM projects WHERE user = " . $this->get()['ID']);
        while ($row = $sql->fetch_object()) {
            $table .= "<tr><td>" . $dfo->format($row->due) . "</td><td>" . $row->name . "</td></tr>";
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Invoices
        $table = "<h3>Verknüpfte Rechnungen</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>ID</b></th><th><b>Datum</b></th></tr>";

        $sql = $db->query("SELECT * FROM invoices WHERE client = " . $this->get()['ID']);
        while ($row = $sql->fetch_object()) {
            $table .= "<tr><td>" . ($row->customno ?: $row->ID) . "</td><td>" . $dfo->format($row->date, false) . "</td></tr>";
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Offers
        $table = "<h3>Verknüpfte Angebote</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>ID</b></th><th><b>Datum</b></th></tr>";

        $sql = $db->query("SELECT * FROM client_quotes WHERE client = " . $this->get()['ID']);
        while ($row = $sql->fetch_object()) {
            $table .= "<tr><td>" . ($row->ID) . "</td><td>" . $dfo->format($row->start, false) . "</td></tr>";
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Files
        $table = "<h3>Hinterlegte Dateien</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>Dateiname</b></th></tr>";

        $sql = $db->query("SELECT * FROM client_files WHERE user = " . $this->get()['ID'] . " AND user_access = 1");
        while ($row = $sql->fetch_object()) {
            $table .= "<tr><td>" . ($row->filename) . "</td></tr>";
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Transactions
        $table = "<h3>Guthaben-Transaktionen</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>Zeit</b></th><th><b>Betreff</b></th><th><b>Betrag</b></th></tr>";

        $sql = $db->query("SELECT * FROM client_transactions WHERE user = " . $this->get()['ID']);
        while ($row = $sql->fetch_object()) {
            $table .= "<tr><td>" . $dfo->format($row->time) . "</td><td>{$transactions->subject($row->subject)}</td><td>" . $nfo->format($row->amount) . "</td></tr>";
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Scoring
        $table = "<h3>Bonitätsauskünfte</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>Zeit</b></th><th><b>Details</b></th></tr>";

        $sql = $db->query("SELECT * FROM client_scoring WHERE user = " . $this->get()['ID']);
        while ($row = $sql->fetch_object()) {
            $table .= "<tr><td>" . $dfo->format($row->time) . "</td><td>{$row->entry}</td></tr>";
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Logs
        $table = "<h3>Log-Einträge</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>Zeit</b></th><th><b>Eintrag</b></th></tr>";

        $sql = $db->query("SELECT * FROM client_log WHERE user = " . $this->get()['ID']);
        while ($row = $sql->fetch_object()) {
            $table .= "<tr><td>" . $dfo->format($row->time) . "</td><td>{$row->action}</td></tr>";
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Cart
        $table = "<h3>Warenkorb-Inhalt</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>Zeit</b></th><th><b>Produkt-Bezeichnung</b></th><th><b>Menge</b></th></tr>";

        $sql = $db->query("SELECT * FROM client_cart WHERE user = " . $this->get()['ID']);
        while ($row = $sql->fetch_object()) {
            $table .= "<tr><td>" . $dfo->format($row->added) . "</td><td>{$row->type}|{$row->relid}</td><td>{$row->qty}</td></tr>";
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Mails
        $table = "<h3>Gesendete E-Mails</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>Zeit</b></th><th><b>Betreff</b></th><th><b>Gelesen</b></th></tr>";

        $sql = $db->query("SELECT * FROM client_mails WHERE user = " . $this->get()['ID']);
        while ($row = $sql->fetch_object()) {
            $table .= "<tr><td>" . $dfo->format($row->time) . "</td><td>{$row->subject}</td><td>" . ($row->seen ? "Ja" : "Nein") . "</td></tr>";
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Tickets
        $table = "<h3>Support-Tickets</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>Zeit</b></th><th><b>Betreff</b></th></tr>";

        $sql = $db->query("SELECT * FROM support_tickets WHERE customer = " . $this->get()['ID']);
        while ($row = $sql->fetch_object()) {
            $table .= "<tr><td>" . $dfo->format($row->created) . "</td><td>{$row->subject}</td></tr>";
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Letters
        $table = "<h3>Gesendete Briefe</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>Zeit</b></th><th><b>Betreff</b></th></tr>";

        $sql = $db->query("SELECT * FROM client_letters WHERE client = " . $this->get()['ID']);
        while ($row = $sql->fetch_object()) {
            $table .= "<tr><td>" . $dfo->format($row->date) . "</td><td>{$row->subject}</td></tr>";
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Calls
        $table = "<h3>Telefonate</h3><style>table, th, td {border: 1px solid black;}</style><br /><table><tr><th><b>Start</b></th><th><b>Ende</b></th><th><b>Thema</b></th></tr>";

        $sql = $db->query("SELECT * FROM client_calls WHERE user = " . $this->get()['ID']);
        while ($row = $sql->fetch_object()) {
            $table .= "<tr><td>" . $dfo->format($row->time) . "</td><td>" . $dfo->format($row->endtime) . "</td><td>{$row->subject}</td></tr>";
        }

        $table .= "</table>";

        $pdf->writeHTML($table, true, false, false, false, '');

        // Output
        return $pdf;
    }

    // Get account data
    public function getAccount($acc)
    {
        global $db, $CFG;

        $acc = $db->real_escape_string($acc);
        $sql = $db->query("SELECT `data` FROM accounts WHERE user = " . $this->get()['ID'] . " AND account = '$acc'");
        return $sql->num_rows == 1 ? unserialize($sql->fetch_object()->data) : false;
    }

    // Set account data
    public function setAccount($acc, array $data)
    {
        global $db, $CFG;

        $acc = $db->real_escape_string($acc);
        $data = $db->real_escape_string(serialize($data));
        $db->query("INSERT INTO accounts (`user`, `account`, `data`) VALUES (" . $this->get()['ID'] . ", '$acc', '$data') ON DUPLICATE KEY UPDATE `data` = '$data'");
    }

    // Get support prio
    public function getSupportPrio($prio)
    {
        $prio = intval($prio);
        $disabled = explode(",", $this->get()['disabled_support_prio']);
        $alternatives = [3, 4, 5, 2, 1];

        foreach ($disabled as $d) {
            $pos = array_search($d, $alternatives);
            if ($pos !== false) {
                unset($alternatives[$pos]);
            }
        }

        $alternatives = array_values($alternatives);
        if (!count($alternatives)) {
            $alternatives = [3];
        }

        return in_array($prio, $disabled) ? $alternatives[0] : $prio;
    }

    // Get email salutation for user
    public function getSalutation()
    {
        global $CFG, $db;

        $specificity = 0;
        $salutation = @unserialize($CFG['FALLBACK_SALUTATION']);
        if (is_array($salutation) && count($salutation) > 0) {
            $salutation = ($salutation[$this->getLanguage()] ?? array_values($salutation)[0]) ?: "";
        } else {
            $salutation = "";
        }

        $sql = $db->query("SELECT * FROM salutations");
        while ($row = $sql->fetch_object()) {
            $mySpecificity = 1;

            if ($row->time != "") {
                $mySpecificity++;

                $ex = explode("-", $row->time);
                if (count($ex) != 2) {
                    continue;
                }
                $currentTime = strtotime(date("H:i"));
                $startTime = strtotime($ex[0]);
                $endTime = strtotime($ex[1]);

                if (!$startTime || !$endTime) {
                    continue;
                }

                if (!(($startTime < $endTime && $currentTime >= $startTime && $currentTime <= $endTime) || ($startTime > $endTime && ($currentTime >= $startTime || $currentTime <= $endTime)))) {
                    continue;
                }
            }

            if ($row->language != "") {
                $mySpecificity++;

                if ($row->language != $this->getLanguage()) {
                    continue;
                }
            }

            if ($row->gender != "") {
                $mySpecificity++;

                if ($row->gender != $this->get()['salutation']) {
                    continue;
                }
            }

            if ($row->cgroup >= 0) {
                $mySpecificity++;

                if ($row->cgroup != $this->get()['cgroup']) {
                    continue;
                }
            }

            if ($row->b2b >= 0) {
                $mySpecificity++;

                if ($row->b2b == 0 && !empty($this->get()['company'])) {
                    continue;
                }

                if ($row->b2b == 1 && empty($this->get()['company'])) {
                    continue;
                }
            }

            if ($row->country >= 0) {
                $mySpecificity++;

                if ($row->country != $this->get()['country']) {
                    continue;
                }
            }

            if ($mySpecificity > $specificity) {
                $specificity = $mySpecificity;
                $salutation = $row->salutation;
            }
        }

        $salutation = trim(strval($salutation)) ?: "";

        return str_replace([
            '{firstName}',
            '{lastName}',
            '{firstNameFirstLetter}',
            '{lastNameFirstLetter}',
            '{city}',
        ], [
            strip_tags($this->get()['firstname']),
            strip_tags($this->get()['lastname']),
            substr($this->get()['firstname'], 0, 1),
            substr($this->get()['lastname'], 0, 1),
            strip_tags($this->get()['city']),
        ], $salutation);
    }

    // Get avatar
    public function getAvatar($size = 120)
    {
        $size = min(2000, max(20, intval($size)));

        if ($this->get()['avatar'] == "none") {
            return "https://www.gravatar.com/avatar/?s=$size&d=mp&r=g";
        }

        return "https://www.gravatar.com/avatar/" . md5(strtolower($this->get()['mail'])) . "?s=$size&d=mp&r=g";
    }

    // Get accounts the user can impersonate
    public function impersonate($right = "*")
    {
        global $db, $CFG;

        $res = [];

        $sql = $db->query("SELECT client, rights FROM client_contacts WHERE rights != '' AND mail LIKE '" . $db->real_escape_string($this->get()['mail']) . "' AND mail != ''");
        while ($row = $sql->fetch_object()) {
            if ($right != "*") {
                $ex = explode(",", $row->rights);
                if (!in_array($right, $ex)) {
                    continue;
                }
            }

            if (!$db->query("SELECT 1 FROM clients WHERE ID = {$row->client} AND locked = 0")->num_rows) {
                continue;
            }

            $res[] = $row->client;
        }

        $res = array_unique($res);

        return $res;
    }

    // Method for trying to clear all open invoices
    public function tryToClear()
    {
        global $paymentReference;

        $this->applyCredit();

        $credit = $this->get()['credit'];

        foreach ($this->getInvoices(0) as $i) {
            $credit -= $i->getOpenAmount();
            $paymentReference .= $i->getInvoiceNo() . " ";
        }

        $paymentReference = trim($paymentReference);

        if ($credit < 0) {
            if ($this->autoPayment($credit / -1, true)) {
                $this->__construct($this->info['mail']);
                $this->applyCredit();
            }
        }
    }
}
