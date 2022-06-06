<?php


namespace Agtech\Paymentoptions\Test\Unit\Controller\Redirect;

use Agtech\Paymentoptions\Controller\Redirect\Success;
use Agtech\Paymentoptions\Helper\OrderPlace;
use Agtech\Paymentoptions\Test\TestCase;


class SucessTest extends TestCase
{
    public function testExecute_completeRedirectFlowAndCreatePayment()
    {
        $request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)->getMock();
        $request->expects($this->any())
            ->method('getParam')
            ->with('redirect_flow_id')
            ->willReturn('RE123');
        $context = $this->getContextMock($request);

        $orderPlace = $this->getMockBuilder(OrderPlace::class)->disableOriginalConstructor()->getMock();

        $controller = new Success(
            $this->getScopeConfigInterfaceMock(),
            $this->getLoggerInterfaceMock(),
            $this->getCheckoutSessionMockWithQuote(),
            $context,
            $orderPlace
        );

        $PaymentoptionsClient = $this->getPaymentoptionsClientMock();
        $redirectFlowService = $this->getMockBuilder(\PaymentoptionsPro\Services\RedirectFlowsService::class)->disableOriginalConstructor()->setMethods(['complete'])->getMock();
        $paymentService = $this->getMockBuilder(\PaymentoptionsPro\Services\PaymentsService::class)->disableOriginalConstructor()->setMethods(['create'])->getMock();
        $paymentService->expects($this->once())
            ->method('create');
        $redirectFlow = new \PaymentoptionsPro\Resources\RedirectFlow([
            'redirect_url' => 'https://pay.Paymentoptions.com/flow/RE123',
            'links' => (object) [
                'mandate'   => 'mandateId'
            ]
        ]);
        $redirectFlowService->expects($this->once())
            ->method('complete')
            ->willReturn($redirectFlow);
        $PaymentoptionsClient->expects($this->any())
            ->method('redirectFlows')
            ->willReturn($redirectFlowService);
        $PaymentoptionsClient->expects($this->any())
            ->method('payments')
            ->willReturn($paymentService);
        $controller->setPaymentoptionsClient($PaymentoptionsClient);

        $controller->execute();
    }
}