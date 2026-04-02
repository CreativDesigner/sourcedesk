<?php
// Addon for orgaMAX integration

class OrgaMaxIntegration extends Addon
{
    public static $shortName = "orgamax";

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
            'version' => "1.3",
            'company' => "sourceWAY.de",
            'url' => "https://sourceway.de/",
        );
    }

    public function getSettings()
    {
        global $sec;

        return array(
            "key" => array("default" => $sec->generatePassword(64, false, "ld"), "label" => $this->getLang("KEY"), "type" => "text", "help" => $this->getLang("KEYH")),
        );
    }

    public function activate()
    {
        global $CFG, $db;
        parent::activate();

        $db->query("ALTER TABLE `clients` ADD `orgamax` tinyint(1) NOT NULL DEFAULT 0;");
        $db->query("ALTER TABLE `invoices` ADD `orgamax` tinyint(1) NOT NULL DEFAULT 0;");
    }

    public function delete()
    {
        return $this->deleteDir(realpath(__DIR__));
    }
}
