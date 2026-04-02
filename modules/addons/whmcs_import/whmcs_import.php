<?php
// Addon for importing data from WHMCS

class WHMCSImportAddon extends Addon
{
    public static $shortName = "whmcs_import";

    public function __construct($language)
    {
        $this->language = $language;
        $this->name = self::$shortName;
        parent::__construct();

        if (!include (__DIR__ . "/language/$language.php")) {
            throw new ModuleException();
        }

        if (!is_array($addonlang) || !isset($addonlang["NAME"])) {
            throw new ModuleException();
        }

        $this->lang = $addonlang;

        $this->info = array(
            'name' => $this->getLang('NAME'),
            'version' => "1.0",
            'company' => "sourceWAY.de",
            'url' => "https://sourceway.de/",
        );
    }

    public function delete()
    {
        return $this->deleteDir(realpath(__DIR__));
    }

    public function adminPages()
    {
        return array(
            "whmcs_import" => "admin",
        );
    }

    public function adminMenu()
    {
        return array($this->getLang("NAME") => "whmcs_import");
    }

    public function admin()
    {
        global $tpl, $var, $CFG, $db;

        $var['addonlang'] = $this->getLang();

        if (isset($_POST['step'])) {
            if (method_exists($this, $_POST['step']) && !in_array($_POST['step'], [
                "admin",
                "adminMenu",
                "adminPages",
                "delete",
                "activate",
                "deactivate",
                "db",
                "id",
            ]) && substr($_POST['step'], 0, 1) != "_") {
                $method = $_POST['step'];
                $this->$method();
                exit;
            } else {
                die($this->getLang("MNF"));
            }
        }

        $tpl = __DIR__ . "/templates/admin.tpl";
    }

    private function clients()
    {
        global $db, $CFG;

        $mdb = $this->db();

        $sql = $mdb->query("SELECT * FROM tblclients ORDER BY id ASC");
        $overall = $sql->num_rows;

        @$start = $db->query("SELECT ID FROM clients ORDER BY ID DESC LIMIT 1")->fetch_object()->ID ?: 0;
        $_SESSION['whmcs_import_client_id_start'] = $start;
        $highestId = $start;

        while ($row = $sql->fetch_object()) {
            $id = $start + $row->id;
            if ($id > $highestId) {
                $highestId = $id;
            }

            foreach ($row as &$v) {
                $v = utf8_encode(trim(html_entity_decode($v)));
            }

            if ($db->query("SELECT 1 FROM clients WHERE mail = '" . $db->real_escape_string($row->email) . "'")->num_rows) {
                $row->email = htmlentities($row->email);
                die("Email address {$row->email} is not unique");
            }

            $limit = doubleval($CFG['POSTPAID_DEF']);
            $db->query("INSERT INTO clients (`ID`, `firstname`, `lastname`, `mail`, postpaid) VALUES ($id, '" . $db->real_escape_string($row->firstname) . "', '" . $db->real_escape_string($row->lastname) . "', '" . $db->real_escape_string($row->email) . "', $limit)");

            $user = User::getInstance($id, "ID");
            if (!($user instanceof User) || !$user) {
                die("Failure while importing client #$id");
            }

            $ex = explode(" ", $row->address1);

            $country = 0;

            if (!empty($row->country) && strlen($row->country) == 2) {
                $sql2 = $db->query("SELECT ID FROM client_countries WHERE alpha2 = '" . $db->real_escape_string($row->country) . "' LIMIT 1");
                if ($sql2->num_rows) {
                    $country = $sql2->fetch_object()->ID;
                }
            }

            $user->set([
                "company" => $row->companyname,
                "street_number" => array_pop($ex),
                "street" => implode(" ", $ex),
                "postcode" => $row->postcode,
                "city" => $row->city,
                "telephone" => $row->phonenumber,
                "credit" => $row->credit,
                "registered" => strtotime($row->datecreated),
                "country" => $country,
            ]);
        }

        $db->query("ALTER TABLE clients AUTO_INCREMENT = " . intval($highestId + 1) . ";");

        die("ok$overall clients imported");
    }

    private function id($id)
    {
        return $_SESSION['whmcs_import_client_id_start'] + $id;
    }

    private function domains()
    {
        global $db, $CFG;

        $mdb = $this->db();

        $sql = $mdb->query("SELECT * FROM tbldomains WHERE `status` = 'Active' ORDER BY id ASC");
        $overall = $sql->num_rows;

        while ($row = $sql->fetch_object()) {
            $status = $row->type == "Register" ? "REG_OK" : "KK_OK";
            $row->domain = strtolower($row->domain);

            $db->query("INSERT INTO domains (`user`, `domain`, `recurring`, `created`, `expiration`, `status`, `registrar`) VALUES ({$this->id($row->userid)}, '" . $db->real_escape_string($row->domain) . "', {$row->recurringamount}, '" . $db->real_escape_string($row->registrationdate) . "', '" . $db->real_escape_string($row->expirydate) . "', '$status', '" . $db->real_escape_string($row->registrar) . "')");
        }

        die("ok$overall domains imported");
    }

    private function contracts()
    {
        global $db, $CFG;

        @$pidMapping = unserialize(file_get_contents(__DIR__ . "/pidmapping.txt")) ?: [];
        $mdb = $this->db();

        $ids = implode(",", array_keys($pidMapping));
        $sql = $mdb->query("SELECT * FROM tblhosting WHERE packageid IN ($ids) AND domainstatus = 'Active'");
        $overall = $sql->num_rows;

        while ($row = $sql->fetch_object()) {
            $product = $db->query("SELECT price, billing, module FROM products WHERE ID = " . $pidMapping[$row->packageid])->fetch_object();

            @$billing = [
                "Monthly" => "monthly",
                "Semiannually" => "semiannually",
                "One Time" => "onetime",
                "Annually" => "annually",
                "Quarterly" => "quarterly",
                "Biennially" => "biennially",
                "Trinnially" => "trinnially",
            ][$row->billingcycle] ?: "onetime";

            $data = [
                "date" => strtotime($row->regdate),
                "user" => $this->id($row->userid),
                "product" => $pidMapping[$row->packageid],
                "price" => $row->amount,
                "billing" => $billing,
                "module" => $product->module,
                "last_billed" => $row->nextduedate,
            ];

            $sqlq = "INSERT INTO client_products (";
            foreach (array_keys($data) as $k) {
                $sqlq .= "`$k`,";
            }
            $sqlq = rtrim($sqlq, ",");
            $sqlq .= ") VALUES (";
            foreach (array_values($data) as $v) {
                $sqlq .= "'" . $db->real_escape_string($v) . "',";
            }
            $sqlq = rtrim($sqlq, ",");
            $sqlq .= ")";
            $db->query($sqlq);
        }

        die("ok$overall contracts imported");
    }

    private function invoices()
    {
        global $db, $CFG;

        $mdb = $this->db();

        $sql = $mdb->query("SELECT * FROM tblinvoices ORDER BY id ASC");
        $overall = $sql->num_rows;

        while ($row = $sql->fetch_object()) {
            $invid = $row->invoicenum ?: $row->id;

            $invoice = new Invoice;
            $invoice->setDate($row->date);
            $invoice->setClient($this->id($row->userid));
            $invoice->setDueDate($row->duedate);
            $invoice->setStatus($row->status == "Paid" ? 1 : ($row->status == "Unpaid" ? 0 : 2));
            $invoice->setCustomNo($row->id);

            $sql2 = $mdb->query("SELECT * FROM tblinvoiceitems WHERE invoiceid = " . $row->id);

            while ($row2 = $sql2->fetch_object()) {
                $item = new InvoiceItem;
                $item->setDescription($row2->description);
                $item->setAmount($row2->amount);
                $item->setTax($row2->taxed);

                $invoice->addItem($item);
            }

            $invoice->save();
        }

        die("ok$overall invoices imported");
    }

    private function products()
    {
        global $db, $CFG, $languages;

        $mdb = $this->db();

        $gidMapping = [];

        $sql = $mdb->query("SELECT * FROM tblproductgroups ORDER BY id ASC");
        while ($row = $sql->fetch_object()) {
            $name = [];
            $cast = [];

            foreach ($languages as $key => $ln) {
                $name[$key] = $row->name;
                $cast[$key] = "";
            }

            $name = $db->real_escape_string(serialize($name));
            $cast = $db->real_escape_string(serialize($cast));

            $view = $row->hidden ? 0 : 1;

            $db->query("INSERT INTO product_categories (`name`, `cast`, `view`) VALUES ('$name', '$cast', $view)");
            $gidMapping[$row->id] = $db->insert_id;
        }

        $sql = $mdb->query("SELECT * FROM tblproducts ORDER BY id ASC");
        $overall = $sql->num_rows;

        $pidMapping = [];

        while ($row = $sql->fetch_object()) {
            $name = [];
            $desc = [];

            foreach ($languages as $key => $ln) {
                $name[$key] = $row->name;
                $desc[$key] = $row->description;
            }

            $name = $db->real_escape_string(serialize($name));
            $desc = $db->real_escape_string(serialize($desc));
            $category = array_key_exists($row->gid, $gidMapping) ? $gidMapping[$row->gid] : 0;
            $status = $row->hidden ? 0 : 1;
            $available = $row->stockcontrol ? $row->qty : -1;

            if ($row->paytype == "free") {
                $price = 0;
                $setup = 0;
                $billing = "onetime";
            } else if ($row->paytype == "onetime") {
                $sql2 = $mdb->query("SELECT * FROM tblpricing WHERE currency = 1 AND type = 'product' AND relid = {$row->id} LIMIT 1");
                $p = $sql2->fetch_object();

                $price = $p->monthly;
                $setup = $p->msetupfee;
                $billing = "onetime";
            } else {
                $sql2 = $mdb->query("SELECT * FROM tblpricing WHERE currency = 1 AND type = 'product' AND relid = {$row->id} LIMIT 1");
                $p = $sql2->fetch_object();

                $period = "";

                foreach (["monthly", "quarterly", "semiannually", "annually", "biennially", "triennially"] as $k) {
                    if ($p->$k >= 0) {
                        $period = $k;
                        break;
                    }
                }

                if ($period == "biennially") {
                    $period = "annually";
                    $p->annually = ceil($p->biennially / 2 * 100) / 100;
                    $p->asetupfee = $p->bsetupfee;
                }

                if ($period == "triennially") {
                    $period = "annually";
                    $p->annually = ceil($p->triennially / 3 * 100) / 100;
                    $p->asetupfee = $p->tsetupfee;
                }

                $price = $p->$k;
                $setupfield = substr($k, 0, 1) . "setupfee";
                $setup = $p->$setupfield;
                $billing = $k;
            }

            $maxpc = $row->allowqty ?: -1;
            $module = $db->real_escape_string(strtolower($row->servertype));
            $type = "HOSTING";

            $db->query("INSERT INTO products (`name`, `type`, `description`, `category`, `status`, `available`, `price`, `setup`, `billing`, `maxpc`, `module`) VALUES ('$name', '$type', '$desc', '$category', $status, $available, $price, $setup, '$billing', $maxpc, '$module')");

            $pidMapping[$row->id] = $db->insert_id;
        }

        file_put_contents(__DIR__ . "/pidmapping.txt", serialize($pidMapping));

        die("ok$overall products imported");
    }

    private function links()
    {
        global $db, $CFG;

        $mdb = $this->db();

        $sql = $mdb->query("SELECT * FROM tbllinks ORDER BY id ASC");
        $overall = $sql->num_rows;

        while ($row = $sql->fetch_object()) {
            $db->query("INSERT INTO cms_links (`slug`, `target`, `status`, `calls`) VALUES ('{$row->id}', '" . $db->real_escape_string($row->link) . "', 1, " . intval($row->clicks) . ")");
        }

        die("ok$overall links imported");
    }

    private function support_blacklist()
    {
        global $db, $CFG;

        $mdb = $this->db();

        $sql = $mdb->query("SELECT type, content FROM tblticketspamfilters");
        while ($row = $sql->fetch_object()) {
            $field = $row->type == "sender" ? "email" : $row->type;

            $db->query("INSERT INTO support_filter (`field`, `type`, `value`, `action`) VALUES ('$field', 'is', '" . $db->real_escape_string($row->content) . "', 'delete')");
        }

        die("oksupport blacklist imported");
    }

    private function blacklist()
    {
        global $db, $CFG;

        $mdb = $this->db();

        $sql = $mdb->query("SELECT domain FROM tblbannedemails");
        while ($row = $sql->fetch_object()) {
            $db->query("INSERT INTO blacklist_mail (`email`, `inserted`) VALUES ('@" . $db->real_escape_string($row->domain) . "', " . time() . ")");
        }

        $sql = $mdb->query("SELECT ip, reason FROM tblbannedips");
        while ($row = $sql->fetch_object()) {
            $db->query("INSERT INTO blacklist_ip (`ip`, `reason`, `inserted`) VALUES ('" . $db->real_escape_string($row->ip) . "', '" . $db->real_escape_string($row->reason) . "', " . time() . ")");
        }

        die("okblacklist imported");
    }

    private function db()
    {
        if (empty($_POST['db_host'])) {
            die("No DB host specified.");
        }

        if (empty($_POST['db_user'])) {
            die("No DB user specified.");
        }

        if (empty($_POST['db_name'])) {
            die("No DB name specified.");
        }

        $mdb = new MySQLi($_POST['db_host'], $_POST['db_user'], $_POST['db_password'], $_POST['db_name']);
        if ($mdb->connect_errno) {
            die($mdb->connect_error);
        }

        if (!$mdb->query("SELECT 1 FROM tblclients")) {
            die("No valid WHMCS database.");
        }

        return $mdb;
    }

    private function checkDb()
    {
        $_SESSION['whmcs_import_db_host'] = $_POST['db_host'];
        $_SESSION['whmcs_import_db_user'] = $_POST['db_user'];
        $_SESSION['whmcs_import_db_name'] = $_POST['db_name'];
        $_SESSION['whmcs_import_db_password'] = $_POST['db_password'];

        $this->db();
        die("ok");
    }
}
