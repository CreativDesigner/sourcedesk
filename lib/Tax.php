<?php
// Class for tax calculations

class Tax
{
    // Deduct standard tax rate from price
    public static function deduct($price)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT percent, alpha2 FROM client_countries WHERE ID = " . intval($CFG['DEFAULT_COUNTRY']));
        if ($sql->num_rows != 1) {
            return $price;
        }

        $couInfo = $sql->fetch_object();
        $percent = $couInfo->percent;
        if ($tempVat = TempVat::rate($couInfo->alpha2, $percent)) {
            $percent = $tempVat;
        }

        return $price / (1 + ($percent / 100));
    }

    // Add current visitor tax rate on price
    public static function add($price)
    {
        global $var, $user, $db, $CFG;
        if ($var['logged_in']) {
            return $user->addTax($price);
        }

        $sql = $db->query("SELECT percent, alpha2 FROM client_countries WHERE ID = " . intval($CFG['DEFAULT_COUNTRY']));
        if ($sql->num_rows != 1) {
            return $price;
        }

        $couInfo = $sql->fetch_object();
        $percent = $couInfo->percent;
        if ($tempVat = TempVat::rate($couInfo->alpha2, $percent)) {
            $percent = $tempVat;
        }

        return $price * (1 + ($percent / 100));
    }
}
