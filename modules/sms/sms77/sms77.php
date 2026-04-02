<?php

class SmsSevenSeven extends SMSProvider
{
    protected $short = "sms77";
    protected $name = "sms77";
    protected $version = "1.0";

    public function getSettings()
    {
        global $lang;

        return array(
            "sender" => array("type" => "text", "name" => $this->getLang("sender"), "help" => $this->getLang("SENDERH")),
            "user" => array("type" => "text", "name" => $this->getLang("user")),
            "key" => array("type" => "password", "name" => $this->getLang("key")),
        );
    }

    private function parseNumber($nr)
    {
        $a = str_split($nr);
        foreach ($a as &$v) {
            if (is_numeric($v)) {
                continue;
            }

            if ($v == "+") {
                continue;
            }

            $v = "";
        }
        $nr = implode($a);

        if (substr($nr, 0, 1) == "0" && substr($nr, 1, 1) != "0") {
            return "49" . ltrim($nr, "0");
        }

        if (substr($nr, 0, 1) == "+") {
            return $this->parseNumber(ltrim($nr, "+"));
        }

        return $nr;
    }

    public function sendMessage($recipient, $message, $type = "direct")
    {
        if (!array_key_exists($type, $this->getTypes())) {
            return false;
        }

        $data = [
            "u" => $this->options->user,
            "p" => $this->options->key,
            "from" => $this->options->sender,
            "text" => $message,
            "to" => $this->parseNumber($recipient),
            "type" => $type == "flash" ? "direct" : $type,
            "flash" => $type == "flash" ? "1" : "0",
        ];

        $res = file_get_contents('https://gateway.sms77.io/api/sms?' . http_build_query($data));

        return $res == "100";
    }

    public function getTypes()
    {
        return array(
            "direct" => $this->getLang("S0"),
            "economy" => $this->getLang("S1"),
            "flash" => $this->getLang("S2"),
        );
    }
}
