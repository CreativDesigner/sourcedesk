<?php
/**
 * @copyright 2010-2014 JTL-Software GmbH
 * @package jtl\Connector\Type
 */

namespace jtl\Connector\Type;

use \jtl\Connector\Type\PropertyInfo;

/**
 * @access public
 * @package jtl\Connector\Type
 */
class ProductFileDownload extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('fileDownloadId', 'Identity', null, true, true, false),
            new PropertyInfo('productId', 'Identity', null, true, true, false),
        );
    }

    public function isMain()
    {
        return false;
    }
}
