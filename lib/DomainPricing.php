<?php
// Class for exporting domain pricing

class DomainPricing
{
    public static function getJson()
    {
        $data = self::getData();
        return json_encode($data);
    }

    public static function getCsv()
    {
        $data = self::getData();
        $csv = '"TLD";"Register";"Transfer";"Renew";"Trade";"Privacy";"Period"' . "\n";
        foreach ($data as $row) {
            $csv .= '"' . implode('";"', array_values($row)) . '"' . "\n";
        }

        return trim($csv);
    }

    public static function getXml()
    {
        $data = self::getData();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n<pricing>\n";

        foreach ($data as $row) {
            $xml .= "  <price>\n";
            foreach ($row as $k => $v) {
                $xml .= "    <$k>$v</$k>\n";
            }
            $xml .= "  </price>\n";
        }

        $xml .= "</pricing>";

        return trim($xml);
    }

    private static function getData()
    {
        global $db, $CFG;

        $data = [];

        $sql = $db->query("SELECT tld, register, transfer, renew, trade, privacy, period FROM domain_pricing ORDER BY `tld` ASC");
        while ($row = $sql->fetch_assoc()) {
            $aSql = $db->query("SELECT `type`, `price` FROM domain_actions WHERE `start` <= '" . date("Y-m-d H:i:s") . "' AND `end` >= '" . date("Y-m-d H:i:s") . "' AND tld = '" . $db->real_escape_string(ltrim($row['tld'], ".")) . "'");
            while ($aRow = $aSql->fetch_object()) {
                $price = [
                    "REG" => "register",
                    "RENEW" => "renew",
                    "KK" => "transfer",
                ][$aRow->type];

                if ($row[$price] > $aRow->price) {
                    $row[$price] = $aRow->price;
                }
            }

            array_push($data, $row);
        }

        return $data;
    }
}
