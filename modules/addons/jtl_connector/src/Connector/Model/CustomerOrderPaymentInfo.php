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
 * Additional payment info for direct debit / banktransfer or payment by credit card. 
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class CustomerOrderPaymentInfo extends DataModel
{
    /**
     * @var Identity Reference to customerOrder
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("customerOrderId")
     * @Serializer\Accessor(getter="getCustomerOrderId",setter="setCustomerOrderId")
     */
    protected $customerOrderId = null;

    /**
     * @var Identity Unique customerOrderPaymentInfo id
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var string Bank account holder name
     * @Serializer\Type("string")
     * @Serializer\SerializedName("accountHolder")
     * @Serializer\Accessor(getter="getAccountHolder",setter="setAccountHolder")
     */
    protected $accountHolder = '';

    /**
     * @var string Bank account number (deprecated in DE since SEPA)
     * @Serializer\Type("string")
     * @Serializer\SerializedName("accountNumber")
     * @Serializer\Accessor(getter="getAccountNumber",setter="setAccountNumber")
     */
    protected $accountNumber = '';

    /**
     * @var string Bank code (deprecated in DE since SEPA)
     * @Serializer\Type("string")
     * @Serializer\SerializedName("bankCode")
     * @Serializer\Accessor(getter="getBankCode",setter="setBankCode")
     */
    protected $bankCode = '';

    /**
     * @var string Bank name
     * @Serializer\Type("string")
     * @Serializer\SerializedName("bankName")
     * @Serializer\Accessor(getter="getBankName",setter="setBankName")
     */
    protected $bankName = '';

    /**
     * @var string Bank Identifier Code (BIC)
     * @Serializer\Type("string")
     * @Serializer\SerializedName("bic")
     * @Serializer\Accessor(getter="getBic",setter="setBic")
     */
    protected $bic = '';

    /**
     * @var string Credit card expiration date
     * @Serializer\Type("string")
     * @Serializer\SerializedName("creditCardExpiration")
     * @Serializer\Accessor(getter="getCreditCardExpiration",setter="setCreditCardExpiration")
     */
    protected $creditCardExpiration = '';

    /**
     * @var string 
     * @Serializer\Type("string")
     * @Serializer\SerializedName("creditCardHolder")
     * @Serializer\Accessor(getter="getCreditCardHolder",setter="setCreditCardHolder")
     */
    protected $creditCardHolder = '';

    /**
     * @var string Credit card number
     * @Serializer\Type("string")
     * @Serializer\SerializedName("creditCardNumber")
     * @Serializer\Accessor(getter="getCreditCardNumber",setter="setCreditCardNumber")
     */
    protected $creditCardNumber = '';

    /**
     * @var string Credit card type (e.g. "visa")
     * @Serializer\Type("string")
     * @Serializer\SerializedName("creditCardType")
     * @Serializer\Accessor(getter="getCreditCardType",setter="setCreditCardType")
     */
    protected $creditCardType = '';

    /**
     * @var string Credit card verification number
     * @Serializer\Type("string")
     * @Serializer\SerializedName("creditCardVerificationNumber")
     * @Serializer\Accessor(getter="getCreditCardVerificationNumber",setter="setCreditCardVerificationNumber")
     */
    protected $creditCardVerificationNumber = '';

    /**
     * @var string International Bank Account Number (IBAN)
     * @Serializer\Type("string")
     * @Serializer\SerializedName("iban")
     * @Serializer\Accessor(getter="getIban",setter="setIban")
     */
    protected $iban = '';

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
     * @return \jtl\Connector\Model\CustomerOrderPaymentInfo
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
     * @param Identity $id Unique customerOrderPaymentInfo id
     * @return \jtl\Connector\Model\CustomerOrderPaymentInfo
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unique customerOrderPaymentInfo id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $accountHolder Bank account holder name
     * @return \jtl\Connector\Model\CustomerOrderPaymentInfo
     */
    public function setAccountHolder($accountHolder)
    {
        return $this->setProperty('accountHolder', $accountHolder, 'string');
    }

    /**
     * @return string Bank account holder name
     */
    public function getAccountHolder()
    {
        return $this->accountHolder;
    }

    /**
     * @param string $accountNumber Bank account number (deprecated in DE since SEPA)
     * @return \jtl\Connector\Model\CustomerOrderPaymentInfo
     */
    public function setAccountNumber($accountNumber)
    {
        return $this->setProperty('accountNumber', $accountNumber, 'string');
    }

    /**
     * @return string Bank account number (deprecated in DE since SEPA)
     */
    public function getAccountNumber()
    {
        return $this->accountNumber;
    }

    /**
     * @param string $bankCode Bank code (deprecated in DE since SEPA)
     * @return \jtl\Connector\Model\CustomerOrderPaymentInfo
     */
    public function setBankCode($bankCode)
    {
        return $this->setProperty('bankCode', $bankCode, 'string');
    }

    /**
     * @return string Bank code (deprecated in DE since SEPA)
     */
    public function getBankCode()
    {
        return $this->bankCode;
    }

    /**
     * @param string $bankName Bank name
     * @return \jtl\Connector\Model\CustomerOrderPaymentInfo
     */
    public function setBankName($bankName)
    {
        return $this->setProperty('bankName', $bankName, 'string');
    }

    /**
     * @return string Bank name
     */
    public function getBankName()
    {
        return $this->bankName;
    }

    /**
     * @param string $bic Bank Identifier Code (BIC)
     * @return \jtl\Connector\Model\CustomerOrderPaymentInfo
     */
    public function setBic($bic)
    {
        return $this->setProperty('bic', $bic, 'string');
    }

    /**
     * @return string Bank Identifier Code (BIC)
     */
    public function getBic()
    {
        return $this->bic;
    }

    /**
     * @param string $creditCardExpiration Credit card expiration date
     * @return \jtl\Connector\Model\CustomerOrderPaymentInfo
     */
    public function setCreditCardExpiration($creditCardExpiration)
    {
        return $this->setProperty('creditCardExpiration', $creditCardExpiration, 'string');
    }

    /**
     * @return string Credit card expiration date
     */
    public function getCreditCardExpiration()
    {
        return $this->creditCardExpiration;
    }

    /**
     * @param string $creditCardHolder 
     * @return \jtl\Connector\Model\CustomerOrderPaymentInfo
     */
    public function setCreditCardHolder($creditCardHolder)
    {
        return $this->setProperty('creditCardHolder', $creditCardHolder, 'string');
    }

    /**
     * @return string 
     */
    public function getCreditCardHolder()
    {
        return $this->creditCardHolder;
    }

    /**
     * @param string $creditCardNumber Credit card number
     * @return \jtl\Connector\Model\CustomerOrderPaymentInfo
     */
    public function setCreditCardNumber($creditCardNumber)
    {
        return $this->setProperty('creditCardNumber', $creditCardNumber, 'string');
    }

    /**
     * @return string Credit card number
     */
    public function getCreditCardNumber()
    {
        return $this->creditCardNumber;
    }

    /**
     * @param string $creditCardType Credit card type (e.g. "visa")
     * @return \jtl\Connector\Model\CustomerOrderPaymentInfo
     */
    public function setCreditCardType($creditCardType)
    {
        return $this->setProperty('creditCardType', $creditCardType, 'string');
    }

    /**
     * @return string Credit card type (e.g. "visa")
     */
    public function getCreditCardType()
    {
        return $this->creditCardType;
    }

    /**
     * @param string $creditCardVerificationNumber Credit card verification number
     * @return \jtl\Connector\Model\CustomerOrderPaymentInfo
     */
    public function setCreditCardVerificationNumber($creditCardVerificationNumber)
    {
        return $this->setProperty('creditCardVerificationNumber', $creditCardVerificationNumber, 'string');
    }

    /**
     * @return string Credit card verification number
     */
    public function getCreditCardVerificationNumber()
    {
        return $this->creditCardVerificationNumber;
    }

    /**
     * @param string $iban International Bank Account Number (IBAN)
     * @return \jtl\Connector\Model\CustomerOrderPaymentInfo
     */
    public function setIban($iban)
    {
        return $this->setProperty('iban', $iban, 'string');
    }

    /**
     * @return string International Bank Account Number (IBAN)
     */
    public function getIban()
    {
        return $this->iban;
    }
}
