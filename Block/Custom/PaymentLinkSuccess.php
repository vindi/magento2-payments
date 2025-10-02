<?php

declare(strict_types=1);

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
 *
 */
namespace Vindi\VP\Block\Custom;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Vindi\VP\Helper\Data as Helper;
use Vindi\VP\Model\PaymentLinkService;

class PaymentLinkSuccess extends Template
{
    /**
     * @var PaymentLinkService
     */
    private $paymentLinkService;

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @param Context $context
     * @param PaymentLinkService $paymentLinkService
     * @param FormKey $formKey
     * @param Helper $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        PaymentLinkService $paymentLinkService,
        FormKey $formKey,
        Helper $helper,
        array $data = [])
    {
        $this->paymentLinkService = $paymentLinkService;
        $this->formKey = $formKey;
        $this->helper = $helper;
        parent::__construct($context, $data);

    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->getRequest()->getParam('order_id');
    }


    /**
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder()
    {
        return $this->paymentLinkService->getOrderByOrderId($this->getOrderId());
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->getOrder()->getPayment()->getMethod();
    }


    /**
     * @throws LocalizedException
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * @return string
     */
    public function getInstructions()
    {
        return $this->helper->getConfig('payment_link_instructions', $this->getPaymentMethod());
    }
}
