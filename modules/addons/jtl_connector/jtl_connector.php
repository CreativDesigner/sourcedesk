<?php
// Addon for JTL Connector

class JtlConnector extends Addon
{
    public static $shortName = "jtl_connector";

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
            'version' => "1.1",
            'company' => "sourceWAY.de",
            'url' => "https://sourceway.de/",
        );
    }

    public function getSettings()
    {
        global $sec;

        return array(
            "token" => array("default" => $sec->generatePassword(64, false, "ld"), "label" => $this->getLang("TOKEN"), "type" => "type"),
            "invoice" => array("type" => "checkbox", "label" => $this->getLang("INVOICE")),
        );
    }

    public function delete()
    {
        return $this->deleteDir(realpath(__DIR__));
    }

    public function hooks()
    {
        return [
            ["SkipCSRF", "csrf", 0],
            ["InvoiceCreated", "invoicing", 0],
        ];
    }

    public function csrf($pars)
    {
        return $pars["page"] == "jtlconnector";
    }

    public function clientPages()
    {
        return array(
            "jtlconnector" => "connector",
        );
    }

    public function connector()
    {
        ini_set("display_errors", 0);

        file_put_contents(__DIR__ . "/config/config.json", json_encode([
            "platform_root" => realpath(__DIR__ . "/../../../"),
            "connector_root" => realpath(__DIR__),
        ]));

        defined('CONNECTOR_DIR') || define("CONNECTOR_DIR", __DIR__);
        include __DIR__ . "/src/bootstrap.php";

        exit;
    }

    public function invoicing($pars) {
        global $db, $CFG;
        $inv = $pars["inv"];
        $info = $inv->getInfo();

        if (!$this->options['invoice'] || in_array($this->options['invoice'], ["no", "off"])) {
            return;
        }
        
        if (!$db->query("SELECT 1 FROM mod_jtl_invitems")) {
            $db->query("CREATE TABLE `mod_jtl_invitems` (
                `ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `uid` int(11) NOT NULL DEFAULT '0',
                `date` date NOT NULL DEFAULT '0000-00-00',
                `duedate` date NOT NULL DEFAULT '0000-00-00',
                `description` longtext NOT NULL,
                `amount` double(100, 2) NOT NULL DEFAULT '0.00',
                `tax` int(1) NOT NULL DEFAULT '1',
                `qty` double(100, 2) NOT NULL DEFAULT '1.00',
                `unit` varchar(20) NOT NULL DEFAULT '',
              );");
        }

        $sql = $db->prepare("INSERT INTO `mod_jtl_invitems` (`uid`, `date`, `duedate`, `description`, `amount`, `tax`, `qty`, `unit`) VALUES (?,?,?,?,?,?,?,?)");
        $sql->bind_param("isssdids", $info->client, $info->date, $info->duedate, $info2->description, $info2->amount, $info2->tax, $info2->qty, $info2->unit);

        foreach ($inv->getItems() as $item) {
            $info2 = $item->getInfo();
            $sql->execute();
        }

        $sql->close();

        $inv->delete();
    }
}
