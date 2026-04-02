<?php
// Addon for exporting contacts to Placetel

class vCardExport extends Addon
{
    public static $shortName = "vcard-export";

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

    public function delete()
    {
        return $this->deleteDir(realpath(__DIR__));
    }

    public function adminPages()
    {
        return array("vcard_export" => "displayAdminPage");
    }

    public function adminMenu()
    {
        return array("vCard" => "vcard_export");
    }

    public function getSettings()
    {
        return array();
    }

    public function displayAdminPage()
    {
        global $tpl, $var, $CFG, $db;
        $var['options'] = $this->options;
        $var['l'] = $this->getLang();
        $tpl = __DIR__ . "/templates/admin.tpl";

        if (isset($_GET['dl'])) {
            if ($_GET['dl'] == "1") {
                $zip = new ZipArchive;
                if ($zip->open(__DIR__ . "/tmp.zip", ZipArchive::CREATE) !== true) {
                    die("Error.");
                }

                alog("general", "vcard_zip");

                header("Content-Type: application/zip");
                header("Content-Disposition: attachment; filename=\"contacts.zip\"");
            } else {
                alog("general", "vcard_vcf");

                header("Content-type: text/directory");
                header("Content-Disposition: attachment; filename=\"contacts.vcf\"");
            }

            $s = "";
            $where = isset($_GET['tl']) ? " WHERE telephone != ''" : "";
            $sql = $db->query("SELECT * FROM clients$where");
            while ($u = $sql->fetch_object()) {
                $vcard = new VCard;

                $sql2 = $db->query("SELECT name FROM client_countries WHERE ID = " . intval($u->country));
                $country = $sql2->num_rows == 1 ? $sql2->fetch_object()->name : "";

                $vcard->set("first_name", $u->firstname);
                $vcard->set("last_name", $u->lastname);
                $vcard->set("display_name", $u->firstname . " " . $u->lastname);
                $vcard->set("company", $u->company);
                $vcard->set("work_address", $u->street . " " . $u->street_number);
                $vcard->set("work_city", $u->city);
                $vcard->set("work_postal_code", $u->postcode);
                $vcard->set("work_country", $country);
                $vcard->set("office_tel", $u->telephone);
                $vcard->set("email1", $u->mail);
                $vcard->set("url", $u->website);
                $vcard->set("birthday", $u->birthday);

                if ($_GET['dl'] == "1") {
                    $zip->addFromString($u->ID . ".vcf", $vcard->show());
                } else {
                    $s .= $vcard->show();
                }

            }

            if ($_GET['dl'] == "1") {
                $zip->close();
                echo file_get_contents(__DIR__ . "/tmp.zip");
                unlink(__DIR__ . "/tmp.zip");
            } else {
                echo $s;
            }

            exit;
        }
    }
}
