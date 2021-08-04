<?php

namespace Payum\Be2Bill\Model;

use Payum\Core\Model\PaymentInterface as PayumPaymentInterface;

interface PaymentInterface extends PayumPaymentInterface
{
    /**
     * @return string
     */
    public function getBillingCity();

    /**
     * @return string
     */
    public function getBillingCountry();

    /**
     * @return string
     */
    public function getBillingAddress();

    /**
     * @return string
     */
    public function getBillingPostalCode();

    /**
     * @return string
     */
    public function getShipToCity();

    /**
     * @return string
     */
    public function getShipToCountry();

    /**
     * @return string
     */
    public function getShipToAddress();

    /**
     * @return string
     */
    public function getShipToPostalCode();

    /**
     * @return string
     */
    public function getShipToAddressType();

    /**
     * @return \DateTime
     */
    public function getPasswordChangeDate();

    /**
     * @return int
     */
    public function getLast6MonthsPurchaseCount();

    /**
     * @return int
     */
    public function getLast24HoursTransactionsCount();

    /**
     * @return string
     */
    public function getSuspiciousAccountActivity();

    /**
     * @return \DateTime
     */
    public function getShipToAddressDate();

    /**
     * @return string
     */
    public function getMobilePhone();

    /**
     * @return string
     */
    public function getReorderingItem();

    /**
     * @return string
     */
    public function getClientAuthMethod();

    /**
     * @return string
     */
    public function getDeliveryEmail();

    /**
     * @return string|null
     */
    public function getAlias();

    /**
     * @return string|null
     */
    public function getAliasMode();

    /**
     * @return bool|null
     */
    public function getCreateAlias();
}
