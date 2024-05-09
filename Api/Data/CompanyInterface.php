<?php

namespace Vindi\VP\Api\Data;

interface CompanyInterface
{
    public function getCnpj(): string;

    public function setCnpj(string $cnpj): void;

    public function getTradeName(): string;

    public function setTradeName(string $tradeName): void;

    public function getCompanyName(): string;

    public function setCompanyName(string $companyName): void;

}
