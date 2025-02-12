<?php
declare(strict_types=1);

namespace Vindi\VP\Controller\PaymentProfile;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Result\PageFactory;
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
class Save extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Session
     */
    protected $customerSession;

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
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Session $customerSession
     * @param CreditCardFactory $creditCardFactory
     * @param CreditCardResource $creditCardResource
     * @param DataPersistorInterface $dataPersistor
     * @param CustomerRepositoryInterface $customerRepository
     * @param Card $cardApi
     * @param Data $helperData
     * @param Config $configHelper
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Session $customerSession,
        CreditCardFactory $creditCardFactory,
        CreditCardResource $creditCardResource,
        DataPersistorInterface $dataPersistor,
        CustomerRepositoryInterface $customerRepository,
        Card $cardApi,
        Data $helperData,
        Config $configHelper,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->resultPageFactory   = $resultPageFactory;
        $this->customerSession     = $customerSession;
        $this->creditCardFactory   = $creditCardFactory;
        $this->creditCardResource  = $creditCardResource;
        $this->dataPersistor       = $dataPersistor;
        $this->customerRepository  = $customerRepository;
        $this->cardApi             = $cardApi;
        $this->helperData          = $helperData;
        $this->configHelper        = $configHelper;
        $this->logger              = $logger;
    }

    /**
     * Dispatch request
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->customerSession->authenticate()) {
            $this->_actionFlag->set('', 'no-dispatch', true);
        }
        return parent::dispatch($request);
    }

    /**
     * Execute action
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $request = $this->getRequest();
        $data = $request->getPostValue();
        $customerId = (int)$this->customerSession->getCustomerId();

        if (empty($data) ||
            !isset($data['cc_type'], $data['cc_number'], $data['cc_name'], $data['cc_cvv'], $data['cc_exp_date'])
        ) {
            $this->messageManager->addErrorMessage(__('Invalid card data.'));
            return $this->resultRedirectFactory->create()->setPath('vindi_vp/paymentprofile/index');
        }

        /*
        try {
            $customer = $this->customerRepository->getById($customerId);
            $accessToken = $this->helperData->getAccessToken((int)$customer->getStoreId());

            if (empty($accessToken)) {
                throw new \Exception('Failed to generate access token.');
            }

            $methodName = strtolower(str_replace(' ', '', (string)$data['cc_type']));
            $paymentMethodId = $this->helperData->getMethodIdByName($methodName);

            $cleanCardNumber = preg_replace('/\D/', '', (string)$data['cc_number']);
            $expDate = (string)$data['cc_exp_date'];
            if (strlen($expDate) < 4) {
                throw new \Exception('Invalid expiration date format.');
            }
            $expMonth = substr($expDate, 0, 2);
            $expYear = '20' . substr($expDate, -2);

            $apiData = [
                'access_token'       => $accessToken,
                'payment_method_id'  => $paymentMethodId,
                'card_number'        => $cleanCardNumber,
                'card_name'          => (string)$data['cc_name'],
                'card_cvv'           => (string)$data['cc_cvv'],
                'card_expdate_month' => $expMonth,
                'card_expdate_year'  => $expYear
            ];

            $response = $this->cardApi->create($apiData);

            if (
                !isset($response['status'], $response['response']['message_response']['message'], $response['response']['data_response']['card_token']) ||
                $response['status'] !== 200 ||
                $response['response']['message_response']['message'] !== 'success'
            ) {
                $this->messageManager->addErrorMessage(__('Failed to create card.'));
                return $this->resultRedirectFactory->create()->setPath('vindi_vp/paymentprofile/index');
            }

            $cardToken = (string) $response['response']['data_response']['card_token'];
            $cardType  = (string) $response['response']['data_response']['payment_method_name'];

            $creditCard = $this->creditCardFactory->create();
            $creditCard->setData([
                'card_token'     => $cardToken,
                'customer_id'    => $customerId,
                'customer_email' => $customer->getEmail(),
                'cc_number'      => '***************' . substr($cleanCardNumber, -4),
                'cc_exp_date'    => $expDate,
                'cc_name'        => (string) $data['cc_name'],
                'cc_type'        => $cardType,
                'cc_last_4'      => substr($cleanCardNumber, -4)
            ]);

            $this->creditCardResource->save($creditCard);

            $this->messageManager->addSuccessMessage(__('Card successfully created.'));
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(
                __('An error occurred while saving the payment profile. Please try again later.')
            );
        }
        */

        return $this->resultRedirectFactory->create()->setPath('vindi_vp/paymentprofile/index');
    }
}
