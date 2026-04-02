<?php

class SMSKaufen extends SMSProvider
{
    protected $short = "smskaufen";
    protected $name = "SMSkaufen.com";
    protected $version = "1.0";

    public function getSettings()
    {
        global $lang;

        return array(
            "sender" => array("type" => "text", "name" => $this->getLang("sender"), "help" => $this->getLang("senderh")),
            "skid" => array("type" => "text", "name" => $lang['GENERAL']['USERNAME']),
            "skpw" => array("type" => "password", "name" => $lang['GENERAL']['PASSWORD'], "help" => $this->getLang("passwordh")),
            "skkey" => array("type" => "password", "name" => $lang['GENERAL']['APIKEY']),
            "hint" => array("name" => $this->getLang("hint"), "help" => $this->getLang("hinth"), "type" => "hint"),
        );
    }

    public function sendMessage($recipient, $message, $type = 0)
    {
        if (!array_key_exists($type, $this->getTypes())) {
            return false;
        }

        $url = "https://www.smskaufen.com/sms/gateway/sms.php";
        $p = array(
            "id" => $this->options->skid,
            "type" => $type,
            "empfaenger" => $recipient,
            "absender" => $this->options->sender,
        );

        if (!empty($this->options->skkey)) {
            $p['apikey'] = $this->options->skkey;
        } else {
            $p['pw'] = $this->options->skpw;
        }

        $message = urlencode(utf8_decode($message));
        $c = curl_init($url . "?" . http_build_query($p) . "&text=" . $message);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
        $r = curl_exec($c);
        curl_close($c);

        if ($r != "100") {
            $codes = array(
                "112" => $this->getLang("e1"),
                "122" => $this->getLang("e2"),
                "123" => $this->getLang("e3"),
                "124" => $this->getLang("e4"),
                "125" => $this->getLang("e5"),
                "140" => $this->getLang("e6"),
                "160" => $this->getLang("e7"),
            );

            if (array_key_exists($r, $codes)) {
                return $codes[$r];
            }

            if (!empty($r)) {
                return strval($r);
            }

            return false;
        }

        return true;
    }

    public function getTypes()
    {
        return array(
            "3" => $this->getLang("s0"),
            "4" => $this->getLang("s1"),
            "8" => $this->getLang("s2"),
        );
    }
}
