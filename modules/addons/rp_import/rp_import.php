<?php
// Addon for importing data from ResellerProfessional

class ResellerProfessionalImportAddon extends Addon
{
    public static $shortName = "rp_import";

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
            "rp_import" => "admin",
        );
    }

    public function adminMenu()
    {
        return array($this->getLang("NAME") => "rp_import");
    }

    public function admin()
    {
        global $tpl, $var, $CFG, $db;

        $var['addonlang'] = $this->getLang();

        if (isset($_FILES['csv'])) {
            $file = file_get_contents($_FILES['csv']['tmp_name']);
            $lines = explode("\n", $file);
            $lines = array_map("trim", $lines);

            $firstLine = array_shift($lines);
            $expected = '"CusNr","AdrCusTitle","AdrCusFirstName","AdrCusLastName","AdrCusCompany","AdrCusAdress1","AdrCusAdress2","AdrCusZip","AdrCusCity","AdrCusPhone1","AdrCusPhone2","AdrCusFax","AdrCusEmail","CusPayIBAN","CusPayBIC","CusPayMR","CusPayHolder"';
            if ($firstLine != $expected) {
                $var['err'] = $this->getLang("FAIL");
            } else {
                $free = 1;
                $sql = $db->query("SELECT ID FROM clients ORDER BY ID DESC");
                if ($sql->num_rows) {
                    $free = $sql->fetch_object()->ID + 1;
                }

                foreach ($lines as $line) {
                    if (empty($line)) {
                        continue;
                    }

                    $ex = array_map(function ($v) {return trim($v, '"');}, explode(",", $line));

                    $sql = $db->query("SELECT 1 FROM clients WHERE ID = " . intval($ex[0]));
                    if ($sql->num_rows) {
                        $ex[0] = $free++;
                    }

                    $ex2 = explode(" ", $ex[5]);
                    $street_number = array_pop($ex2);
                    $street = implode(" ", $ex2);

                    $data = [
                        "ID" => $ex[0],
                        "salutation" => $ex[1] == "Herr" ? "MALE" : "FEMALE",
                        "firstname" => $ex[2],
                        "lastname" => $ex[3],
                        "company" => $ex[4],
                        "street" => $street,
                        "street_number" => $street_number,
                        "postcode" => $ex[7],
                        "city" => $ex[8],
                        "telephone" => $ex[9],
                        "fax" => $ex[11],
                        "mail" => $ex[12],
                        "postpaid" => doubleval($CFG['POSTPAID_DEF']),
                    ];

                    if ($db->query("SELECT 1 FROM clients WHERE mail = '" . $db->real_escape_string($ex[12]) . "'")->num_rows) {
                        $ex[12] = htmlentities($ex[12]);
                        die("Email address {$ex[12]} is not unique");
                    }

                    $fields = "`" . implode("`,`", array_map([$db, "real_escape_string"], array_keys($data))) . "`";
                    $values = "'" . implode("','", array_map([$db, "real_escape_string"], array_values($data))) . "'";

                    $db->query("INSERT INTO clients ($fields) VALUES ($values)");
                }

                $var['suc'] = $this->getLang("FINISH");
            }
        }

        $tpl = __DIR__ . "/templates/admin.tpl";
    }
}
