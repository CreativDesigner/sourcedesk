<?php

// Parsing class for csv files for many major german banks

class BankCSV
{

    // This property is to temporary save the file stream into the object
    protected $filestream = null;

    // The constructor needs a file stream
    public function __construct($fs = null)
    {
        // We check if the file stream is correct
        if ($fs === null || !$fs) {
            throw new BankCSV_Exception("1001");
        }

        $this->filestream = $fs;
    }

    // Method to get the parsed data
    public function getCsvData($bank = null)
    {
        // Check if the bank exists
        if ($bank === null || !isset($this->getAvailableBanks(false, true)[$bank])) {
            throw new BankCSV_Exception("1002");
        }

        // Check if class exists
        if (!class_exists('BankCSV_' . ucfirst($bank))) {
            throw new BankCSV_Exception("1003");
        }

        $bankClass = "BankCSV_" . ucfirst($bank);
        $bankObj = new $bankClass;

        if (!($bankObj instanceof BankCSV_Model)) {
            throw new BankCSV_Exception("1003");
        }

        // Read file data
        $fileData = "";
        if ($this->filestream) {
            while (!feof($this->filestream)) {
                $fileData .= fgets($this->filestream);
            }
        }

        if (strlen(trim($fileData)) <= 0) {
            throw new BankCSV_Exception("1004");
        }

        // Get CSV data
        $csvData = $bankObj->getCsvData($fileData);
        if (!is_array($csvData)) {
            throw new BankCSV_Exception("1004");
        }

        return $csvData;
    }

    // Method returns all available banks within name
    public static function getAvailableBanks($real = false, $auto = false)
    {
        $banks = array();
        $banks["coba"] = "Commerzbank";
        $banks["fidor"] = "Fidor-Bank";
        $banks["post"] = "Postbank";
        $banks["sparkasse"] = "Sparkasse";
        $banks["n26"] = "N26";
        if (!$real) {
            $banks["starmoney"] = "Starmoney (Business)";
            $banks["mt940"] = "MT940";
        }

        $banks["vr"] = "VR-Bank";

        if ($auto) {
            $banks["hbci"] = "HBCI";
            $banks["aqbanking"] = "AqBanking";
            $banks["bunq"] = "Bunq";
            $banks["holvi"] = "Holvi";
            $banks["cpsds"] = "CPS-Datensysteme";
            $banks["smskaufen"] = "SMSkaufen";
            $banks["inwx"] = "InterNetWorX";
            $banks["contabo"] = "Contabo";
            $banks["noez"] = "Noez";
            $banks["paypal"] = "PayPal";
            $banks["stripe"] = "Stripe";
            $banks["udreselling"] = "UD-Reselling";
            $banks["fix"] = "Fix-Konto";
        }

        ksort($banks);
        return $banks;
    }

}

// Model for bank implementation

abstract class BankCSV_Model
{
    protected $required = null;
    protected $firstLine = null;

    abstract public function getCsvData($data = null);

    // Method to check if the first line is correct
    protected function checkRequired()
    {
        foreach ($this->required as $v) {
            if (!in_array($v, $this->firstLine)) {
                return false;
            }
        }

        return true;
    }
}

// Class for Holvi API
class Holvi_API
{
    public $ch;
    public $jwt;
    private $file;

    public function __construct($email, $pwd)
    {
        $this->file = __DIR__ . "/.holvi." . time() . "." . rand(10000000, 99999999);

        // Perform login
        $this->ch = curl_init("https://app.holvi.com/login/");
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->file);
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->file);
        curl_setopt($this->ch, CURLOPT_HEADER, true);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
            "Referer: https://app.holvi.com/login/",
            "Cookie: csrftoken=qrdX8iL574ToS9s4DmvTaZIArNStyhLZ",
            "Content-Type: application/x-www-form-urlencoded",
        ]);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query([
            "csrfmiddlewaretoken" => "qrdX8iL574ToS9s4DmvTaZIArNStyhLZ",
            "username" => $email,
            "pass1" => $pwd,
        ]));

        $res = curl_exec($this->ch);

        $pos = strpos($res, "holvi_jwt_auth=");
        if ($pos !== false) {
            $this->jwt = substr($res, $pos + 15);
            $this->jwt = substr($this->jwt, 0, strpos($this->jwt, ";"));
        }

        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_HEADER, false);

        curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
            "Referer: https://app.holvi.com/group",
            "Authorization: Bearer " . $this->jwt,
        ]);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, "");
        curl_setopt($this->ch, CURLOPT_POST, false);
    }

    public function __destruct()
    {
        curl_close($this->ch);
        @unlink($this->file);
    }
}

// Implementation of Holvi
class BankCSV_Holvi extends BankCSV_Model
{
    public function getCsvData($data = null)
    {
        $data = @unserialize($data);
        if (!is_array($data)) {
            return false;
        }

        return $data;
    }

    public function automationSettings()
    {
        return array("E-Mailadresse", "Passwort", "Konto-ID");
    }

    // Method for getting the account balance
    public function getBalance($account, $credentials)
    {
        return $this->getCSV($account, $credentials, true);
    }

    // Method for getting the CSV
    public function getCSV($accountId, $credentials, $balance = false)
    {
        $api = new Holvi_API($credentials["E-Mailadresse"], $credentials["Passwort"]);

        if ($balance) {
            curl_setopt($api->ch, CURLOPT_URL, "https://holvi.com/api/pool/" . $credentials["Konto-ID"] . "/category-insight");
            $res = curl_exec($api->ch);
            if (!$res || !is_array($res = @json_decode($res, true))) {
                return false;
            }

            return doubleval($res['psd']['initial_balance']);
        }

        curl_setopt($api->ch, CURLOPT_URL, "https://holvi.com/api/pool/" . $credentials["Konto-ID"] . "/feed/?_=" . time() . "&o=-timestamp&page_size=50&time_from=" . date("Y-m-d", strtotime("-7 days")) . "T13%3A08%3A00.585Z&type=transaction");
        $res = curl_exec($api->ch);
        if (!$res || !is_array($res = @json_decode($res, true)) || !array_key_exists("results", $res)) {
            return false;
        }

        $transactions = $res["results"];
        $res = [];

        foreach ($transactions as $transaction) {
            $res[] = array(
                'time' => date("Y-m-d", strtotime($transaction["create_time"])),
                'transactionId' => $transaction["id"],
                'amount' => floatval($transaction["data"]["amount"]),
                'subject' => $transaction["data"]["message"],
                'sender' => $transaction["data"]["sender"],
            );
        }

        return serialize($res);
    }
}

// Implementation of Bunq
class BankCSV_Bunq extends BankCSV_Model
{
    public function getCsvData($data = null)
    {
        $data = @unserialize($data);
        if (!is_array($data)) {
            return false;
        }

        return $data;
    }

    public function automationSettings()
    {
        return array("API-Schlüssel");
    }

    // Method for getting the account balance
    public function getBalance($account, $credentials)
    {
        return $this->getCSV($account, $credentials, true);
    }

    // Method for getting the CSV
    public function getCSV($accountId, $credentials, $balance = false)
    {
        $api = $this->api($accountId, $credentials);

        $accounts = \bunq\Model\Generated\Endpoint\MonetaryAccountBank::listing()->getValue();
        foreach ($accounts as $account) {
            foreach ($account->getAlias() as $alias) {
                if ($alias->getType() == "IBAN") {
                    if (str_replace(" ", "", $alias->getValue()) == str_replace(" ", "", $accountId)) {
                        $accountId = $account->getId();

                        if ($balance) {
                            return floatval($account->getBalance()->getValue());
                        }

                        $res = [];

                        $transactions = \bunq\Model\Generated\Endpoint\Payment::listing($accountId)->getValue();
                        foreach ($transactions as $transaction) {
                            $res[] = array(
                                'time' => $transaction->getCreated(),
                                'transactionId' => $transaction->getId(),
                                'amount' => $transaction->getAmount()->getValue(),
                                'subject' => $transaction->getDescription(),
                                'sender' => $transaction->getCounterpartyAlias()->getIban(),
                            );
                        }

                        return serialize($res);
                    }

                    break;
                }
            }
        }

        return false;
    }

    // Internal method for getting the Bunq API
    private function api($account, $credentials)
    {
        $apiContext = \bunq\Context\ApiContext::create(
            bunq\Util\BunqEnumApiEnvironmentType::PRODUCTION(),
            $credentials["API-Schlüssel"],
            "sourceDESK"
        );

        \bunq\Context\BunqContext::loadApiContext($apiContext);
    }
}

// Implementation of Fidor-Bank

class BankCSV_Fidor extends BankCSV_Model
{

    // Method fills the @array required
    public function __construct()
    {
        $this->required = array(
            'Datum',
            'Beschreibung',
            'Beschreibung2',
            'Wert',
        );
    }

    // Method to return CSV data
    public function getCsvData($data = null)
    {
        $arr = array();

        // Check if any data is passed
        if ($data === null) {
            throw new BankCSV_Exception("1004");
        }

        // Try to get CSV file parsed
        $csv = BankCSV_HelperCSV::parse($data);
        if (!is_array($csv) || count($csv) <= 0) {
            throw new BankCSV_Exception("1004");
        }

        $this->firstLine = array_shift($csv);

        if (!$this->checkRequired()) {
            throw new BankCSV_Exception("1004");
        }

        // Iterate through the csv file
        foreach ($csv as $line) {
            foreach ($line as &$v) {
                $v = trim(trim($v), "\n");
            }

            if (strtotime($line[0]) <= strtotime("05.06.2017")) {
                continue;
            }

            $arr[] = array(
                'time' => $line[0],
                'transactionId' => substr(hash("sha512", implode(";", $line)), 0, 12),
                'amount' => str_replace(',', '.', $line[3]),
                'subject' => str_replace(array("Gutschrift ", "Überweisung: "), "", utf8_encode($line[1])),
                'sender' => str_replace(array("Absender: ", "Empfänger: "), "", utf8_encode($line[2])),
            );
        }

        // Return the data as multidimensional array
        return $arr;
    }

}

// Implementation of Number26

class BankCSV_N26 extends BankCSV_Model
{

    // Method fills the @array required
    public function __construct()
    {
        $this->required = array(
            'Datum',
            'Empfänger',
            'Kontonummer',
            'Transaktionstyp',
            'Verwendungszweck',
            'Kategorie',
            'Betrag (EUR)',
            'Betrag (Fremdwährung)',
            'Fremdwährung',
            'Wechselkurs',
        );
    }

    // Method to return CSV data
    public function getCsvData($data = null)
    {
        $arr = array();

        // Check if any data is passed
        if ($data === null) {
            throw new BankCSV_Exception("1004");
        }

        // Try to get CSV file parsed
        $csv = BankCSV_HelperCSV::parse($data, ",", '"');
        if (!is_array($csv) || count($csv) <= 0) {
            throw new BankCSV_Exception("1004");
        }

        $this->firstLine = array_shift($csv);

        if (!$this->checkRequired()) {
            throw new BankCSV_Exception("1004");
        }

        // Iterate through the csv file
        foreach ($csv as $line) {
            $arr[] = array(
                'time' => $line[0],
                'transactionId' => substr(hash("sha512", implode(";", $line)), 0, 12),
                'amount' => $line[6],
                'subject' => $line[4],
                'sender' => $line[1],
            );
        }

        // Return the data as multidimensional array
        return $arr;
    }

    // Method for providing automation settings
    public function automationSettings()
    {
        return array("E-Mailadresse", "Passwort");
    }

    // Method for getting the account balance
    public function getBalance($account, $credentials)
    {
        return $this->getCSV($account, $credentials, true);
    }

    // Method for getting the CSV
    public function getCSV($account, $credentials, $balance = false)
    {
        $mail = $credentials['E-Mailadresse'];
        $password = $credentials['Passwort'];

        $token = "bXktdHJ1c3RlZC13ZHBDbGllbnQ6c2VjcmV0";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, "https://api.tech26.de/oauth/token");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["username" => $mail, "password" => $password, "grant_type" => "password"]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Basic " . $token));
        $loginContent = json_decode(curl_exec($ch));

        if (!$loginContent) {
            return false;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $loginContent->access_token));
        curl_setopt($ch, CURLOPT_POSTFIELDS, "");
        curl_setopt($ch, CURLOPT_POST, false);

        if ($balance) {
            curl_setopt($ch, CURLOPT_URL, "https://api.tech26.de/api/me?full=true");
            $res = json_decode(curl_exec($ch));

            if (!$res) {
                return false;
            }

            return doubleval($res->account->availableBalance);
        }

        curl_setopt($ch, CURLOPT_URL, "https://api.tech26.de/api/smrt/reports/" . strtotime("-7 days") . "000/" . time() . "000/statements");
        return curl_exec($ch);
    }

}

// Implementation of VR-Bank

class BankCSV_Vr extends BankCSV_Model
{

    // Method fills the @array required
    public function __construct()
    {
        $this->required = array(
            'Kontonummer',
            'Buchungstag',
            'Wertstellung',
            'Buchungstext',
            'VWZ1',
            'Betrag',
        );
    }

    // Method to return CSV data
    public function getCsvData($data = null)
    {
        $arr = array();

        // Check if any data is passed
        if ($data === null) {
            throw new BankCSV_Exception("1004");
        }

        // Try to get CSV file parsed
        $csv = BankCSV_HelperCSV::parse($data);
        if (!is_array($csv) || count($csv) <= 0) {
            throw new BankCSV_Exception("1004");
        }

        $this->firstLine = $csv[0];

        if (!$this->checkRequired()) {
            throw new BankCSV_Exception("1004");
        }

        // Iterate through the csv file
        foreach ($csv as $line) {
            $tid = substr(hash("sha512", serialize($line)), 0, 10);

            $arr[] = array(
                'time' => $line[1],
                'transactionId' => $tid,
                'amount' => str_replace(',', '.', $line[19]),
                'subject' => $line[5],
                'sender' => $line[3],
            );
        }

        // Return the data as multidimensional array
        return $arr;
    }

}

// Implementation of Sparkasse

class BankCSV_Sparkasse extends BankCSV_Model
{

    // Method fills the @array required
    public function __construct()
    {
        $this->required = array(
            'Auftragskonto',
            'Buchungstag',
            'Valutadatum',
            'Buchungstext',
            'Verwendungszweck',
            'Kontonummer',
            'BLZ',
            'Waehrung',
            'Info',
        );
    }

    // Method to return CSV data
    public function getCsvData($data = null)
    {
        $arr = array();

        // Check if any data is passed
        if ($data === null) {
            throw new BankCSV_Exception("1004");
        }

        // Try to get CSV file parsed
        $csv = BankCSV_HelperCSV::parse($data);
        if (!is_array($csv) || count($csv) <= 0) {
            throw new BankCSV_Exception("1004");
        }

        foreach ($csv as $k => $v) {
            foreach ($v as $k2 => $v2) {
                $csv[$k][$k2] = str_replace('"', '', $v2);
            }
        }

        $this->firstLine = $csv[0];

        if (!$this->checkRequired()) {
            throw new BankCSV_Exception("1004");
        }

        // Iterate through the csv file
        foreach ($csv as $line) {
            $tid = substr(hash("sha512", serialize($line)), 0, 10);

            $arr[] = array(
                'time' => $line[2],
                'transactionId' => $tid,
                'amount' => str_replace(',', '.', $line[8]),
                'subject' => $line[3] . " / " . $line[4],
                'sender' => $line[5] . " / " . $line[6],
            );
        }

        // Return the data as multidimensional array
        return $arr;
    }

}

// Implementation of Starmoney (Business)

class BankCSV_Starmoney extends BankCSV_Model
{

    // Method fills the @array required
    public function __construct()
    {
        $this->required = array(
            'Verwendungszweck',
            'Kostenstelle',
            'Betrag',
        );
    }

    // Method to return CSV data
    public function getCsvData($data = null)
    {
        $arr = array();

        // Check if any data is passed
        if ($data === null) {
            throw new BankCSV_Exception("1004");
        }

        // Try to get CSV file parsed
        $csv = BankCSV_HelperCSV::parse($data);
        if (!is_array($csv) || count($csv) <= 0) {
            throw new BankCSV_Exception("1004");
        }

        $this->firstLine = $csv[0];
        foreach ($this->firstLine as &$v) {
            $v = trim($v, '\\"');
        }

        if (!$this->checkRequired()) {
            throw new BankCSV_Exception("1004");
        }

        // Iterate through the csv file
        foreach ($csv as $line) {
            $tid = substr(hash("sha512", serialize($line)), 0, 10);
            $amount = $line[count($line) - 2];
            $amount = trim(str_replace(array(".", ",", "EUR", '"'), array("", ".", "", ""), $amount));
            $subject = trim($line[3], '\\"');
            $subject = trim($subject, "<BR>");

            $arr[] = array(
                'time' => trim(trim($line[0], '\\"')),
                'transactionId' => $tid,
                'amount' => $amount,
                'subject' => $subject,
                'sender' => trim($line[2], '\\"'),
            );
        }

        // Return the data as multidimensional array
        return $arr;
    }

}

// Implementation of Starmoney (Business)

class BankCSV_Mt940 extends BankCSV_Model
{
    // Method to return CSV data
    public function getCsvData($data = null)
    {
        if ($data === null) {
            throw new BankCSV_Exception("1004");
        }

        try {
            $parser = new \Kingsquare\Parser\Banking\Mt940;
            $parsed = $parser->parse($data)[0]->getTransactions();
        } catch (Exception $ex) {
            throw new BankCSV_Exception("1004");
        }

        $arr = array();

        foreach ($parsed as $t) {
            $tid = substr(hash("sha512", $t->getEntryTimestamp() . $t->getPrice() . $t->getDescription() . $t->getAccountName()), 0, 10);

            $arr[] = array(
                'time' => $t->getValueTimestamp("Y-m-d") ?: $t->getEntryTimestamp("Y-m-d"),
                'transactionId' => $tid,
                'amount' => $t->getPrice(),
                'subject' => $t->getDescription(),
                'sender' => $t->getAccountName(),
            );
        }

        return $arr;
    }

}

// Implementation of Commerzbank

class BankCSV_Coba extends BankCSV_Model
{

    // Method fills the @array required
    public function __construct()
    {
        $this->required = array(
            'Wertstellung',
            'Umsatzart',
            'Buchungstext',
            'Betrag',
            'Auftraggeberkonto',
            'Bankleitzahl Auftraggeberkonto',
        );
    }

    // Method to return CSV data
    public function getCsvData($data = null)
    {
        $arr = array();

        // Check if any data is passed
        if ($data === null) {
            throw new BankCSV_Exception("1004");
        }

        // Try to get CSV file parsed
        $csv = BankCSV_HelperCSV::parse($data);
        if (!is_array($csv) || count($csv) <= 0) {
            throw new BankCSV_Exception("1004");
        }

        $this->firstLine = $csv[0];

        if (!$this->checkRequired()) {
            throw new BankCSV_Exception("1004");
        }

        // Iterate through the csv file
        foreach ($csv as $line) {
            $description = $line[3];
            $position = strpos($description, "Kundenreferenz: ");
            if ($position === false) {
                continue;
            }

            $str = substr($description, $position + 16);
            $transactionId = "";
            for ($i = 0; $i < strlen($str); $i++) {
                if ($i < 4 || is_numeric(substr($str, $i, 1))) {
                    $transactionId .= substr($str, $i, 1);
                } else {
                    break;
                }

            }

            $arr[] = array(
                'time' => $line[0],
                'transactionId' => $transactionId,
                'amount' => str_replace(',', '.', $line[4]),
                'subject' => substr($line[3], 0, $position - 1),
                'sender' => "IBAN: " . trim($line[8]),
            );
        }

        // Return the data as multidimensional array
        return $arr;
    }

}

// Implementation of Postbank

class BankCSV_Post extends BankCSV_Model
{

    // Method fills the @array required
    public function __construct()
    {
        $this->required = array(
            'Buchungstag',
            'Wertstellung',
            'Umsatzart',
            'Buchungsdetails',
            'Auftraggeber',
        );
    }

    // Method to return CSV data
    public function getCsvData($data = null)
    {
        $arr = array();

        // Check if any data is passed
        if ($data === null) {
            throw new BankCSV_Exception("1004");
        }

        // Try to get CSV file parsed
        $csv = BankCSV_HelperCSV::parse($data);
        if (!is_array($csv) || count($csv) <= 0) {
            throw new BankCSV_Exception("1004");
        }

        foreach ($csv as $k => $v) {
            if (count($v) <= 2) {
                unset($csv[$k]);
            }

            foreach ($v as $k2 => $v2) {
                while (substr($csv[$k][$k2], 0, 1) == '"') {
                    $csv[$k][$k2] = substr($csv[$k][$k2], 1);
                }

                while (substr($csv[$k][$k2], strlen($csv[$k][$k2]) - 1) == '"') {
                    $csv[$k][$k2] = substr($csv[$k][$k2], 0, strlen($csv[$k][$k2]) - 1);
                }

            }
        }

        $csv = array_values($csv);

        $this->firstLine = $csv[0];

        if (!$this->checkRequired()) {
            throw new BankCSV_Exception("1004");
        }

        // Iterate through the csv file
        foreach ($csv as $i => $line) {
            if ($i == 0) {
                continue;
            }

            $tid = substr(hash("sha512", serialize($line)), 0, 10);

            $arr[] = array(
                'time' => $line[0],
                'transactionId' => $tid,
                'amount' => str_replace(array('?', '.', ',', ' '), array('-', '', '.', ''), utf8_decode(substr($line[6], 2))),
                'subject' => $line[3],
                'sender' => $line[4],
            );
        }

        // Return the data as multidimensional array
        return $arr;
    }

}

// Implementation of CPS-Datensysteme

class BankCSV_Cpsds extends BankCSV_Model
{
    // Method to return CSV data
    public function getCsvData($data = null)
    {
        return array();
    }

    // Method for providing automation settings
    public function automationSettings()
    {
        return array("Benutzername", "Passwort");
    }

    // Method for getting the account balance
    public function getBalance($account, $credentials)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://orms.cps-datensysteme.de:700");
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_POST, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS, utf8_encode('<?xml version="1.0" encoding="utf-8"?>
            <request>
              <auth>
                <cid>' . $account . '</cid>
                <user>' . $credentials['Benutzername'] . '</user>
                <pwd>' . $credentials['Passwort'] . '</pwd>
                <secure_token></secure_token>
              </auth>
              <transaction>
                <group>account</group>
                <action>info</action>
                <attribute>account_data</attribute>
                <values></values>
              </transaction>
            </request>'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        curl_close($curl);

        $xml = simplexml_load_string($res);
        if (!$xml) {
            return false;
        }

        return doubleval($xml->result->detail->values->account_saldo) / -1;
    }

    // Method for getting the CSV
    public function getCSV($ktn, $credentials, $balance = false)
    {
        return "";
    }

}

// Implementation of InterNetWorX

class BankCSV_Inwx extends BankCSV_Model
{
    // Method to return CSV data
    public function getCsvData($data = null)
    {
        return array();
    }

    // Method for providing automation settings
    public function automationSettings()
    {
        return array("Benutzername", "Passwort");
    }

    // Method for getting the account balance
    public function getBalance($account, $credentials)
    {
        if (!file_exists(__DIR__ . "/../modules/domain/inwx/domrobot.class.php")) {
            return false;
        }

        require __DIR__ . "/../modules/domain/inwx/domrobot.class.php";

        $addr = "https://api.domrobot.com/xmlrpc/";
        $domrobot = new domrobot($addr);
        $domrobot->setDebug(false);
        $domrobot->setLanguage('en');
        $res = $domrobot->login($credentials['Benutzername'], $credentials['Passwort']);

        if ($res['code'] == "1000") {
            $res = $domrobot->call("accounting", "accountBalance", array());
        }

        if (!isset($res['resData']['available'])) {
            return 0;
        }

        $domrobot->logout();

        return (double) $res['resData']['available'];
    }

    // Method for getting the CSV
    public function getCSV($ktn, $credentials, $balance = false)
    {
        return "";
    }

}

// Implementation of fix account

class BankCSV_Fix extends BankCSV_Model
{
    // Method to return CSV data
    public function getCsvData($data = null)
    {
        return array();
    }

    // Method for providing automation settings
    public function automationSettings()
    {
        return array("Kontostand");
    }

    // Method for getting the account balance
    public function getBalance($account, $credentials)
    {
        return (double) $credentials['Kontostand'];
    }

    // Method for getting the CSV
    public function getCSV($ktn, $credentials, $balance = false)
    {
        return "";
    }

}

// Implementation of SMSkaufen account

class BankCSV_Smskaufen extends BankCSV_Model
{
    // Method to return CSV data
    public function getCsvData($data = null)
    {
        return array();
    }

    // Method for providing automation settings
    public function automationSettings()
    {
        return array("Passwort");
    }

    // Method for getting the account balance
    public function getBalance($account, $credentials)
    {
        $params = array(
            "bn" => $account,
            "pw" => $credentials['Passwort'],
        );

        $ch = curl_init("https://www.smskaufen.com/sms/index.php?seite=login");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $res = curl_exec($ch);
        curl_close($ch);

        $pos = strpos($res, "Guthaben:");
        if ($pos === false) {
            return false;
        }

        $credit = substr($res, $pos + 24);
        return doubleval(str_replace(",", ".", $credit));
    }

    // Method for getting the CSV
    public function getCSV($ktn, $credentials, $balance = false)
    {
        return "";
    }

}

// Implementation of Stripe

class BankCSV_Stripe extends BankCSV_Model
{
    // Method to return CSV data
    public function getCsvData($data = null)
    {
        return array();
    }

    // Method for providing automation settings
    public function automationSettings()
    {
        return array("API-Token");
    }

    // Method for getting the account balance
    public function getBalance($account, $credentials)
    {
        $waiting = 0;
        require_once __DIR__ . "/../modules/payment/stripe/lib/init.php";
        \Stripe\Stripe::setApiKey($credentials['API-Token']);

        $res = \Stripe\Balance::retrieve();
        $balance = 0;

        foreach ($res->available as $a) {
            $balance += $a->amount;
        }

        foreach ($res->pending as $p) {
            $balance += $p->amount;
        }

        return $balance / 100;
    }

    // Method for getting the CSV
    public function getCSV($ktn, $credentials, $balance = false)
    {
        return "";
    }

}

// Implementation of Contabo

class BankCSV_Contabo extends BankCSV_Model
{
    // Method to return CSV data
    public function getCsvData($data = null)
    {
        return array();
    }

    // Method for providing automation settings
    public function automationSettings()
    {
        return array("Passwort");
    }

    // Method for getting the account balance
    public function getBalance($account, $credentials)
    {
        $file = __DIR__ . "/." . rand(10000000, 99999999);

        $ch = curl_init("https://my.contabo.de/account/login");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $file);
        $res = curl_exec($ch);

        $pos = strpos($res, 'name="data[_Token][key]"');
        if ($pos === false) {
            return false;
        }

        $token = substr($res, $pos + 32, 128);

        $params = array(
            "_method" => "POST",
            "data" => array(
                "_Token" => array(
                    "key" => $token,
                    "fields" => "436024fe670d373300f2922d85362ee012b208a4%3A",
                    "unlocked" => "x%7Cy",
                ),
                "Account" => array(
                    "username" => $account,
                    "password" => $credentials["Passwort"],
                ),
            ),
            "x" => "59",
            "y" => "12",
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $res = curl_exec($ch);

        curl_setopt($ch, CURLOPT_POSTFIELDS, "");
        curl_setopt($ch, CURLOPT_URL, "https://my.contabo.de/account/payment");
        $res = curl_exec($ch);

        curl_close($ch);

        unlink($file);

        $pos = strpos($res, "Ihr Guthaben beträgt ");
        if ($pos === false) {
            return false;
        }

        $credit = substr($res, $pos + 22);
        return doubleval(str_replace(",", ".", $credit));
    }

    // Method for getting the CSV
    public function getCSV($ktn, $credentials, $balance = false)
    {
        return "";
    }

}

// Implementation of Noez

class BankCSV_Noez extends BankCSV_Model
{
    // Method to return CSV data
    public function getCsvData($data = null)
    {
        return array();
    }

    // Method for providing automation settings
    public function automationSettings()
    {
        return array("Passwort");
    }

    // Method for getting the account balance
    public function getBalance($account, $credentials)
    {
        $file = __DIR__ . "/." . rand(10000000, 99999999);

        $params = array(
            "_method" => "POST",
            "data" => array(
                "User" => array(
                    "email" => $account,
                    "password" => $credentials["Passwort"],
                ),
            ),
        );

        $ch = curl_init("https://nas.noez.de/users/login");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $file);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $res = curl_exec($ch);
        curl_close($ch);

        if (file_exists($file)) {
            unlink($file);
        }

        $pos = strpos($res, "Guthaben<");
        if ($pos === false) {
            return false;
        }

        $credit = substr($res, $pos + 17);
        return doubleval(str_replace(",", ".", $credit));
    }

    // Method for getting the CSV
    public function getCSV($ktn, $credentials, $balance = false)
    {
        return "";
    }

}

// Implementation of UD-Reselling

class BankCSV_UDReselling extends BankCSV_Model
{
    // Method to return CSV data
    public function getCsvData($data = null)
    {
        return array();
    }

    // Method for providing automation settings
    public function automationSettings()
    {
        return array("Passwort");
    }

    // Method for getting the account balance
    public function getBalance($account, $credentials)
    {
        @$res = $this->parse_response(file_get_contents("https://api.domainreselling.de/api/call.cgi?s_login=" . urlencode($account) . "&s_pw=" . urlencode($credentials['Passwort']) . "&command=statusUser"));
        if (!$res) {
            return false;
        }

        return (double) $res['PROPERTY']['ACCOUNTCURRENT'][0];
    }

    // Method for getting the CSV

    private function parse_response($sResponse)
    {
        if (empty($sResponse)) {
            return array();
        }

        // add all entries below the 'PROPERTY' hash as hash
        $mHash = array('PROPERTY' => array());

        // split the response
        foreach (explode("\n", $sResponse) as $sItem) {
            // get all entries - but skip [RESPONSE], EOF etc.
            if (preg_match('/^([^\=]*[^\t\= ])[\t ]*=[\t ]*(.*)$/', $sItem, $aMatch)) {
                $sAttr = strtoupper($aMatch[1]);
                $sValue = preg_replace('/[\t ]*$/', '', $aMatch[2]);
                $sub = &$mHash['PROPERTY'];

                // get everything between []
                if (preg_match_all('/\[([^]]+)\]/', $sAttr, $aMatch)) {
                    // as long as [] are there, create multi hash
                    foreach ($aMatch[1] as $elem) {
                        $elem = str_replace(array(' ', "\t"), '', strtoupper($elem));
                        if (!@is_array($sub[$elem])) {
                            $sub[$elem] = array();
                        }
                        $sub = &$sub[$elem];
                    }

                    // last element reached - set value
                    $sub = $sValue;
                } else {
                    // has no multi hashs - set value
                    $mHash[$sAttr] = $sValue;
                }
            }
        }

        return $mHash;
    }

    public function getCSV($ktn, $credentials, $balance = false)
    {
        return "";
    }
}

// Implementation of HBCI standard

class BankCSV_Hbci extends BankCSV_Model
{
    // Method to return CSV data
    public function getCsvData($data = null)
    {
        $arr = array();

        // Check if any data is passed
        if ($data === null || !is_array($data)) {
            throw new BankCSV_Exception("1004");
        }

        // Iterate through the csv file
        foreach ($data as $t) {
            $tid = substr(hash("sha512", serialize($t)), 0, 10);

            $arr[] = array(
                'time' => $t->getBookingDate()->format("Y-m-d"),
                'amount' => $t->getAmount() / ($t->getCreditDebit() == "debit" ? -1 : 1),
                'subject' => $t->getDescription1(),
                'sender' => $t->getName(),
                'transactionId' => $tid,
            );
        }

        // Return the data as multidimensional array
        return $arr;
    }

    // Method for providing automation settings
    public function automationSettings()
    {
        return array("Bankleitzahl", "HBCI-URL", "HBCI-Port", "HBCI-Benutzer", "HBCI-PIN");
    }

    // Method for getting the account balance
    public function getBalance($account, $credentials)
    {
        return $this->getCSV($account, $credentials, true);
    }

    // Method for getting the CSV
    public function getCSV($ktn, $credentials, $balance = false)
    {
        global $CFG;

        try {
            $fints = new Fhp\FinTs(
                $credentials['HBCI-URL'],
                intval($credentials['HBCI-Port']),
                $credentials['Bankleitzahl'],
                $credentials['HBCI-Benutzer'],
                $credentials['HBCI-PIN'],
                null,
                "7C066410D3E77C97ACA0EBC9E",
                "sourceDESK",
                $CFG['VERSION']
            );

            $accounts = $fints->getSEPAAccounts();

            foreach ($accounts as $a) {
                if ($a->getAccountNumber() != $ktn) {
                    continue;
                }

                if ($balance) {
                    return $fints->getSaldo($a)->getAmount();
                }

                return $fints->getStatementOfAccount($a, new DateTime(date("Y-m-d", strtotime("-7 days"))), new DateTime(date("Y-m-d")))->getStatements()[0]->getTransactions();
            }

            return false;
        } catch (Fhp\Dialog\Exception\FailedRequestException $ex) {
            return false;
        }
    }

}

// Implementation of AqBanking application

class BankCSV_Aqbanking extends BankCSV_Model
{
    // Method to return CSV data
    public function getCsvData($data = null)
    {
        $arr = array();

        if (!is_array($data)) {
            $data = @unserialize($data);
        }

        // Check if any data is passed
        if ($data === null || !is_array($data)) {
            throw new BankCSV_Exception("1004");
        }

        // Iterate through the csv file
        foreach ($data as $t) {
            $hashStr = "";
            foreach ([5, 6, 7, 8, 9, 10, 12, 13, 14, 15] as $k) {
                $hashStr .= $t[$k];
            }

            $tid = $t[0] ?: substr(hash("sha512", $hashStr), 0, 10);

            $vz = "";
            foreach ([12, 13, 14, 15] as $k) {
                $vz .= $t[$k];
            }

            $arr[] = array(
                'time' => date("Y-m-d", strtotime($t[5])),
                'amount' => $t[7],
                'subject' => $vz,
                'sender' => $t[10] . $t[11],
                'transactionId' => $tid,
            );
        }

        // Return the data as multidimensional array
        return $arr;
    }

    // Method for providing automation settings
    public function automationSettings()
    {
        return array("AqBanking-Pfad", "PIN-Datei", "Bankleitzahl");
    }

    // Method for getting the account balance
    public function getBalance($account, $credentials)
    {
        return $this->getCSV($account, $credentials, true);
    }

    // Method for getting the CSV
    public function getCSV($account, $credentials, $balance = false)
    {
        $aq = new AqBanking($credentials['AqBanking-Pfad'], $credentials['PIN-Datei']);
        $aq->fetchData($credentials['Bankleitzahl'], $account);

        if ($balance) {
            return $aq->getSaldo();
        }

        return $aq->getTransactions();
    }

}

// Helper for CSV files

abstract class BankCSV_HelperCSV
{

    // Method to parse CSV file
    public static function parse($fileContent = null, $explode = ";", $trim = "")
    {
        if ($fileContent === null) {
            return false;
        }

        $con = array();

        // Explode lines
        $lines = explode("\n", $fileContent);

        // Iterate through lines and explode entries
        $i = 0;
        foreach ($lines as $line) {
            $ex = explode($explode, $line);

            if (!empty($trim)) {
                foreach ($ex as &$c) {
                    $c = trim($c, $trim);
                }
            }

            $con[$i++] = $ex;
        }

        return $con;
    }

}

// Exception for any errors

class BankCSV_Exception extends Exception
{

    // We save the error code in the object
    protected $errorCode;

    // You need to pass the error code to the constructor
    // It will be checked if code exists, otherwise an unknown error will be thrown
    public function __construct($errorCode = false)
    {
        if (!isset($this->returnErrorCodes()[$errorCode])) {
            $errorCode = "1000";
        }

        $this->errorCode = $errorCode;
    }

    // Method to get the error code

    public static function returnErrorCodes()
    {
        $errors = array();
        $errors["1000"] = "Unknown error";
        $errors["1001"] = "No input file provided";
        $errors["1002"] = "Bank not found";
        $errors["1003"] = "Implementation not found";
        $errors["1004"] = "Parsing error (wrong bank?)";

        ksort($errors);
        return $errors;
    }

    // Method to get the description of the error code

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    // Method returns the available error codes within the class

    public function getErrorDescription()
    {
        return $this->returnErrorCodes()[$this->errorCode];
    }

}
