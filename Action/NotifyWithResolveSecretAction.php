<?php

namespace Payum\Be2Bill\Action;

use Payum\Be2Bill\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;

/**
 * @property Api $api
 */
class NotifyWithResolveSecretAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
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
     * @param $request Notify
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (!$this->api->verifyHashWithIdentifier($httpRequest->query)) {
            throw new HttpResponse('The notification is invalid. Code 1', 400);
        }

        if ($details['AMOUNT'] !== $httpRequest->query['AMOUNT']) {
            throw new HttpResponse('The notification is invalid. Code 2', 400);
        }

        if (!$httpRequest->query['ORDERID'] || !$httpRequest->query['TRANSACTIONID'] || !$httpRequest->query['EXECCODE']) {
            throw new HttpResponse('The notification is invalid. Code 3', 400);
        }

        $details->replace($httpRequest->query);

        throw new HttpResponse('OK', 200);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
