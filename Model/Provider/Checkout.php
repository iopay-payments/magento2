<?php

namespace IoPay\Core\Model\Provider;

use \Magento\Checkout\Model\ConfigProviderInterface;

class Checkout implements ConfigProviderInterface
{

    const IOPAY_METHOD_PIX_CODE         = 'iopay_pix';
    const IOPAY_METHOD_BOLETO_CODE      = 'iopay_boleto';
    const IOPAY_METHOD_CREDITCARD_CODE  = 'iopay_creditcard';

    public function getConfig()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $_helper = $objectManager->create('IoPay\Core\Helper\Data');

        $logo               = "";
        $use_logo           = $_helper->getIopayLogoCheckout();

        if ($use_logo) {
            $logo = $_helper->getIopayLogo();
        }

        $environment            = $_helper->getIopayEnvironment();
        $pix_instructions       = $_helper->getIopayPixInstructions();
        $boleto_instructions    = $_helper->getIopayBoletoInstructions();
        $installments           = $_helper->getIopayCreditCardInstallments();
        $cvvImage               = $_helper->getImageUrl('IoPay_Core::images/cvv.png');
        $cardToken              = $_helper->getCreditCardToken();

        return [
            'payment' => [
                self::IOPAY_METHOD_PIX_CODE => [
                    'instructions'  => $pix_instructions,
                    'logo_iopay'    => $logo
                ],
                self::IOPAY_METHOD_BOLETO_CODE => [
                    'instructions'  => $boleto_instructions,
                    'logo_iopay'    => $logo
                ],
                self::IOPAY_METHOD_CREDITCARD_CODE => [
                    'logo_iopay'    => $logo,
                    'installments'  => $installments,
                    'cvvImage'      => $cvvImage,
                    'cardToken'     => $cardToken,
                    'environment'   => $environment
                ]
            ]
        ];
    }
}
