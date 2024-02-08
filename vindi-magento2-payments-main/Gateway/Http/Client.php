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
use Vindi\VP\Helper\Data;

class Client
{
    public const STATUS_UNDEFINED = 'undefined';

    public const STATUS_APPROVED = 'approved';
    public const STATUS_DENIED = 'denied';

    public const STATUS_REASON_EMAIL_VALIDATION = 'EmailValidation';
    public const STATUS_REASON_PROVIDER_REVIEW = 'ProviderReview';
    public const STATUS_REASON_FIRST_PAYMENT = 'FirstPayment';

    /**
     * @var Data
     */
    protected $helper;

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
     * @param Data $helper
     * @param EncryptorInterface $encryptor
     * @param Json $json
     */
    public function __construct(
        Data $helper,
        EncryptorInterface $encryptor,
        Json $json
    ) {
        $this->helper = $helper;
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
        $fullEndpoint = $this->helper->getEndpointConfig($endpoint);
        return str_replace(
            ['{order_id}', '{token}'],
            [$orderId, $token],
            $fullEndpoint
        );
    }

    public function getApi($path, $type = 'payments', $storeId = null): HttpClient
    {
        $uri = $this->helper->getEndpointConfig($type . '_uri');

        if ($this->helper->getGeneralConfig('use_sandbox')) {
            $uri = $this->helper->getEndpointConfig($type . '_uri_sandbox');
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
    protected function makeRequest(string $path, string $method, $type = 'payments', $data = [], $storeId = null): array
    {
        $api = $this->getApi($path, $type, $storeId);
        $api->setMethod($method);
        if (!empty($data)) {
            $api->setRawBody($this->json->serialize($data));
        }
        $response = $api->send();
        $content = $response->getBody();
        if ($content && $response->getStatusCode() != 204) {
            $content = $this->json->unserialize($content);
        }

        return [
            'status' => $response->getStatusCode(),
            'response' => $content
        ];
    }
}
