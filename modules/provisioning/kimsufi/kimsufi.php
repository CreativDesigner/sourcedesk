<?php

if (!class_exists("OvhProv")) {
    require_once __DIR__ . "/../ovh/ovh.php";
}

class KimsufiProv extends OvhProv
{
    protected $name = "Kimsufi";
    protected $short = "kimsufi";

    protected $host = "kimsufi.com";
    protected $appkey = "y7tGtaJ3IhwykTPy";
    protected $appsecret = "0Cd2bhv8fHT3oOlxoirx1hfRw855Vufh";
}
