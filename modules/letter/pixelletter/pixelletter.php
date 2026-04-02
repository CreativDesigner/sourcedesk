<?php

class PixelLetter extends LetterProvider
{
    protected $short = "pixelletter";
    protected $name = "PixelLetter";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "mail" => array("type" => "text", "name" => $this->getLang("mail")),
            "pswd" => array("type" => "password", "name" => $this->getLang("pswd")),
        );
    }

    public function sendLetter($pdfPath, $color = true, $country = "DE", $type = 0)
    {
        if (!array_key_exists($type, $this->getTypes())) {
            return false;
        }

        if (!class_exists("PixelLetterApi")) {
            require __DIR__ . "/pixelletter_api.php";
        }

        switch ($type) {
            case 1:
                $option = "27";
                break;
            
            case 2:
                $option = "27,29";
                break;

            case 3:
                $option = "27,28";
                break;
            
            case 4:
                $option = "27,28,29";
                break;

            default:
                $option = "";
        }

        $pl = new PixelLetterApi($this->options->mail, $this->options->pswd, "ja", "ja", false);
        $res = $pl->submit_upload("1", "", "", [$pdfPath], "", $option, $country);

        return strpos($res, 'code="100"') !== false;
    }

    public function getTypes()
    {
        return array(
            "0" => $this->getLang("l0"),
            "1" => $this->getLang("l1"),
            "2" => $this->getLang("l2"),
            "3" => $this->getLang("l3"),
            "4" => $this->getLang("l4"),
        );
    }
}
