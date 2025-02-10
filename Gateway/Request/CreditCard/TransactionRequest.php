<?php
declare(strict_types=1);

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to a newer
 * version in the future.
 *
 * @category Vindi
 * @package  Vindi_VP
 */

namespace Vindi\VP\Gateway\Request\CreditCard;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Gateway\ConfigInterface;
use Vindi\VP\Gateway\Http\Client\Api;
use Vindi\VP\Gateway\Request\PaymentsRequest;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Vindi\VP\Helper\Data;
use Vindi\VP\Model\CreditCardRepository;
use Vindi\VP\Model\ResourceModel\CreditCard\CollectionFactory as CreditCardCollectionFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Builds transaction request data
 */
class TransactionRequest extends PaymentsRequest implements BuilderInterface
{
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var SessionManagerInterface
     */
    protected $session;

    /**
     * @var CreditCardRepository
     */
    protected $creditCardRepository;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var CreditCardCollectionFactory
     */
    protected $creditCardCollectionFactory;

    /**
     * TransactionRequest constructor.
     *
     * @param ManagerInterface              $eventManager
     * @param Data                          $helper
     * @param DateTime                      $date
     * @param ConfigInterface               $config
     * @param CustomerSession               $customerSession
     * @param DateTime                      $dateTime
     * @param ProductRepositoryInterface    $productRepository
     * @param CategoryRepositoryInterface   $categoryRepository
     * @param Api                           $api
     * @param EncryptorInterface            $encryptor
     * @param SessionManagerInterface       $session
     * @param CreditCardRepository          $creditCardRepository
     * @param CreditCardCollectionFactory   $creditCardCollectionFactory
     */
    public function __construct(
        ManagerInterface $eventManager,
        Data $helper,
        DateTime $date,
        ConfigInterface $config,
        CustomerSession $customerSession,
        DateTime $dateTime,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        Api $api,
        EncryptorInterface $encryptor,
        SessionManagerInterface $session,
        CreditCardRepository $creditCardRepository,
        CreditCardCollectionFactory $creditCardCollectionFactory
    ) {
        $this->eventManager = $eventManager;
        $this->helper = $helper;
        $this->date = $date;
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->dateTime = $dateTime;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->api = $api;
        $this->encryptor = $encryptor;
        $this->session = $session;
        $this->creditCardRepository = $creditCardRepository;
        $this->creditCardCollectionFactory = $creditCardCollectionFactory;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws LocalizedException
     */
    public function build(array $buildSubject): array
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $payment = $buildSubject['payment']->getPayment();
        $order   = $payment->getOrder();

        $request     = $this->getTransaction($order, (float)$buildSubject['amount']);
        $paymentData = $this->getPaymentData($payment);

        $request['payment'] = $paymentData;

        return [
            'request'       => $request,
            'client_config' => ['store_id' => (int)$order->getStoreId()]
        ];
    }

    /**
     * Retrieves payment data for the transaction request
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return array
     */
    protected function getPaymentData($payment): array
    {
        $paymentProfileId = $payment->getAdditionalInformation('payment_profile');

        if ($paymentProfileId) {
            return $this->getSavedCardData((string)$paymentProfileId, $payment);
        }

        return $this->getNewCardData($payment);
    }

    /**
     * Retrieves saved credit card data
     *
     * @param string                             $paymentProfileId
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return array
     * @throws LocalizedException
     */
    protected function getSavedCardData(string $paymentProfileId, $payment): array
    {
        $customerId = $this->customerSession->getCustomerId();

        if (!$customerId) {
            throw new LocalizedException(__('Customer is not logged in.'));
        }

        $savedCard = $this->creditCardCollectionFactory->create()
            ->addFieldToFilter('entity_id', $paymentProfileId)
            ->addFieldToFilter('customer_id', $customerId)
            ->getFirstItem();

        if (!$savedCard->getId()) {
            throw new LocalizedException(__('Saved card not found or does not belong to the current customer.'));
        }

        $order      = $payment->getOrder();
        $methodName = strtolower(str_replace(' ', '', (string)$savedCard->getCcType()));

        return [
            'card_token'         => $savedCard->getCardToken(),
            'payment_method_id'  => $this->helper->getMethodIdByName($methodName),
            'split'              => (string)$this->getInstallments($order)
        ];
    }

    /**
     * Retrieves new credit card data
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return array
     */
    protected function getNewCardData($payment): array
    {
        $order = $payment->getOrder();
        $saveCard = $payment->getAdditionalInformation('save_card');

        if ($saveCard) {
            $encryptedData = $this->encryptor->encrypt(json_encode([
                'cc_last_4' => substr($payment->getCcNumber(), -4),
                'cc_exp_date' => $payment->getCcExpMonth() . '/' . $payment->getCcExpYear(),
                'cc_name' => $payment->getCcOwner()
            ]));
            $this->session->setData('encrypted_card_info', $encryptedData);
        }

        return [
            'payment_method_id'  => $this->helper->getMethodId($payment->getCcType()),
            'card_name'          => $payment->getCcOwner(),
            'card_number'        => $payment->getCcNumber(),
            'card_expdate_month' => $payment->getCcExpMonth(),
            'card_expdate_year'  => $payment->getCcExpYear(),
            'card_cvv'           => $payment->getCcCid(),
            'split'              => (string)$this->getInstallments($order)
        ];
    }

    /**
     * Retrieves the number of installments
     *
     * @param \Magento\Sales\Model\Order $entity
     * @return int
     */
    protected function getInstallments($entity): int
    {
        $installments = $entity->getPayment()->getAdditionalInformation('cc_installments');
        return (int)$installments ?: 1;
    }
}
