<?php

class Messagebird extends SMSProvider
{
    protected $short = "messagebird";
    protected $name = "Messagebird";
    protected $version = "1.0";

    public function getSettings()
    {
        global $lang;

        return array(
            "sender" => array("type" => "text", "name" => $this->getLang("sender"), "help" => $this->getLang("SENDERH")),
            "btkey" => array("type" => "password", "name" => $lang['GENERAL']['APIKEY']),
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

    public function sendMessage($recipient, $message, $type = "t0")
    {
        if (!array_key_exists($type, $this->getTypes())) {
            return false;
        }

        $data = [
            "originator" => is_numeric($this->options->sender) ? "+" . $this->options->sender : $this->options->sender,
            "body" => $message,
            "recipients" => "+" . $this->parseNumber($recipient),
        ];

        $ch = curl_init("https://rest.messagebird.com/messages");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: AccessKey {$this->options->btkey}"]);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        if (is_object($res) && $res->id) {
            return true;
        }

        return false;
    }

    public function getTypes()
    {
        return array(
            "t0" => $this->getLang("S0"),
        );
    }
}
