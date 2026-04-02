<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Application
 */

namespace jtl\Connector\Authentication;

use jtl\Connector\Core\Model\AuthRequest;

interface ITokenValidator
{
    /**
     * @param AuthRequest $request
     * @return boolean
     */
    public function validate(AuthRequest $request);
}