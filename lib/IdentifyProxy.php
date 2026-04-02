<?php

class IdentifyProxy
{
    public static function is()
    {
        global $session;

        if ($session->get('proxy_ip') == ip()) {
            return (bool) $session->get('proxy_is');
        }

        if (!file_exists(__DIR__ . "/tmp/proxy_list.txt")) {
            return false;
        }

        $s = in_array(ip(), explode("\n", file_get_contents(__DIR__ . "/tmp/proxy_list.txt")));

        $session->set('proxy_ip', ip());
        $session->set('proxy_is', $s);
        return $s;
    }

    public static function update()
    {
        @$r = file_get_contents("https://check.torproject.org/cgi-bin/TorBulkExitList.py?ip=8.8.8.8&port=443");
        if (false === $r) {
            return false;
        }

        file_put_contents(__DIR__ . "/tmp/proxy_list.txt", $r);
    }
}
