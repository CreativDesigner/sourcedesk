<?php

class Branding
{
    public static function get()
    {
        global $db, $CFG;

        $host = $_SERVER['HTTP_HOST'] ?? "";
        if (empty($host)) {
            return false;
        }

        $sql = $db->query("SELECT * FROM branding WHERE host = '" . $db->real_escape_string($host) . "'");
        return $sql->num_rows ? $sql->fetch_object() : false;
    }
}
