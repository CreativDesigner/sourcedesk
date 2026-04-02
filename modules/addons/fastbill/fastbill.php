<?php
// Addon for FastBill integration

class FastBillIntegration extends Addon
{
    public static $shortName = "fastbill";

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
            'name' => $this->getLang("NAME"),
            'version' => "1.0",
            'company' => "sourceWAY.de",
            'url' => "https://sourceway.de/",
        );
    }

    public function getSettings()
    {
        return array(
            "email" => array("placeholder" => "john@example.com", "label" => $this->getLang("EMAIL"), "type" => "text"),
            "api_key" => array("placeholder" => "8aee7e017645b1daa38677c1272858c4", "label" => $this->getLang("TOKEN"), "type" => "password", "help" => $this->getLang("TOKENH")),
        );
    }

    public function activate()
    {
        global $CFG, $db;
        parent::activate();

        $db->query("ALTER TABLE `clients` ADD `fastbill_id` INT(11) NOT NULL DEFAULT '0';");
        $db->query("ALTER TABLE `invoices` ADD `fastbill_id` INT(11) NOT NULL DEFAULT '0';");
    }

    public function delete()
    {
        return $this->deleteDir(realpath(__DIR__));
    }

    public function adminPages()
    {
        return ["fastbill" => "adminarea"];
    }

    public function adminMenu()
    {
        return ["FastBill" => "fastbill"];
    }

    public function hooks()
    {
        return array(
            array("CustomerCreated", "customerCreated", 0),
            array("InvoiceCreated", "invoiceCreated", 0),
            array("InvoiceChanged", "invoiceChanged", 0),
        );
    }

    public function customerCreated($pars)
    {
        $user = $pars['user'];

        if (!is_object($user) || !($user instanceof User) || $user->get()['fastbill_id']) {
            return false;
        }

        $genders = [
            "MALE" => "mr",
            "FEMALE" => "mrs",
        ];

        $res = $this->call("customer.create", [
            "CUSTOMER_NUMBER" => $user->get()['ID'],
            "CUSTOMER_TYPE" => empty($user->get()['company']) ? "consumer" : "business",
            "ORGANIZATION" => $user->get()['company'],
            "SALUTATION" => $genders[$user->get()['salutation']] ?? "",
            "FIRST_NAME" => $user->get()['firstname'],
            "LAST_NAME" => $user->get()['lastname'],
            "ADDRESS" => $user->get()['street'] . " " . $user->get()['street_number'],
            "ZIPCODE" => $user->get()['postcode'],
            "CITY" => $user->get()['city'],
            "COUNTRY_CODE" => $user->get()['country_alpha2'],
            "PHONE" => $user->get()['telephone'],
            "FAX" => $user->get()['fax'],
            "EMAIL" => $user->get()['mail'],
            "WEBSITE" => $user->get()['website'],
            "CURRENCY_CODE" => $user->get()['currency'],
            "VAT_ID" => $user->get()['VATID'],
        ]);

        $status = $res['RESPONSE']['STATUS'] ?? "";
        if ($status != "success") {
            return false;
        }

        $user->set([
            "fastbill_id" => $sid = ($res['RESPONSE']['CUSTOMER_ID'] ?? 0),
        ]);

        return $sid;
    }

    public function invoiceCreated($pars)
    {
        global $db, $CFG, $cur, $lang;

        $inv = $pars['inv'];
        $user = User::getInstance($inv->getClient(), "ID");

        if (!$user) {
            return false;
        }

        if (!($sid = $user->get()['fastbill_id'])) {
            if (!($sid = $this->customerCreated(["user" => $user]))) {
                return false;
            }
        }

        if (!in_array($inv->getStatus(), ["0", "1"])) {
            return false;
        }

        $orgLang = $lang;

        $language = $user->get()['language'];
        if (!file_exists(__DIR__ . "/../../../languages/" . basename($language) . ".php")) {
            $language = "english";
        }

        require __DIR__ . "/../../../languages/" . basename($language) . ".php";

        $pdf = new PDFInvoice;
        $pdf->add($inv);

        $lang = $orgLang;

        $data = json_encode([
            "SERVICE" => "revenue.create",
            "DATA" => [
                "INVOICE_DATE" => $inv->getDate(),
                "DUE_DATE" => $inv->getDueDate(),
                "CUSTOMER_ID" => $user->get()['fastbill_id'],
                "INVOICE_NUMBER" => $inv->getInvoiceNo(),
                "SUB_TOTAL" => $inv->getNet(),
                "VAT_TOTAL" => $inv->getTaxAmount(),
                "CURRENCY_CODE" => $cur->getBaseCurrency(),
            ],
        ]);

        $boundary = uniqid();
        $delimiter = '-------------' . $boundary;

        $rawData = $this->build_data_files($boundary, [
            "httpbody" => $data,
        ], [
            "inv.pdf" => $pdf->output("", "S", false),
        ]);

        $res = $this->call("revenue.create", $rawData, [
            "Content-Type: multipart/form-data, boundary=$delimiter",
            "Content-Length: " . strlen($rawData),
        ]);

        $status = $res['RESPONSE']['STATUS'] ?? "";
        if ($status != "success") {
            return false;
        }

        $iid = intval($res['RESPONSE']['INVOICE_ID'] ?? 0);

        $db->query("UPDATE invoices SET fastbill_id = $iid WHERE ID = " . $inv->getId());

        if ($inv->getStatus() == "1") {
            $pars['inv']->load($inv->getId());
            $this->invoiceChanged($pars);
        }

        return $iid;
    }

    public function invoiceChanged($pars)
    {
        $inv = $pars['inv'];
        $iid = $inv->getInfo()->fastbill_id;
        if (!$iid) {
            $iid = $this->invoiceCreated($pars);
            if (!$iid) {
                return false;
            }
        }

        if ($inv->getStatus() == "1") {
            $this->call("revenue.setpaid", [
                "INVOICE_ID" => $iid,
            ]);
        }

        return true;
    }

    public function adminarea()
    {
        global $db, $CFG, $tpl, $var;
        if (!empty($_GET['sync_clients'])) {
            $sql = $db->query("SELECT ID FROM clients WHERE fastbill_id = 0");
            while ($row = $sql->fetch_object()) {
                $this->customerCreated(["user" => User::getInstance($row->ID, "ID")]);
            }
            die("<i class='fa fa-check' style='color: green;'></i> {$sql->num_rows} " . $this->getLang("CSYNC"));
        }

        if (!empty($_GET['sync_invoices'])) {
            $sql = $db->query("SELECT ID FROM invoices WHERE fastbill_id = 0");
            while ($row = $sql->fetch_object()) {
                $inv = new Invoice;
                if ($inv->load($row->ID)) {
                    $this->invoiceCreated(["inv" => $inv]);
                }
            }
            die("<i class='fa fa-check' style='color: green;'></i> {$sql->num_rows} " . $this->getLang("ISYNC"));
        }

        $var['l'] = $this->getLang();
        $tpl = __DIR__ . "/admin.tpl";
    }

    private function call($service = "", $data = null, $overwriteHeader = null)
    {
        $ch = curl_init("https://my.fastbill.com/api/1.0/api.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
        ]);

        if (is_array($overwriteHeader) && count($overwriteHeader)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $overwriteHeader);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            if (!is_array($data)) {
                $data = [];
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                "SERVICE" => $service,
                "DATA" => $data,
            ]));
        }

        curl_setopt($ch, CURLOPT_USERPWD, $this->options['email'] . ":" . $this->options['api_key']);

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    }

    private function build_data_files($boundary, $fields, $files)
    {
        $data = '';
        $eol = "\r\n";

        $delimiter = '-------------' . $boundary;

        foreach ($fields as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol
                . $content . $eol;
        }

        foreach ($files as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="document"; filename="' . $name . '"' . $eol
                . 'Content-Type: application/pdf' . $eol;

            $data .= $eol;
            $data .= $content . $eol;
        }
        $data .= "--" . $delimiter . "--" . $eol;

        return $data;
    }
}
