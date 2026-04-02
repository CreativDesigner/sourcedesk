<?php

class Bulktrade extends SMSProvider
{
    protected $short = "bulktrade";
    protected $name = "Bulktrade";
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
            "key" => $this->options->btkey,
            "sender" => $this->options->sender,
            "message" => $message,
            "msisdn" => $this->parseNumber($recipient),
            "flash" => substr($type, 1) == "1" ? "1" : "0",
        ];

        $ch = curl_init("https://bulktrade.de/sms-api");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        if (is_object($res) && $res->succeed) {
            return true;
        }

        return false;
    }

    public function getTypes()
    {
        return array(
            "t0" => $this->getLang("S0"),
            "t1" => $this->getLang("S1"),
        );
    }
}
