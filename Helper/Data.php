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

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Directory\Helper\Data as DirectoryData;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Encryption\EncryptorInterface;
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
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Vindi\VP\Api\AccessTokenRepositoryInterface;
use Vindi\VP\Gateway\Http\Client\Api;
use Vindi\VP\Model\AccessTokenFactory;
use Vindi\VP\Helper\Logger as HelperLogger;
use Vindi\VP\Helper\Config as HelperConfig;
use Vindi\VP\Model\Customer\Company;
use Magento\Framework\Module\Manager as ModuleManager;
use Vindi\VP\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;

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

    /** @var HelperLogger */
    protected $helperLogger;

    /** @var HelperConfig */
    protected $helperConfig;

    /** @var OrderInterface  */
    protected $order;

    /** @var Json */
    private $json;

    /** @var StoreManagerInterface */
    private $storeManager;

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

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var AccessTokenRepositoryInterface
     */
    protected $accessTokenRepository;

    /**
     * @var AccessTokenFactory
     */
    protected $accessTokenFactory;

    protected $api;

    /**
     * @var bool
     */
    private $isDebugEnabled;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param LayoutFactory $layoutFactory
     * @param Factory $paymentMethodFactory
     * @param Emulation $appEmulation
     * @param Config $paymentConfig
     * @param Initial $initialConfig
     * @param HelperLogger $helperLogger
     * @param HelperConfig $helperConfig
     * @param Json $json
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param CategoryRepositoryInterface $categoryRepository
     * @param OrderInterface $order
     * @param ComponentRegistrar $componentRegistrar
     * @param DateTime $dateTime
     * @param DirectoryData $helperDirectory
     * @param EncryptorInterface $encryptor
     * @param File $file
     * @param ModuleManager $moduleManager
     * @param AccessTokenRepositoryInterface $accessTokenRepository
     * @param AccessTokenFactory $accessTokenFactory
     * @param Api $api
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        HelperLogger $helperLogger,
        HelperConfig $helperConfig,
        Json $json,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        CategoryRepositoryInterface $categoryRepository,
        OrderInterface $order,
        ComponentRegistrar $componentRegistrar,
        DateTime $dateTime,
        DirectoryData $helperDirectory,
        EncryptorInterface $encryptor,
        File $file,
        ModuleManager $moduleManager,
        AccessTokenRepositoryInterface $accessTokenRepository,
        AccessTokenFactory $accessTokenFactory,
        Api $api,
        Logger $logger
    ) {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);

        $this->helperLogger = $helperLogger;
        $this->helperConfig = $helperConfig;
        $this->json = $json;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->categoryRepository = $categoryRepository;
        $this->order = $order;
        $this->componentRegistrar = $componentRegistrar;
        $this->dateTime = $dateTime;
        $this->helperDirectory = $helperDirectory;
        $this->encryptor = $encryptor;
        $this->file = $file;
        $this->moduleManager = $moduleManager;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->accessTokenFactory = $accessTokenFactory;
        $this->api = $api;
        $this->isDebugEnabled = $helperConfig->isDebugEnabled();
        $this->logger = $logger;
    }

    /**
     * Retrieve the allowed payment methods
     *
     * @return array
     */
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
        $this->helperLogger->execute($message, $name);
    }

    /**
     * Retrieve the token
     *
     * @param $storeId
     * @return string
     * @throws \Exception
     */
    public function getToken($storeId = null): string
    {
        $this->logDebug('getToken: Attempting to retrieve token.');

        try {
            $token = $this->encryptor->decrypt(
                $this->helperConfig->getGeneralConfig('token', $storeId)
            );

            if (empty($token)) {
                $this->logDebug('getToken: Private key is empty.', [], 'error');
            }

            $this->logDebug('getToken: Token retrieved successfully.');
            return $token;
        } catch (\Exception $e) {
            $this->logDebug('getToken: Error retrieving token.', ['exception' => $e->getMessage()], 'error');
            throw new \Exception('Unable to retrieve token');
        }
    }

    /**
     * Retrieve the access token
     *
     * @param int|null $storeId
     * @return string
     * @throws LocalizedException
     */
    public function getAccessToken($storeId = null): string
    {
        if (!is_null($storeId) && !is_int($storeId)) {
            throw new LocalizedException(__('Invalid store ID provided.'));
        }

        $this->logDebug('getAccessToken: Attempting to retrieve access token.');

        try {
            $accessToken = $this->accessTokenRepository->getValidAccessToken($storeId);
            $this->logDebug('getAccessToken: Valid access token retrieved successfully.');
            return $accessToken;
        } catch (\Exception $e) {
            $this->logDebug('getAccessToken: No valid access token found.', ['exception' => $e->getMessage()], 'error');
        }

        $this->logDebug('getAccessToken: Checking for a refresh token.');

        $tokens = null;
        try {
            $tokens = $this->accessTokenRepository->getLastRefreshToken($storeId);
            $this->logDebug('getAccessToken: Refresh token found.');
        } catch (\Exception $e) {
            $this->logDebug('getAccessToken: Failed to retrieve refresh token.', ['exception' => $e->getMessage()], 'error');
        }

        if (!empty($tokens)) {
            try {
                $this->logDebug('getAccessToken: Attempting to update access token using refresh token.');

                $newToken = $this->api->token()->updateAccessToken(
                    $tokens['access_token'],
                    $tokens['refresh_token'],
                    $storeId
                );

                $this->saveAccessToken($newToken, $storeId);
                $this->logDebug('getAccessToken: Access token updated successfully.');
                return $newToken['access_token'];
            } catch (\Exception $e) {
                $this->logDebug('getAccessToken: Failed to update access token using refresh token.', ['exception' => $e->getMessage()], 'error');
            }
        }

        $this->logDebug('getAccessToken: No refresh token available, checking for an authorization code.');

        $vindiCode = $this->helperConfig->getVindiCode($storeId);

        if (empty($vindiCode)) {
            $this->logDebug('getAccessToken: Authorization code is not available.', 'error');
            throw new LocalizedException(
                __('Authorization code is not available. Please authenticate the application.')
            );
        }

        try {
            $this->logDebug('getAccessToken: Attempting to generate a new access token using authorization code.');

            $accessToken = $this->generateNewAccessToken($storeId);
            $this->logDebug('getAccessToken: New access token generated successfully.');
            return $accessToken;
        } catch (\Exception $e) {
            $this->logDebug('getAccessToken: Failed to generate new access token.', ['exception' => $e->getMessage()], 'error');
            throw new LocalizedException(
                __('Failed to generate a new access token: %1', $e->getMessage())
            );
        }
    }

    /**
     * Generate a new access token
     *
     * @param null $storeId
     * @return string
     * @throws \Exception
     */
    public function generateNewAccessToken($storeId = null): string
    {
        $this->logDebug('generateNewAccessToken: Attempting to generate a new access token.');

        try {
            $consumerKey    = $this->getConsumerKey($storeId);
            $consumerSecret = $this->getConsumerSecret($storeId);

            $code = $this->getSavedCode($storeId);

            if (empty($code)) {
                $this->logDebug('generateNewAccessToken: Authorization code is not available.', [], 'error');
                throw new \Exception('Authorization code is not available. Please complete the authentication process.');
            }

            $newToken = $this->api->token()->generateAccessToken(
                $code,
                $consumerKey,
                $consumerSecret,
                $storeId
            );

            $this->saveAccessToken($newToken, $storeId);
            $this->logDebug('generateNewAccessToken: New access token generated successfully.');
            return $newToken['access_token'];
        } catch (\Exception $e) {
            $this->logDebug('generateNewAccessToken: Failed to generate new access token.', ['exception' => $e->getMessage()], 'error');
            throw new \Exception('Unable to generate a new access token');
        }
    }

    /**
     * Retrieve the saved authorization code
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getSavedCode(?int $storeId = null): ?string
    {
        return $this->helperConfig->getVindiCode($storeId);
    }

    /**
     * @param array $tokenData
     * @param null $storeId
     */
    private function saveAccessToken(array $tokenData, $storeId = null): void
    {
        $this->accessTokenRepository->saveNewAccessToken(
            $tokenData['access_token'],
            $tokenData['refresh_token'],
            $tokenData['access_token_expiration'],
            $tokenData['refresh_token_expiration'],
            $storeId
        );

        $this->log('New access token saved successfully');
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getConsumerKey($storeId = null): string
    {
        $key = $this->encryptor->decrypt(
            $this->helperConfig->getGeneralConfig('consumer_key', $storeId)
        );

        if (empty($key)) {
            $this->log('Consumer key is empty');
        }
        return $key;
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getConsumerSecret($storeId = null): string
    {
        $secret = $this->encryptor->decrypt(
            $this->helperConfig->getGeneralConfig('consumer_secret', $storeId)
        );
        if (empty($secret)) {
            $this->log('Secret key is empty');
        }
        return $secret;
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getResellerToken($storeId = null): string
    {
        return $this->helperConfig->getGeneralConfig('reseller_token', $storeId);
    }

    /**
     * @param $message
     * @return string
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
        $this->helperLogger->saveRequest($request, $response, $statusCode, $method);
    }

    public function getConfig(
        string $config,
        string $group = 'vindi_vp_bankslip',
        string $section = 'payment',
        $scopeCode = null
    ): string {
        return $this->helperConfig->getConfig($config, $group, $section, $scopeCode);
    }

    public function saveConfig(
        string $value,
        string $config,
        string $group = 'general',
        string $section = 'vindi_vp'
    ): void {
        $this->helperConfig->saveConfig($value, $config, $group, $section);
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

    public function getCompanyData(OrderInterface $order, array $customerData): array
    {
        $company = new Company();

        $this->_eventManager->dispatch(
            'vindi_payments_get_company_data',
            ['order' => $order, 'customer_data' => $customerData, 'company' => $company]
        );

        if ($company->getCnpj()) {
            $customerData['cnpj'] = $company->getCnpj();
            $customerData['trade_name'] = $company->getTradeName();
            $customerData['company_name'] = $company->getCompanyName();
        }

        return $customerData;
    }

    /**
     * Logs debug messages if debug is enabled.
     *
     * @param string $message
     * @param array|string $data
     * @param string $type
     */
    private function logDebug(string $message, $data = [], string $type = 'info'): void
    {
        if ($this->isDebugEnabled) {
            if ($type === 'error') {
                $this->logger->error($message, is_array($data) ? $data : ['data' => $data]);
            } else {
                $this->logger->info($message, is_array($data) ? $data : ['data' => $data]);
            }
        }
    }
}
