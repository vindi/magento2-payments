<?php

namespace Vindi\VP\Controller\PaymentProfile;

use Magento\Customer\Controller\AbstractAccount;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class Index
 * @package Vindi\VP\Controller\PaymentProfile
 */
class Index extends AbstractAccount
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    public function __construct(
        Context     $context,
        Session     $customerSession,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context, $customerSession);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        return $this->resultPageFactory->create();
    }
}
