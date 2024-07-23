<?php

declare(strict_types=1);

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
 */

namespace Vindi\VP\Api;

interface PaymentLinkRepositoryInterface
{
    /**
     * @param int $id
     * @return \Vindi\VP\Api\Data\PaymentLinkInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * @param \Vindi\VP\Api\Data\PaymentLinkInterface $paymentLink
     * @return \Vindi\VP\Api\Data\PaymentLinkInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Vindi\VP\Api\Data\PaymentLinkInterface $paymentLink);

    /**
     * @param \Vindi\VP\Api\Data\PaymentLinkInterface $paymentLink
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\Vindi\VP\Api\Data\PaymentLinkInterface $paymentLink);
}
