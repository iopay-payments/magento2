<?php

namespace IoPay\Core\Model\Payment;

use IoPay\Core\Model\Api;
use IoPay\Core\Model\Authentication;

class Boleto extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE        = 'iopay_boleto';
    protected $_code         = self::METHOD_CODE;

    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_isInitializeNeeded = true;
    protected $_cart;
    protected $_helper;
    protected $_infoBlockType = 'IoPay\Core\Block\Payment\Info\Boleto';

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $attributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Checkout\Model\Cart $cart
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $attributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

        $this->_cart = $cart;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('IoPay\Core\Helper\Data');
    }

    public function initialize($paymentAction, $stateObject)
    {
        $payment    = $this->getInfoInstance();
        $order      = $payment->getOrder();
        $cpf        = $order->getBillingAddress()->getVatId();

        $customerIopayId = $this->_helper->createComprador($order);
        $this->_helper->logs('--- Comprador ----');
        $this->_helper->logs($customerIopayId);

        $boletoArray = array(
            "amount"        => round($order->getGrandTotal(), 2) * 100,
            "currency"      => "BRL",
            "description"   => "Pedido # {$order->getIncrementId()} na loja {$this->_helper->getStoreName()}",
            "statement_descriptor"   => $this->_helper->getStoreName(),
            "io_seller_id"  => $this->_helper->getIopaySellerId(),
            "payment_type"  => "boleto",
            "reference_id"  => $order->getIncrementId(),
            "products"      => $this->_helper->getShoppingCart($order)
        );

        $this->_helper->logs('--- Calling APi Boleto ----');
        $this->_helper->logs($boletoArray);

        $auth = new Authentication();
        $token = $auth->getToken();

        $headers = array(
            "Authorization: Bearer {$token}",
            "cache-control: no-cache",
            "content-type: application/json",
        );

        $api = new Api();
        $api->setHeader($headers);
        $api->setUri("/v1/transaction/new/{$customerIopayId}");
        $api->setData($boletoArray);
        $api->connect();

        $response = $api->getResponse();

        if (isset($response['success']['id'])) {
            $payment_type       = $response['success']['payment_type'];
            $payment_method     = $response['success']['payment_method'];
            $id                 = $payment_method['id'];
            $url                = $payment_method['url'];
            $barcode            = $payment_method['barcode'];
            $expiration_date    = $payment_method['expiration_date'];
            $payment_limit_date = $payment_method['payment_limit_date'];

            try {
                $payment
                    ->setAdditionalInformation("iopayCustomer", $customerIopayId)
                    ->setAdditionalInformation("iopayBoletoUrl", $url)
                    ->setAdditionalInformation("iopayBoletoBarcode", $barcode)
                    ->setAdditionalInformation("iopayPaymentId", $id)
                    ->setAdditionalInformation("iopayPaymentType", $payment_type)
                    ->setAdditionalInformation("iopayExpirationDate", $expiration_date)
                    ->setAdditionalInformation("iopayPaymentLimitDate", $payment_limit_date);

            } catch (Exception $e) {
                $this->_helper->logs('IOPay Boleto Error Payment Save: ' . $e->getMessage());
                throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }
        } else {
            if (isset($response['error'])) {
                $err = null;
                foreach ($response['error'] as $group => $errors) {
                    foreach ($errors as $k => $v) {
                        $err .= "- Error [{$group}]: ".$v;
                    }
                }
                throw new \Magento\Framework\Exception\LocalizedException(__($err));
            } else {
                $err = json_encode($response);
                throw new \Magento\Framework\Exception\LocalizedException(__("IoPay Boleto - Erro desconhecido: {$err}"));
            }
        }

        return $this;
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {

    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {

    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null){
        return true;
    }

}
