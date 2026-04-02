<?php

class Massenversand extends SMSProvider
{
    protected $short = "massenversand";
    protected $name = "Massenversand";
    protected $version = "1.0";

    public function getSettings()
    {
        global $lang;

        return array(
            "sender" => array("type" => "text", "name" => $this->getLang("sender"), "help" => $this->getLang("SENDERH")),
            "aid" => array("type" => "text", "name" => $this->getLang("aid")),
            "pwd" => array("type" => "password", "name" => $this->getLang("pwd")),
        );
    }

    private function parseNumber($nr)
    {
        $a = str_split($nr);
        foreach ($a as &$v) {
            if (is_numeric($v)) {
                continue;
            }

            $v = "";
        }
        $nr = implode($a);

        if (substr($nr, 0, 1) == "0" && substr($nr, 1, 1) != "0") {
            return "0049" . ltrim($nr, "0");
        }
        
        return "00" . ltrim($nr, "0");
    }

    public function sendMessage($recipient, $message, $type = "t0")
    {
        if (!array_key_exists($type, $this->getTypes())) {
            return false;
        }

        $data = [
            "receiver" => $this->parseNumber($recipient),
            "sender" => $this->options->sender,
            "msg" => $message,
            "id" => $this->options->aid,
            "pw" => $this->options->pwd,
            "msgtype" => "t",
        ];

        $res = file_get_contents("https://gate1.goyyamobile.com/sms/sendsms.asp?" . http_build_query($data));
        
        return (bool) preg_match("/OK/",$res);
    }

    public function getTypes()
    {
        return array(
            "t0" => $this->getLang("SMS"),
        );
    }
}
