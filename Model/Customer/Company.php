<?php

namespace Vindi\VP\Model\Customer;

use Magento\Framework\DataObject;
use Vindi\VP\Api\Data\CompanyInterface;

class Company extends DataObject implements CompanyInterface
{
    public function getCnpj(): string
    {
        return (string) $this->getData('cnpj');
    }

    public function setCnpj(string $cnpj): void
    {
        $this->setData('cnpj', $cnpj);
    }

    public function getTradeName(): string
    {
        return (string) $this->getData('trade_name');
    }

    public function setTradeName(string $tradeName): void
    {
        $this->setData('trade_name', $tradeName);
    }

    public function getCompanyName(): string
    {
        return (string) $this->getData('compnay_name');
    }

    public function setCompanyName(string $companyName): void
    {
        $this->setData('compnay_name', $companyName);
    }
}
