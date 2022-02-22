# Modulo para Magento 2.x - IOPAY
![IoPay](https://static.iopay.dev/assets/img/capa_git.jpg)
## Documentação

Este módulo para Magento 2.x permite integrar sua loja com a IOPAY API.

Métodos de pagamento disponíveis:

- Pix
- Boleto
- Cartão de Crédito

## Requerimentos para integração
- [PHP 7.1+](https://www.php.net)
- [Magento 2.x](https://magento.com/tech-resources/download)

## Instalação via Composer
	$ composer require iopay-payments/magento2

## Instalação via GIT
    $ git clone https://github.com/iopay-payments/magento2 ~/iopay
    $ cp -r ~/iopay/* /dir/magento2x/app/code/IoPay/Core

### Manual
1. Clique [aqui](https://github.com/iopay-payments/magento2/archive/refs/tags/v1.0.0.zip) e baixe o arquivo `.zip` de nossa versão mais recente. O arquivo será semelhante a `iopay-payments-magento2-master.zip`
2. Descompacte o arquivo **zip** e copie todo o conteúdo na pasta raiz da sua instalação do Magento em `app`/`code`/`IoPay`/`Core` (essa pasta deverá ser criada manualmente)
3. Limpe o cache em `Sistema > Gerenciamento de Cache`

#### Após a instalação execute os comandos a seguir:
    $ rm -rf pub/static/*
    $ php bin/magento setup:upgrade;
    $ php bin/magento setup:di:compile;
    $ php bin/magento setup:static-content:deploy -f
    $ php bin/magento cache:clean;
    $ php bin/magento cache:flush;

## Configuração
Todas as opção de configuração do módulo se encontram no painel administrativo
Acesse o menu: `Lojas`/`Configurações`/`Vendas`/`Formas de pagamento`/`IOPAY Pagamentos`

## Webhook Callback Url
    $ seudominio.com.br/iopay/webhook

## Api
https://docs.iopay.com.br/

## Contato
Para maiores informações acesse: https://iopay.com.br