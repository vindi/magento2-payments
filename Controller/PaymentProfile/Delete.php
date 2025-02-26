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
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param CreditCardFactory $creditCardFactory
     * @param CreditCardResource $creditCardResource
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        CreditCardFactory $creditCardFactory,
        CreditCardResource $creditCardResource,
        CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context);
        $this->customerSession    = $customerSession;
        $this->creditCardFactory  = $creditCardFactory;
        $this->creditCardResource = $creditCardResource;
        $this->customerRepository = $customerRepository;
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

            $this->creditCardResource->delete($creditCard);
            $this->messageManager->addSuccessMessage(__('Card successfully deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while deleting the card: ') . $e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('vindi_vp/paymentprofile/index');
    }
}
