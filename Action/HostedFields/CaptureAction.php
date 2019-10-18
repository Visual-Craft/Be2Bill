<?php

namespace Payum\Be2Bill\Action\HostedFields;

use Payum\Be2Bill\Request\Api\RecurringPayment;
use Payum\Be2Bill\Request\RenderObtainCardToken;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Be2Bill\Api;
use Payum\Be2Bill\Request\Api\ObtainCartToken;
use Payum\Core\Request\GetHttpRequest;

class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = new ArrayObject($request->getModel());

        // Already processed
        if ($model['status'] || $model['HFTOKEN']) {
            return;
        }

        $getHttpRequest = new GetHttpRequest();
        $this->gateway->execute($getHttpRequest);

        if ($getHttpRequest->method === 'POST') {

            if ($model['ALIAS']) {
                $paymentRequest = new RecurringPayment($request->getToken());
                $paymentRequest->setModel($model);
                $this->gateway->execute($paymentRequest);

                return;
            }

            // Should obtain cart token
            $obtainToken = new ObtainCartToken($request->getToken());
            $obtainToken->setModel($model);
            $this->gateway->execute($obtainToken);

            return;
        }

        $renderObtainCardToken = new RenderObtainCardToken($request->getToken());
        $renderObtainCardToken->setModel($model);
        $this->gateway->execute($renderObtainCardToken);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
