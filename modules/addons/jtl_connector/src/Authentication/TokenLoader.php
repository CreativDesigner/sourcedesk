<?php

namespace jtl\Connector\Example\Authentication;

use jtl\Connector\Authentication\ITokenLoader;

class TokenLoader implements ITokenLoader
{

    /**
     * Loads the connector token
     *
     * @return string
     */
    public function load()
    {
        global $addons;
        return $addons->get()["jtl_connector"]->getOption("token");
    }
}
