<?php
// Addon for Todoist integration

class Todoist extends Addon
{
    public static $shortName = "todoist";

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
            array("ReminderApplied", "reminderApplied", 0),
        );
    }

    public function reminderApplied($params)
    {
        $inv = $params['invoice'];
        $rem = $params['reminder'];

        if ($rem->days < $this->options["reminder_threshold"]) {
            return;
        }

        $this->createTask($inv->getInvoiceNo() . ": " . $rem->name);
    }

    private function createTask($msg, $priority = "1")
    {
        $todoistReq = curl_init("https://beta.todoist.com/API/v8/tasks");
        curl_setopt($todoistReq, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($todoistReq, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->options["token"],
        ]);
        curl_setopt($todoistReq, CURLOPT_POSTFIELDS, json_encode([
            "content" => $msg,
            "project_id" => intval($this->options["project"]),
            "priority" => intval($priority),
            "due_string" => "Today",
        ]));
        $data = @json_decode(curl_exec($todoistReq), true);
        curl_close($todoistReq);

        return boolval($data["id"] ?? 0);
    }

    public function getSettings()
    {
        return array(
            "token" => array("placeholder" => $this->getLang("SECRET"), "label" => $this->getLang("TOKEN"), "type" => "password"),
            "project" => array("placeholder" => "1234567890", "label" => $this->getLang("PROJECT"), "type" => "text"),
            "reminder_threshold" => array("placeholder" => "14", "default" => "14", "label" => $this->getLang("REMINDER_THRESHOLD"), "type" => "text"),
        );
    }
}
