<?php

class MaxMindScoring extends Scoring
{
    protected $short = "maxmind";
    protected $name = "MaxMind";
    protected $version = "1.0";

    public function getSettings()
    {
        global $lang;

        return array(
            "userid" => array("type" => "text", "name" => $lang['GENERAL']['USERID']),
            "licensekey" => array("type" => "password", "name" => $lang['GENERAL']['LICENSEKEY']),
        );
    }

    public function getMethods()
    {
        return array(
            "check" => $this->getLang("CHECK"),
        );
    }

    public function check($u)
    {
        global $db, $CFG;

        require_once __DIR__ . "/../../../vendor/autoload.php";

        $mf = new MaxMind\MinFraud($this->options->userid, $this->options->licensekey);

        // Select last IP address and device
        $sql = $db->query("SELECT * FROM client_log WHERE user = " . $u->get()['ID'] . " AND ip != 'Admin' AND ua != '' ORDER BY ID DESC, time DESC LIMIT 1");
        if ($sql->num_rows > 0 && is_object($info = $sql->fetch_object()) && filter_var($info->ip, FILTER_VALIDATE_IP)) {
            $mf = $mf->withDevice([
                "ip_address" => $info->ip,
                "user_agent" => $info->ua,
            ]);
        } else {
            return false;
        }

        // Add account details
        $mf = $mf->withAccount([
            "user_id" => $u->get()['ID'],
            "username_md5" => md5($u->get()['mail']),
        ]);

        // Add billing details
        $mf = $mf->withBilling($details = [
            "first_name" => $u->get()['firstname'],
            "last_name" => $u->get()['lastname'],
            "company" => $u->get()['company'],
            "address" => $u->get()['street'] . " " . $u->get()['street_number'],
            "address" => "",
            "city" => $u->get()['city'],
            "country" => $u->get()['country_alpha2'],
            "postal" => $u->get()['postcode'],
        ])->withShipping($details);

        // Get response
        $points = $mf->score()->riskScore;

        $score = "D";
        if ($points > 1) {
            $score = "E";
        }

        if ($points > 10) {
            $score = "F";
        }

        return array($this->getLang("SCORE") . ": {$points}", $score, "");
    }
}
