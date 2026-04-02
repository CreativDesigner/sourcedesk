<?php
// Addon for sevDesk integration

class SevDeskIntegration extends Addon
{
    public static $shortName = "sevdesk";

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
            'version' => "1.2",
            'company' => "sourceWAY.de",
            'url' => "https://sourceway.de/",
        );
    }

    public function getSettings()
    {
        return array(
            "url" => array("placeholder" => "https://my.sevdesk.de/", "default" => "https://my.sevdesk.de/", "label" => $this->getLang("URL")),
            "api_key" => array("placeholder" => "8aee7e017645b1daa38677c1272858c4", "label" => $this->getLang("TOKEN"), "type" => "password", "help" => $this->getLang("TOKENH")),
        );
    }

    public function activate()
    {
        global $CFG, $db;
        parent::activate();

        $db->query("ALTER TABLE `clients` ADD `sevdesk_id` INT(11) NOT NULL DEFAULT '0';");
        $db->query("ALTER TABLE `invoices` ADD `sevdesk_id` INT(11) NOT NULL DEFAULT '0';");
    }

    public function delete()
    {
        return $this->deleteDir(realpath(__DIR__));
    }

    public function adminPages()
    {
        return ["sevdesk" => "adminarea"];
    }

    public function adminMenu()
    {
        return ["sevDesk" => "sevdesk"];
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

        if (!is_object($user) || !($user instanceof User) || $user->get()['sevdesk_id']) {
            return false;
        }

        $genders = [
            "MALE" => "m",
            "FEMALE" => "w",
        ];

        $data = [
            "status" => "100",
            "customerNumber" => $user->get()['ID'],
            "category" => ["id" => "3", "objectName" => "Category"],
            "objectName" => "Contact",
        ];

        if ($user->get()['company']) {
            $data["name"] = $user->get()['company'];
        } else {
            $data["surename"] = $user->get()['firstname'];
            $data["familyname"] = $user->get()['lastname'];
            $data["gender"] = $genders[$user->get()['salutation']] ?? "";
        }

        $res = $this->call("Contact", $data, "POST");

        if (empty($sid = $res['objects']['id'])) {
            return false;
        }

        $user->set([
            "sevdesk_id" => $sid,
        ]);

        $countries = [
            "DE" => "1",
            "AT" => "3",
            "CH" => "2",
        ];

        $data = [
            "street" => $user->get()['street'] . " " . $user->get()['street_number'],
            "zip" => $user->get()['postcode'],
            "city" => $user->get()['city'],
            "country" => $countries[$user->get()['country_alpha2']] ?? "",
        ];

        $this->call("Contact/{$sid}/addAddress", $data, "POST");

        $data = [
            "key" => "1",
            "value" => $user->get()['mail'],
        ];

        $this->call("Contact/{$sid}/addEmail", $data, "POST");

        return $sid;
    }

    public function invoiceCreated($pars)
    {
        global $db, $CFG, $lang;

        $inv = $pars['inv'];
        $user = User::getInstance($inv->getClient(), "ID");

        if (!$user) {
            return false;
        }

        if (!($sid = $user->get()['sevdesk_id'])) {
            if (!($sid = $this->customerCreated(["user" => $user]))) {
                return false;
            }
        }

        if (!in_array($inv->getStatus(), ["0", "1"])) {
            return false;
        }
        $status = $inv->getStatus() == "0" ? "100" : "1000";

        $language = $user->get()['language'];
        if (!file_exists(__DIR__ . "/../../../languages/" . basename($language) . ".php")) {
            $language = "english";
        }

        $oldLang = $lang;
        require __DIR__ . "/../../../languages/" . basename($language) . ".php";

        $pdf = new PDFInvoice;
        $pdf->add($inv);
        $pdf = $pdf->output("", "S", false);

        $lang = $oldLang;

        $data = $files = [];
        $files["invoice.pdf"] = $pdf;

        $url = rtrim($this->options['url'], "/") . "/api/v1/Voucher/Factory/uploadTempFile?token=" . $this->options['api_key'];

        $ch = curl_init($url);

        $boundary = uniqid();
        $delimiter = '-------------' . $boundary;

        $post_data = $this->build_data_files($boundary, $data, $files);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Content-Type: multipart/form-data; boundary=" . $delimiter,
            "Content-Length: " . strlen($post_data),
        ]);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $res = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($res, true);

        $filename = $res['objects']['filename'];
        $sumNet = $inv->getNet();
        $sumTax = $inv->getTaxAmount();
        $sumGross = $inv->getGross();

        $positions = '';
        $i = 0;
        foreach ($inv->getItems() as $item) {
            $taxRate = $inv->getTaxRate();
            $net = $item->getNet();
            $desc = $item->getDescription();

            $positions .= '&voucherPosSave[' . $i . '][accountingType][id]=26&voucherPosSave[' . $i . '][accountingType][objectName]=AccountingType&voucherPosSave[' . $i . '][taxRate]=' . $taxRate . '&voucherPosSave[' . $i . '][sum]=' . $net . '&voucherPosSave[' . $i . '][net]=false&voucherPosSave[' . $i . '][comment]=' . $desc . '&voucherPosSave[' . $i . '][objectName]=VoucherPos&voucherPosSave[' . $i . '][mapAll]=true';
            $i++;
        }

        $data = 'voucher[voucherDate]=' . strtotime($inv->getDate()) . '&voucher[supplier][id]=' . $sid . '&voucher[supplier][objectName]=Contact&voucher[description]=' . urlencode($inv->getInvoiceNo()) . '&voucher[status]=' . $status . '&voucher[sumNet]=' . $sumNet . '&voucher[sumTax]=' . $sumTax . '&voucher[sumGross]=' . $sumGross . '&voucher[taxType]=default&voucher[creditDebit]=D&voucher[voucherType]=VOU&voucher[paymentDeadline]=' . strtotime($inv->getDueDate()) . '&voucher[objectName]=Voucher&voucher[mapAll]=true&voucher[total]=' . $sumGross . $positions . '&voucherPosDelete=null&filename=' . $filename . '&existenceCheck=true';

        $res = $this->call("Voucher/Factory/saveVoucher", $data, "POST", true);
        $iid = 0;
        foreach ($res['objects'] as $object) {
            if ($object['objectName'] == "Voucher") {
                $iid = intval($object['id']);
                break;
            }
        }

        if ($iid === 0) {
            return false;
        }

        $db->query("UPDATE invoices SET sevdesk_id = " . intval($iid) . " WHERE ID = " . $inv->getId());

        return $iid;
    }

    public function invoiceChanged($pars)
    {
        $inv = $pars['inv'];
        $iid = $inv->getInfo()->sevdesk_id;
        if (!$iid) {
            $iid = $this->invoiceCreated($pars);
            if (!$iid) {
                return false;
            }
        }

        $status = $inv->getStatus() == "0" ? "100" : "1000";

        $this->call("Voucher/$iid", [
            "status" => $status,
        ], "PUT");

        return true;
    }

    public function adminarea()
    {
        global $db, $CFG, $tpl, $var;
        if (!empty($_GET['sync_clients'])) {
            $sql = $db->query("SELECT ID FROM clients WHERE sevdesk_id = 0");
            while ($row = $sql->fetch_object()) {
                $this->customerCreated(["user" => User::getInstance($row->ID, "ID")]);
            }
            die("<i class='fa fa-check' style='color: green;'></i> {$sql->num_rows} " . $this->getLang("CSYNC"));
        }

        if (!empty($_GET['sync_invoices'])) {
            $inv = new Invoice;
            $sql = $db->query("SELECT ID FROM invoices WHERE sevdesk_id = 0");
            while ($row = $sql->fetch_object()) {
                if ($inv->load($row->ID)) {
                    $this->invoiceCreated(["inv" => $inv]);
                }
            }
            die("<i class='fa fa-check' style='color: green;'></i> {$sql->num_rows} " . $this->getLang("ISYNC"));
        }

        $var['l'] = $this->getLang();
        $tpl = __DIR__ . "/admin.tpl";
    }

    private function call($url, $data = null, $method = "GET", $rawData = false)
    {
        $sep = strpos($url, "?") === false ? "?" : "&";
        $url = rtrim($this->options['url'], "/") . "/api/v1/" . $url . "{$sep}token=" . $this->options['api_key'];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
        ]);

        if ($method != "GET") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

            if (is_array($data) && count($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        } else {
            if (is_array($data) && count($data)) {
                $url .= "&" . http_build_query($data);
            }

            curl_setopt($ch, CURLOPT_URL, $url);
        }

        if ($rawData) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

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
            if (is_array($content)) {
                foreach ($content as $k => $v) {
                    $data .= "--" . $delimiter . $eol
                        . 'Content-Disposition: form-data; name="' . $name . "[$k]\"" . $eol . $eol
                        . $v . $eol;
                }
            } else {
                $data .= "--" . $delimiter . $eol
                    . 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol
                    . $content . $eol;
            }
        }

        foreach ($files as $name => $content) {
            $data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="file"; filename="' . $name . '"' . $eol
            . 'Content-Type: application/pdf' . $eol
            //. 'Content-Transfer-Encoding: binary' . $eol
            ;

            $data .= $eol;
            $data .= $content . $eol;
        }
        $data .= "--" . $delimiter . "--" . $eol;

        return $data;
    }
}
