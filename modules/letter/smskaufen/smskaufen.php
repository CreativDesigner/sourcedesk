<?php

class SMSKaufen extends LetterProvider
{
    protected $short = "smskaufen";
    protected $name = "SMSkaufen.com";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "skid" => array("type" => "text", "name" => $this->getLang("username")),
            "skpw" => array("type" => "password", "name" => $this->getLang("password"), "help" => $this->getLang("passwordh")),
            "skkey" => array("type" => "password", "name" => $this->getLang("key")),
            "hint" => array("name" => $this->getLang("hint"), "help" => $this->getLang("hinth"), "type" => "hint"),
        );
    }

    public function sendLetter($pdfPath, $color = true, $country = "DE", $type = 0)
    {
        if (!array_key_exists($type, $this->getTypes())) {
            return false;
        }

        $url = "https://www.smskaufen.com/sms/post/postin.php";
        $p = array(
            "id" => $this->options->skid,
            "document" => class_exists("CURLFile") ? new CURLFile($pdfPath) : "@" . $pdfPath,
            "art" => "b",
            "mode" => "0",
            "color" => $color ? "f" : "",
            "ausland" => $country != "DE" ? "1" : "0",
        );

        if ($type > 0) {
            $p['einschreiben'] = $type;
        }

        if (!empty($this->options->skkey)) {
            $p['apikey'] = $this->options->skkey;
        } else {
            $p['pw'] = $this->options->skpw;
        }

        $c = curl_init($url);
        curl_setopt($c, CURLOPT_POSTFIELDS, $p);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
        $r = curl_exec($c);
        curl_close($c);

        if (strlen($r) != 13) {
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
            "0" => $this->getLang("l0"),
            "1" => $this->getLang("l1"),
            "2" => $this->getLang("l2"),
            "3" => $this->getLang("l3"),
        );
    }
}
