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

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Vindi\VP\Api\RequestRepositoryInterface;
use Vindi\VP\Logger\Logger as DefaultLogger;
use Vindi\VP\Model\RequestFactory;

/**
 * Class Data
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Logger extends AbstractHelper
{
    /** @var DefaultLogger */
    protected $logger;

    /** @var Mask */
    protected $mask;

    /** @var Json */
    protected $json;

    /** @var RequestRepositoryInterface  */
    protected $requestRepository;

    /** @var RequestFactory  */
    protected $requestFactory;

    public function __construct(
        Context $context,
        Json $json,
        DefaultLogger $logger,
        Mask $mask,
        RequestRepositoryInterface $requestRepository,
        RequestFactory $requestFactory
    ) {
        parent::__construct($context);
        $this->json = $json;
        $this->logger = $logger;
        $this->mask = $mask;
        $this->requestRepository = $requestRepository;
        $this->requestFactory = $requestFactory;
    }

    /**
     * Log custom message using Vindi logger instance
     *
     * @param $message
     * @param string $name
     * @param void
     */
    public function execute($message, string $name = 'vindi_vp'): void
    {
        if ($this->scopeConfig->getValue('vindi_vp/general/debug')) {
            try {
                if (!is_string($message)) {
                    $message = $this->json->serialize($message);
                }

                $this->logger->setName($name);
                $this->logger->debug($this->mask->execute($message));
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
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
        try {
            if (!is_string($request)) {
                $request = $this->json->serialize($request);
            }
            if (!is_string($response)) {
                $response = $this->json->serialize($response);
            }
            $request = $this->mask->execute($request);
            $response = $this->mask->execute($response);

            $requestModel = $this->requestFactory->create();
            $requestModel->setRequest($request);
            $requestModel->setResponse($response);
            $requestModel->setMethod($method);
            $requestModel->setStatusCode($statusCode);

            $this->requestRepository->save($requestModel);
        } catch (\Exception $e) {
            $this->execute($e->getMessage());
        }
    }
}
