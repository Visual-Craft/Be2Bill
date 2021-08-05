<?php

namespace Payum\Be2Bill\Tests\Action\SDD;

use Payum\Be2Bill\Action\SDD\ObtainSDDAction;
use Payum\Be2Bill\Request\SDD\ExecutePayment;
use Payum\Be2Bill\Request\SDD\ObtainSDDData;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Tests\GenericActionTest;

class ObtainSDDActionTest extends GenericActionTest
{
    protected $actionClass = ObtainSDDAction::class;

    protected $requestClass = ObtainSDDData::class;

    protected function setUp(): void
    {
        $this->action = new ObtainSDDAction();
    }

    public function couldBeConstructedWithoutAnyArguments()
    {
        //overwrite
    }

    public function provideSupportedRequests(): \Iterator
    {
        yield array(new $this->requestClass(new \ArrayObject()));
    }

    public function provideNotSupportedRequests(): \Iterator
    {
        yield array('foo');
        yield array(array('foo'));
        yield array(new \stdClass());
        yield array(new $this->requestClass('foo'));
        yield array(new $this->requestClass(new \stdClass()));
        yield array($this->getMockForAbstractClass(Generic::class, array(array())));
    }

    /**
     * @test
     */
    public function shouldImplementGatewayAwareInterface()
    {
        $rc = new \ReflectionClass(ObtainSDDAction::class);

        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

    /**
     * @test
     */
    public function shouldDoNothingIfExeccodeG()
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute');

        $action = new ObtainSDDAction();
        $action->setGateway($gatewayMock);

        $request = new ObtainSDDData(new \Payum\Core\Bridge\Spl\ArrayObject(['EXECCODE' => 1]));

        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldExecGatewayExecutePaymentIfRequestMethodPostAndSetAllRequiredRequestParameters()
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf('Payum\Core\Request\GetHttpRequest'))
            ->willReturnCallback(
                static function (GetHttpRequest $request) {
                    $request->method = 'POST';
                    $request->request = [
                        'BILLINGFIRSTNAME' => 'firstName',
                        'BILLINGLASTNAME' => 'lastName',
                        'BILLINGADDRESS' => 'address',
                        'BILLINGCITY' => 'city',
                        'BILLINGCOUNTRY' => 'country',
                        'BILLINGMOBILEPHONE' => 'mobilePhone',
                        'BILLINGPOSTALCODE' => 'postalCode',
                        'CLIENTGENDER' => 'gender',
                    ];
                }
            )
        ;

        $gatewayMock
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->isInstanceOf(ExecutePayment::class))
        ;

        $action = new ObtainSDDAction();
        $action->setGateway($gatewayMock);

        $action->execute(new ObtainSDDData(new \Payum\Core\Bridge\Spl\ArrayObject([
            'AMOUNT' => 1.0
        ])));

    }
}
