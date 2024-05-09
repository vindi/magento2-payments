# Vindi VP - Adobe Commerce 

**Composer**

```
composer require vindi/magento2-payments

php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy pt_BR en_US
```

**Instalação Manual**

  

1 - Faça Download do módulo e coloque na pasta
```
app/code/Vindi/VP
```

3 - Depois rodar os comandos de instalação

```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy pt_BR en_US
```

## Desinstalar

1 - Remova o módulo, isso dependerá da forma como foi instalado

**Composer**  

Rode o comando de remoção via composer:  
```
composer remove vindi/magento2-payments
```

**Manual**  

Remova a pasta:  
```
app/code/Vindi/VP
```

2 - Rode os comandos de atualização

```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy pt_BR en_US
```


## Descrição
Módulo disponível em português e inglês, compatível com a versão 2.4 do Adobe Commerce.
O módulo utiliza a API da Vindi para a geração de pagamentos com:  
- Boleto
- Bolepix
- PIX  
- Cartão de Crédito

