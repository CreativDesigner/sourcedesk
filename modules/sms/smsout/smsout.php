<?php

class Smsout extends SMSProvider
{
    protected $short = "smsout";
    protected $name = "SMSout";
    protected $version = "1.0";

    public function getSettings()
    {
        global $lang;

        return array(
            "usr" => array("type" => "text", "name" => $this->getLang("USR")),
            "pwd" => array("type" => "password", "name" => $this->getLang("PWD")),
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

    public function sendMessage($recipient, $message, $type = "V1")
    {
        if (!array_key_exists($type, $this->getTypes())) {
            return false;
        }

        $data = [
            "Username" => $this->options->usr,
            "Password" => $this->options->pwd,
            "SMSTo" => $this->parseNumber($recipient),
            "SMSType" => $type,
            "SMSText" => $message,
        ];

        $ch = curl_init("https://www.smsout.de/client/sendsms.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $res = curl_exec($ch);
        curl_close($ch);

        return strpos($res, "Return: OK") !== false;
    }

    public function getTypes()
    {
        return array(
            "V1" => $this->getLang("V1"),
            "V3" => $this->getLang("V3"),
        );
    }
}
