<?php

class AnydeskLog extends TelephoneLogModule
{
    protected $name = "Anydesk";
    protected $short = "anydesk";

    public function getSettings()
    {
        global $lang;

        return array(
            "licenseId" => array("type" => "text", "name" => $lang['GENERAL']['LICENSEID']),
            "apiPassword" => array("type" => "password", "name" => $lang['GENERAL']['APIKEY']),
        );
    }

    public function getLogs()
    {
        $licenseId = $this->options['licenseId'];
        $apiPassword = $this->options['apiPassword'];

        $time = time();
        $contentHash = "2jmj7l5rSw0yVb/vlWAYkK/YBwk=";
        $resource = "/sessions?limit=20";

        $tokenRaw = "GET\n$resource\n$time\n$contentHash";
        $token = base64_encode(hash_hmac("sha1", $tokenRaw, $apiPassword, true));
        $auth = "AD $licenseId:$time:$token";

        $ch = curl_init("https://v1.api.anydesk.com:8081" . $resource);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: $auth"]);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        if (empty($res->list)) {
            return array();
        }

        $c = array();

        foreach ($res->list as $i) {
            $c[] = array(
                "start" => date("Y-m-d H:i:s", $i->{'start-time'}),
                "end" => date("Y-m-d H:i:s", $i->{'end-time'}),
                "info" => $i->to->alias,
            );
        }

        return $c;
    }
}
