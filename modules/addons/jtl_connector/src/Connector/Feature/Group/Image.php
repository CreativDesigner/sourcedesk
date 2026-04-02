<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Feature
 */

namespace jtl\Connector\Feature\Group;

use \jtl\Connector\Feature\Group\Base as BaseGroup;

/**
 * Special image group, that supports additional parameters inside of the params
 * array.
 *
 * @author David Spickers <david.spickers@jtl-software.de>
 */
class Image extends BaseGroup
{

    const RELATIONS_KEY_NAME = 'relationTypes';

    /**
     * @var array
     */
    protected $_relation_types = array();

    /**
     * Creates the instance, parses the params and will look for relation types.
     *
     * If there is the entry with the key "relationTypes" inside of this array,
     * all relations will be extracted and saved in the _relation_types array.
     *
     * @param array $params The features array entry of the image feature as
     * reference.
     */
    public function __construct(array &$params)
    {
        $this->name = 'Image';
        if (array_key_exists(self::RELATIONS_KEY_NAME, $params)) {
            $this->_relation_types = $params[self::RELATIONS_KEY_NAME];
            unset($params[self::RELATIONS_KEY_NAME]);
        }
    }

    /**
     * Returns the relations.
     *
     * @return array
     */
    public function getRelations()
    {
        return $this->_relation_types;
    }
}
