<?php
// Class for product functions

class Product
{
    public static function getClientPrice($price, $type)
    {
        global $db, $CFG, $var, $user;

        if (!$CFG['TAXES']) {
            return $price;
        }

        if ($type != "net" && $type != "dynamic") {
            return $price;

        }

        if ($type == "dynamic") {
            $sql = $db->query("SELECT percent, alpha2 FROM client_countries WHERE ID = " . $CFG['DEFAULT_COUNTRY']);
            if ($sql->num_rows) {
                $couInfo = $sql->fetch_object();
                $percent = $couInfo->percent;
                if ($tempVat = TempVat::rate($couInfo->alpha2, $percent)) {
                    $percent = $tempVat;
                }

                $price = $price / (1 + $percent / 100);
            }
        }

        if ($var['logged_in']) {
            $price = $user->addTax($price);
        } else {
            $country = isset($_POST['country']) ? intval($_POST['country']) : (isset($_SESSION['country']) ? intval($_SESSION['country']) : intval($CFG['DEFAULT_COUNTRY']));
            $sql = $db->query("SELECT percent, alpha2 FROM client_countries WHERE ID = " . $country);
            if ($sql->num_rows) {
                $couInfo = $sql->fetch_object();
                $percent = $couInfo->percent;
                if ($tempVat = TempVat::rate($couInfo->alpha2, $percent)) {
                    $percent = $tempVat;
                }

                $price = $price * (1 + $percent / 100);
            }
        }

        return round($price, 2);
    }
}
