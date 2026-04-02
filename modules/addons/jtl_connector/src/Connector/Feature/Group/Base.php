<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Feature
 */

namespace jtl\Connector\Feature\Group;

use \jtl\Connector\Feature\Group\IGroup;
use \jtl\Connector\Feature\Base\Base as BaseClass;

/**
 * Basic group class.
 *
 * @author David Spickers <david.spickers@jtl-software.de>
 */
abstract class Base extends BaseClass implements IGroup
{

    /**
     * @var array
     */
    protected $_children;

    /**
     * Adds a children to the group.
     *
     * @param mixed $children A children of a group.
     */
    public function addChildren($children)
    {
        $this->_children[] = $children;
    }

    /**
     * Returns all children as array.
     *
     * @return array Returns the full array with childrens, stored in this group.
     */
    public function getChildrens()
    {
        return $this->_children;
    }

    /**
     * Returns if the Group has children.
     *
     * @return boolean If the group has children it will be TRUE, otherwhise
     * FALSE.
     */
    public function hasChildren()
    {
        return !empty($this->_children);
    }
}
