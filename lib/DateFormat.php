<?php

// Class for formating dates

class DateFormat
{

    private $dfo = null;

    // Constructor gets format
    public function __construct()
    {
        // Global @var CFG for security reasons
        global $CFG;

        if (!isset($CFG['DATE_FORMAT']) || empty(trim($CFG['DATE_FORMAT']))) {
            $this->dfo = "d.m.Y";
        } else {
            $this->dfo = str_replace(array("DD", "MM", "YYYY"), array("d", "m", "Y"), $CFG['DATE_FORMAT']);
        }

    }

    // Method to format date
    // Requires UNIX timestamp or PHP-readable timestring
    // @var f gives the things to show

    public function format_smarty($params)
    {
        return $this->format(isset($params["d"]) ? $params["d"] : 0, isset($params["m"]) ? $params["m"] : 1, isset($params["s"]) ? $params["s"] : 0, isset($params["t"]) ? $params["t"] : "-");
    }

    // Method to format date from Smarty

    public function format($d, $m = 1, $s = 0, $t = "-", $format = null)
    {
        if (!is_numeric($d)) {
            $d = $res = strtotime($d);
        }

        if ($format === null) {
            $format = $this->dfo;
        } else {
            $format = str_replace(array("DD", "MM", "YYYY"), array("d", "m", "Y"), $format);
        }

        if (isset($res) && !$res) {
            return false;
        }

        $f = date($format, $d);

        if ($m) {
            if (empty(trim($t))) {
                $f .= " ";
            } else {
                $f .= " $t ";
            }

            $f .= date("H:i", $d);
            if ($s) {
                $f .= date(":s", $d);
            }

        }

        return $f;
    }

    // Method to generate a placeholder

    public function placeholder($m = 1, $s = 0, $t = "-", $format = null)
    {
        return $this->format(time(), $m, $s, $t, $format);
    }

}
