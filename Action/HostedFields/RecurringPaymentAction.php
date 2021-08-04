<?php

namespace Payum\Be2Bill\Action\HostedFields;

use Payum\Be2Bill\Request\Api\RecurringPayment;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Be2Bill\Api;

class RecurringPaymentAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
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
        /** @var RecurringPayment $request */
        $model = new ArrayObject($request->getModel());

        $model->validateNotEmpty(['ALIAS', 'ALIASMODE', 'CARDFULLNAME', 'CARDTYPE']);

        if ($model['HFTOKEN']) {
            throw new \LogicException('The token has already been set. Misusing of RecurringPaymentAction');
        }

        if (!$model['CLIENTUSERAGENT']) {
            $this->gateway->execute($httpRequest = new GetHttpRequest());
            $model['CLIENTUSERAGENT'] = $httpRequest->userAgent;
        }

        if (!$model['CLIENTIP']) {
            $this->gateway->execute($httpRequest = new GetHttpRequest());
            $model['CLIENTIP'] = $httpRequest->clientIp;
        }

        /** @var Api $api */
        $api = $this->api;

        if ($api->getIsForce3dSecure()) {
            $model['3DSECUREDISPLAYMODE'] = 'main';
            $model['3DSECURE'] = true;
        }

        $result = $api->hostedFieldsPayment($model->toUnsafeArray(), $model['CARDTYPE']);

        if ($result->EXECCODE === Api::EXECCODE_3DSECURE_IDENTIFICATION_REQUIRED) {
            throw new HttpResponse(base64_decode($result->{'3DSECUREHTML'}));
        }

        $model->replace((array) $result);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof RecurringPayment
                &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
