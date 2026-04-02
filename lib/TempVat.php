<?php
// Class for handling temporary VAT changes

class TempVat
{
    public static function rate($country, $rate, $date = null)
    {
        $date = date("Y-m-d", strtotime($date ?: date("Y-m-d")));
        if ($date < "2020-07-01" || $date > "2020-12-31") {
            return false;
        }

        if ($country == "DE") {
            switch ($rate) {
                case 19:
                    return 16;

                case 7:
                    return 5;
            }
        }

        return false;
    }
}
