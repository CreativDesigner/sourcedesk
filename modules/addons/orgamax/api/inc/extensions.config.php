<?php

class Extensions
{
    private static $requiredExtensions = array(
        "mysqli",
        "openssl"
    );

    public static function getList()
    {
        return Extensions::$requiredExtensions;
    }
}

?>