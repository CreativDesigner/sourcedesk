<?php

if (!class_exists("OvhProv")) {
    require_once __DIR__ . "/../ovh/ovh.php";
}

class SoYouStartProv extends OvhProv
{
    protected $name = "So you Start";
    protected $short = "soyoustart";

    protected $host = "soyoustart.com";
    protected $appkey = "6tGjsMRQS47Ir7WI";
    protected $appsecret = "FP0PxnpmanVSXtedTsIxVmSIHjzFZdlz";
}
