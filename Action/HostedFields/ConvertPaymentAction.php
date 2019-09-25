<?php

namespace Payum\Be2Bill\Action\HostedFields;

use Payum\Be2Bill\Model\PaymentInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\PaymentInterface as PayumPaymentInterface;
use Payum\Core\Request\Convert;
use Payum\Be2Bill\Action\ConvertPaymentAction as CommonConvertPaymentAction;

class ConvertPaymentAction extends CommonConvertPaymentAction
{
    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        parent::execute($request);

        /** @var PayumPaymentInterface $payment */
        $payment = $request->getSource();
        $details = ArrayObject::ensureArrayObject($request->getResult());

        if ($payment instanceof PaymentInterface) {
            if ($payment->getShipToAddressType()) {
                $details['SHIPTOADDRESSTYPE'] = $payment->getShipToAddressType();
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
