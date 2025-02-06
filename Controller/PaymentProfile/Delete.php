<?php
declare(strict_types=1);

namespace Vindi\VP\Controller\PaymentProfile;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;
use Vindi\VP\Model\ResourceModel\CreditCard as CreditCardResource;
use Vindi\VP\Model\CreditCardFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Vindi\VP\Gateway\Http\Client\Api\Card;
use Vindi\VP\Helper\Data;

/**
 * Class Delete
 * Controller to delete a credit card
 */
class Delete extends Action
{
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
     * @var Card
     */
    protected $cardApi;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param CreditCardFactory $creditCardFactory
     * @param CreditCardResource $creditCardResource
     * @param Card $cardApi
     * @param CustomerRepositoryInterface $customerRepository
     * @param Data $helperData
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        CreditCardFactory $creditCardFactory,
        CreditCardResource $creditCardResource,
        Card $cardApi,
        CustomerRepositoryInterface $customerRepository,
        Data $helperData
    ) {
        parent::__construct($context);
        $this->customerSession     = $customerSession;
        $this->creditCardFactory   = $creditCardFactory;
        $this->creditCardResource  = $creditCardResource;
        $this->cardApi             = $cardApi;
        $this->customerRepository  = $customerRepository;
        $this->helperData          = $helperData;
    }

    /**
     * Execute the action
     *
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $request = $this->getRequest();
        $customerId = (int)$this->customerSession->getCustomerId();

        if (!$customerId) {
            $this->messageManager->addErrorMessage(__('Customer not authenticated.'));
            return $this->resultRedirectFactory->create()->setPath('vindi_vp/paymentprofile/index');
        }

        $creditCardId = (int)$request->getParam('id');

        if (!$creditCardId) {
            $this->messageManager->addErrorMessage(__('Invalid credit card ID.'));
            return $this->resultRedirectFactory->create()->setPath('vindi_vp/paymentprofile/index');
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            $accessToken = $this->helperData->getAccessToken((int)$customer->getStoreId());

            if (empty($accessToken)) {
                throw new \Exception('Failed to generate access token.');
            }

            $creditCard = $this->creditCardFactory->create();
            $this->creditCardResource->load($creditCard, $creditCardId);

            if (!$creditCard->getId()) {
                $this->messageManager->addErrorMessage(__('Credit card not found.'));
                return $this->resultRedirectFactory->create()->setPath('vindi_vp/paymentprofile/index');
            }

            if ((int)$creditCard->getCustomerId() !== $customerId) {
                $this->messageManager->addErrorMessage(__('Unauthorized access to credit card.'));
                return $this->resultRedirectFactory->create()->setPath('vindi_vp/paymentprofile/index');
            }

            $apiData = [
                'access_token' => $accessToken,
                'card_token'   => $creditCard->getCardToken()
            ];

            $response = $this->cardApi->deactivate($apiData);

            if (!isset($response['status']) || $response['status'] !== 200) {
                $this->messageManager->addErrorMessage(__('Failed to delete card.'));
                return $this->resultRedirectFactory->create()->setPath('vindi_vp/paymentprofile/index');
            }

            $this->creditCardResource->delete($creditCard);

            $this->messageManager->addSuccessMessage(__('Card successfully deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while deleting the card: ') . $e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('vindi_vp/paymentprofile/index');
    }
}
