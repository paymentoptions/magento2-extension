<?php


namespace Agtech\Paymentoptions\Controller\Redirect;


use Magento\Payment\Gateway\Http\ClientInterface;
//use PaymentoptionsPro\Environment;
use Agtech\Paymentoptions\Controller\AbstractAction;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Model\Quote;


abstract class AbstractRedirectAction extends AbstractAction
{
    /** @var  null|Quote */
    protected $quote;
    /** @var  CheckoutSession */
    protected $checkoutSession;

    /** @var  ClientInterface */
    private $PaymentoptionsClient;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CheckoutSession $checkoutSession,
        Context $context)
    {
        $this->checkoutSession = $checkoutSession;
        parent::__construct($scopeConfig, $context);
    }

    /**
     * @return ClientInterface
     */
    protected function getPaymentoptionsClient()
    {
        if (!$this->PaymentoptionsClient) {
            $accessToken = $this->scopeConfig->getValue('payment/paymentoptions/access_token');
            $sandboxEnvironment = (bool)$this->scopeConfig->getValue('payment/paymentoptions/sandbox_environment');
            //$environment = $sandboxEnvironment ? Environment::SANDBOX : Environment::LIVE;
            $client = new ClientInterface([
                'access_token' => $accessToken,
                'redirect_url' => "http://apiuat.zsolu.com/apm/api/QRPayment/QRGenerator"
            ]);
            $this->PaymentoptionsClient = $client;
        }
        return $this->PaymentoptionsClient;
    }

    public function setPaymentoptionsClient(ClientInterface $PaymentoptionsClient)
    {
        $this->PaymentoptionsClient = $PaymentoptionsClient;
    }

    protected function getSessionToken()
    {
        return $this->checkoutSession->getSessionId();
    }

    /**
     * @return Quote
     * @throws \Exception
     */
    protected function getQuote()
    {
        if (!$this->quote) {
            if (!$this->checkoutSession) {
                throw new \Exception('No checkout session');
            }
            $this->quote = $this->checkoutSession->getQuote();
        }

        return $this->quote;
    }
}