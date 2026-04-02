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
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class GlobalData extends DataModel
{
    /**
     * @var \jtl\Connector\Model\ConfigGroup[]
     * @Serializer\Type("array<jtl\Connector\Model\ConfigGroup>")
     * @Serializer\SerializedName("configGroups")
     * @Serializer\AccessType("reflection")
     */
    protected $configGroups = array();

    /**
     * @var \jtl\Connector\Model\ConfigItem[]
     * @Serializer\Type("array<jtl\Connector\Model\ConfigItem>")
     * @Serializer\SerializedName("configItems")
     * @Serializer\AccessType("reflection")
     */
    protected $configItems = array();

    /**
     * @var \jtl\Connector\Model\CrossSellingGroup[]
     * @Serializer\Type("array<jtl\Connector\Model\CrossSellingGroup>")
     * @Serializer\SerializedName("crossSellingGroups")
     * @Serializer\AccessType("reflection")
     */
    protected $crossSellingGroups = array();

    /**
     * @var \jtl\Connector\Model\Currency[]
     * @Serializer\Type("array<jtl\Connector\Model\Currency>")
     * @Serializer\SerializedName("currencies")
     * @Serializer\AccessType("reflection")
     */
    protected $currencies = array();

    /**
     * @var \jtl\Connector\Model\CustomerGroup[]
     * @Serializer\Type("array<jtl\Connector\Model\CustomerGroup>")
     * @Serializer\SerializedName("customerGroups")
     * @Serializer\AccessType("reflection")
     */
    protected $customerGroups = array();

    /**
     * @var \jtl\Connector\Model\Language[]
     * @Serializer\Type("array<jtl\Connector\Model\Language>")
     * @Serializer\SerializedName("languages")
     * @Serializer\AccessType("reflection")
     */
    protected $languages = array();

    /**
     * @var \jtl\Connector\Model\MeasurementUnit[]
     * @Serializer\Type("array<jtl\Connector\Model\MeasurementUnit>")
     * @Serializer\SerializedName("measurementUnits")
     * @Serializer\AccessType("reflection")
     */
    protected $measurementUnits = array();

    /**
     * @var \jtl\Connector\Model\ProductType[]
     * @Serializer\Type("array<jtl\Connector\Model\ProductType>")
     * @Serializer\SerializedName("productTypes")
     * @Serializer\AccessType("reflection")
     */
    protected $productTypes = array();

    /**
     * @var \jtl\Connector\Model\ShippingClass[]
     * @Serializer\Type("array<jtl\Connector\Model\ShippingClass>")
     * @Serializer\SerializedName("shippingClasses")
     * @Serializer\AccessType("reflection")
     */
    protected $shippingClasses = array();

    /**
     * @var \jtl\Connector\Model\ShippingMethod[]
     * @Serializer\Type("array<jtl\Connector\Model\ShippingMethod>")
     * @Serializer\SerializedName("shippingMethods")
     * @Serializer\AccessType("reflection")
     */
    protected $shippingMethods = array();

    /**
     * @var \jtl\Connector\Model\TaxRate[]
     * @Serializer\Type("array<jtl\Connector\Model\TaxRate>")
     * @Serializer\SerializedName("taxRates")
     * @Serializer\AccessType("reflection")
     */
    protected $taxRates = array();

    /**
     * @var \jtl\Connector\Model\Unit[]
     * @Serializer\Type("array<jtl\Connector\Model\Unit>")
     * @Serializer\SerializedName("units")
     * @Serializer\AccessType("reflection")
     */
    protected $units = array();

    /**
     * @var \jtl\Connector\Model\Warehouse[]
     * @Serializer\Type("array<jtl\Connector\Model\Warehouse>")
     * @Serializer\SerializedName("warehouses")
     * @Serializer\AccessType("reflection")
     */
    protected $warehouses = array();


    /**
     * @param \jtl\Connector\Model\ConfigGroup $configGroup
     * @return \jtl\Connector\Model\GlobalData
     */
    public function addConfigGroup(\jtl\Connector\Model\ConfigGroup $configGroup)
    {
        $this->configGroups[] = $configGroup;
        return $this;
    }
    
    /**
     * @param array $configGroups
     * @return \jtl\Connector\Model\GlobalData
     */
    public function setConfigGroups(array $configGroups)
    {
        $this->configGroups = $configGroups;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\ConfigGroup[]
     */
    public function getConfigGroups()
    {
        return $this->configGroups;
    }

    /**
     * @return \jtl\Connector\Model\GlobalData
     */
    public function clearConfigGroups()
    {
        $this->configGroups = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\ConfigItem $configItem
     * @return \jtl\Connector\Model\GlobalData
     */
    public function addConfigItem(\jtl\Connector\Model\ConfigItem $configItem)
    {
        $this->configItems[] = $configItem;
        return $this;
    }
    
    /**
     * @param array $configItems
     * @return \jtl\Connector\Model\GlobalData
     */
    public function setConfigItems(array $configItems)
    {
        $this->configItems = $configItems;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\ConfigItem[]
     */
    public function getConfigItems()
    {
        return $this->configItems;
    }

    /**
     * @return \jtl\Connector\Model\GlobalData
     */
    public function clearConfigItems()
    {
        $this->configItems = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\CrossSellingGroup $crossSellingGroup
     * @return \jtl\Connector\Model\GlobalData
     */
    public function addCrossSellingGroup(\jtl\Connector\Model\CrossSellingGroup $crossSellingGroup)
    {
        $this->crossSellingGroups[] = $crossSellingGroup;
        return $this;
    }
    
    /**
     * @param array $crossSellingGroups
     * @return \jtl\Connector\Model\GlobalData
     */
    public function setCrossSellingGroups(array $crossSellingGroups)
    {
        $this->crossSellingGroups = $crossSellingGroups;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\CrossSellingGroup[]
     */
    public function getCrossSellingGroups()
    {
        return $this->crossSellingGroups;
    }

    /**
     * @return \jtl\Connector\Model\GlobalData
     */
    public function clearCrossSellingGroups()
    {
        $this->crossSellingGroups = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\Currency $currency
     * @return \jtl\Connector\Model\GlobalData
     */
    public function addCurrency(\jtl\Connector\Model\Currency $currency)
    {
        $this->currencies[] = $currency;
        return $this;
    }
    
    /**
     * @param array $currencies
     * @return \jtl\Connector\Model\GlobalData
     */
    public function setCurrencies(array $currencies)
    {
        $this->currencies = $currencies;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\Currency[]
     */
    public function getCurrencies()
    {
        return $this->currencies;
    }

    /**
     * @return \jtl\Connector\Model\GlobalData
     */
    public function clearCurrencies()
    {
        $this->currencies = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\CustomerGroup $customerGroup
     * @return \jtl\Connector\Model\GlobalData
     */
    public function addCustomerGroup(\jtl\Connector\Model\CustomerGroup $customerGroup)
    {
        $this->customerGroups[] = $customerGroup;
        return $this;
    }
    
    /**
     * @param array $customerGroups
     * @return \jtl\Connector\Model\GlobalData
     */
    public function setCustomerGroups(array $customerGroups)
    {
        $this->customerGroups = $customerGroups;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\CustomerGroup[]
     */
    public function getCustomerGroups()
    {
        return $this->customerGroups;
    }

    /**
     * @return \jtl\Connector\Model\GlobalData
     */
    public function clearCustomerGroups()
    {
        $this->customerGroups = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\Language $language
     * @return \jtl\Connector\Model\GlobalData
     */
    public function addLanguage(\jtl\Connector\Model\Language $language)
    {
        $this->languages[] = $language;
        return $this;
    }
    
    /**
     * @param array $languages
     * @return \jtl\Connector\Model\GlobalData
     */
    public function setLanguages(array $languages)
    {
        $this->languages = $languages;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\Language[]
     */
    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * @return \jtl\Connector\Model\GlobalData
     */
    public function clearLanguages()
    {
        $this->languages = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\MeasurementUnit $measurementUnit
     * @return \jtl\Connector\Model\GlobalData
     */
    public function addMeasurementUnit(\jtl\Connector\Model\MeasurementUnit $measurementUnit)
    {
        $this->measurementUnits[] = $measurementUnit;
        return $this;
    }
    
    /**
     * @param array $measurementUnits
     * @return \jtl\Connector\Model\GlobalData
     */
    public function setMeasurementUnits(array $measurementUnits)
    {
        $this->measurementUnits = $measurementUnits;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\MeasurementUnit[]
     */
    public function getMeasurementUnits()
    {
        return $this->measurementUnits;
    }

    /**
     * @return \jtl\Connector\Model\GlobalData
     */
    public function clearMeasurementUnits()
    {
        $this->measurementUnits = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\ProductType $productType
     * @return \jtl\Connector\Model\GlobalData
     */
    public function addProductType(\jtl\Connector\Model\ProductType $productType)
    {
        $this->productTypes[] = $productType;
        return $this;
    }
    
    /**
     * @param array $productTypes
     * @return \jtl\Connector\Model\GlobalData
     */
    public function setProductTypes(array $productTypes)
    {
        $this->productTypes = $productTypes;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\ProductType[]
     */
    public function getProductTypes()
    {
        return $this->productTypes;
    }

    /**
     * @return \jtl\Connector\Model\GlobalData
     */
    public function clearProductTypes()
    {
        $this->productTypes = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\ShippingClass $shippingClass
     * @return \jtl\Connector\Model\GlobalData
     */
    public function addShippingClass(\jtl\Connector\Model\ShippingClass $shippingClass)
    {
        $this->shippingClasses[] = $shippingClass;
        return $this;
    }
    
    /**
     * @param array $shippingClasses
     * @return \jtl\Connector\Model\GlobalData
     */
    public function setShippingClasses(array $shippingClasses)
    {
        $this->shippingClasses = $shippingClasses;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\ShippingClass[]
     */
    public function getShippingClasses()
    {
        return $this->shippingClasses;
    }

    /**
     * @return \jtl\Connector\Model\GlobalData
     */
    public function clearShippingClasses()
    {
        $this->shippingClasses = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\ShippingMethod $shippingMethod
     * @return \jtl\Connector\Model\GlobalData
     */
    public function addShippingMethod(\jtl\Connector\Model\ShippingMethod $shippingMethod)
    {
        $this->shippingMethods[] = $shippingMethod;
        return $this;
    }
    
    /**
     * @param array $shippingMethods
     * @return \jtl\Connector\Model\GlobalData
     */
    public function setShippingMethods(array $shippingMethods)
    {
        $this->shippingMethods = $shippingMethods;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\ShippingMethod[]
     */
    public function getShippingMethods()
    {
        return $this->shippingMethods;
    }

    /**
     * @return \jtl\Connector\Model\GlobalData
     */
    public function clearShippingMethods()
    {
        $this->shippingMethods = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\TaxRate $taxRate
     * @return \jtl\Connector\Model\GlobalData
     */
    public function addTaxRate(\jtl\Connector\Model\TaxRate $taxRate)
    {
        $this->taxRates[] = $taxRate;
        return $this;
    }
    
    /**
     * @param array $taxRates
     * @return \jtl\Connector\Model\GlobalData
     */
    public function setTaxRates(array $taxRates)
    {
        $this->taxRates = $taxRates;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\TaxRate[]
     */
    public function getTaxRates()
    {
        return $this->taxRates;
    }

    /**
     * @return \jtl\Connector\Model\GlobalData
     */
    public function clearTaxRates()
    {
        $this->taxRates = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\Unit $unit
     * @return \jtl\Connector\Model\GlobalData
     */
    public function addUnit(\jtl\Connector\Model\Unit $unit)
    {
        $this->units[] = $unit;
        return $this;
    }
    
    /**
     * @param array $units
     * @return \jtl\Connector\Model\GlobalData
     */
    public function setUnits(array $units)
    {
        $this->units = $units;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\Unit[]
     */
    public function getUnits()
    {
        return $this->units;
    }

    /**
     * @return \jtl\Connector\Model\GlobalData
     */
    public function clearUnits()
    {
        $this->units = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\Warehouse $warehouse
     * @return \jtl\Connector\Model\GlobalData
     */
    public function addWarehouse(\jtl\Connector\Model\Warehouse $warehouse)
    {
        $this->warehouses[] = $warehouse;
        return $this;
    }
    
    /**
     * @param array $warehouses
     * @return \jtl\Connector\Model\GlobalData
     */
    public function setWarehouses(array $warehouses)
    {
        $this->warehouses = $warehouses;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\Warehouse[]
     */
    public function getWarehouses()
    {
        return $this->warehouses;
    }

    /**
     * @return \jtl\Connector\Model\GlobalData
     */
    public function clearWarehouses()
    {
        $this->warehouses = array();
        return $this;
    }
}
