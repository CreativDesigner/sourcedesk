<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Feature
 */

namespace jtl\Connector\Feature\Exception;

/**
 * If you are adding a new feature to your a feature already exists
 *
 * @access public
 * @author David Spickers <david.spickers@jtl-software.de>
 */
class FeatureExist extends \RuntimeException
{

    public function __construct($feature, $code = 0, $previous = null)
    {
        parent::__construct(sprintf('The feature "%s" already exists', $feature), $code, $previous);
    }
}
