<?php

namespace Payum\Be2Bill\Tests\Action\SDD;

use Payum\Be2Bill\Action\SDD\CaptureAction;
use Payum\Be2Bill\Request\Api\RecurringPayment;
use Payum\Be2Bill\Request\RenderObtainCardToken;
use Payum\Be2Bill\Request\SDD\ObtainSDDData;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Tests\GenericActionTest;

class CaptureActionTest extends GenericActionTest
{
    protected $actionClass = CaptureAction::class;

    protected $requestClass = Capture::class;

    /**
     * @test
     */
    public function shouldImplementGatewayAwareInterface()
    {
        $rc = new \ReflectionClass(CaptureAction::class);

        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

    /**
     * @test
     */
    public function shouldDoNothingIfExeccodeSet()
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute');

        $action = new CaptureAction();
        $action->setGateway($gatewayMock);

        $request = new Capture(['EXECCODE' => 1]);

        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldBeCallExecuteRecurringPayment()
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf('Payum\Core\Request\GetHttpRequest'))
            ->willReturnCallback(
                static function (GetHttpRequest $request) {
                    $request->method = 'POST';
                }
            )
        ;

        $gatewayMock
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->isInstanceOf(RecurringPayment::class));

        $action = new CaptureAction();
        $action->setGateway($gatewayMock);

        $request = new Capture([
            'AMOUNT' => 10,
            'ALIAS' => 'alias',
        ]);

        //guard
        $this->assertTrue($action->supports($request));

        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldBeCallExecuteObtainSDDDate()
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf('Payum\Core\Request\GetHttpRequest'))
            ->willReturnCallback(
                static function (GetHttpRequest $request) {
                    $request->method = 'POST';
                }
            )
        ;

        $gatewayMock
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->isInstanceOf(ObtainSDDData::class));

        $action = new CaptureAction();
        $action->setGateway($gatewayMock);

        $request = new Capture([
            'AMOUNT' => 10,
        ]);

        //guard
        $this->assertTrue($action->supports($request));

        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldReturnRenderTemplateResponseIfMethodNotPost()
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf('Payum\Core\Request\GetHttpRequest'))
            ->willReturnCallback(
                static function (GetHttpRequest $request) {
                    $request->method = 'GET';
                }
            )
        ;

        $gatewayMock
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->isInstanceOf(RenderObtainCardToken::class))
        ;

        $action = new CaptureAction();
        $action->setGateway($gatewayMock);

        $request = new Capture([
            'AMOUNT' => 10,
        ]);

        //guard
        $this->assertTrue($action->supports($request));

        $action->execute($request);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->createMock(GatewayInterface::class);
    }
}
