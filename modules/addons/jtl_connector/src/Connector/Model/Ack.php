<?php
/**
 * @copyright 2010-2014 JTL-Software GmbH
 * @package jtl\Connector\Model
 * @subpackage Ack
 */

namespace jtl\Connector\Model;

use JMS\Serializer\Annotation as Serializer;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * Ack
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Internal
 * @Serializer\AccessType("public_method")
 */
class Ack extends DataModel
{
    /**
     * @var Identity list
     * @Serializer\Type("ArrayCollection<string, ArrayCollection<jtl\Connector\Model\Identity>>")
     * @Serializer\SerializedName("identities")
     * @Serializer\Accessor(getter="getIdentities",setter="setIdentities")
     */
    protected $identities = null;

    /**
     * @var Checksum[] 
     * @Serializer\Type("array<jtl\Connector\Model\Checksum>")
     * @Serializer\SerializedName("checksums")
     * @Serializer\AccessType("reflection")
     */
    protected $checksums = array();

    /**
     * Identities getter
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getIdentities()
    {
        return $this->identities;
    }

    /**
     * Identities getter
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $identities
     * @return \jtl\Connector\Model\Ack
     */
    public function setIdentities(ArrayCollection $identities)
    {
        $this->identities = $identities;
        return $this;
    }

    /**
     * @param Checksum $checksum
     * @return \jtl\Connector\Model\Ack
     */
    public function addChecksum(Checksum $checksum)
    {
        $this->checksums[] = $checksum;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Checksum\IChecksum[]
     */
    public function getChecksums()
    {
        return $this->checksums;
    }

    /**
     * @return \jtl\Connector\Model\Ack
     */
    public function clearChecksums()
    {
        $this->checksums = array();
        return $this;
    }
}
