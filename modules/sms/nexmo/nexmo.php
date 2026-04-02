<?php

class Nexmo extends SMSProvider
{
    protected $short = "nexmo";
    protected $name = "Nexmo";
    protected $version = "1.0";

    public function getSettings()
    {
        global $lang;

        return array(
            "sender" => array("type" => "text", "name" => $this->getLang("sender"), "help" => $this->getLang("SENDERH")),
            "apikey" => array("type" => "password", "name" => $this->getLang("APIKEY")),
            "apisecret" => array("type" => "password", "name" => $this->getLang("APISECRET")),
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
            "api_key" => $this->options->apikey,
            "api_secret" => $this->options->apisecret,
            "from" => $this->options->sender,
            "text" => $message,
            "to" => $this->parseNumber($recipient),
        ];

        $ch = curl_init("https://rest.nexmo.com/sms/json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $res = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (is_object($res) && $res['message-count']) {
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
