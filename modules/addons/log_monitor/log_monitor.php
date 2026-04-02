<?php
// Addon for monitoring log

class LogMonitor extends Addon
{
    public static $shortName = "log_monitor";

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

    public function hooks()
    {
        return array(
            array("UserLogEntry", "log", 0),
        );
    }

    public function log($params)
    {
        $user = $params['user'];
        $log = $params['log'];

        if ($this->interestingUser($user) || $this->interestingText($log)) {
            Telegram::sendMessage($this->buildMsg($user, $log));
        }

    }

    private function buildMsg($user, $log)
    {
        global $raw_cfg;
        if (!($user instanceof User)) {
            return false;
        }

        return '<a href="' . $raw_cfg['PAGEURL'] . 'admin/?p=customers&edit=' . $user->get()['ID'] . '">' . htmlentities($user->get()['name']) . '</a>: ' . htmlentities($log);
    }

    private function interestingUser($user)
    {
        if (!($user instanceof User)) {
            return false;
        }

        $ex = explode(",", $this->options["user"]);
        foreach ($ex as &$v) {
            $v = intval(trim($v));
        }

        return in_array($user->get()["ID"], $ex);
    }

    private function interestingText($log)
    {
        $log = trim($log);

        $ex = explode(",", $this->options["text"]);
        foreach ($ex as $v) {
            if (strpos($log, trim($v)) !== false && !empty(trim($v))) {
                return true;
            }
        }

        return false;
    }

    public function getSettings()
    {
        return array(
            "user" => array("placeholder" => "1,5,7,...", "label" => $this->getLang("LOGUSER"), "type" => "text"),
            "text" => array("placeholder" => "Domain,...", "label" => $this->getLang("LOGTEXT"), "type" => "text"),
        );
    }
}
