<?php

class SipgateLog extends TelephoneLogModule
{
    protected $name = "sipgate";
    protected $short = "sipgate";

    public function getSettings()
    {
        global $lang;

        return array(
            "username" => array("type" => "text", "name" => $lang['GENERAL']['USERNAME']),
            "password" => array("type" => "password", "name" => $lang['GENERAL']['PASSWORD']),
        );
    }

    public function getLogs()
    {
        $header = [
            "Authorization: Basic " . base64_encode($this->options["username"] . ":" . $this->options["password"]),
            "Accept: application/json",
        ];

        $ch = curl_init("https://api.sipgate.com/v2/history?types=CALL&limit=25");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $res = json_decode(curl_exec($ch), true);
        curl_close($res);

        if (!is_array($res) || !array_key_exists("items", $res)) {
            return false;
        }

        $c = array();
        foreach ($res["items"] as $i) {
            $c[] = array(
                "start" => date("Y-m-d H:i:s", strtotime($i["created"])),
                "end" => date("Y-m-d H:i:s", strtotime($i["lastModified"])),
                "info" => $i["source"] . " -> " . $i["target"],
            );
        }

        return $c;
    }
}
