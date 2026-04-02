<?php

class AbuseIpDbScoring extends Scoring
{
    protected $short = "abuseipdb";
    protected $name = "AbuseIPDB";
    protected $version = "1.1";

    public function getSettings()
    {
        global $lang;

        return array(
            "apikey" => array("type" => "password", "name" => $lang['GENERAL']['APIKEY']),
        );
    }

    public function getMethods()
    {
        return array(
            "check" => $this->getLang("check"),
        );
    }

    public function check($u)
    {
        global $db, $CFG;

        // Select last IP address
        $sql = $db->query("SELECT * FROM client_log WHERE user = " . $u->get()['ID'] . " AND ip != 'Admin' ORDER BY ID DESC, time DESC LIMIT 1");
        if ($sql->num_rows > 0 && is_object($info = $sql->fetch_object()) && filter_var($info->ip, FILTER_VALIDATE_IP)) {
            $ch = curl_init("https://api.abuseipdb.com/api/v2/check?" . http_build_query(["ipAddress" => $info->ip, "maxAgeInDays" => "360"]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Key: " . $this->options->apikey,
                "Accept: application/json",
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $res = @curl_exec($ch);
            curl_close($ch);

            if (!$res) {
                return null;
            }

            $res = json_decode($res, true);

            if (!is_array($res) || !$res || !array_key_exists("data", $res)) {
                return null;
            }

            $res = $res["data"];

            if ($res["totalReports"]) {
                return array($this->getLang("entry"), "F", "<a href='https://www.abuseipdb.com/check/" . urlencode($info->ip) . "' target='_blank'>" . $this->getLang("details") . "</a>");
            } else {
                return array($this->getLang("noentry"), "D", "");
            }
        }
    }
}
