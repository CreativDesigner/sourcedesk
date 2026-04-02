<?php

class SipgateCallthrough extends TelephoneModule
{
    protected $name = "sipgate";
    protected $version = "1.2";

    public function call($number, $info)
    {
        $ex = explode("|", $info);

        $header = [
            "Authorization: Basic " . base64_encode($ex[0] . ":" . $ex[1]),
            "Accept: application/json",
            "Content-Type: application/json",
        ];

        $data = [
            "caller" => $ex[2],
            "callee" => $number,
        ];

        if (!empty($ex[3])) {
            $data["callerId"] = $ex[3];
        }

        if (!empty($ex[4])) {
            $data["deviceId"] = $ex[4];
        }

        $ch = curl_init("https://api.sipgate.com/v2/sessions/calls");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $res = curl_exec($ch);
        $res = json_decode($res, true);
        
        curl_close($res);

        return !empty($res['sessionId']);
    }
}
