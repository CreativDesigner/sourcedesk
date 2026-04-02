<?php
// Class for handling invoices

class Invoice
{
    private $info;

    public function __construct()
    {
        $this->info = new stdClass;
    }

    public function load($id)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT * FROM invoices WHERE ID = " . intval($id));
        if ($sql->num_rows != 1) {
            return false;
        }

        $info = $sql->fetch_object();

        foreach ($info as $k => $v) {
            $this->info->$k = $v;
        }

        return true;
    }

    public function set($arr)
    {
        foreach ($arr as $k => $v) {
            if (isset($this->info->$k)) {
                $this->info->$k = $v;
            }
        }
    }

    public function save($auto = false)
    {
        global $db, $CFG, $addons;

        if (!empty($this->info->ID)) {
            $str = "";
            foreach ($this->info as $k => $v) {
                $str .= "`$k` = '" . $db->real_escape_string($v) . "', ";
            }

            $str = rtrim($str, ", ");

            if ($this->info->status == 1) {
                $oldStatus = 0;
                $osSql = $db->query("SELECT `status` FROM invoices WHERE ID = " . $this->info->ID);
                if ($osSql->num_rows) {
                    $oldStatus = $osSql->fetch_object()->status;
                }

                if ($oldStatus != 1) {
                    $addons->runHook("InvoicePaid", [
                        "id" => $this->info->ID,
                        "inv" => $this,
                    ]);
                }

                if (!$this->info->cancel_invoice) {
                    $db->query("UPDATE client_products SET payment = 0 WHERE payment = " . $this->info->ID);
                    $db->query("UPDATE domains SET payment = 0 WHERE payment = " . $this->info->ID);
                }
            }

            $db->query("UPDATE invoices SET $str WHERE ID = " . $this->info->ID . " LIMIT 1");

            $addons->runHook("InvoiceChanged", [
                "id" => $this->info->ID,
                "inv" => $this,
            ]);
        } else {
            if ($CFG['NO_INVOICING']) {
                return;
            }

            $fields = $values = "";
            foreach ($this->info as $k => $v) {
                $fields .= "`$k`, ";
                $values .= "'" . $db->real_escape_string($v) . "', ";
            }
            $fields = rtrim($fields, ", ");
            $values = rtrim($values, ", ");
            $db->query("INSERT INTO invoices ($fields) VALUES ($values)");
            $this->info->ID = $db->insert_id;

            $nextId = $this->info->ID;

            $dist_min = max(1, intval($CFG['INVOICE_DIST_MIN']));
            $dist_max = max(1, intval($CFG['INVOICE_DIST_MAX']));

            if ($dist_max < $dist_min) {
                $dist_max = $dist_min;
            }

            if ($dist_min == $dist_max) {
                $nextId += $dist_min;
            } else {
                $nextId += rand($dist_min, $dist_max);
            }

            $db->query("ALTER TABLE invoices AUTO_INCREMENT = " . intval($nextId) . ";");

            if (!$auto) {
                $addons->runHook("InvoiceCreated", [
                    "id" => $this->info->ID,
                    "inv" => $this,
                ]);
            }
        }
    }

    public function delete()
    {
        global $db, $CFG;

        if (empty($this->info->ID)) {
            return false;
        }

        foreach ($this->getItems() as $item) {
            $item->delete();
        }

        $db->query("DELETE FROM invoices WHERE ID = " . $this->info->ID);
    }

    public function remind()
    {
        global $db, $CFG, $cur, $nfo, $dfo, $maq, $raw_cfg, $addons;

        if ($this->info->status == 3 || $this->getStatus() == 1) {
            return;
        }

        if ($this->info->client == "0") {
            $d = (object) unserialize($this->info->client_data);
            $b2b = !empty($d->company);

            $sql = $db->query("SELECT * FROM client_countries WHERE active = 1 ORDER BY name ASC");
            $var['countries'] = array();
            while ($row = $sql->fetch_object()) {
                $var['countries'][$row->ID] = $row->name;
            }

            $country = array_search($d->country, $var['countries']);
        } else {
            if ($db->query("SELECT 1 FROM clients WHERE ID = " . $this->info->client . " AND no_reminders = 0")->num_rows != 1) {
                return false;
            }

            $b2b = $db->query("SELECT 1 FROM clients WHERE ID = " . $this->info->client . " AND company != ''")->num_rows == 1;
            $country = $db->query("SELECT country FROM clients WHERE ID = " . $this->info->client)->fetch_object()->country;
        }

        $feeSum = 0;
        $newSum = 0;
        $reached = $this->getReminder() == 0;
        $sql = $db->query("SELECT * FROM reminders ORDER BY days ASC, name ASC");
        while ($row = $sql->fetch_object()) {
            if (strtotime("+{$row->days} days", strtotime($this->getDueDate())) > time()) {
                break;
            }

            if (!in_array($country, explode(",", $row->countries))) {
                continue;
            }

            if ((!$b2b && !$row->b2c) || ($b2b && !$row->b2b)) {
                continue;
            }

            $feeSum += $this->getAmount() * ($b2b ? $row->b2b_percent : $row->b2c_percent) / 100;
            $feeSum += $b2b ? $row->b2b_absolute : $row->b2c_absolute;

            if (!$reached) {
                if ($this->getReminder() == $row->ID) {
                    $reached = true;
                }

                continue;
            }

            $newSum += $this->getAmount() * ($b2b ? $row->b2b_percent : $row->b2c_percent) / 100;
            $newSum += $b2b ? $row->b2b_absolute : $row->b2c_absolute;

            $last = $row;
        }

        if (isset($last)) {
            $row = $last;
            $this->setReminder($last->ID);
            $this->save();

            if ($newSum != 0) {
                if ($this->getClient() == "0") {
                    $this->setLateFees($this->getLateFees() + $newSum);
                } else {
                    $subject = ($b2b ? $row->b2b_item : $row->b2c_item) . " (" . $this->getInvoiceNo() . ")";
                    $db->query("INSERT INTO client_transactions (user, time, amount, subject) VALUES (" . intval($this->getClient()) . ", " . time() . ", '-" . doubleval($newSum) . "', '" . $db->real_escape_string($subject) . "')");
                    $db->query("UPDATE clients SET credit = credit - " . $newSum . " WHERE ID = " . intval($this->getClient()));
                }
            }

            $m = $b2b ? $row->b2b_mail : $row->b2c_mail;
            if (!empty($m) && is_object($sql = $db->query("SELECT name FROM email_templates WHERE ID = " . intval($m))) && $sql->num_rows == 1 && ($this->getClient() > 0 || !empty($d->email))) {
                if ($this->getClient() > 0) {
                    $user = User::getInstance($this->getClient(), "ID");
                    $language = $user->getLanguage();
                    $currency = $user->getCurrency();
                    $name = $user->get()['name'];
                    $id = $user->get()['ID'];
                    $nf = $user->getNumberFormat();
                    $df = $user->getDateFormat();
                    $email = $user->get()['mail'];
                } else {
                    $language = $this->getLanguage();
                    $currency = $d->currency;
                    $name = $d->firstname . " " . $d->lastname;
                    $id = 0;
                    $nf = unserialize($raw_cfg['NUMBER_FORMAT'])[$language];
                    if (empty($nf)) {
                        $nf = $CFG['NUMBER_FORMAT'];
                    }

                    $df = unserialize($raw_cfg['DATE_FORMAT'])[$language];
                    if (empty($df)) {
                        $df = $CFG['DATE_FORMAT'];
                    }

                    $email = $d->email;
                }

                $mtObj = new MailTemplate($sql->fetch_object()->name);
                $title = $mtObj->getTitle($language);
                $mail = $mtObj->getMail($language, $name);

                $maq->enqueue([
                    "invoice" => $this->getInvoiceNo(),
                    "date" => $dfo->format($this->getDate(), 0, 0, '', $df),
                    "duedate" => $dfo->format($this->getDueDate(), 0, 0, '', $df),
                    "amount" => $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $this->getAmount(), $currency), 2, 0, $nf), $currency),
                    "fee" => $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $feeSum, $currency), 2, 0), $currency, $nf),
                    "sum" => $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $this->getAmount() + $feeSum, $currency, $nf), 2, 0), $currency),
                    "customer" => $name,
                ], $mtObj, $email, $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $id, true, 0, 0, $mtObj->getAttachments($language));
            }

            $m = $b2b ? $row->b2b_admin_mail : $row->b2c_admin_mail;
            if (!empty($m) && is_object($sql = $db->query("SELECT name FROM email_templates WHERE ID = " . intval($m))) && $sql->num_rows == 1) {
                $mtObj = new MailTemplate($sql->fetch_object()->name);

                $sql = $db->query("SELECT * FROM admins WHERE email != ''");
                while ($row = $sql->fetch_object()) {
                    if (!is_array(unserialize($row->rights)) || !in_array("16", unserialize($row->rights))) {
                        continue;
                    }

                    $title = $mtObj->getTitle($row->language);
                    $mail = $mtObj->getMail($row->language, $row->name);

                    $l = $row->language;
                    $formats = unserialize($raw_cfg['NUMBER_FORMAT']);
                    $nf = isset($formats[$l]) ? $formats[$l] : $CFG['NUMBER_FORMAT'];
                    $df = !empty($l) && isset($raw_cfg['DATE_FORMAT']) && is_array(unserialize($raw_cfg['DATE_FORMAT'])) && isset(unserialize($raw_cfg['DATE_FORMAT'])[$l]) ? unserialize($raw_cfg['DATE_FORMAT'])[$l] : $CFG['DATE_FORMAT'];

                    $url = $raw_cfg['PAGEURL'] . "admin/?p=customers&edit=" . $this->getClient();
                    if ($this->getClient() == "0") {
                        $url = $raw_cfg['PAGEURL'] . "admin/?p=einvoice&id=" . $this->info->ID;
                    }

                    $oldLang = $lang;
                    require __DIR__ . '/../languages/' . basename($row->language) . '.php';

                    $file = __DIR__ . "/" . $this->getInvoiceNo() . ".pdf";
                    $pdf = new PDFInvoice;
                    $pdf->add($this);
                    $pdf->output($file, "F", false, "");

                    $lang = $oldLang;

                    $maq->enqueue([
                        "invoice" => $this->getInvoiceNo(),
                        "date" => $dfo->format($this->getDate(), 0, 0, '', $df),
                        "duedate" => $dfo->format($this->getDueDate(), 0, 0, '', $df),
                        "amount" => $cur->infix($nfo->format($this->getAmount(), 2, 0, $nf), $cur->getBaseCurrency()),
                        "fee" => $cur->infix($nfo->format($feeSum, 2, 0, $nf), $cur->getBaseCurrency()),
                        "sum" => $cur->infix($nfo->format($this->getAmount() + $feeSum, 2, 0, $nf), $cur->getBaseCurrency()),
                        "customer" => $name,
                        "profile_link" => $url,
                    ], $mtObj, $row->email, $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", 0, true, 0, 0, array_merge([$file], $mtObj->getAttachments($row->language)));
                    unlink($file);
                }
            }

            $l = $b2b ? $row->b2b_letter : $row->b2c_letter;
            if ($l) {
                $t = $b2b ? $row->b2b_letter_text : $row->b2c_letter_text;
                $s = $b2b ? $row->b2b_letter_send : $row->b2c_letter_send;

                $sql = $db->query("SELECT * FROM client_countries WHERE active = 1 ORDER BY name ASC");
                while ($row2 = $sql->fetch_object()) {
                    if ($row2->ID == $country) {
                        $country = $row2->name;
                        $country_alpha = $row2->alpha2;
                    }
                }

                if ($this->getClient() > 0) {
                    $user = User::getInstance($this->getClient(), "ID");
                    $language = $user->getLanguage();
                    $currency = $user->getCurrency();
                    $firstname = $user->get()['firstname'];
                    $lastname = $user->get()['lastname'];
                    $id = $user->get()['ID'];
                    $nf = $user->getNumberFormat();
                    $df = $user->getDateFormat();
                    $email = $user->get()['mail'];
                    $d = (object) $user->get();
                    $salutation = $user->getSalutation();
                } else {
                    $language = $this->getLanguage();
                    $currency = $d->currency;
                    $firstname = $d->firstname;
                    $lastname = $d->lastname;
                    $id = 0;
                    $nf = unserialize($raw_cfg['NUMBER_FORMAT'])[$language];
                    if (empty($nf)) {
                        $nf = $CFG['NUMBER_FORMAT'];
                    }

                    $df = unserialize($raw_cfg['DATE_FORMAT'])[$language];
                    if (empty($df)) {
                        $df = $CFG['DATE_FORMAT'];
                    }

                    $email = $d->email;

                    $fallback = @unserialize($CFG['FALLBACK_SALUTATION']);
                    if (is_array($fallback)) {
                        if (array_key_exists($language, $fallback)) {
                            $salutation = $fallback[$language];
                        } elseif (count($fallback)) {
                            $salutation = array_values($fallback)[0];
                        }
                    }
                }

                $vars = [
                    "invoice" => $this->getInvoiceNo(),
                    "date" => $dfo->format($this->getDate(), 0, 0, '', $df),
                    "duedate" => $dfo->format($this->getDueDate(), 0, 0, '', $df),
                    "amount" => $cur->infix($nfo->format($this->getAmount(), 2, 0, $nf), $cur->getBaseCurrency()),
                    "fee" => $cur->infix($nfo->format($feeSum, 2, 0, $nf), $cur->getBaseCurrency()),
                    "sum" => $cur->infix($nfo->format($this->getAmount() + $feeSum, 2, 0, $nf), $cur->getBaseCurrency()),
                    "customer" => $name,
                    "profile_link" => $url,
                    "salutation" => $salutation,
                    "pagename" => $CFG['PAGENAME'],
                    "pageurl" => $raw_cfg['PAGEURL'],
                    "clientid" => $id,
                ];

                foreach ($vars as $k => $v) {
                    $t = str_replace("%$k%", $v, $t);
                    $t = str_replace('{$' . $k . '}', $v, $t);
                }

                $recipient = array($firstname, $lastname, $d->street, $d->street_number, $d->postcode, $d->city, $country, $language, $d->company);
                $recipient = serialize($recipient);

                $sql = $db->prepare("INSERT INTO client_letters (client, subject, `text`, sent, recipient, `date`) VALUES (?,?,?,?,?,?)");
                $sql->bind_param("ississ", $this->info->client, $row->name, $t, $sent = 0, $recipient, $a = date("Y-m-d"));
                $sql->execute();
                $id = $db->insert_id;

                if ($s !== "") {
                    $pdf = new PDFLetter($id);
                    if (!$pdf->wasFound()) {
                        return;
                    }

                    if (file_exists(__DIR__ . "/tmp.pdf")) {
                        unlink(__DIR__ . "/tmp.pdf");
                    }

                    $pdf->output(__DIR__ . "/tmp.pdf");

                    $f = false;

                    $ex = explode("#", $s, 2);
                    if (count($ex) != 2) {
                        $ex2 = explode("|", $CFG['LETTER_PROVIDER']);
                        $ex[1] = $ex[0];
                        $ex[0] = $ex2[0] ?? "";
                    }

                    if (array_key_exists($ex[0], LetterHandler::myDrivers())) {
                        foreach (LetterHandler::myDrivers()[$ex[0]]->getTypes() as $code => $name) {
                            if ($ex[1] === (string) $code) {
                                $f = true;
                            }
                        }
                    }

                    if (!$f || LetterHandler::myDrivers()[$ex[0]]->sendLetter(__DIR__ . "/tmp.pdf", true, $country_alpha, $ex[1]) !== true) {
                        if (file_exists(__DIR__ . "/tmp.pdf")) {
                            unlink(__DIR__ . "/tmp.pdf");
                        }

                        return;
                    }

                    $db->query("UPDATE client_letters SET sent = 1 WHERE ID = " . intval($id));
                    if (file_exists(__DIR__ . "/tmp.pdf")) {
                        unlink(__DIR__ . "/tmp.pdf");
                    }

                }
            }

            $addons->runHook("ReminderApplied", [
                "invoice" => $this,
                "reminder" => $last,
            ]);
        }
    }

    public function reminderLevel()
    {
        global $db, $CFG;

        if (!empty($this->info->reminder) && is_object($sql = $db->query("SELECT name, color, bold FROM reminders WHERE ID = {$this->info->reminder}")) && $sql->num_rows == 1 && is_object($i = $sql->fetch_object())) {
            return (!empty($i->color) ? "<font color=\"{$i->color}\">" : "") . ($i->bold ? "<b>" : "") . $i->name . ($i->bold ? "</b>" : "") . (!empty($i->color) ? "</font>" : "");
        }

        return false;
    }

    public function clearClientData()
    {
        $this->info->client_data = "";
    }

    public function applyCredit($force = true, $only_special = false)
    {
        global $transactions, $db, $CFG, $paymentReference;

        if ($this->getClient() == "0") {
            return false;
        }

        if (!($user = User::getInstance($this->getClient(), "ID"))) {
            return false;
        }

        if ($this->getStatus() != 0) {
            return false;
        }

        if ($only_special) {
            if ($user->get()['special_credit'] > 0) {
                $sc = $user->get()['special_credit'];
                if ($sc >= $this->getAmount()) {
                    $user->set(array("special_credit" => $sc - $this->getAmount()));
                    $item = new InvoiceItem;
                    $item->setDescription("special_credit");
                    $item->setAmount($this->getAmount() / -1);
                    $this->addItem($item);
                    $this->save();
                } else {
                    $user->set(array("special_credit" => 0));
                    $item = new InvoiceItem;
                    $item->setDescription("special_credit");
                    $item->setAmount($sc / -1);
                    $this->addItem($item);
                    $this->save();
                }
            }

            return true;
        }

        if (!$force && $user->get()['credit'] < $this->getAmount()) {
            if (!$user->autoPaymentStatus()) {
                return false;
            }

            $paymentReference = $this->getInvoiceNo();
            if (!$user->autoPayment($this->getAmount() - $user->get()['credit'])) {
                return false;
            }
        }

        $transactions->insert("invoice", $this->getId(), $this->getAmount() / -1, $this->getClient());

        $originalAmount = $this->getAmount();

        if ($user->get()['special_credit'] > 0) {
            $sc = $user->get()['special_credit'];
            if ($sc >= $this->getAmount()) {
                $user->set(array("special_credit" => $sc - $this->getAmount()));
                $item = new InvoiceItem;
                $item->setDescription("special_credit");
                $item->setAmount($this->getAmount() / -1);
                $this->addItem($item);
                $this->save();
            } else {
                $user->set(array("special_credit" => 0));
                $item = new InvoiceItem;
                $item->setDescription("special_credit");
                $item->setAmount($sc / -1);
                $this->addItem($item);
                $this->save();
            }
        }

        $originalAmount = doubleval($originalAmount);
        $db->query("UPDATE clients SET credit = credit - $originalAmount WHERE ID = {$user->get()['ID']} LIMIT 1");
        $this->setStatus(1);
        $this->save();
    }

    public function addItem(InvoiceItem $item)
    {
        if (empty($this->info->ID)) {
            $this->save(true);
        }

        $item->setInvoice($this);
        $item->save();
    }

    public function getId()
    {
        return @$this->info->ID ?: false;
    }

    public function getDate()
    {
        return @$this->info->date ?: "0000-00-00";
    }

    public function getDeliveryDate()
    {
        return @$this->info->deliverydate ? ($this->info->deliverydate == "0000-00-00" ? $this->getDate() : $this->info->deliverydate) : $this->getDate();
    }

    public function getDueDate()
    {
        return @$this->info->duedate ?: "0000-00-00";
    }

    public function getClient()
    {
        return @$this->info->client ?: 0;
    }

    public function getCustomNo()
    {
        return @$this->info->customno ?: "";
    }

    public function getClientData()
    {
        return @$this->info->client_data ?: "";
    }

    public function getStatus()
    {
        if (isset($this->info->status) && $this->info->status == 0 && $this->getAmount() == 0) {
            return 1;
        }

        return @$this->info->status ?: 0;
    }

    public function getPaidAmount()
    {
        if ($this->getStatus() == 1) {
            return $this->getAmount() + $this->getLateFees();
        }

        return @$this->info->paid_amount ?: 0;
    }

    public function getLateFees()
    {
        return @$this->info->latefee ?: 0;
    }

    public function setLateFees($r)
    {
        $this->info->latefee = $r;
    }

    public function getOpenAmount()
    {
        return $this->getAmount() + $this->getLateFees() - $this->getPaidAmount();
    }

    public function getVoucher()
    {
        return @$this->info->voucher ?: "";
    }

    public function getLetterSent()
    {
        return @$this->info->letter_sent ?: 0;
    }

    public function setLetterSent($r)
    {
        $this->info->letter_sent = $r;
    }

    public function getInvoiceNo()
    {
        global $CFG;
        if (!empty($this->getCustomNo())) {
            return $this->getCustomNo();
        }

        $no = $this->getId();
        while (strlen($no) < $CFG['MIN_INVLEN']) {
            $no = "0" . $no;
        }

        $prefix = $CFG['INVOICE_PREFIX'];
        $date = strtotime($this->getDate());
        $prefix = str_replace("{YEAR}", date("Y", $date), $prefix);
        $prefix = str_replace("{MONTH}", date("m", $date), $prefix);
        $prefix = str_replace("{DAY}", date("d", $date), $prefix);

        return $prefix . $no;
    }

    public function getShortNo()
    {
        if (!empty($this->getCustomNo())) {
            return $this->getCustomNo();
        }

        return $this->getId();
    }

    public function getItems($onlyTax = false)
    {
        global $db, $CFG;

        $items = array();
        $sql = $db->query("SELECT ID FROM invoiceitems WHERE invoice = " . $this->info->ID);
        if (!$sql) {
            return [];
        }

        while ($item = $sql->fetch_object()) {
            $obj = new InvoiceItem;
            $obj->load($item->ID);
            if (!$onlyTax || $obj->getTax()) {
                array_push($items, $obj);
            }

        }

        return $items;
    }

    public function getAmount($onlyTax = false)
    {
        $a = 0;
        foreach ($this->getItems($onlyTax) as $item) {
            $a += round($item->getAmount() * $item->getQty(), 2);
        }

        return $a;
    }

    public function cancel()
    {
        global $db, $CFG;

        $inv = new Invoice;

        if ($this->info->cancel_invoice && $inv->load($this->info->cancel_invoice)) {
            return false;
        }

        if ($this->getStatus() == "2" || $this->getStatus() == "3") {
            return false;
        }

        $this->info->cancel_invoice = 1;

        $this->applyCredit();

        $inv->setDate(date("Y-m-d"));
        $inv->setClient($this->getClient());
        $inv->setDueDate();

        foreach ($this->getItems() as $i) {
            $item = new InvoiceItem;
            $item->setDescription($i->getDescription());
            $item->setAmount($i->getAmount());
            $item->setQty($i->getQty() / -1);
            $item->setUnit($i->getUnit());
            $item->setRelid($i->getRelid());
            $item->setTax($i->getTax());
            $item->save();

            $inv->addItem($item);
        }

        $inv->save();
        $inv->applyCredit();

        $this->info->cancel_invoice = $inv->getId();
        $this->save();

        return true;
    }

    public function getCountry($field = "ID")
    {
        global $db, $CFG;

        $data = unserialize($this->getClientData());
        if ($data !== false && isset($data['country'])) {
            $sql = $db->query("SELECT ID, alpha2 FROM client_countries WHERE active = 1 AND name = '" . $db->real_escape_string($data['country']) . "'");
            if ($sql->num_rows == 1) {
                return $sql->fetch_object()->$field;
            }

            return false;
        } else {
            if ($this->getClient() == "0") {
                return false;
            }

            $u = User::getInstance($this->getClient(), "ID");
            if ($u === false) {
                return false;
            }

            return $u->get()['country'];
        }
    }

    public function getTaxAmount()
    {
        return $this->getTaxRate() == 0 ? 0 : $this->getAmount(true) - round($this->getAmount(true) * 100 / (100 + $this->getTaxRate()), 2);
    }

    public function getTaxRate()
    {
        $data = unserialize($this->getClientData());

        if ($data !== false && is_array($data) && isset($data['ptax']) && is_array($data['ptax'])) {
            return (double) $data['ptax'][1];
        }

        if ($this->getClient() == "0") {
            return 0;
        }

        $user = User::getInstance($this->getClient(), "ID");
        if (!$user) {
            return 0;
        }

        $vat = $user->getVAT(null, $this->getDate());
        return is_array($vat) ? (double) $vat[1] : 0;
    }

    public function getNet()
    {
        return $this->getAmount() - $this->getTaxAmount();
    }

    public function getGross()
    {
        return $this->getAmount();
    }

    public function getReminders()
    {
        return $this->info->no_reminders ? false : true;
    }

    public function getReminder()
    {
        return $this->info->reminder;
    }

    public function setDate($date)
    {
        $this->info->date = $date;
    }

    public function setDeliveryDate($date)
    {
        $this->info->deliverydate = $date;
    }

    public function setDueDate($duedate = false)
    {
        global $CFG;
        $invdate = $this->info->date > "0000-00-00" ? $this->info->date : date("Y-m-d");
        if (false === $duedate) {
            $default = $CFG['INVOICE_DUEDATE'];
            if ($this->info->client && $uI = User::getInstance($this->info->client, "ID")) {
                if ($uI->get()['inv_due'] >= 0) {
                    $default = $uI->get()['inv_due'];
                }
            }

            $duedate = date("Y-m-d", strtotime("+$default days", strtotime($invdate)));
        }

        $this->info->duedate = $duedate;
    }

    public function setClient($clientId)
    {
        $this->info->client = $clientId;
    }

    public function setCustomNo($customNo)
    {
        $this->info->customno = $customNo;
    }

    public function setClientData($clientData)
    {
        $this->info->client_data = $clientData;
    }

    public function setStatus($status)
    {
        $this->info->status = $status;
    }

    public function setVoucher($voucher)
    {
        $this->info->voucher = $voucher;
    }

    public function setReminders($r)
    {
        $this->info->no_reminders = $r ? 0 : 1;
    }

    public function setReminder($r)
    {
        $this->info->reminder = $r;
    }

    public function setPaidAmount($r)
    {
        $this->info->paid_amount = $r;
    }

    public function getAttachment()
    {
        return $this->info->attachment;
    }

    public function setAttachment($a)
    {
        $this->info->attachment = basename($a);
    }

    public function send($type = "new")
    {
        global $CFG, $cur, $nfo, $dfo, $maq, $lang, $raw_cfg;

        if ($CFG['NO_INVOICING'] && empty($this->info->ID)) {
            return;
        }

        if ($this->info->status == "3") {
            return false;
        }

        if ($type == "new") {
            $tpl = "Neue Rechnung";
        } else if ($type == "send") {
            $tpl = "Rechnung";
        }

        if (!isset($tpl)) {
            return false;
        }

        if ($this->getClient() == 0) {
            $d = (object) unserialize($this->getClientData());
            if (empty($d->email)) {
                return false;
            }

            $language = $this->getLanguage();
            $currency = $d->currency;
            $nf = unserialize($raw_cfg['NUMBER_FORMAT'])[$language];
            if (empty($nf)) {
                $nf = $CFG['NUMBER_FORMAT'];
            }

            $df = unserialize($raw_cfg['DATE_FORMAT'])[$language];
            if (empty($df)) {
                $df = $CFG['DATE_FORMAT'];
            }

            $name = $d->firstname . " " . $d->lastname;
            $email = $d->email;
            $uid = 0;
        } else {
            if (false === ($user = User::getInstance($this->getClient(), "ID"))) {
                return false;
            }

            $language = $user->getLanguage();
            $currency = $user->getCurrency();
            $nf = $user->getNumberFormat();
            $df = $user->getDateFormat();
            $name = $user->get()['name'];
            $email = $user->get()['mail'];
            $uid = $user->get()['ID'];
        }

        $mtObj = new MailTemplate($tpl);
        $title = $mtObj->getTitle($language);

        $mail = $mtObj->getMail($language, $name);

        $oldLang = $lang;
        require __DIR__ . '/../languages/' . basename($language) . '.php';

        $file = __DIR__ . "/" . $this->getInvoiceNo() . ".pdf";
        $pdf = new PDFInvoice;
        $pdf->add($this);
        $pdf->output($file, "F", false, "");

        $lang = $oldLang;

        $maq->enqueue([
            "invoice" => $this->getInvoiceNo(),
            "amount" => $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $this->getAmount(), $currency), 2, 0, $nf), $currency),
            "date" => $dfo->format($this->getDate(), 0, 0, "", $df),
            "due" => $dfo->format($this->getDueDate(), 0, 0, "", $df),
        ], $mtObj, $email, $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $uid, true, 0, 0, array_merge(array($file), $mtObj->getAttachments($language)));
        unlink($file);
    }

    public function getLanguage()
    {
        global $CFG;

        if ($this->getClient() != "0") {
            $uI = new User($this->getClient(), "ID");
            return $uI->getLanguage();
        } else {
            $data = unserialize($this->getClientData());
            if (!empty($data['language']) && file_exists(__DIR__ . "/../languages/" . basename($data['language']) . ".php")) {
                return basename($data['language']);
            }

            return $CFG['LANG'];
        }
    }

    public function isEncashment()
    {
        return @!empty($this->info->encashment_provider);
    }

    public function getEncashmentFile()
    {
        return @$this->info->encashment_file ?: "";
    }

    public function encashmentStatus()
    {
        global $lang;

        if (!$this->isEncashment()) {
            return $lang['INVOICE']['NOENCASHMENT'];
        }

        if (!array_key_exists($this->info->encashment_provider, EncashmentHandler::getDrivers())) {
            return $lang['INVOICE']['UNKENCASHMENT'] . " " . $this->info->encashment_provider;
        }

        if (empty($this->info->encashment_file)) {
            return $lang['INVOICE']['UNKENCASHMENT2'];
        }

        return EncashmentHandler::getDrivers()[$this->info->encashment_provider]->claimStatus($this->info->encashment_file) ?: $lang['INVOICE']['ENCSTAFAI'];
    }

    public function getEncashmentProvider()
    {
        return $this->info->encashment_provider;
    }

    public function claimEncashment($provider, $reason, $note)
    {
        global $db, $CFG, $lang;
        $l = $lang['INVOICE'];

        if ($this->info->status == "3") {
            return [false, $l['ENCFA1']];
        }

        if ($uI = User::getInstance($this->getClient(), "ID")) {
            $debtor = (object) $uI->get();
            $debtor->country = $debtor->country_alpha2;
        } else {
            $data = unserialize($this->getClientData());
            if ($data === false) {
                return array(false, $l['ENCFA2']);
            }

            $country = $data['country'];
            if (strlen($country) != 2) {
                $sql = $db->query("SELECT alpha2 FROM client_countries WHERE name = '" . $db->real_escape_string($country) . "'");
                if ($sql->num_rows != 1) {
                    return array(false, $l['ENCFA3']);
                }

                $country = $sql->fetch_object()->alpha2;
            }

            $debtor = (object) array(
                "ID" => $data['ID'],
                "firstname" => $data['firstname'],
                "lastname" => $data['lastname'],
                "company" => $data['company'],
                "street" => $data['street'],
                "street_number" => $data['street_number'],
                "postcode" => $data['postcode'],
                "city" => $data['city'],
                "country" => $country,
                "telephone" => "",
                "mail" => $data['email'],
            );
        }

        $claim = (object) array(
            "invoice" => $this->getInvoiceNo(),
            "reason" => $reason,
            "net" => $this->getNet(),
            "amount" => $this->getGross(),
            "latefee" => $this->getGrossLateFees(),
            "latefee_date" => $this->getLastLateFeeDate(),
            "date" => $this->getDate(),
            "duedate" => $this->getDueDate(),
            "lastnotice" => $this->getLastReminderDate(),
            "note" => $note,
        );

        $res = EncashmentHandler::getDrivers()[$provider]->newClaim($debtor, $claim);
        if ($res[0] === false) {
            return array(false, $res[1]);
        }

        $this->info->encashment_provider = $provider;
        $this->info->encashment_file = $res[1];
        $this->save();
        return array(true, "");
    }

    public function getLastReminderDate()
    {
        global $db, $CFG;
        $reminder = $this->getReminder();
        if ($reminder > 0 && $sql = $db->query("SELECT days FROM reminders WHERE ID = $reminder")) {
            return date("Y-m-d", strtotime("+" . $sql->fetch_object()->days . " days", strtotime($this->getDueDate())));
        }

        return $this->getDate();
    }

    public function getGrossLateFees()
    {
        global $db, $CFG;
        $sum = $this->getLateFees();
        $sql = $db->query("SELECT amount FROM client_transactions WHERE subject LIKE '% ({$this->getInvoiceNo()})'");
        while ($row = $sql->fetch_object()) {
            $sum -= $row->amount;
        }

        return $sum;
    }

    public function getLastLateFeeDate()
    {
        global $db, $CFG;
        $sum = $this->getLateFees();
        $sql = $db->query("SELECT time FROM client_transactions WHERE subject LIKE '% ({$this->getInvoiceNo()})' ORDER BY time DESC, ID DESC");
        if ($sql->num_rows == 0) {
            return "0000-00-00";
        }

        return date("Y-m-d", $sql->fetch_object()->time);
    }

    public function getInfo()
    {
        return $this->info;
    }
}
