<?php


namespace Agtech\Paymentoptions\Test\Unit\Controller\Webhook;


use Agtech\Paymentoptions\Controller\Webhook\Index;
use Agtech\Paymentoptions\Test\TestCase;

class IndexTest extends TestCase
{
    public function testExecute_wrongSignature()
    {
        $request = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)->disableOriginalConstructor()->getMock();
        $request->expects($this->any())
            ->method('getParam')
            ->with('redirect_flow_id')
            ->willReturn('RE123');
        $context = $this->getContextMock($request);
        $controller = new Index($this->getScopeConfigInterfaceMock(), $this->getLoggerInterfaceMock(), $context);
        $response = $controller->execute();
        $this->assertEquals(498, $response->getStatusCode());
    }
}