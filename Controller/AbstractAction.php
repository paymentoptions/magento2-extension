<?php


namespace Agtech\Paymentoptions\Controller;


use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Action\Context;


abstract class AbstractAction extends Action
{

    /** @var  ScopeConfigInterface */
    protected $scopeConfig;
    

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Context $context)
    {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }
}