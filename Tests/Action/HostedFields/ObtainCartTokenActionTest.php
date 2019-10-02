<?php

namespace Payum\Be2Bill\Tests\Action\HostedFields;

use Payum\Be2Bill\Action\HostedFields\ObtainCartTokenAction;
use Payum\Be2Bill\Api;
use Payum\Be2Bill\Request\Api\ExecutePayment;
use Payum\Be2Bill\Request\Api\ObtainCartToken;
use Payum\Core\ApiAwareInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\RenderTemplate;
use Payum\Core\Tests\GenericActionTest;

class ObtainCartTokenActionTest extends GenericActionTest
{
    protected $actionClass = ObtainCartTokenAction::class;

    protected $requestClass = ObtainCartToken::class;

    protected function setUp()
    {
        $this->action = new ObtainCartTokenAction('template');
    }

    public function couldBeConstructedWithoutAnyArguments()
    {
        //overwrite
    }

    public function provideSupportedRequests()
    {
        return array(
            array(new $this->requestClass(new \ArrayObject())),
        );
    }

    public function provideNotSupportedRequests()
    {
        return array(
            array('foo'),
            array(array('foo')),
            array(new \stdClass()),
            array(new $this->requestClass('foo')),
            array(new $this->requestClass(new \stdClass())),
            array($this->getMockForAbstractClass(Generic::class, array(array()))),
        );
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

        $action = new ObtainCartTokenAction('template');
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
        $action = new ObtainCartTokenAction('template');

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
        $action = new ObtainCartTokenAction('template');
        $action->setGateway($gatewayMock);
        $action->setApi($api);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The token has already been set.');
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

        $apiStub = $this->createApiMock();
        $apiStub
            ->method('getObtainJsTokenCredentials')
            ->willReturn([
                'id' => 'id',
                'value' => 'value'
            ])
        ;
        $apiStub
            ->method('getHostedFieldsJsLibUrl')
            ->willReturn('hostedFieldsJsLibUrl')
        ;
        $apiStub
            ->method('getBrandDetectorJsLibUrl')
            ->willReturn('brandDetectorJsLibUrl')
        ;

        $gatewayMock
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->isInstanceOf(RenderTemplate::class))
            ->willReturnCallback(
                function (RenderTemplate $renderTemplate) {
                    $renderTemplate->setResult('content');
                    $this->assertSame('template', $renderTemplate->getTemplateName());
                    $parameters = $renderTemplate->getParameters();
                    $this->assertArrayHasKey('actionUrl', $parameters);
                    $this->assertNull($parameters['token']);
                    $this->assertArrayHasKey('token', $parameters);
                    $this->assertNull($parameters['token']);
                    $this->assertArrayHasKey('amount', $parameters);
                    $this->assertSame(1, $parameters['amount']);
                    $this->assertArrayHasKey('hostedFieldsJsLibUrl', $parameters);
                    $this->assertSame('hostedFieldsJsLibUrl', $parameters['hostedFieldsJsLibUrl']);

                    $this->assertArrayHasKey('brandDetectorJsLibUrl', $parameters);
                    $this->assertSame('brandDetectorJsLibUrl', $parameters['brandDetectorJsLibUrl']);

                    $this->assertArrayHasKey('credentials', $parameters);
                    $this->assertSame([
                        'id' => 'id',
                        'value' => 'value'
                    ], $parameters['credentials']);
                }
            )
        ;

        $action = new ObtainCartTokenAction('template');
        $action->setGateway($gatewayMock);
        $action->setApi($apiStub);

        try {
            $action->execute(new ObtainCartToken(new \ArrayObject([
                'AMOUNT' => 100,
                'HFTOKEN' => null,
            ])));
        } catch (HttpResponse $reply) {
            $this->assertSame(200, $reply->getStatusCode());
            $this->assertSame('content', $reply->getContent());

            return;
        }

        $this->fail('The exception is expected');
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

        $action = new ObtainCartTokenAction('template');
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

        $action = new ObtainCartTokenAction('template');
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
