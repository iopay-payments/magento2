<?php

namespace IoPay\Core\Model\Provider;

use \Magento\Checkout\Model\ConfigProviderInterface;

class Checkout implements ConfigProviderInterface
{

    protected $_checkoutSession;
    protected $_scopeConfig;
    protected $_storeManager;

    const IOPAY_METHOD_PIX_CODE         = 'iopay_pix';
    const IOPAY_METHOD_BOLETO_CODE      = 'iopay_boleto';
    const IOPAY_METHOD_CREDITCARD_CODE  = 'iopay_creditcard';

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager = $storeManager;
    }

    public function getConfig()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $_helper = $objectManager->create('IoPay\Core\Helper\Data');

        $environment            = $_helper->getIopayEnvironment();
        $pix_instructions       = $_helper->getIopayPixInstructions();
        $boleto_instructions    = $_helper->getIopayBoletoInstructions();
        $cvvImage               = $_helper->getImageUrl('IoPay_Core::images/cvv.png');
        $cardToken              = $_helper->getCreditCardToken();

        $order          = $this->_checkoutSession->getLastRealOrder();
        $ccInstallments = $_helper->getIopayCcInstallments($order);

        return [
            'payment' => [
                self::IOPAY_METHOD_PIX_CODE => [
                    'instructions'  => $pix_instructions
                ],
                self::IOPAY_METHOD_BOLETO_CODE => [
                    'instructions'  => $boleto_instructions
                ],
                self::IOPAY_METHOD_CREDITCARD_CODE => [
                    'installments'  => $ccInstallments,
                    'cvvImage'      => $cvvImage,
                    'cardToken'     => $cardToken,
                    'environment'   => $environment
                ]
            ]
        ];
    }
}
