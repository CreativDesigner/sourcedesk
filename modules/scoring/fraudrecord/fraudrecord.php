<?php

class FraudRecordScoring extends Scoring
{
    protected $short = "fraudrecord";
    protected $name = "FraudRecord";
    protected $version = "1.0";

    public function getSettings()
    {
        global $lang;

        return array(
            "apikey" => array("type" => "text", "name" => $lang['GENERAL']['APIKEY']),
        );
    }

    public function getMethods()
    {
        return array(
            "check" => $this->getLang("check"),
        );
    }

    private function hash($value)
    {
        $value = strtolower(str_replace(" ", "", trim($value)));
        for ($i = 0; $i < 32000; $i++) {
            $value = sha1("fraudrecord-" . $value);
        }

        return $value;
    }

    public function check($u)
    {
        $url = "https://www.fraudrecord.com/api/?_action=query&_api=" . $this->options->apikey . "&name=" . $this->hash($u->get()['name']) . "&email=" . $this->hash($u->get()['mail']);
        $c = trim(file_get_contents($url));
        if (substr($c, 0, 8) != '<report>') {
            return;
        }

        $c = substr($c, 8, -9);

        $ex = explode("-", $c);

        $score = "D";
        if ($ex[0] > 0) {
            $score = "E";
        }

        if ($ex[0] > 5) {
            $score = "F";
        }

        return array($this->getLang("SCORE") . ": {$ex[0]} ({$ex[1]} " . ($ex[1] == 1 ? $this->getLang("1E") : $this->getLang("XE")) . ", {$ex[2]} " . $this->getLang("rel") . ")", $score, "<a href='https://www.fraudrecord.com/api/?showreport={$ex[3]}' target='_blank'>" . $this->getLang("REP") . "</a>");
    }
}
