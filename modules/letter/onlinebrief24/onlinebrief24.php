<?php

class OnlineBrief24 extends LetterProvider
{
    protected $short = "onlinebrief24";
    protected $name = "onlinebrief24";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "obun" => array("type" => "email", "name" => $this->getLang("mail")),
            "obpw" => array("type" => "password", "name" => $this->getLang("password")),
        );
    }

    public function sendLetter($pdfPath, $color = true, $country = "DE", $type = 0)
    {
        if (!array_key_exists($type, $this->getTypes())) {
            return false;
        }

        $filename = ($color ? "1" : "0") . "10" . ($country == "DE" ? "1" : "3") . $type . "00000000-" . substr(basename($pdfPath), 0, -4) . ".pdf";

        $sftp = new phpseclib\Net\SFTP('api.letterei-onlinebrief.de');
        if (!$sftp->login($this->options->obun, $this->options->obpw)) {
            return false;
        }

        $sftp->chdir('upload/api');
        if (!$sftp->put($filename, $pdfPath, NET_SFTP_LOCAL_FILE)) {
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
