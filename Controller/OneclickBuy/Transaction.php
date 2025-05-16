<?php

declare(strict_types=1);

namespace Vindi\VP\Controller\OneclickBuy;

use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreRepository;
use Vindi\VP\Model\CreditCardRepository;

class Transaction implements HttpPostActionInterface
{

    /**
     * @var CheckoutSession
     */
    protected CheckoutSession $checkoutSession;

    /**
     * @var StoreRepository
     */
    protected StoreRepository $storeRepository;

    /**
     * @var RequestInterface
     */
    private RequestInterface $httpRequest;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var ProductRepository
     */
    private ProductRepository $productRepository;

    /**
     * @var CustomerSession
     */
    protected CustomerSession $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $customerRepository;

    /**
     * @var AddressInterfaceFactory
     */
    private AddressInterfaceFactory $addressInterface;

    /**
     * @var QuoteFactory
     */
    private QuoteFactory $quoteFactory;

    /**
     * @var QuoteResource
     */
    private QuoteResource $quoteResource;

    /**
     * @var QuoteManagement
     */
    private QuoteManagement $quoteManagement;

    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * @var CreditCardRepository
     */
    private CreditCardRepository $creditCardRepository;

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @param CheckoutSession $checkoutSession
     * @param StoreRepository $storeRepository
     * @param ProductRepository $productRepository
     * @param CustomerSession $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param AddressInterfaceFactory $addressInterface
     * @param QuoteFactory $quoteFactory
     * @param QuoteResource $quoteResource
     * @param QuoteManagement $quoteManagement
     * @param JsonFactory $resultJsonFactory
     * @param RequestInterface $httpRequest
     * @param OrderRepositoryInterface $orderRepository
     * @param ManagerInterface $messageManager
     * @param CreditCardRepository $creditCardRepository
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        StoreRepository $storeRepository,
        ProductRepository $productRepository,
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AddressInterfaceFactory $addressInterface,
        QuoteFactory $quoteFactory,
        QuoteResource $quoteResource,
        QuoteManagement $quoteManagement,
        JsonFactory $resultJsonFactory,
        RequestInterface $httpRequest,
        OrderRepositoryInterface $orderRepository,
        ManagerInterface $messageManager,
        CreditCardRepository $creditCardRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->storeRepository = $storeRepository;
        $this->productRepository = $productRepository;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->addressInterface = $addressInterface;
        $this->quoteFactory = $quoteFactory;
        $this->quoteResource = $quoteResource;
        $this->quoteManagement = $quoteManagement;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->httpRequest = $httpRequest;
        $this->orderRepository = $orderRepository;
        $this->messageManager = $messageManager;
        $this->creditCardRepository = $creditCardRepository;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $result = ['success' => false, 'message' => 'Ocorreu um erro ao processar o seu pagamento'];

        $productId = $this->httpRequest->getParam('productId');
        $paymentProfileId = $this->httpRequest->getParam('profile');
        $cvv = $this->httpRequest->getParam('cvv');

        $customerId = $this->customerSession->getCustomerId();
        $customer = $this->customerRepository->getById($customerId);
        $store = $this->storeRepository->getById($customer->getStoreId());

        $quote = $this->quoteFactory->create();
        $quote->setStore($store);
        $quote->setStoreId($store->getId());
        $quote->assignCustomer($customer);

        $product = $this->productRepository->getById($productId);
        $buyRequest = [
            'product' => $productId,
            'qty' => 1,
        ];

        $quote->addProduct(
            $product,
            new DataObject($buyRequest)
        );

        $quoteAddress = $this->getQuoteAddress($customer);
        $quote->setBillingAddress($quoteAddress);
        $this->quoteResource->save($quote);
        // Set Sales Order Payment
        $payment = $quote->getPayment();
        $payment->importData([
            'method'           => 'vindi_vp_cc',
            'additional_data'  => [
                'payment_profile' => $paymentProfileId,
                'installments'    => '1'
            ]
        ]);
        $payment->setAdditionalInformation('payment_profile', $paymentProfileId);
        $payment->setCcCid($cvv);
        $payment->setAdditionalInformation('cc_cid', $cvv);
        $quote->getPayment()->setAdditionalInformation('payment_profile', $paymentProfileId);

        $this->quoteResource->save($quote);

        // Collect Totals & Save Quote
        $quote->collectTotals();
        $this->quoteResource->save($quote);

        // Create Order From Quote and send e-mail
        /** @var \Magento\Sales\Model\Order $order */
        try {
            $order = $this->quoteManagement->submit($quote);
            $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
            $this->checkoutSession->setLastQuoteId($quote->getId());
            $this->checkoutSession->setLastOrderId($order->getId());
            $result = ['success' => true, 'message' => ''];
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultJson->setData($result);
    }


    public function getQuoteAddress($customer){
        $address = $customer->getAddresses()[0];
        $quoteBillingAddress = $this->addressInterface->create();
        $quoteBillingAddress->addData([
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'street' => $address->getStreet(),
            'city' => $address->getCity(),
            'country_id' => $address->getCountryId(),
            'region' => $address->getRegion(),
            'region_id' => $address->getRegionId(),
            'postcode' => $address->getPostcode(),
            'telephone' => $address->getTelephone(),
            'address_type' => Address::TYPE_BILLING,
            'should_ignore_validation' => true
        ]);
        return $quoteBillingAddress;
    }
}
