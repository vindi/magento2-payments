<?php

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
 *
 */

namespace Vindi\VP\Helper;

use Magento\Framework\Encryption\EncryptorInterface;
use Vindi\VP\Logger\Logger;
use Vindi\VP\Api\RequestRepositoryInterface;
use Vindi\VP\Model\RequestFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Directory\Helper\Data as DirectoryData;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;

/**
 * Class Data
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Magento\Payment\Helper\Data
{
    public const FINGERPRINT_URL = 'https://static.traycheckout.com.br/js/finger_print.js';

    protected array $methodNames = [
        'MC' => 'Mastercard',
        'AU' => 'Aura',
        'VI' => 'Visa',
        'ELO' => 'Elo',
        'AE' => 'American Express',
        'JCB' => 'JCB',
        'HC' => 'Hipercard',
        'HI' => 'Hiper'
    ];

    protected array $methodIds = [
        'MC' => '4',
        'VI' => '3',
        'ELO' => '16',
        'AE' => '5',
        'HC' => '28',
        'HI' => '25',
        'PIX' => '27',
        'BANKSLIP' => '6',
        'BANKSLIPPIX' => '28'
    ];

    protected array $transactionStatus = [
        '4' => 'waiting_payment',
        '6' => 'approved',
        '7' => 'canceled',
        '24' => 'contestation',
        '87' => 'monitoring',
        '89' => 'failed'
    ];

    /** @var \Vindi\VP\Logger\Logger */
    protected $logger;

    /** @var OrderInterface  */
    protected $order;

    /** @var RequestRepositoryInterface  */
    protected $requestRepository;

    /** @var RequestFactory  */
    protected $requestFactory;

    /** @var WriterInterface */
    private $configWriter;

    /** @var Json */
    private $json;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var RemoteAddress */
    private $remoteAddress;

    /** @var CategoryRepositoryInterface  */
    protected $categoryRepository;

    /** @var CustomerSession  */
    protected $customerSession;

    /**
     * @var DirectoryData
     */
    protected $helperDirectory;

    /**
     * @var ComponentRegistrar
     */
    protected $componentRegistrar;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var File
     */
    protected $file;

    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        Logger $logger,
        WriterInterface $configWriter,
        Json $json,
        StoreManagerInterface $storeManager,
        RemoteAddress $remoteAddress,
        CustomerSession $customerSession,
        CategoryRepositoryInterface $categoryRepository,
        RequestRepositoryInterface $requestRepository,
        RequestFactory $requestFactory,
        OrderInterface $order,
        ComponentRegistrar $componentRegistrar,
        DateTime $dateTime,
        DirectoryData $helperDirectory,
        EncryptorInterface $encryptor,
        File $file
    ) {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);

        $this->logger = $logger;
        $this->configWriter = $configWriter;
        $this->json = $json;
        $this->storeManager = $storeManager;
        $this->remoteAddress = $remoteAddress;
        $this->customerSession = $customerSession;
        $this->categoryRepository = $categoryRepository;
        $this->requestRepository = $requestRepository;
        $this->requestFactory = $requestFactory;
        $this->order = $order;
        $this->componentRegistrar = $componentRegistrar;
        $this->dateTime = $dateTime;
        $this->helperDirectory = $helperDirectory;
        $this->encryptor = $encryptor;
        $this->file = $file;
    }

    public function getAllowedMethods(): array
    {
        return [
            \Vindi\VP\Model\Ui\BankSlip\ConfigProvider::CODE,
            \Vindi\VP\Model\Ui\BankSlipPix\ConfigProvider::CODE,
            \Vindi\VP\Model\Ui\Pix\ConfigProvider::CODE,
            \Vindi\VP\Model\Ui\CreditCard\ConfigProvider::CODE
        ];
    }

    /**
     * Log custom message using Vindi logger instance
     *
     * @param $message
     * @param string $name
     * @param void
     */
    public function log($message, string $name = 'vindi_vp'): void
    {
        if ($this->getGeneralConfig('debug')) {
            try {
                if (!is_string($message)) {
                    $message = $this->json->serialize($message);
                }

                $this->logger->setName($name);
                $this->logger->debug($this->mask($message));
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    public function getToken($storeId = null): string
    {
        $token = $this->encryptor->decrypt($this->getGeneralConfig('token', $storeId));
        if (empty($token)) {
            $this->log('Private key is empty');
        }
        return $token;
    }

    public function getConsumerKey($storeId = null): string
    {
        $key = $this->encryptor->decrypt($this->getGeneralConfig('consumer_key', $storeId));
        if (empty($key)) {
            $this->log('Consumer key is empty');
        }
        return $key;
    }

    public function getConsumerSecret($storeId = null): string
    {
        $secret = $this->encryptor->decrypt($this->getGeneralConfig('consumer_secret', $storeId));
        if (empty($secret)) {
            $this->log('Secret key is empty');
        }
        return $secret;
    }

    public function getResellerToken($storeId = null): string
    {
        if ((bool) $this->getGeneralConfig('use_sandbox', $storeId)) {
            return '';
        }
        return $this->getGeneralConfig('reseller_token', $storeId);
    }

    /**
     * @param string $message
     * @return string
     */
    public function mask(string $message): string
    {
        $message = preg_replace('/"token_account":\s?"([^"]+)"/', '"token_account":"*********"', $message);
        $message = preg_replace('/"reseller_token":\s?"([^"]+)"/', '"reseller_token":"*********"', $message);
        $message = preg_replace('/"hash":\s?"([^"]+)"/', '"hash":"*********"', $message);
        $message = preg_replace('/"card_cvv":\s?"([^"]+)"/', '"card_cvv":"***"', $message);
        $message = preg_replace('/"card_expdate_month":\s?"([^"]+)"/', '"card_expdate_month":"**"', $message);
        $message = preg_replace('/"card_expdate_year":\s?"([^"]+)"/', '"card_expdate_year":"****"', $message);
        $message = preg_replace('/"notification_url":\s?\["([^"]+)"\]/', '"notification_url":["*****************"]', $message);
        return preg_replace('/"card_number":\s?"(\d{6})\d{3,9}(\d{4})"/', '"card_number":"$1******$2"', $message);
    }

    /**
     * @param $message
     * @return bool|string
     */
    public function jsonEncode($message): string
    {
        try {
            return $this->json->serialize($message);
        } catch (\Exception $e) {
            $this->log($e->getMessage());
        }
        return $message;
    }

    /**
     * @param $message
     * @return bool|string
     */
    public function jsonDecode($message): string
    {
        try {
            return $this->json->unserialize($message);
        } catch (\Exception $e) {
            $this->log($e->getMessage());
        }
        return $message;
    }

    /**
    * @param $request
    * @param $response
    * @param $statusCode
    * @param $method
    * @return void
     */
    public function saveRequest($request, $response, $statusCode, string $method = 'vindi_vp'): void
    {
        if ($this->getGeneralConfig('debug')) {
            try {
                if (!is_string($request)) {
                    $request = $this->json->serialize($request);
                }
                if (!is_string($response)) {
                    $response = $this->json->serialize($response);
                }
                $request = $this->mask($request);
                $response = $this->mask($response);

                $requestModel = $this->requestFactory->create();
                $requestModel->setRequest($request);
                $requestModel->setResponse($response);
                $requestModel->setMethod($method);
                $requestModel->setStatusCode($statusCode);

                $this->requestRepository->save($requestModel);
            } catch (\Exception $e) {
                $this->log($e->getMessage());
            }
        }
    }

    public function getConfig(
        string $config,
        string $group = 'vindi_vp_bankslip',
        string $section = 'payment',
        $scopeCode = null
    ): string {
        return (string) $this->scopeConfig->getValue(
            $section . '/' . $group . '/' . $config,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function saveConfig(
        string $value,
        string $config,
        string $group = 'general',
        string $section = 'vindi_vp'
    ): void {
        $this->configWriter->save(
            $section . '/' . $group . '/' . $config,
            $value
        );
    }

    public function getGeneralConfig(string $config, $scopeCode = null): string
    {
        return $this->getConfig($config, 'general', 'vindi_vp', $scopeCode);
    }

    public function getPaymentsNotificationUrl(Order $order): string
    {
        $orderId = $order->getStoreId() ?: $this->storeManager->getDefaultStoreView()->getId();
        return $this->storeManager->getStore($orderId)->getUrl(
            'vindi_vp/callback/payments',
            [
                '_query' => ['hash' => sha1($this->getToken())],
                '_secure' => true
            ]
        );
    }

    public function getEndpointConfig(string $config, $scopeCode = null): string
    {
        return $this->getConfig($config, 'endpoints', 'vindi_vp', $scopeCode);
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMediaUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    public function getStoreName(): string
    {
        return $this->getConfig('name', 'store_information', 'general');
    }

    public function getUrl(string $route, array $params = []): string
    {
        return $this->_getUrl($route, $params);
    }

    public function getLogger(): LoggerInterface
    {
        return $this->_logger;
    }

    public function digits(string $string): string
    {
        return preg_replace('/\D/', '', (string) $string);
    }

    public function formatPhoneNumber(string $phoneNumber): string
    {
        return $this->clearNumber($phoneNumber);
    }

    public function clearNumber(string $string): string
    {
        return preg_replace('/\D/', '', (string) $string);
    }

    public function loadOrder(string $incrementId): OrderInterface
    {
        return $this->order->loadByIncrementId($incrementId);
    }

    public function getMethodName(string $ccType): string
    {
        $brandName = 'Outro';
        if (isset($this->methodNames[$ccType])) {
            $brandName = $this->methodNames[$ccType];
        }
        return $brandName;
    }

    public function getMethodId(string $ccType): string
    {
        if (isset($this->methodIds[$ccType])) {
            $methodId = $this->methodIds[$ccType];
        }
        return $methodId ?? '0';
    }

    public function getModuleVersion(): string
    {
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'Vindi_VP');
        $composerJsonPath = $modulePath . '/composer.json';

        if ($this->file->fileExists($composerJsonPath)) {
            $composerJsonContent = $this->file->read($composerJsonPath);
            $composerData = json_decode($composerJsonContent, true);

            if (isset($composerData['version'])) {
                return $composerData['version'];
            }
        }

        return '*.*.*';
    }

    public function isUrl(string $trackNumber): bool
    {
        return filter_var($trackNumber, FILTER_VALIDATE_URL);
    }

    public function formatDate(string $date): string
    {
        return date('d/m/Y', strtotime($date));
    }
}
