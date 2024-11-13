<?php

/**
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
 */

namespace Vindi\VP\Gateway\Http;

use Magento\Framework\Encryption\EncryptorInterface;
use Laminas\Http\Client as HttpClient;
use Magento\Framework\Serialize\Serializer\Json;
use Vindi\VP\Helper\Config;

class Client
{
    public const STATUS_UNDEFINED = 'undefined';

    public const STATUS_APPROVED = 'approved';
    public const STATUS_DENIED = 'denied';

    public const STATUS_REASON_EMAIL_VALIDATION = 'EmailValidation';
    public const STATUS_REASON_PROVIDER_REVIEW = 'ProviderReview';
    public const STATUS_REASON_FIRST_PAYMENT = 'FirstPayment';

    /**
     * @var Config
     */
    protected $helperConfig;

    /**
     * @var HttpClient
     */
    protected $api;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var string
     */
    protected $token;


    /**
     * @param Config $helperConfig
     * @param EncryptorInterface $encryptor
     * @param Json $json
     */
    public function __construct(
        Config $helperConfig,
        EncryptorInterface $encryptor,
        Json $json
    ) {
        $this->helperConfig = $helperConfig;
        $this->encryptor = $encryptor;
        $this->json = $json;
    }

    /**
     * @return string[]
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * @return array
     */
    protected function getDefaultOptions(): array
    {
        return [
            'timeout' => 30
        ];
    }

    /**
     * @param string $endpoint
     * @param string $orderId
     * @return string
     */
    public function getEndpointPath($endpoint, $orderId = null, $token = null): string
    {
        $fullEndpoint = $this->helperConfig->getEndpointConfig($endpoint);
        return str_replace(
            ['{order_id}', '{token}'],
            [$orderId, $token],
            $fullEndpoint
        );
    }

    public function getApi($path, $type = 'payments', $storeId = null): HttpClient
    {
        $uri = $this->helperConfig->getEndpointConfig($type . '_uri');

        if ($this->helperConfig->getGeneralConfig('use_sandbox')) {
            $uri = $this->helperConfig->getEndpointConfig($type . '_uri_sandbox');
        }

        $this->api = new HttpClient(
            $uri . $path,
            $this->getDefaultOptions()
        );

        $this->api->setHeaders($this->getDefaultHeaders());
        $this->api->setEncType('application/json');

        return $this->api;
    }

    /**
     * @param string $path
     * @param string $method
     * @param array|object $data
     * @param int|null $storeId
     * @return array
     */
    protected function makeRequest(
        string $path,
        string $method,
        $type = 'auth',
        $data = [],
        $storeId = null,
        $responseType = 'json'
    ): array {
        $api = $this->getApi($path, $type, $storeId);
        $api->setMethod($method);
        if (!empty($data)) {
            $api->setRawBody($this->json->serialize($data));
        }
        $response = $api->send();
        $content = $response->getBody();
        if ($content && $response->getStatusCode() != 204) {
            if ($responseType == 'xml') {
                $content = simplexml_load_string($content);
                $content = $this->json->unserialize($this->json->serialize($content));
            } else {
                $content = $this->json->unserialize($content);
            }
        }

        return [
            'status' => $response->getStatusCode(),
            'response' => $content
        ];
    }
}
