<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started\n", FILE_APPEND);
// Cronjob for updating currency rates

// Get base currency code
$sql = $db->query("SELECT currency_code FROM currencies WHERE base = 1 AND conversion_rate = 1 LIMIT 1");
if ($sql->num_rows != 1) {
    die("Base currency not found.");
}

$baseCur = $sql->fetch_object()->currency_code;

// Update all other currencies
$sql = $db->query("SELECT * FROM currencies WHERE base != 1");
while ($currency = $sql->fetch_object()) {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Updating {$currency->currency_code}\n", FILE_APPEND);

    @$res = file_get_contents("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml");
    if (!$res) {
        continue;
    }

    $res = new SimpleXMLElement($res);
    if (!is_object($res->Cube->Cube->Cube[0])) {
        continue;
    }

    $cube = $res->Cube->Cube->Cube;
    
    if ($baseCur != "EUR") {
        for ($i = 0; $i < count($cube); $i++) {
            $c = strval($cube[$i]['currency']);
            $r = 1 / doubleval($cube[$i]['rate']);

            if ($c == $baseCur) {
                $foundBaseCur = 1;
                $fact = $r;
            }
        }

        if (!isset($foundBaseCur)) {
            continue;
        }

    } else {
        $fact = 1;
    }

    if ($currency->currency_code == "EUR") {
        $rate = $fact;
    } else {
        unset($rate);
        for ($i = 0; $i < count($cube); $i++) {
            $c = strval($cube[$i]['currency']);
            $r = doubleval($cube[$i]['rate']);

            if ($c == $currency->currency_code) {
                $rate = $r * $fact;
            }

        }
    }
    if (!isset($rate)) {
        continue;
    }

    $rate = 1 / $rate;

    $db->query("UPDATE currencies SET conversion_rate = '" . $db->real_escape_string($rate) . "' WHERE ID = " . $currency->ID . " LIMIT 1");
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] {$currency->currency_code} updated\n", FILE_APPEND);
}

// Update product pricing
$sql = $db->query("SELECT currency_price, currency_id, ID FROM products WHERE currency_active = 1");
while ($row = $sql->fetch_object()) {
    $price = $cur->convertAmount($row->currency_id, $row->currency_price, $cur->getBaseCurrency());
    $db->query("UPDATE products SET price = $price WHERE ID = " . $row->ID);
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);
