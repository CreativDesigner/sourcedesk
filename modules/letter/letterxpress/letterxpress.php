<?php

class LetterXpress extends LetterProvider
{
    protected $short = "letterxpress";
    protected $name = "LetterXpress";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "usr" => array("type" => "text", "name" => $this->getLang("usr")),
            "key" => array("type" => "password", "name" => $this->getLang("key")),
        );
    }

    public function sendLetter($pdfPath, $color = true, $country = "DE", $type = 0)
    {
        if (!array_key_exists($type, $this->getTypes())) {
            return false;
        }

        $data = [
            "auth" => [
                "username" => $this->options->usr,
                "apikey" => $this->options->key,
            ],
            "letter" => [
                "base64_file" => $file = base64_encode(file_get_contents($pdfPath)),
                "base64_checksum" => md5($file),
                "address" => "read",
                "specification" => [
                    "color" => $color ? 4 : 1,
                    "mode" => $type == 0 ? "simplex" : "duplex",
                    "ship" => $country == "DE" ? "national" : "international",
                    "c4" => "n"
                ],
            ],
        ];

        $url = "https://api.letterxpress.de/v1/setJob";
        $c = curl_init($url);
        curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($c, CURLOPT_HTTPHEADER, ["Content-type: application/json"]);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        $r = json_decode(curl_exec($c), true);
        curl_close($c);

        return $r['message'] == "OK";
    }

    public function getTypes()
    {
        return array(
            "0" => $this->getLang("l0"),
            "1" => $this->getLang("l1"),
        );
    }
}
