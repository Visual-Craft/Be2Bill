<?php

namespace Payum\Be2Bill\Tests\Action\HostedFields;

use Payum\Be2Bill\Action\HostedFields\CaptureAction;
use Payum\Be2Bill\Request\Api\ObtainCartToken;
use Payum\Be2Bill\Request\Api\RecurringPayment;
use Payum\Be2Bill\Request\RenderObtainCardToken;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Model\Token;
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
     * @dataProvider paidModelProvider
     * @param array $model
     */
    public function shouldDoNothingIfAlreadyPerformedObtainTokenRequest(array $model)
    {
        $request = new Capture($model);
        $gateway = $this->createGatewayMock();
        $gateway
            ->expects($this->never())
            ->method('execute')
        ;

        $action = new CaptureAction();
        $action->setGateway($gateway);

        //guard
        $this->assertTrue($action->supports($request));

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

        $request = new Capture(new Token());
        $request->setModel([
            'AMOUNT' => 10,
            'status' => null,
            'HFTOKEN' => null,
            'ALIAS' => 'alias',
        ]);

        //guard
        $this->assertTrue($action->supports($request));

        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldBeCallExecuteObtainCartToken()
    {
        $token = new Token();
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
            ->with($this->isInstanceOf(ObtainCartToken::class))
            ->willReturnCallback(function (ObtainCartToken $request) use ($token) {
                $model = iterator_to_array($request->getModel());
                $this->assertSame([
                    'AMOUNT' => 10,
                    'status' => null,
                    'HFTOKEN' => null,
                ], $model);
                $this->assertSame($token, $request->getToken());

            })
        ;

        $action = new CaptureAction();
        $action->setGateway($gatewayMock);

        $request = new Capture($token);
        $request->setModel([
            'AMOUNT' => 10,
            'status' => null,
            'HFTOKEN' => null,
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
            ->with($this->isInstanceOf(RenderObtainCardToken::class));

        $action = new CaptureAction();
        $action->setGateway($gatewayMock);

        $request = new Capture(new Token());
        $request->setModel([
            'AMOUNT' => 10,
            'status' => null,
            'HFTOKEN' => null,
        ]);

        //guard
        $this->assertTrue($action->supports($request));

        $action->execute($request);
    }

    /**
     * @return \Generator
     */
    public function paidModelProvider()
    {
        yield [['status' => null, 'HFTOKEN' => 'token']];
        yield [['status' => 'status', 'HFTOKEN' => null]];
        yield [['status' => 'status', 'HFTOKEN' => 'token']];
    }
}
