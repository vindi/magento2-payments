<?php
declare(strict_types=1);

namespace Vindi\VP\Controller\PaymentProfile;

use Magento\Customer\Controller\AbstractAccount;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultInterface;
use Vindi\VP\Model\CreditCardFactory;
use Vindi\VP\Model\ResourceModel\CreditCard as CreditCardResource;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Vindi\VP\Gateway\Http\Client\Api\Card;
use Vindi\VP\Helper\Data;
use Vindi\VP\Helper\Config;
use Vindi\VP\Logger\Logger;

/**
 * Class Save
 *
 * @package Vindi\VP\Controller\PaymentProfile
 */
class Save extends AbstractAccount
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var CreditCardFactory
     */
    protected $creditCardFactory;

    /**
     * @var CreditCardResource
     */
    protected $creditCardResource;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Card
     */
    protected $cardApi;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Save constructor.
     *
     * @param Context                      $context
     * @param Session                      $customerSession
     * @param PageFactory                  $resultPageFactory
     * @param CreditCardFactory            $creditCardFactory
     * @param CreditCardResource           $creditCardResource
     * @param DataPersistorInterface       $dataPersistor
     * @param CustomerRepositoryInterface  $customerRepository
     * @param Card                         $cardApi
     * @param Data                         $helperData
     * @param Config                       $configHelper
     * @param Logger                       $logger
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        CreditCardFactory $creditCardFactory,
        CreditCardResource $creditCardResource,
        DataPersistorInterface $dataPersistor,
        CustomerRepositoryInterface $customerRepository,
        Card $cardApi,
        Data $helperData,
        Config $configHelper,
        Logger $logger
    ) {
        parent::__construct($context, $customerSession);
        $this->resultPageFactory  = $resultPageFactory;
        $this->creditCardFactory  = $creditCardFactory;
        $this->creditCardResource = $creditCardResource;
        $this->dataPersistor      = $dataPersistor;
        $this->customerRepository = $customerRepository;
        $this->cardApi            = $cardApi;
        $this->helperData         = $helperData;
        $this->configHelper       = $configHelper;
        $this->logger             = $logger;
    }

    /**
     * Execute action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $request = $this->getRequest();
        $data = $request->getPostValue();

        if (empty($data)
            || !isset(
                $data['cc_type'],
                $data['cc_number'],
                $data['cc_name'],
                $data['cc_cvv'],
                $data['cc_exp_date']
            )
        ) {
            $this->messageManager->addErrorMessage(__('Invalid card data.'));
            return $this->resultRedirectFactory->create()->setPath('vindi_vp/paymentprofile/index');
        }

        return $this->resultRedirectFactory->create()->setPath('vindi_vp/paymentprofile/index');
    }
}
