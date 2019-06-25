<?php

namespace Payum\Be2Bill\Action\SDD;

use Payum\Be2Bill\Request\SDD\ObtainSDDData;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;

class CaptureAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

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
        if ($model['EXECCODE']) {
            return;
        }

        $obtainToken = new ObtainSDDData($request->getToken());
        $obtainToken->setModel($model);
        $this->gateway->execute($obtainToken);
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
