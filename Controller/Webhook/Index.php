<?php


namespace Agtech\Paymentoptions\Controller\Webhook;


use Agtech\Paymentoptions\Controller\AbstractAction;
use Magento\Framework\App\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;


class Index implements CsrfAwareActionInterface
{
    
    protected $request;
    protected $response;
    /**
     * @var \Magento\Framework\App\Action\Context
     */
    public $context;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    public $checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    public $orderFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $configSettings;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;
    
    public function __construct(
        RequestInterface $request,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $configSettings,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\App\Response\Http $response
        )
    {
        $this->context         = $context;
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory    = $orderFactory;
        $this->configSettings  = $configSettings;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\App\Response\Http
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $orderid = $this->request->getParam('id');
        
        $order = $this->orderFactory->create()->load($orderid);
        //getting response data - start
        $request = $this->request->getContent();
        $response = $this->response;
        $request = json_decode($request);
        
        if($request->success){          
            
            if($order->canInvoice()) {
                
                $order->addCommentToStatusHistory("Order Is successful. Transation ID: ".$request->transaction_token);
                $order->addCommentToStatusHistory(json_encode($request->transaction));
                $order->setState(Order::STATE_PROCESSING);
                $order->setStatus('processing');
                
                $invoice = $this->_invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->save();
                $transactionSave = $this->_transaction->addObject(
                    $invoice
                )->addObject(
                    $invoice->getOrder()
                );
                $transactionSave->save();
                //$this->invoiceSender->send($invoice);
                //send notification code
                $order->addStatusHistoryComment(
                    __('Notified customer about invoice #%1.', $invoice->getId())
                )
                ->setIsCustomerNotified(true)
                ->save();
                
                $payment = $order->getPayment();
			
                $payment->setTransactionId($request->transaction_token);
                  $payment->setAdditionalInformation(  
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array("Transaction is completed")]
                );
                $trn = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE,null,true);
                $trn->setIsClosed(1)->save();
                 $payment->addTransactionCommentsToOrder(
                    $trn,
                   "The transaction is completed."
                );

                $payment->setParentTransactionId(null);
                $payment->save();
                
            }
            //$order->setTotalPaid($order->getTotalPaid() - $invoice->getGrandTotal());

            //$order->setBaseTotalPaid($order->getBaseTotalPaid() - $invoice->getBaseGrandTotal());
            
            $order->save();
                        
            $response->setBody('Success');
            $response->setCustomStatusCode(498);  
                

            
        }else{
            $order->addCommentToStatusHistory("Order Canceled because of Payment Failed. Transation ID: ".$request->transaction_token);
            $order->addCommentToStatusHistory(json_encode($request->response));
            $order->cancel();
            $order->save();
            $response->setBody('Failed');
            $response->setCustomStatusCode(498);
        }
        
        
        
        
        // Check payment getway response
        $request = $this->request->getContent();
        
        return $response;
    }
    
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return true;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}