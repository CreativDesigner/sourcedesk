<?php
/**
 * @copyright 2010-2015 JTL-Software GmbH
 * @package jtl\Connector\Model
 * @subpackage Product
 */

namespace jtl\Connector\Model;

use DateTime;
use JMS\Serializer\Annotation as Serializer;

/**
 * A delivery note created for shipment.
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class DeliveryNote extends DataModel
{
    /**
     * @var Identity Reference to customerOrder
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("customerOrderId")
     * @Serializer\Accessor(getter="getCustomerOrderId",setter="setCustomerOrderId")
     */
    protected $customerOrderId = null;

    /**
     * @var Identity Unique deliveryNote id
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var DateTime Creation date
     * @Serializer\Type("DateTime")
     * @Serializer\SerializedName("creationDate")
     * @Serializer\Accessor(getter="getCreationDate",setter="setCreationDate")
     */
    protected $creationDate = null;

    /**
     * @var boolean Optional flag for fulfillment. True, if delivery ist fulfilled by someone else
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("isFulfillment")
     * @Serializer\Accessor(getter="getIsFulfillment",setter="setIsFulfillment")
     */
    protected $isFulfillment = false;

    /**
     * @var string Optional text note
     * @Serializer\Type("string")
     * @Serializer\SerializedName("note")
     * @Serializer\Accessor(getter="getNote",setter="setNote")
     */
    protected $note = '';

    /**
     * @var \jtl\Connector\Model\DeliveryNoteItem[]
     * @Serializer\Type("array<jtl\Connector\Model\DeliveryNoteItem>")
     * @Serializer\SerializedName("items")
     * @Serializer\AccessType("reflection")
     */
    protected $items = array();

    /**
     * @var \jtl\Connector\Model\DeliveryNoteTrackingList[]
     * @Serializer\Type("array<jtl\Connector\Model\DeliveryNoteTrackingList>")
     * @Serializer\SerializedName("trackingLists")
     * @Serializer\AccessType("reflection")
     */
    protected $trackingLists = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = new Identity();
        $this->customerOrderId = new Identity();
    }

    /**
     * @param Identity $customerOrderId Reference to customerOrder
     * @return \jtl\Connector\Model\DeliveryNote
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setCustomerOrderId(Identity $customerOrderId)
    {
        return $this->setProperty('customerOrderId', $customerOrderId, 'Identity');
    }

    /**
     * @return Identity Reference to customerOrder
     */
    public function getCustomerOrderId()
    {
        return $this->customerOrderId;
    }

    /**
     * @param Identity $id Unique deliveryNote id
     * @return \jtl\Connector\Model\DeliveryNote
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unique deliveryNote id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param DateTime $creationDate Creation date
     * @return \jtl\Connector\Model\DeliveryNote
     * @throws \InvalidArgumentException if the provided argument is not of type 'DateTime'.
     */
    public function setCreationDate(DateTime $creationDate = null)
    {
        return $this->setProperty('creationDate', $creationDate, 'DateTime');
    }

    /**
     * @return DateTime Creation date
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param boolean $isFulfillment Optional flag for fulfillment. True, if delivery ist fulfilled by someone else
     * @return \jtl\Connector\Model\DeliveryNote
     */
    public function setIsFulfillment($isFulfillment)
    {
        return $this->setProperty('isFulfillment', $isFulfillment, 'boolean');
    }

    /**
     * @return boolean Optional flag for fulfillment. True, if delivery ist fulfilled by someone else
     */
    public function getIsFulfillment()
    {
        return $this->isFulfillment;
    }

    /**
     * @param string $note Optional text note
     * @return \jtl\Connector\Model\DeliveryNote
     */
    public function setNote($note)
    {
        return $this->setProperty('note', $note, 'string');
    }

    /**
     * @return string Optional text note
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param \jtl\Connector\Model\DeliveryNoteItem $item
     * @return \jtl\Connector\Model\DeliveryNote
     */
    public function addItem(\jtl\Connector\Model\DeliveryNoteItem $item)
    {
        $this->items[] = $item;
        return $this;
    }
    
    /**
     * @param array $items
     * @return \jtl\Connector\Model\DeliveryNote
     */
    public function setItems(array $items)
    {
        $this->items = $items;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\DeliveryNoteItem[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return \jtl\Connector\Model\DeliveryNote
     */
    public function clearItems()
    {
        $this->items = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\DeliveryNoteTrackingList $trackingList
     * @return \jtl\Connector\Model\DeliveryNote
     */
    public function addTrackingList(\jtl\Connector\Model\DeliveryNoteTrackingList $trackingList)
    {
        $this->trackingLists[] = $trackingList;
        return $this;
    }
    
    /**
     * @param array $trackingLists
     * @return \jtl\Connector\Model\DeliveryNote
     */
    public function setTrackingLists(array $trackingLists)
    {
        $this->trackingLists = $trackingLists;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\DeliveryNoteTrackingList[]
     */
    public function getTrackingLists()
    {
        return $this->trackingLists;
    }

    /**
     * @return \jtl\Connector\Model\DeliveryNote
     */
    public function clearTrackingLists()
    {
        $this->trackingLists = array();
        return $this;
    }
}
