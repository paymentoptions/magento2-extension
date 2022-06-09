<?php

namespace Agtech\Paymentoptions\Controller\Redirect;


use Agtech\Paymentoptions\Helper\OrderPlace;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;


class Success extends AbstractRedirectAction
{
    /** @var  OrderPlace */
    private $orderPlace;
    protected $orderFactory;
    
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;
    
    protected $messageManager;
    
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CheckoutSession $checkoutSession,
        Context $context,
        OrderFactory $orderFactory,
        OrderPlace $orderPlace,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction
    ) {
        parent::__construct($scopeConfig, $checkoutSession, $context);
        $this->orderPlace = $orderPlace;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->orderFactory = $orderFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        try {
            
            $param = $this->getRequest()->getParams();
            $orderId = $this->getRequest()->getParam('id');
            $order = $this->orderFactory->create()->load($orderId);
            
            $order->addCommentToStatusHistory("Customer Redirect back from PaymentOptions Gateway After Transaction.");
            $order->save();
            
            $QuoteId = $order->getQuoteId();
            
            $this->checkoutSession->clearHelperData();
            $this->checkoutSession->setLastQuoteId($QuoteId);
            $this->checkoutSession->setLastSuccessQuoteId($QuoteId);
            $this->checkoutSession->setLastOrderId($orderId);

            return $this->_redirect('checkout/onepage/success');

        } catch (\Exception $e) {
            $this->messageManager->addError($e, 'Error processing Paymentoptions payment: ' . $e->getMessage());
        }

        return $this->_redirect('checkout/cart');
    }

}