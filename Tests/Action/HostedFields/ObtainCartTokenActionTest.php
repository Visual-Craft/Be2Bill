<?php

namespace Payum\Be2Bill\Tests\Action\HostedFields;

use Payum\Be2Bill\Action\HostedFields\ObtainCartTokenAction;
use Payum\Be2Bill\Api;
use Payum\Be2Bill\Request\Api\ExecutePayment;
use Payum\Be2Bill\Request\Api\ObtainCartToken;
use Payum\Core\ApiAwareInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Tests\GenericActionTest;

class ObtainCartTokenActionTest extends GenericActionTest
{
    protected $actionClass = ObtainCartTokenAction::class;

    protected $requestClass = ObtainCartToken::class;

    protected function setUp(): void
    {
        $this->action = new ObtainCartTokenAction();
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
        $rc = new \ReflectionClass(ObtainCartTokenAction::class);

        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

    /**
     * @test
     */
    public function shouldImplementApiAwareInterface()
    {
        $rc = new \ReflectionClass(ObtainCartTokenAction::class);

        $this->assertTrue($rc->implementsInterface(ApiAwareInterface::class));
    }

    /**
     * @test
     */
    public function shouldAllowSetApi()
    {
        $expectedApi = $this->createApiMock();

        $action = new ObtainCartTokenAction();
        $action->setApi($expectedApi);

        $this->assertAttributeSame($expectedApi, 'api', $action);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\UnsupportedApiException
     */
    public function throwIfUnsupportedApiGiven()
    {
        $action = new ObtainCartTokenAction();

        $action->setApi(new \stdClass());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfHFTokenSet()
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute');

        $api = $this->createApiMock();

        $request = new ObtainCartToken(new \ArrayObject(['HFTOKEN' => 'HFTOKEN']));
        $action = new ObtainCartTokenAction();
        $action->setGateway($gatewayMock);
        $action->setApi($api);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The token has already been set.');
        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldExecuteGatewayExecutePaymentIfRequestMethodPostAndSetAllRequiredRequestParameters()
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
                        'hfToken' => 'hfToken',
                        'cardfullname' => 'cardfullname',
                        'brand' => 'brand',
                        'cardType' => 'cardType',
                        'execCode' => 'execCode',
                    ];
                }
            )
        ;

        $gatewayMock
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->isInstanceOf(ExecutePayment::class))
            ->willReturnCallback(function (ExecutePayment $request) {
                $this->assertSame('cardType', $request->getCardType());
                $this->assertSame('execCode', $request->getExecCode());
                $model = iterator_to_array($request->getModel());

                $this->assertSame([
                    'AMOUNT' => 100,
                    'HFTOKEN' => 'hfToken',
                    'FOO' => 'BAR',
                    'CARDFULLNAME' => 'cardfullname',
                    'SELECTEDBRAND' => 'brand',
                ], $model);

            })
        ;

        $apiStub = $this->createApiMock();
        $apiStub
            ->method('getIsForce3dSecure')
            ->willReturn(false)
        ;

        $action = new ObtainCartTokenAction();
        $action->setGateway($gatewayMock);
        $action->setApi($apiStub);

        $action->execute(new ObtainCartToken(new \ArrayObject([
            'AMOUNT' => 100,
            'HFTOKEN' => null,
            'FOO' => 'BAR',
        ])));
    }


    /**
     * @test
     */
    public function shouldExecuteGatewayExecutePaymentIfRequestMethodPostAndSetAllRequiredRequestParametersAndIsForce3DSecure()
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
                        'hfToken' => 'hfToken',
                        'cardfullname' => 'cardfullname',
                        'brand' => 'brand',
                        'cardType' => 'cardType',
                        'execCode' => 'execCode',
                    ];
                }
            )
        ;

        $gatewayMock
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->isInstanceOf(ExecutePayment::class))
            ->willReturnCallback(function (ExecutePayment $request) {
                $this->assertSame('cardType', $request->getCardType());
                $this->assertSame('execCode', $request->getExecCode());
                $model = iterator_to_array($request->getModel());

                $this->assertSame([
                    'AMOUNT' => 100,
                    'HFTOKEN' => 'hfToken',
                    'FOO' => 'BAR',
                    'CARDFULLNAME' => 'cardfullname',
                    'SELECTEDBRAND' => 'brand',
                    '3DSECUREDISPLAYMODE' => 'main',
                    '3DSECURE' => true,
                ], $model);

            })
        ;

        $apiStub = $this->createApiMock();
        $apiStub
            ->method('getIsForce3dSecure')
            ->willReturn(true)
        ;

        $action = new ObtainCartTokenAction();
        $action->setGateway($gatewayMock);
        $action->setApi($apiStub);

        $action->execute(new ObtainCartToken(new \ArrayObject([
            'AMOUNT' => 100,
            'HFTOKEN' => null,
            'FOO' => 'BAR',
        ])));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Api
     */
    protected function createApiMock()
    {
        return $this->createMock(Api::class);
    }
}
