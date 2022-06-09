<?php


namespace Agtech\Paymentoptions\Test\Unit\Controller\Redirect;


use Agtech\Paymentoptions\Controller\Redirect\Start;
use Magento\Framework\HTTP\PhpEnvironment\Response;
use Agtech\Paymentoptions\Test\TestCase;
use Magento\Framework\Locale\Resolver as LocaleResolver;

class StartTest extends TestCase
{
    public function testExecute()
    {
        $localeResolver = $this->getMockBuilder(LocaleResolver::class)->disableOriginalConstructor()->getMock();

        $controller = new Start(
            $this->getScopeConfigInterfaceMock(),
            $this->getLoggerInterfaceMock(),
            $this->getCheckoutSessionMockWithQuote(),
            $localeResolver,
            $this->getContextMock()
        );

        $expectedRedirectUrl = 'https://pay.Paymentoptions.com/flow/RE123';
        $PaymentoptionsClient = $this->getPaymentoptionsClientMock();
        $redirectFlow = new \PaymentoptionsPro\Resources\RedirectFlow([
            'redirect_url' => $expectedRedirectUrl,
        ]);
        $redirectFlowService = $this->getMockBuilder(\PaymentoptionsPro\Services\RedirectFlowsService::class)->disableOriginalConstructor()->setMethods(['create'])->getMock();
        $redirectFlowService->expects($this->any())
            ->method('create')
            ->willReturn($redirectFlow);
        $PaymentoptionsClient->expects($this->any())
            ->method('redirectFlows')
            ->willReturn($redirectFlowService);
        $controller->setPaymentoptionsClient($PaymentoptionsClient);

        /** @var Response $response */
        $response = $controller->execute();
        $this->assertEquals('Location: ' . $expectedRedirectUrl, $response->getHeader('Location')->toString());
    }


}