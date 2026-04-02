<?php
// Addon for importing data from WEBFAKT

class WebfaktImportAddon extends Addon
{
    public static $shortName = "webfakt_import";

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
            'version' => "1.1",
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
            "webfakt_import" => "admin",
        );
    }

    public function adminMenu()
    {
        return array($this->getLang("NAME") => "webfakt_import");
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
        global $db, $CFG, $sec;

        $clients = $_POST['clients'] ?? [];
        if (!is_array($clients)) {
            $clients = [];
        }

        $mdb = $this->db();

        $sql = $mdb->query("SELECT * FROM kunden ORDER BY ID ASC");
        $overall = 0;

        @$highestId = $db->query("SELECT ID FROM clients ORDER BY ID DESC LIMIT 1")->fetch_object()->ID ?: 0;
        $_SESSION['webfakt_import_client_id_start'] = $offset = 0;

        while ($row = $sql->fetch_object()) {
            $id = $row->KUNDNR;

            if (!in_array($id, $clients)) {
                continue;
            }

            if ($id <= $highestId) {
                $_SESSION['webfakt_import_client_id_start'] = $offset = $highestId - $id + 1;
                $id = $highestId + 1;
            } else {
                $id += $offset;
            }

            if ($id > $highestId) {
                $highestId = $id;
            }

            if (empty($row->EMAIL)) {
                $row->EMAIL = $id . "@webfakt.de";
            }

            if ($db->query("SELECT 1 FROM clients WHERE mail = '" . $db->real_escape_string($row->EMAIL) . "'")->num_rows) {
                $row->EMAIL = htmlentities($row->EMAIL);
                die("Email address {$row->EMAIL} is not unique");
            }

            $limit = doubleval($CFG['POSTPAID_DEF']);
            $db->query("INSERT INTO clients (`ID`, `firstname`, `lastname`, `mail`, postpaid) VALUES ($id, '" . $db->real_escape_string($row->VORNAME) . "', '" . $db->real_escape_string($row->NAME) . "', '" . $db->real_escape_string($row->EMAIL) . "', $limit)");

            $user = User::getInstance($id, "ID");
            if (!($user instanceof User) || !$user) {
                die("Failure while importing client #$id");
            }

            $ex = explode(" ", $row->STR);

            $country = 0;

            if ($row->NA == "D") {
                $row->NA = "DE";
            }

            if (!empty($row->NA) && strlen($row->NA) == 2) {
                $sql2 = $db->query("SELECT ID FROM client_countries WHERE alpha2 = '" . $db->real_escape_string($row->NA) . "' LIMIT 1");
                if ($sql2->num_rows) {
                    $country = $sql2->fetch_object()->ID;
                }
            }

            $user->set([
                "company" => $row->FIRMA,
                "street_number" => array_pop($ex),
                "street" => implode(" ", $ex),
                "postcode" => $row->PLZ,
                "city" => $row->ORT,
                "telephone" => $row->TELEPRIVAT ?: $row->TELEGESCHAEFT,
                "fax" => $row->FAX,
                "registered" => strtotime($row->EINGABE),
                "country" => $country,
                "salutation" => $row->ANREDE == "Frau" ? "FEMALE" : "MALE",
                "vatid" => $row->UID,
                "salt" => $salt = $sec->generateSalt(),
                "pwd" => $sec->hash($row->KENNWORT, $salt),
                "locked" => $row->GESPERRT != "N" ? 1 : 0,
                "birthday" => $row->GEBDAT ?: "0000-00-00",
            ]);

            $overall++;
        }

        $db->query("ALTER TABLE clients AUTO_INCREMENT = " . intval($highestId + 1) . ";");

        die("ok$overall clients imported");
    }

    private function id($id)
    {
        return $_SESSION['webfakt_import_client_id_start'] + $id;
    }

    private function domains()
    {
        global $db, $CFG;

        $clients = $_POST['clients'] ?? [];
        if (!is_array($clients)) {
            $clients = [];
        }

        $mdb = $this->db();

        $sql = $mdb->query("SELECT * FROM domains WHERE GELOESCHT IS NULL ORDER BY ID ASC");
        $overall = 0;

        while ($row = $sql->fetch_object()) {
            if (!in_array($row->KUNDNR, $clients)) {
                continue;
            }

            $status = "REG_OK";
            $row->ACCOUNT = strtolower($row->ACCOUNT);
            $recurring = doubleval($row->DOMAINKOST);

            $db->query("INSERT INTO domains (`user`, `domain`, `recurring`, `created`, `expiration`, `status`, `registrar`) VALUES ({$this->id($row->KUNDNR)}, '" . $db->real_escape_string($row->ACCOUNT) . "', $recurring, '" . $db->real_escape_string($row->DOMAINDAT) . "', '" . $db->real_escape_string($row->ABLAUFDOMAIN) . "', '$status', '')");
            $overall++;
        }

        die("ok$overall domains imported");
    }

    private function contracts()
    {
        global $db, $CFG;

        $clients = $_POST['clients'] ?? [];
        if (!is_array($clients)) {
            $clients = [];
        }

        @$pidMapping = unserialize(file_get_contents(__DIR__ . "/pidmapping.txt")) ?: [];
        $mdb = $this->db();

        $ids = implode("','", array_keys($pidMapping));
        $sql = $mdb->query("SELECT * FROM webserver WHERE ARTNRSERVER IN ('$ids') AND GELOESCHT IS NULL");
        $overall = 0;

        while ($row = $sql->fetch_object()) {
            if (!in_array($row->KUNDNR, $clients)) {
                continue;
            }

            $product = $mdb->query("SELECT VKI FROM artikel WHERE ARTNR = '" . $mdb->real_escape_string($row->ARTNRSERVER) . "'")->fetch_object();

            @$billing = [
                "monatlich" => "monthly",
                "jährlich" => "annually",
            ][$row->ZAHLUNGSART] ?: "monthly";

            $data = [
                "date" => strtotime($row->DATUM),
                "user" => $this->id($row->KUNDNR),
                "product" => $pidMapping[$row->ARTNRSERVER],
                "price" => $product->VKI * ($row->ZAHLUNGSART == "jährlich" ? 12 : 1),
                "billing" => $billing,
                "module" => "",
                "last_billed" => $row->NRECHNUNG,
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

            $overall++;
        }

        die("ok$overall contracts imported");
    }

    private function services()
    {
        global $db, $CFG;

        $clients = $_POST['clients'] ?? [];
        if (!is_array($clients)) {
            $clients = [];
        }

        $mdb = $this->db();

        $sql = $mdb->query("SELECT * FROM dienstleistungen WHERE GELOESCHT IS NULL");
        $overall = 0;

        while ($row = $sql->fetch_object()) {
            if (!in_array($row->KUNDNR, $clients)) {
                continue;
            }

            $billing = [
                "monatlich" => "1 month",
                "zweimonatlich" => "2 months",
                "vierteljährlich" => "3 months",
                "halbjährlich" => "6 months",
                "jährlich" => "1 year",
                "zweijährlich" => "2 years",
                "dreijährlich" => "3 years",
                "vierjährlich" => "4 years",
                "fünfjährlich" => "5 years",
                "zehnjährlich" => "10 years",
            ];

            if (!array_key_exists($row->ABRECHNUNGSART, $billing)) {
                continue;
            }

            $billing = $billing[$row->ABRECHNUNGSART];

            $data = [
                "user" => $this->id($row->KUNDNR),
                "first" => $row->DATUM,
                "last" => date("Y-m-d", strtotime("-" . $billing, strtotime($row->NRECHNUNG))),
                "status" => $row->ABRECH == "J" ? "1" : "0",
                "description" => $row->LEISTUNG,
                "amount" => $row->PREIS * $row->ANZAHL,
                "show_period" => "1",
                "period" => $billing,
            ];

            $sqlq = "INSERT INTO invoice_items_recurring (";
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

            $overall++;
        }

        die("ok$overall services imported");
    }

    private function invoices()
    {
        global $db, $CFG;

        $clients = $_POST['clients'] ?? [];
        if (!is_array($clients)) {
            $clients = [];
        }

        $due = max(intval($CFG["INVOICE_DUEDATE"]), 0);

        $mdb = $this->db();

        $sql = $mdb->query("SELECT * FROM rechnung WHERE `STATUS` IN ('Rechnung', 'Gutschrift') ORDER BY RECHNNR ASC");
        $overall = 0;

        while ($row = $sql->fetch_object()) {
            if (!in_array($row->KUNDENNR, $clients)) {
                continue;
            }

            $invoice = new Invoice;
            $invoice->setDate($row->RECHDATUM);
            $invoice->setClient($this->id($row->KUNDENNR));
            $invoice->setDueDate();
            $invoice->setStatus($row->BEZAHLT != null ? 1 : 0);
            $invoice->setCustomNo($row->RECHNNR);

            $factor = 1;
            if ($row->STATUS == "Gutschrift") {
                $factor = -1;
            }

            $sql2 = $mdb->query("SELECT * FROM rechartikel WHERE RECHNNR = " . $row->RECHNNR);

            while ($row2 = $sql2->fetch_object()) {
                $item = new InvoiceItem;
                $item->setDescription($row2->ARTIKEL1);
                $item->setAmount(($row2->GESPREIS / $factor) * (1 + $row2->MWST / 100));
                $item->setTax($row2->MWST > 0);

                $invoice->addItem($item);
            }

            $invoice->save();

            $overall++;
        }

        die("ok$overall invoices imported");
    }

    private function products()
    {
        global $db, $CFG, $languages;

        $mdb = $this->db();

        $sql = $mdb->query("SELECT * FROM artikel ORDER BY ARTNR ASC, ID ASC");
        $overall = $sql->num_rows;
        $pidMapping = [];

        while ($row = $sql->fetch_object()) {
            $name = [];
            $desc = [];

            foreach ($languages as $key => $ln) {
                $name[$key] = $row->ARTIKEL1;
                $desc[$key] = $row->LANGTEXT;
            }

            $name = $db->real_escape_string(serialize($name));
            $desc = $db->real_escape_string(serialize($desc));
            $category = 0;
            $status = $row->ARTIKELONLINE == "N" ? 0 : 1;
            $available = -1;

            $setup = 0;
            $price = $row->VKI;
            $billing = "onetime";

            $maxpc = -1;
            $module = "";
            $type = "HOSTING";

            $db->query("INSERT INTO products (`name`, `type`, `description`, `category`, `status`, `available`, `price`, `setup`, `billing`, `maxpc`, `module`) VALUES ('$name', '$type', '$desc', '$category', $status, $available, $price, $setup, '$billing', $maxpc, '$module')");

            $pidMapping[strval($row->ARTNR)] = $db->insert_id;
        }

        file_put_contents(__DIR__ . "/pidmapping.txt", serialize($pidMapping));

        die("ok$overall products imported");
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
        $mdb->set_charset("UTF8");

        if (!$mdb->query("SELECT 1 FROM kunden")) {
            die("No valid WEBFAKT database.");
        }

        return $mdb;
    }

    private function checkDb()
    {
        $_SESSION['webfakt_import_db_host'] = $_POST['db_host'];
        $_SESSION['webfakt_import_db_user'] = $_POST['db_user'];
        $_SESSION['webfakt_import_db_name'] = $_POST['db_name'];
        $_SESSION['webfakt_import_db_password'] = $_POST['db_password'];

        $mdb = $this->db();

        $clients = [];
        $sql = $mdb->query("SELECT KUNDNR, VORNAME, NAME, FIRMA FROM kunden ORDER BY KUNDNR ASC");
        while ($row = $sql->fetch_object()) {
            $desc = "";
            if ($row->FIRMA) {
                $desc = $row->FIRMA;
                if ($row->VORNAME) {
                    $desc .= " (" . $row->VORNAME;
                    if ($row->NAME) {
                        $desc .= " " . $row->NAME;
                    }
                    $desc .= ")";
                }
            } else {
                $desc = $row->VORNAME;
                if ($row->NAME) {
                    $desc .= " " . $row->NAME;
                }
            }

            $desc = htmlentities($desc);
            $clients[$row->KUNDNR] = "#{$row->KUNDNR} - $desc";
        }

        die(json_encode([
            "status" => "ok",
            "clients" => $clients,
        ]));
    }
}
