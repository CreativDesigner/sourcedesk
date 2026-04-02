<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Linker
 */
namespace jtl\Connector\Linker;

use \jtl\Connector\Mapper\IPrimaryKeyMapper;
use \jtl\Connector\Model\DataModel;
use \jtl\Connector\Exception\LinkerException;
use \jtl\Connector\Model\Identity;
use \jtl\Connector\Core\Logger\Logger;
use \jtl\Connector\Drawing\ImageRelationType;

/**
 * Identity Connector Linker
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.com>
 */
class IdentityLinker
{
    const CACHE_TYPE_HOST = 'h';
    const CACHE_TYPE_ENDPOINT = 'e';

    const TYPE_CATEGORY = 1;
    const TYPE_CUSTOMER = 2;
    const TYPE_CUSTOMER_ORDER = 4;
    const TYPE_DELIVERY_NOTE = 8;
    const TYPE_IMAGE = 16;
    const TYPE_MANUFACTURER = 32;
    const TYPE_PRODUCT = 64;
    const TYPE_SPECIFIC = 128;
    const TYPE_SPECIFIC_VALUE = 256;
    const TYPE_PAYMENT = 512;
    const TYPE_CROSSSELLING = 1024;
    const TYPE_CROSSSELLING_GROUP = 2048;
    const TYPE_SHIPPING_CLASS = 4096;
    const TYPE_CONFIG_GROUP = 6;
    const TYPE_CONFIG_ITEM = 10;
    const TYPE_CURRENCY = 12;
    const TYPE_CUSTOMER_GROUP = 14;
    const TYPE_LANGUAGE = 18;
    const TYPE_UNIT = 20;
    const TYPE_MEASUREMENT_UNIT = 22;
    const TYPE_PRODUCT_TYPE = 24;
    const TYPE_SHIPPING_METHOD = 26;
    const TYPE_TAX_RATE = 28;
    const TYPE_WAREHOUSE = 30;
    const TYPE_CATEGORY_ATTRIBUTE = 34;
    const TYPE_PRODUCT_ATTRIBUTE = 36;
    const TYPE_PRODUCT_VARIATION = 38;
    const TYPE_PRODUCT_VARIATION_VALUE = 40;

    /**
     * Session Database Mapper
     *
     * @var IPrimaryKeyMapper
     */
    protected static $mapper;

    /**
     * @var bool
     */
    public static $useCache;

    /**
     * @var array
     */
    protected static $cache = array();

    /**
     * @var self
     */
    protected static $instance;

    /**
     * @var array
     */
    protected static $types = array(
        'Category' => self::TYPE_CATEGORY,
        'CategoryAttr' => self::TYPE_CATEGORY_ATTRIBUTE,
        'ConfigGroup' => self::TYPE_CONFIG_GROUP,
        'ConfigItem' => self::TYPE_CONFIG_ITEM,
        'CrossSelling' => self::TYPE_CROSSSELLING,
        'CrossSellingGroup' => self::TYPE_CROSSSELLING_GROUP,
        'CrossSellingItem' => self::TYPE_CROSSSELLING,
        'Currency' => self::TYPE_CURRENCY,
        'Customer' => self::TYPE_CUSTOMER,
        'CustomerGroup' => self::TYPE_CUSTOMER_GROUP,
        'CustomerOrder' => self::TYPE_CUSTOMER_ORDER,
        'DeliveryNote' => self::TYPE_DELIVERY_NOTE,
        'Image' => self::TYPE_IMAGE,
        'Language' => self::TYPE_LANGUAGE,
        'Manufacturer' => self::TYPE_MANUFACTURER,
        'MeasurementUnit' => self::TYPE_MEASUREMENT_UNIT,
        'Payment' => self::TYPE_PAYMENT,
        'Product' => self::TYPE_PRODUCT,
        'ProductAttr' => self::TYPE_PRODUCT_ATTRIBUTE,
        'ProductType' => self::TYPE_PRODUCT_TYPE,
        'ProductVariation' => self::TYPE_PRODUCT_VARIATION,
        'ProductVariationValue' => self::TYPE_PRODUCT_VARIATION_VALUE,
        'ShippingClass' => self::TYPE_SHIPPING_CLASS,
        'ShippingMethod' => self::TYPE_SHIPPING_METHOD,
        'Specific' => self::TYPE_SPECIFIC,
        'SpecificValue' => self::TYPE_SPECIFIC_VALUE,
        'TaxRate' => self::TYPE_TAX_RATE,
        'Unit' => self::TYPE_UNIT,
        'Warehouse' => self::TYPE_WAREHOUSE,
    );

    /**
     * @var array
     */
    protected static $mappings = [
        'Category' => [
            'id' => self::TYPE_CATEGORY,
            'parentCategoryId' => self::TYPE_CATEGORY
        ],
        'CategoryAttr' => [
            'categoryId' => self::TYPE_CATEGORY
        ],
        'CategoryCustomerGroup' => [
            'categoryId' => self::TYPE_CATEGORY
        ],
        'CategoryI18n' => [
            'categoryId' => self::TYPE_CATEGORY
        ],
        'CategoryInvisibility' => [
            'categoryId' => self::TYPE_CATEGORY
        ],
        'ConfigGroup' => [
            'id' => self::TYPE_CONFIG_GROUP
        ],
        'ConfigItem' => [
            'id' => self::TYPE_CONFIG_ITEM,
            'configGroupId' => self::TYPE_CONFIG_GROUP,
            'productId' => self::TYPE_PRODUCT,
        ],
        'CrossSelling' => [
            'id' => self::TYPE_CROSSSELLING,
            'productId' => self::TYPE_PRODUCT
        ],
        'CrossSellingItem' => [
            'crossSellingGroupId' => self::TYPE_CROSSSELLING_GROUP,
            'productIds' => self::TYPE_PRODUCT  // List of Product identities
        ],
        'CrossSellingGroup' => [
            'id' => self::TYPE_CROSSSELLING_GROUP
        ],
        'Currency' => [
            'id' => self::TYPE_CURRENCY,
        ],
        'Customer' => [
            'id' => self::TYPE_CUSTOMER,
            'customerGroupId' => self::TYPE_CUSTOMER_GROUP,
        ],
        'CustomerAttr' => [
            'customerId' => self::TYPE_CUSTOMER
        ],
        'CustomerGroup' => [
            'id' => self::TYPE_CUSTOMER_GROUP,
        ],
        'CustomerOrder' => [
            'id' => self::TYPE_CUSTOMER_ORDER,
            'customerId' => self::TYPE_CUSTOMER,
            'shippingMethodId' => self::TYPE_SHIPPING_METHOD,
        ],
        'CustomerOrderAttr' => [
            'customerOrderId' => self::TYPE_CUSTOMER_ORDER
        ],
        'CustomerOrderBillingAddress' => [
            'customerId' => self::TYPE_CUSTOMER
        ],
        'CustomerOrderShippingAddress' => [
            'customerId' => self::TYPE_CUSTOMER
        ],
        'CustomerOrderPaymentInfo' => [
            'customerOrderId' => self::TYPE_CUSTOMER_ORDER
        ],
        'CustomerOrderItem' => [
            'productId' => self::TYPE_PRODUCT,
            'customerOrderId' => self::TYPE_CUSTOMER_ORDER
        ],
        'DeliveryNote' => [
            'id' => self::TYPE_DELIVERY_NOTE,
            'customerOrderId' => self::TYPE_CUSTOMER_ORDER
        ],
        'DeliveryNoteItem' => [
            'deliveryNoteId' => self::TYPE_DELIVERY_NOTE,
            'productId' => self::TYPE_PRODUCT
        ],
        'FileUpload' => [
            'productId' => self::TYPE_PRODUCT
        ],
        'Image' => [
            'id' => self::TYPE_IMAGE,
            'foreignKey' => self::TYPE_IMAGE
        ],
        'ImageI18n' => [
            'imageId' => self::TYPE_IMAGE
        ],
        'Language' => [
            'id' => self::TYPE_LANGUAGE,
        ],
        'Manufacturer' => [
            'id' => self::TYPE_MANUFACTURER
        ],
        'ManufacturerI18n' => [
            'manufacturerId' => self::TYPE_MANUFACTURER
        ],
        'MeasurementUnit' => [
            'id' => self::TYPE_MEASUREMENT_UNIT,
        ],
        'MeasurementUnitI18n' => [
            'measurementUnitId' => self::TYPE_MEASUREMENT_UNIT,
        ],
        'MediaFile' => [
            'productId' => self::TYPE_PRODUCT
        ],
        'PartsList' => [
            'productId' => self::TYPE_PRODUCT
        ],
        'Payment' => [
            'id' => self::TYPE_PAYMENT,
            'customerOrderId' => self::TYPE_CUSTOMER_ORDER
        ],
        'Product' => [
            'id' => self::TYPE_PRODUCT,
            'masterProductId' => self::TYPE_PRODUCT,
            'manufacturerId' => self::TYPE_MANUFACTURER,
            'measurementUnitId' => self::TYPE_MEASUREMENT_UNIT,
            'productTypeId' => self::TYPE_PRODUCT_TYPE,
            'shippingClassId' => self::TYPE_SHIPPING_CLASS,
            'unitId' => self::TYPE_UNIT,
        ],
        'Product2Category' => [
            'productId' => self::TYPE_PRODUCT,
            'categoryId' => self::TYPE_CATEGORY
        ],
        'ProductAttr' => [
            'id' => self::TYPE_PRODUCT_ATTRIBUTE,
            'productId' => self::TYPE_PRODUCT,
        ],
        'ProductConfigGroup' => [
            'productId' => self::TYPE_PRODUCT,
            'configGroupId' => self::TYPE_CONFIG_GROUP,
        ],
        'ProductFileDownload' => [
            'productId' => self::TYPE_PRODUCT
        ],
        'ProductI18n' => [
            'productId' => self::TYPE_PRODUCT
        ],
        'ProductInvisibility' => [
            'productId' => self::TYPE_PRODUCT
        ],
        'ProductPrice' => [
            'productId' => self::TYPE_PRODUCT
        ],
        'ProductSpecialPrice' => [
            'productId' => self::TYPE_PRODUCT
        ],
        'ProductSpecific' => [
            'id' => self::TYPE_SPECIFIC,
            'productId' => self::TYPE_PRODUCT,
            'specificValueId' => self::TYPE_SPECIFIC_VALUE
        ],
        'ProductStockLevel' => [
            'productId' => self::TYPE_PRODUCT
        ],
        'ProductType' => [
            'id' => self::TYPE_PRODUCT_TYPE,
        ],
        'ProductVariation' => [
            'id' => self::TYPE_PRODUCT_VARIATION,
            'productId' => self::TYPE_PRODUCT
        ],
        'ProductVariationI18n' => [
            'productVariationId' => self::TYPE_PRODUCT_VARIATION,
        ],
        'ProductVariationValue' => [
            'id' => self::TYPE_PRODUCT_VARIATION_VALUE,
            'productVariationId' => self::TYPE_PRODUCT_VARIATION,
        ],
        'ProductVariationValueI18n' => [
            'productVariationValueId ' => self::TYPE_PRODUCT_VARIATION_VALUE,
        ],
        'ProductVariationValueExtraCharge' => [
            'customerGroupId' => self::TYPE_CUSTOMER_GROUP,
            'productVariationValueId ' => self::TYPE_PRODUCT_VARIATION_VALUE,
        ],
        'ProductVariationValueInvisibility' => [
            'customerGroupId' => self::TYPE_CUSTOMER_GROUP,
            'productVariationValueId ' => self::TYPE_PRODUCT_VARIATION_VALUE,
        ],
        'ProductWarehouseInfo' => [
            'productId' => self::TYPE_PRODUCT,
            'warehouseId' => self::TYPE_WAREHOUSE,
        ],
        'ShippingClass' => [
            'id' => self::TYPE_SHIPPING_CLASS,
        ],
        'ShippingMethod' => [
            'id' => self::TYPE_SHIPPING_METHOD,
        ],
        'Shipment' => [
            'deliveryNoteId' => self::TYPE_DELIVERY_NOTE
        ],
        'Specific' => [
            'id' => self::TYPE_SPECIFIC
        ],
        'SpecificI18n' => [
            'specificId' => self::TYPE_SPECIFIC
        ],
        'SpecificValue' => [
            'id' => self::TYPE_SPECIFIC_VALUE,
            'specificId' => self::TYPE_SPECIFIC
        ],
        'SpecificValueI18n' => [
            'specificValueId' => self::TYPE_SPECIFIC_VALUE
        ],
        'StatusChange' => [
            'customerOrderId' => self::TYPE_CUSTOMER_ORDER
        ],
        'TaxRate' => [
            'id' => self::TYPE_TAX_RATE,
        ],
        'Unit' => [
            'id' => self::TYPE_UNIT,
        ],
        'UnitI18n' => [
            'unitId' => self::TYPE_UNIT,
        ],
        'Warehouse' => [
            'id' => self::TYPE_WAREHOUSE,
        ],
    ];

    /**
     * @var array
     */
    protected $runtimeInfos = array();

    /**
     * Singleton
     *
     * @param boolean $useCache
     * @return \jtl\Connector\Linker\IdentityLinker
     */
    public static function getInstance($useCache = true)
    {
        if (self::$instance === null) {
            self::$instance = new self($useCache);
        } else {
            self::$instance->useCache($useCache);
        }

        return self::$instance;
    }

    /**
     * Constructor
     *
     * @param boolean $useCache
     */
    protected function __construct($useCache = true)
    {
        self::$useCache = (bool)$useCache;
    }

    /**
     * Constructor
     *
     * @param boolean $useCache
     * @return \jtl\Connector\Linker\IdentityLinker
     */
    public function useCache($useCache)
    {
        self::$useCache = (bool)$useCache;
    }

    /**
     * Database setter
     *
     * @param IPrimaryKeyMapper $mapper
     */
    public function setPrimaryKeyMapper(IPrimaryKeyMapper $mapper)
    {
        self::$mapper = $mapper;
    }

    /**
     * Database setter
     *
     * @param \jtl\Connector\Model\DataModel $model
     * @param bool $isDeleted
     * @throws \jtl\Connector\Exception\LinkerException
     */
    public function linkModel(DataModel &$model, $isDeleted = false)
    {
        $reflect = new \ReflectionClass($model);

        // Image work around
        if (isset($this->runtimeInfos['relationType'])) {
            unset($this->runtimeInfos['relationType']);
        }

        if ($reflect->getShortName() === 'Image' && method_exists($model, 'getRelationType')) {
            $this->runtimeInfos['relationType'] = $model->getRelationType();
        }

        foreach ($model->getModelType()->getProperties() as $propertyInfo) {
            $property = ucfirst($propertyInfo->getName());
            $setter = 'set' . $property;
            $getter = 'get' . $property;

            if ($propertyInfo->isNavigation() && $model->{$getter}() !== null) {
                if (is_array($model->{$getter}())) {
                    $list = $model->{$getter}();
                    foreach ($list as &$entity) {
                        if ($entity instanceof DataModel) {
                            $this->linkModel($entity, $isDeleted);
                        } elseif ($entity instanceof Identity && $this->isType($reflect->getShortName(), $property)) {
                            $this->linkIdentityList($entity, $reflect->getShortName(), $property, $isDeleted);
                        } else {
                            Logger::write(
                                sprintf('Property (%s) from model (%s) is not an instance of DataModel or Identity', $propertyInfo->getName(), $reflect->getShortName()),
                                Logger::WARNING, 'linker'
                            );
                        }
                    }
                } elseif ($model->{$getter}() instanceof DataModel) {
                    $entity = $model->{$getter}();
                    $this->linkModel($entity, $isDeleted);
                } else {
                    Logger::write(
                        sprintf('Property (%s) from model (%s) is not an array or an instance of DataModel', $propertyInfo->getName(), $reflect->getShortName()),
                        Logger::WARNING, 'linker'
                    );
                }
            } elseif ($propertyInfo->isIdentity() && $this->isType($reflect->getShortName(), $property)) {
                $identity = $model->{$getter}();

                if (!($identity instanceof Identity)) {
                    continue;
                }

                if (strlen($identity->getEndpoint()) > 0 && $identity->getHost() > 0) {
                    if ($isDeleted) {
                        $this->delete($identity->getEndpoint(), $identity->getHost(), $reflect->getShortName());
                    } elseif (!$this->exists(null, $identity->getHost(), $reflect->getShortName(), $property, true)) {
                        $this->save($identity->getEndpoint(), $identity->getHost(), $reflect->getShortName(), $property);
                    }

                    continue;
                } else {
                    if ($identity->getHost() > 0) {
                        if ($isDeleted) {
                            $this->delete(null, $identity->getHost(), $reflect->getShortName());
                        } elseif ($this->exists(null, $identity->getHost(), $reflect->getShortName(), $property)) {
                            $identity->setEndpoint($this->getEndpointId($identity->getHost(), $reflect->getShortName(), $property));

                            $model->{$setter}($identity);
                        }
                    } elseif (strlen($identity->getEndpoint()) > 0) {
                        if ($isDeleted) {
                            $this->delete($identity->getEndpoint(), null, $reflect->getShortName());
                        } elseif ($this->exists($identity->getEndpoint(), null, $reflect->getShortName(), $property)) {
                            $identity->setHost($this->getHostId($identity->getEndpoint(), $reflect->getShortName(), $property));

                            $model->{$setter}($identity);
                        }
                    }
                }
            }
        }
    }

    /**
     * Linker for identity lists
     *
     * @param \jtl\Connector\Model\Identity $identity
     * @param string $modelName
     * @param string $property
     * @param bool $isDeleted
     * @throws \jtl\Connector\Exception\LinkerException
     */
    public function linkIdentityList(Identity &$identity, $modelName, $property, $isDeleted = false)
    {
        if (strlen($identity->getEndpoint()) > 0 && $identity->getHost() > 0) {
            if ($isDeleted) {
                $this->delete($identity->getEndpoint(), $identity->getHost(), $modelName);
            } elseif (!$this->exists(null, $identity->getHost(), $modelName, $property, true)) {
                $this->save($identity->getEndpoint(), $identity->getHost(), $modelName, $property);
            }
        } else {
            if ($identity->getHost() > 0) {
                if ($isDeleted) {
                    $this->delete(null, $identity->getHost(), $modelName);
                } elseif ($this->exists(null, $identity->getHost(), $modelName, $property)) {
                    $identity->setEndpoint($this->getEndpointId($identity->getHost(), $modelName, $property));
                }
            } elseif (strlen($identity->getEndpoint()) > 0) {
                if ($isDeleted) {
                    $this->delete($identity->getEndpoint(), null, $modelName);
                } elseif ($this->exists($identity->getEndpoint(), null, $modelName, $property)) {
                    $identity->setHost($this->getHostId($identity->getEndpoint(), $modelName, $property));
                }
            }
        }
    }

    /**
     * Type id getter
     *
     * @param string $modelName
     * @param string $property
     * @throws \jtl\Connector\Exception\LinkerException
     * @return int
     */
    public function getType($modelName, $property = null)
    {
        // Work around
        if ($modelName === 'Image' && $property === 'ForeignKey' && isset($this->runtimeInfos['relationType'])) {
            switch ($this->runtimeInfos['relationType']) {
                case ImageRelationType::TYPE_PRODUCT:
                    return self::TYPE_PRODUCT;
                    break;
                case ImageRelationType::TYPE_CATEGORY:
                    return self::TYPE_CATEGORY;
                    break;
                case ImageRelationType::TYPE_SPECIFIC:
                    return self::TYPE_SPECIFIC;
                    break;
                case ImageRelationType::TYPE_SPECIFIC_VALUE:
                    return self::TYPE_SPECIFIC_VALUE;
                    break;
                case ImageRelationType::TYPE_MANUFACTURER:
                    return self::TYPE_MANUFACTURER;
                    break;
            }
        }

        $modelName = ucfirst($modelName);

        if ($property === null) {
            if (!isset(self::$types[$modelName])) {
                throw new LinkerException(sprintf('Type for model (%s) not found', $modelName));
            }

            return self::$types[$modelName];
        }

        $property = lcfirst($property);

        if (!isset(self::$mappings[$modelName])) {
            throw new LinkerException(sprintf('Type for model (%s) not found', $modelName));
        }

        if (!isset(self::$mappings[$modelName][$property])) {
            throw new LinkerException(sprintf('Type for model (%s) and property (%s) not found', $modelName, $property));
        }

        return self::$mappings[$modelName][$property];
    }

    /**
     * Type id getter
     *
     * @param string $modelName
     * @param string $property
     * @return boolean
     */
    public function isType($modelName, $property = null)
    {
        $modelName = ucfirst($modelName);
        $property = is_string($property) ? lcfirst($property) : $property;

        return ($property === null) ? isset(self::$types[$modelName]) : isset(self::$mappings[$modelName][$property]);
    }

    /**
     * Model name getter
     *
     * @param int $type
     * @throws \jtl\Connector\Exception\LinkerException
     * @return string
     */
    public function getModelName($type)
    {
        $modelName = array_search($type, self::$types);
        if ($modelName === false) {
            throw new LinkerException(sprintf('Model for type (%s) not found', $type));
        }

        return $modelName;
    }

    /**
     * Checks if link exists
     *
     * @param string $endpointId
     * @param integer $hostId
     * @param string $modelName
     * @param string $property
     * @param boolean $validate
     * @throws \jtl\Connector\Exception\LinkerException
     * @return boolean
     */
    public function exists($endpointId = null, $hostId = null, $modelName, $property, $validate = false)
    {
        if (!$this->isValidEndpointId($endpointId) && !$this->isValidHostId($hostId)) {
            throw new LinkerException(sprintf(
                'Both parameters (endpointId = %s, hostId = %s, modelName: %s, property: %s) are invalid.',
                $endpointId,
                $hostId,
                $modelName,
                $property
            ));
        }

        $type = $this->getType($modelName, $property);

        $id = null;
        $cacheType = null;
        if ($this->isValidEndpointId($endpointId)) {
            $id = $endpointId;
            $cacheType = self::CACHE_TYPE_ENDPOINT;
        } elseif ($this->isValidHostId($hostId)) {
            $id = $hostId;
            $cacheType = self::CACHE_TYPE_HOST;
        }

        if ($this->checkCache($id, $type, $cacheType, $validate)) {
            return true;
        }

        if ($this->isValidEndpointId($endpointId)) {
            $hostId = self::$mapper->getHostId($endpointId, $type);
        } elseif ($this->isValidHostId($hostId)) {
            $relationType = isset($this->runtimeInfos['relationType']) ? $this->runtimeInfos['relationType'] : null;
            $endpointId = self::$mapper->getEndpointId($hostId, $type, $relationType);
        }

        if ($validate && !$this->isValidEndpointId($endpointId)) {
            return false;
        }

        //if ($endpointId !== null && $hostId !== null) {
        $this->saveCache($endpointId, $hostId, $type, $cacheType);

        return true;
        //}

        //return false;
    }

    /**
     * Save link to database
     *
     * @param string $endpointId
     * @param integer $hostId
     * @param string $modelName
     * @param string $property
     * @return boolean
     * @throws \jtl\Connector\Exception\LinkerException
     */
    public function save($endpointId, $hostId, $modelName, $property = null)
    {
        $type = $this->getType($modelName, $property);

        $result = self::$mapper->save($endpointId, $hostId, $type);
        if ($result) {
            $this->saveCache($endpointId, $hostId, $type, self::CACHE_TYPE_ENDPOINT);
            $this->saveCache($endpointId, $hostId, $type, self::CACHE_TYPE_HOST);

            return true;
        }

        return false;
    }

    /**
     * Delete link from database
     *
     * @param string $endpointId
     * @param integer $hostId
     * @param string $modelName
     * @return boolean
     */
    public function delete($endpointId = null, $hostId = null, $modelName)
    {
        $type = $this->getType($modelName);

        $result = self::$mapper->delete($endpointId, $hostId, $type);
        if ($result) {
            $this->deleteCache($endpointId, null, $type, self::CACHE_TYPE_ENDPOINT);
            $this->deleteCache(null, $hostId, $type, self::CACHE_TYPE_HOST);

            return true;
        }

        return false;
    }

    /**
     * Clears the entire link table
     *
     * @return boolean
     */
    public function clear()
    {
        return self::$mapper->clear();
    }

    /**
     * Endpoint ID getter
     *
     * @param integer $hostId
     * @param string $modelName
     * @param string $property
     * @return string
     */
    public function getEndpointId($hostId, $modelName, $property)
    {
        $type = $this->getType($modelName, $property);

        if (($endpointId = $this->loadCache($hostId, $type, self::CACHE_TYPE_HOST)) !== false) {
            //if (($endpointId = $this->loadCache($hostId, $type, self::CACHE_TYPE_HOST)) !== null) {
            return $endpointId;
        }

        $relationType = isset($this->runtimeInfos['relationType']) ? $this->runtimeInfos['relationType'] : null;
        $endpointId = self::$mapper->getEndpointId($hostId, $type, $relationType);

        //if (is_string($endpointId) && strlen(trim($endpointId)) > 0) {
        $this->saveCache($endpointId, $hostId, $type, self::CACHE_TYPE_HOST);

        return $endpointId;
        //}

        //return '';
    }

    /**
     * Host ID getter
     *
     * @param string $endpointId
     * @param string $modelName
     * @param string $property
     * @return int
     */
    public function getHostId($endpointId, $modelName, $property)
    {
        $type = $this->getType($modelName, $property);

        if (($hostId = $this->loadCache($endpointId, $type, self::CACHE_TYPE_ENDPOINT)) !== false) {
            //if (($hostId = $this->loadCache($endpointId, $type, self::CACHE_TYPE_ENDPOINT)) !== null) {
            return $hostId;
        }

        $hostId = self::$mapper->getHostId($endpointId, $type);

        //if (is_int($hostId) && $hostId > 0) {
        $this->saveCache($endpointId, $hostId, $type, self::CACHE_TYPE_ENDPOINT);

        return $hostId;
        //}

        //return 0;
    }

    /**
     * @param mixed $id
     * @param int $type
     * @param string $cacheType
     * @param bool $validate
     * @return bool
     */
    protected function checkCache($id, $type, $cacheType, $validate = false)
    {
        //return (self::$useCache && array_key_exists($this->buildKey($id, $type, $cacheType), self::$cache));
        //return (self::$useCache && isset(self::$cache[$this->buildKey($id, $type, $cacheType)]));

        $result = self::$useCache && array_key_exists($this->buildKey($id, $type, $cacheType), self::$cache);
        /*
        if (!$validate) {
            return $result;
        }
        */

        if ($validate && $result) {
            $data = self::$cache[$this->buildKey($id, $type, $cacheType)];
            switch ($cacheType) {
                case self::CACHE_TYPE_ENDPOINT:
                    $result = $this->isValidHostId($data);
                    break;
                case self::CACHE_TYPE_HOST:
                    $result = $this->isValidEndpointId($data);
                    break;
            }
        }

        /*
        $result = (self::$useCache && array_key_exists($this->buildKey($id, $type, $cacheType), self::$cache)
            && (!$validate || self::$cache[$this->buildKey($id, $type, $cacheType)] !== null));
        */

        // Debug
        Logger::write(sprintf(
            'Checkcache (id: %s, type: %s, cacheType: %s) with result %s',
            $id,
            $type,
            $cacheType,
            $result ? 'true' : 'false'
        ), Logger::DEBUG, 'linker');

        return $result;
    }

    /**
     * @param mixed $id
     * @param int $type
     * @param string $cacheType
     * @return bool|mixed
     */
    protected function loadCache($id, $type, $cacheType)
    {
        $result = $this->checkCache($id, $type, $cacheType) ? self::$cache[$this->buildKey($id, $type, $cacheType)] : false;
        //return $this->checkCache($id, $type, $cacheType) ? self::$cache[$this->buildKey($id, $type, $cacheType)] : null;

        // Debug
        Logger::write(sprintf(
            'LoadCache (id: %s, type: %s, cacheType: %s) with result %s',
            $id,
            $type,
            $cacheType,
            $result
        ), Logger::DEBUG, 'linker');

        return $result;
    }

    /**
     * @param mixed $endpointId
     * @param int $hostId
     * @param int $type
     * @param string $cacheType
     */
    protected function saveCache($endpointId, $hostId, $type, $cacheType)
    {
        // Debug
        Logger::write(sprintf(
            'SaveCache (endpointId: %s, hostId: %s, type: %s, cacheType: %s)',
            $endpointId,
            $hostId,
            $type,
            $cacheType
        ), Logger::DEBUG, 'linker');

        if (self::$useCache) {
            switch ($cacheType) {
                case self::CACHE_TYPE_ENDPOINT:
                    self::$cache[$this->buildKey($endpointId, $type, $cacheType)] = $hostId;
                    break;
                case self::CACHE_TYPE_HOST:
                    self::$cache[$this->buildKey($hostId, $type, $cacheType)] = $endpointId;
                    break;
            }
        }
    }

    /**
     * @param mixed $endpointId
     * @param int $hostId
     * @param int $type
     * @param string $cacheType
     */
    protected function deleteCache($endpointId = null, $hostId = null, $type, $cacheType)
    {
        // Debug
        Logger::write(sprintf(
            'DeleteCache (endpointId: %s, hostId: %s, type: %s, cacheType: %s)',
            $endpointId,
            $hostId,
            $type,
            $cacheType
        ), Logger::DEBUG, 'linker');

        if (self::$useCache) {
            switch ($cacheType) {
                case self::CACHE_TYPE_ENDPOINT:
                    unset(self::$cache[$this->buildKey($endpointId, $type, $cacheType)]);
                    break;
                case self::CACHE_TYPE_HOST:
                    unset(self::$cache[$this->buildKey($hostId, $type, $cacheType)]);
                    break;
            }
        }
    }

    /**
     * @param mixed $id
     * @param int $type
     * @param string $cacheType
     * @return string
     */
    protected function buildKey($id, $type, $cacheType)
    {
        return sprintf('%s_%s_%s', $cacheType, $id, $type);
    }

    /**
     * @return array
     */
    public static function getCache()
    {
        return self::$cache;
    }

    /**
     * @param mixed $endpointId
     * @return boolean
     */
    public function isValidEndpointId($endpointId)
    {
        return !is_null($endpointId) && is_string($endpointId) && strlen(trim($endpointId)) > 0;
    }

    /**
     * @param mixed $hostId
     * @return boolean
     */
    public function isValidHostId($hostId)
    {
        return !is_null($hostId) && is_int($hostId) && $hostId > 0;
    }
}
