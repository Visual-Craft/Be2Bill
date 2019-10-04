<?php
namespace Payum\Be2Bill\Action;

use Payum\Be2Bill\Model\PaymentInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\PaymentInterface as PayumPaymentInterface;
use Payum\Core\Request\Convert;

class ConvertPaymentAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PayumPaymentInterface $payment */
        $payment = $request->getSource();

        $details = ArrayObject::ensureArrayObject($payment->getDetails());
        $details['DESCRIPTION'] = $payment->getDescription();
        $details['AMOUNT'] = $payment->getTotalAmount();
        $details['CLIENTIDENT'] = $payment->getClientId();
        $details['CLIENTEMAIL'] = $payment->getClientEmail();
        $details['ORDERID'] = $payment->getNumber();

        if ($payment instanceof PaymentInterface) {
            if ($payment->getBillingCity()) {
                $details['BILLINGCITY'] = $payment->getBillingCity();
            }

            if ($payment->getBillingCountry()) {
                $details['BILLINGCOUNTRY'] = $payment->getBillingCountry();
            }

            if ($payment->getBillingAddress()) {
                $details['BILLINGADDRESS'] = $payment->getBillingAddress();
            }

            if ($payment->getBillingPostalCode()) {
                $details['BILLINGPOSTALCODE'] = $payment->getBillingPostalCode();
            }

            if ($payment->getShipToCity()) {
                $details['SHIPTOCITY'] = $payment->getShipToCity();
            }

            if ($payment->getShipToCountry()) {
                $details['SHIPTOCOUNTRY'] = $payment->getShipToCountry();
            }

            if ($payment->getShipToAddress()) {
                $details['SHIPTOADDRESS'] = $payment->getShipToAddress();
            }

            if ($payment->getShipToPostalCode()) {
                $details['SHIPTOPOSTALCODE'] = $payment->getShipToPostalCode();
            }

            if ($payment->getPasswordChangeDate()) {
                $details['PASSWORDCHANGEDATE'] = $payment->getPasswordChangeDate()->format('Y-m-d');
            }

            if ($payment->getLast6MonthsPurchaseCount()) {
                $details['LAST6MONTHSPURCHASECOUNT'] = $payment->getLast6MonthsPurchaseCount();
            }

            if ($payment->getLast24HoursTransactionsCount()) {
                $details['LAST24HOURSTRANSACTIONCOUNT'] = $payment->getLast24HoursTransactionsCount();
            }

            if ($payment->getSuspiciousAccountActivity()) {
                $details['SUSPICIOUSACCOUNTACTIVITY'] = $payment->getSuspiciousAccountActivity();
            }

            if ($payment->getShipToAddressDate()) {
                $details['SHIPTOADDRESSDATE'] = $payment->getShipToAddressDate()->format('Y-m-d');
            }

            if ($payment->getMobilePhone()) {
                $details['MOBILEPHONE'] = $payment->getMobilePhone();
            }

            if ($payment->getReorderingItem()) {
                $details['REORDERINGITEM'] = $payment->getReorderingItem();
            }

            if ($payment->getDeliveryEmail()) {
                $details['DELIVERYEMAIL'] = $payment->getDeliveryEmail();
            }

            if ($payment->getClientAuthMethod()) {
                $details['CLIENTAUTHMETHOD'] = $payment->getClientAuthMethod();
            }

            if ($payment->getAlias()) {
                $details['ALIAS'] = $payment->getAlias();
            }

            if ($payment->getCreateAlias() !== null) {
                $details['CREATEALIAS'] = $payment->getCreateAlias();
            }

            if ($payment->getAliasMode()) {
                $details['ALIASMODE'] = $payment->getAliasMode();
            }
        }

       $request->setResult((array) $details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array'
        ;
    }
}
