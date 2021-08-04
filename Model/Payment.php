<?php
namespace Payum\Be2Bill\Model;

use Payum\Core\Model\Payment as PayumPayment;

class Payment extends PayumPayment implements PaymentInterface
{
    /**
     * @var string
     */
    protected $billingCity;

    /**
     * @var string
     */
    protected $billingCountry;

    /**
     * @var string
     */
    protected $billingAddress;

    /**
     * @var string
     */
    protected $billingPostalCode;

    /**
     * @var string
     */
    protected $shipToCity;

    /**
     * @var string
     */
    protected $shipToCountry;

    /**
     * @var string
     */
    protected $shipToAddress;

    /**
     * @var string
     */
    protected $shipToPostalCode;

    /**
     * @var string
     */
    protected $shipToAddressType;

    /**
     * @var \DateTime
     */
    protected $passwordChangeDate;

    /**
     * @var int
     */
    protected $last6MonthsPurchaseCount;

    /**
     * @var int
     */
    protected $last24HoursTransactionsCount;

    /**
     * @var int
     */
    protected $suspiciousAccountActivity;

    /**
     * @var \DateTime
     */
    protected $shipToAddressDate;

    /**
     * @var string
     */
    protected $mobilePhone;

    /**
     * @var string
     */
    protected $reorderingItem;

    /**
     * @var string
     */
    protected $clientAuthMethod;

    /**
     * @var string
     */
    protected $deliveryEmail;

    /**
     * @return string
     */
    public function getBillingCity()
    {
        return $this->billingCity;
    }

    /**
     * @return string
     */
    public function getBillingCountry()
    {
        return $this->billingCountry;
    }

    /**
     * @return string
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @return string
     */
    public function getBillingPostalCode()
    {
        return $this->billingPostalCode;
    }

    /**
     * @return string
     */
    public function getShipToCity()
    {
        return $this->shipToCity;
    }

    /**
     * @return string
     */
    public function getShipToCountry()
    {
        return $this->shipToCountry;
    }

    /**
     * @return string
     */
    public function getShipToAddress()
    {
        return $this->shipToAddress;
    }

    /**
     * @return string
     */
    public function getShipToPostalCode()
    {
        return $this->shipToPostalCode;
    }

    /**
     * @return string
     */
    public function getShipToAddressType()
    {
        return $this->shipToAddressType;
    }

    /**
     * @return \DateTime
     */
    public function getPasswordChangeDate()
    {
        return $this->passwordChangeDate;
    }

    /**
     * @return int
     */
    public function getLast6MonthsPurchaseCount()
    {
        return $this->last6MonthsPurchaseCount;
    }

    /**
     * @return int
     */
    public function getLast24HoursTransactionsCount()
    {
        return $this->last24HoursTransactionsCount;
    }

    /**
     * @return string
     */
    public function getSuspiciousAccountActivity()
    {
        return $this->suspiciousAccountActivity;
    }

    /**
     * @return \DateTime
     */
    public function getShipToAddressDate()
    {
        return $this->shipToAddressDate;
    }

    /**
     * @return string
     */
    public function getMobilePhone()
    {
        return $this->mobilePhone;
    }

    /**
     * @return string
     */
    public function getReorderingItem()
    {
        return $this->reorderingItem;
    }

    /**
     * @return string
     */
    public function getClientAuthMethod()
    {
        return $this->clientAuthMethod;
    }

    /**
     * @return string
     */
    public function getDeliveryEmail()
    {
        return $this->deliveryEmail;
    }
}
