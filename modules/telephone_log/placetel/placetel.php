<?php

class PlacetelLog extends TelephoneLogModule
{
    protected $name = "Placetel";
    protected $short = "placetel";

    public function getSettings()
    {
        global $lang;

        return array(
            "key" => array("type" => "password", "name" => $lang['GENERAL']['APIKEY']),
        );
    }

    private function callsByDay($time)
    {
        $params = array(
            "api_key" => $this->options['key'],
            "year" => date("Y", $time),
            "month" => date("m", $time),
            "day" => date("d", $time),
        );

        $ch = curl_init("https://api.placetel.de/api/getCDRsByDay.json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        if (!$res) {
            return array();
        }

        if ($res->result == "-1") {
            return array();
        }

        $c = array();
        foreach ($res as $i) {
            $c[] = array(
                "start" => date("Y-m-d H:i:s", strtotime($i->when_date)),
                "end" => date("Y-m-d H:i:s", strtotime("+{$i->length} seconds", strtotime($i->when_date))),
                "info" => $i->from . " -> " . $i->to,
            );
        }

        return $c;
    }

    public function getLogs()
    {
        $c = array();
        for ($i = 0; $i <= 10; $i++) {
            $c = array_merge($c, $this->callsByDay(strtotime("-$i days")));
        }

        return $c;
    }
}
