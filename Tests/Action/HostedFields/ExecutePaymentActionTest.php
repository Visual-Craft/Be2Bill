<?php

namespace Payum\Be2Bill\Tests\Action\HostedFields;

use Payum\Be2Bill\Action\HostedFields\ExecutePaymentAction;
use Payum\Be2Bill\Api;
use Payum\Be2Bill\Request\Api\ExecutePayment;
use Payum\Core\ApiAwareInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Tests\GenericActionTest;

class ExecutePaymentActionTest extends GenericActionTest
{
    protected $actionClass = ExecutePaymentAction::class;

    protected $requestClass = ExecutePayment::class;

    public function provideSupportedRequests()
    {
        return array(
            array(new $this->requestClass(new \ArrayObject(), '', '')),
        );
    }

    public function provideNotSupportedRequests()
    {
        return array(
            array('foo'),
            array(array('foo')),
            array(new \stdClass()),
            array($this->getMockForAbstractClass('Payum\Core\Request\Generic', array(array()))),
        );
    }

    /**
     * @test
     */
    public function shouldImplementGatewayAwareInterface()
    {
        $rc = new \ReflectionClass(ExecutePaymentAction::class);

        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

    /**
     * @test
     */
    public function shouldImplementApiAwareInterface()
    {
        $rc = new \ReflectionClass(ExecutePaymentAction::class);

        $this->assertTrue($rc->implementsInterface(ApiAwareInterface::class));
    }

    /**
    * @test
    */
    public function shouldAllowSetApi()
    {
        $expectedApi = $this->createApiMock();

        $action = new ExecutePaymentAction();
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
        $action = new ExecutePaymentAction();

        $action->setApi(new \stdClass());
    }

    /**
     * @test
     */
    public function shouldDoNothingIfExecCodeNotEqualSuccessful()
    {
        $request = new ExecutePayment(new \ArrayObject([
            'HFTOKEN' => 'HFTOKEN',
        ]), 'cartType',  'execCode');


        $api = $this->createApiMock();
        $api
            ->expects($this->never())
            ->method('hostedFieldsPayment')
        ;
        $getaway = $this->createGatewayMock();
        $getaway
            ->expects($this->never())
            ->method('execute')
        ;
        $action = new ExecutePaymentAction();
        $action->setApi($api);
        $action->setGateway($getaway);

        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldUpdateModelIfPaymentSuccess()
    {
        $request = new ExecutePayment([
            'HFTOKEN' => 'HFTOKEN',
            'CLIENTUSERAGENT' => 'CLIENTUSERAGENT',
            'CLIENTIP' => 'CLIENTIP',
        ], 'cardType',  Api::EXECCODE_SUCCESSFUL);

        $result = new \StdClass();
        $result->EXECCODE = null;
        $result->FOO = 'BAR';

        $api = $this->createApiMock();
        $api
            ->expects($this->once())
            ->method('hostedFieldsPayment')
            ->with($this->equalTo([
                    'HFTOKEN' => 'HFTOKEN',
                    'CLIENTUSERAGENT' => 'CLIENTUSERAGENT',
                    'CLIENTIP' => 'CLIENTIP',
                ]), $this->equalTo('cardType')
            )
            ->willReturn($result)
        ;
        $getaway = $this->createGatewayMock();

        $action = new ExecutePaymentAction();
        $action->setApi($api);
        $action->setGateway($getaway);

        $action->execute($request);

        $model = iterator_to_array($request->getModel());

        $this->assertSame([
            'HFTOKEN' => 'HFTOKEN',
            'CLIENTUSERAGENT' => 'CLIENTUSERAGENT',
            'CLIENTIP' => 'CLIENTIP',
            'EXECCODE' => null,
            'FOO' => 'BAR',
        ], $model);
    }

    /**
     * @test
     */
    public function shouldResponseIfExeccode3dSecureRequired()
    {
        $request = new ExecutePayment([
            'HFTOKEN' => 'HFTOKEN',
            'CLIENTUSERAGENT' => 'CLIENTUSERAGENT',
            'CLIENTIP' => 'CLIENTIP',
        ], 'cardType',  Api::EXECCODE_SUCCESSFUL);

        $result = new \StdClass();
        $result->EXECCODE = Api::EXECCODE_3DSECURE_IDENTIFICATION_REQUIRED;
        $result->{'3DSECUREHTML'} = base64_encode('<html>3DSECUREHTML</html>');

        $api = $this->createApiMock();
        $api
            ->expects($this->once())
            ->method('hostedFieldsPayment')
            ->willReturn($result)
        ;
        $getaway = $this->createGatewayMock();

        $action = new ExecutePaymentAction();
        $action->setApi($api);
        $action->setGateway($getaway);

        try {
            $action->execute($request);
        } catch (HttpResponse $response) {
            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame('<html>3DSECUREHTML</html>', $response->getContent());

            return;
        }

        $this->fail('The exception is expected');
    }
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Api
     */
    protected function createApiMock()
    {
        return $this->createMock(Api::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->createMock(GatewayInterface::class);
    }
}
