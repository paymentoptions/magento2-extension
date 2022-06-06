<?php


namespace Agtech\Paymentoptions\Controller\Redirect;


use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\Resolver as LocaleResolver;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;

class Start extends AbstractRedirectAction
{
    /** @var  LocaleResolver */
    private $localeResolver;
    public $orderFactory;
    
    /**
    * @var \Magento\Framework\App\Config\ScopeConfigInterface
    */
    protected $scopeConfig;
    /**
    * @var Curl
    */
    protected $curl;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        LocaleResolver $localeResolver,
        Curl $curl,
        Context $context)
    {
        $this->localeResolver = $localeResolver;
        $this->curl = $curl;
        $this->orderFactory = $orderFactory;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($scopeConfig, $checkoutSession, $context);
    }

    const XML_PATH_ACCESS_TOKEN = 'payment/paymentoptions/access_token';
    const XML_PATH_ACCESS_ENVIORMENT = 'payment/paymentoptions/sandbox';
    const XML_PATH_MERCHANT_ID = 'payment/paymentoptions/merchant_id';
    const XML_PATH_DEBUG_MODE = 'payment/paymentoptions/debug_mode';
    const PAYMENT_OPTION_LIVE_GATEWAY = 'https://sme.dasgateway.com/v1/api/QRPayment/QRGenerator';
    const PAYMENT_OPTION_SANDBOX_GATEWAY = "https://apiuat.zsolu.com/apm/api/QRPayment/QRGenerator";
    
    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        try {   
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            // Dynamic Variables - start
            
           $access_token = 'Authorization: Basic '.$this->scopeConfig->getValue(self::XML_PATH_ACCESS_TOKEN, $storeScope);
           $merchant_id = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_ID, $storeScope);
            
            $sandboxEnvironment = (bool)$this->scopeConfig->getValue(self::XML_PATH_ACCESS_ENVIORMENT, $storeScope);
            $debugMode = (bool)$this->scopeConfig->getValue(self::XML_PATH_DEBUG_MODE, $storeScope);
            if($sandboxEnvironment){
                $redirectUrl = self::PAYMENT_OPTION_SANDBOX_GATEWAY;
            }else{
                $redirectUrl = self::PAYMENT_OPTION_LIVE_GATEWAY;
            }
            
            // Dynamic Variables - end 
            
            $orderId = $this->checkoutSession->getLastOrderId();
            $order = $this->orderFactory->create()->load($orderId);
            //$this->logger->info('Creating Order for orderId $orderId');
            $billingAddress = $order->getBillingAddress();
            $shippingAddress = $order->getShippingAddress();
            //$client = $this->getPaymentoptionsClient();
            
            
            $redirectFlowParams = array();
            $redirectFlowParams['merchant_id'] = $merchant_id;
            $redirectFlowParams['RETURN_URLS']['SUCCESS_URL'] = $this->_url->getUrl('paymentoptions/redirect/success/id/'.$orderId);
            $redirectFlowParams['RETURN_URLS']['DECLINE_URL'] = $this->_url->getUrl('paymentoptions/redirect/failed/id/'.$orderId);
            $redirectFlowParams['RETURN_URLS']['CANCEL_URL'] = $this->_url->getUrl('paymentoptions/redirect/failed/id/'.$orderId);
            $redirectFlowParams['amount'] = $order->getGrandTotal();
            $redirectFlowParams['currency'] = "SGD";
            $redirectFlowParams['client_id'] = null;
            $redirectFlowParams['merchant_email'] = (string) $billingAddress->getEmail();
            $redirectFlowParams['merchant_txn_ref'] = $this->RandomString();
            $redirectFlowParams['RETURN_URLS']['webhook_url'] = $this->_url->getUrl('paymentoptions/webhook/index/id/'.$orderId);
            $redirectFlowParams['billing_address']['address'] = (string) $billingAddress->getStreetLine(1);
            $redirectFlowParams['billing_address']['address2'] = (string) $billingAddress->getStreetLine(2);
            $redirectFlowParams['billing_address']['city'] = (string) $billingAddress->getCity();
            $redirectFlowParams['billing_address']['phone'] = $billingAddress->getTelephone();
            $redirectFlowParams['billing_address']['country'] = (string) $billingAddress->getCountryId();
            $redirectFlowParams['billing_address']['last_name'] = (string) $billingAddress->getLastname();
            $redirectFlowParams['billing_address']['first_name'] = (string) $billingAddress->getFirstname();
            $redirectFlowParams['billing_address']['postal_code'] = (string) $billingAddress->getPostcode();
            $redirectFlowParams['billing_address']['state'] = (string) $billingAddress->getRegion();
            $redirectFlowParams['shipping_address']['address'] = (string) $shippingAddress->getStreetLine(1);
            $redirectFlowParams['shipping_address']['address2'] = (string) $shippingAddress->getStreetLine(2);
            $redirectFlowParams['shipping_address']['city'] = (string) $shippingAddress->getCity();
            $redirectFlowParams['shipping_address']['phone'] = $shippingAddress->getTelephone();
            $redirectFlowParams['shipping_address']['country'] = (string) $shippingAddress->getCountryId();
            $redirectFlowParams['shipping_address']['last_name'] = (string) $shippingAddress->getLastname();
            $redirectFlowParams['shipping_address']['first_name'] = (string) $shippingAddress->getFirstname();
            $redirectFlowParams['shipping_address']['postal_code'] = (string) $shippingAddress->getPostcode();
            $redirectFlowParams['shipping_address']['state'] = (string) $shippingAddress->getRegion();
            
            
            
            
            $this->curl->setOption(CURLOPT_ENCODING, '');
            $this->curl->setOption(CURLOPT_MAXREDIRS, 10);
            $this->curl->setOption(CURLOPT_TIMEOUT, 60);
            $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            $this->curl->setOption(CURLOPT_HTTPHEADER, [$access_token,'Content-Type: text/json']);
          
            //set curl header
            $redirectFlowParams = json_encode($redirectFlowParams);
            $this->curl->post($redirectUrl,  $redirectFlowParams);
            
            //read response
            $response = $this->curl->getBody();
            
            $responseData = json_decode($response,true);
            
            $order->addCommentToStatusHistory("Order Is placed. Customer Redirect to PaymentOptions Gateway for Payment.");
            $order->save();
            
            
            /* $this->logger->info($redirectFlowParams);
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info($redirectFlowParams);
            $logger->info($response); */
            
            
            return $this->_redirect($responseData['url']);
            
        } catch (\Exception $e) {
           /*  $this->logger->error($e->getMessage()); */
            $this->messageManager->addExceptionMessage($e, 'Can not go to Paymentoptions: ' . $e->getMessage());
        }

        return $this->_redirect('checkout/cart');
    }

    /**
     * @return \Magento\Quote\Model\Quote\Address
     */
    private function getBillingAddress()
    {
        return $this->getQuote()->getBillingAddress();
    }

    /**
     * @return \Magento\Quote\Model\Quote\Address
     */
    private function getShippingAddress()
    {
        return $this->getQuote()->getShippingAddress();
    }
    
    private function RandomString()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
      
        for ($i = 0; $i < 20; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
      
        return $randomString;
    }
}