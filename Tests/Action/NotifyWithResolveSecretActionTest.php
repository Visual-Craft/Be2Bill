<?php

namespace Action;

use Payum\Be2Bill\Action\NotifyWithResolveSecretAction;
use Payum\Be2Bill\Api;
use Payum\Core\ApiAwareInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Payum\Core\Tests\GenericActionTest;

class NotifyWithResolveSecretActionTest extends GenericActionTest
{
    protected $actionClass = NotifyWithResolveSecretAction::class;

    protected $requestClass = Notify::class;

    /**
     * @test
     */
    public function shouldImplementGatewayAwareInterface()
    {
        $rc = new \ReflectionClass(NotifyWithResolveSecretAction::class);

        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

    /**
     * @test
     */
    public function shouldImplementApiAwareInterface()
    {
        $rc = new \ReflectionClass(NotifyWithResolveSecretAction::class);

        $this->assertTrue($rc->implementsInterface(ApiAwareInterface::class));
    }

    /**
     * @test
     */
    public function shouldAllowSetApi()
    {
        $expectedApi = $this->createApiMock();

        $action = new NotifyWithResolveSecretAction();
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
        $action = new NotifyWithResolveSecretAction();

        $action->setApi(new \stdClass());
    }


    /**
     * @test
     */
    public function throwIfQueryHashDoesNotMatchExpected()
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->will($this->returnCallback(function (GetHttpRequest $request) {
                $request->query = ['expected be2bill query'];
            }))
        ;

        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('verifyHashWithIdentifier')
            ->willReturn(false)
        ;

        $action = new NotifyWithResolveSecretAction();
        $action->setGateway($gatewayMock);
        $action->setApi($apiMock);

        try {
            $action->execute(new Notify([]));
        } catch (HttpResponse $reply) {
            $this->assertSame(400, $reply->getStatusCode());
            $this->assertSame('The notification is invalid. Code 1', $reply->getContent());

            return;
        }

        $this->fail('The exception is expected');
    }

    /**
     * @test
     */
    public function throwIfQueryAmountDoesNotMatchOneFromModel()
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->will($this->returnCallback(function (GetHttpRequest $request) {
                $request->query = ['AMOUNT' => 2.0];
            }))
        ;

        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('verifyHashWithIdentifier')
            ->willReturn(true)
        ;

        $action = new NotifyWithResolveSecretAction();
        $action->setGateway($gatewayMock);
        $action->setApi($apiMock);

        try {
            $action->execute(new Notify([
                'AMOUNT' => 1.0,
            ]));
        } catch (HttpResponse $reply) {
            $this->assertSame(400, $reply->getStatusCode());
            $this->assertSame('The notification is invalid. Code 2', $reply->getContent());

            return;
        }

        $this->fail('The exception is expected');
    }

    /**
     * @test
     * @dataProvider provideNotSupportedOrderIdTransactionIdAndExeccode
     * @param string|null $orderId
     * @param string|null $transactionId
     * @param string|null $execcode
     */
    public function throwIfQueryNotHasRequiredField($orderId, $transactionId, $execcode)
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->willReturnCallback(static function (GetHttpRequest $request) use ($orderId, $transactionId, $execcode) {
                $request->query = [
                    'AMOUNT' => 1.0,
                    'ORDERID' => $orderId,
                    'TRANSACTIONID' => $transactionId,
                    'EXECCODE' => $execcode,
                ];
            })
        ;

        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('verifyHashWithIdentifier')
            ->willReturn(true)
        ;

        $action = new NotifyWithResolveSecretAction();
        $action->setGateway($gatewayMock);
        $action->setApi($apiMock);

        try {
            $action->execute(new Notify([
                'AMOUNT' => 1.0
            ]));
        } catch (HttpResponse $reply) {
            $this->assertSame(400, $reply->getStatusCode());
            $this->assertSame('The notification is invalid. Code 3', $reply->getContent());

            return;
        }

        $this->fail('The exception is expected');
    }

    public function provideNotSupportedOrderIdTransactionIdAndExeccode()
    {
        yield ['orderId' => null, 'transactionId' => null, 'execcode' => null];
        yield ['orderId' => null, 'transactionId' => null, 'execcode' => '1'];
        yield ['orderId' => null, 'transactionId' => '1', 'execcode' => null];
        yield ['orderId' => '1', 'transactionId' => null, 'execcode' => null];
        yield ['orderId' => '1', 'transactionId' => '1', 'execcode' => null];
        yield ['orderId' => '1', 'transactionId' => null, 'execcode' => '1'];
        yield ['orderId' => null, 'transactionId' => '1', 'execcode' => '1'];
    }

    /**
     * @test
     */
    public function shouldUpdateModelIfNotificationValid()
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->will($this->returnCallback(function (GetHttpRequest $request) {
                $request->query = [
                    'AMOUNT' => 1.0,
                    'FOO' => 'FOO',
                    'BAR' => 'BAR',
                    'EXECCODE' => 'EXECCODE',
                    'ORDERID' => 'ORDERID',
                    'TRANSACTIONID' => 'TRANSACTIONID',
                ];
            }))
        ;

        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('verifyHashWithIdentifier')
            ->willReturn(true)
        ;

        $action = new NotifyWithResolveSecretAction();
        $action->setGateway($gatewayMock);
        $action->setApi($apiMock);

        $model = new \ArrayObject([
            'AMOUNT' => 1.0,
            'FOO' => 'FOOOLD',
        ]);

        try {
            $action->execute(new Notify($model));
        } catch (HttpResponse $reply) {
            $this->assertEquals([
                'AMOUNT' => 1.0,
                'FOO' => 'FOO',
                'BAR' => 'BAR',
                'EXECCODE' => 'EXECCODE',
                'ORDERID' => 'ORDERID',
                'TRANSACTIONID' => 'TRANSACTIONID',
            ], (array) $model);

            $this->assertSame(200, $reply->getStatusCode());
            $this->assertSame('OK', $reply->getContent());

            return;
        }

        $this->fail('The exception is expected');
    }
    /**
     * @return Api|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createApiMock()
    {
        return $this->createMock(Api::class);
    }
}
