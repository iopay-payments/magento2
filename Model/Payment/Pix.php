<?php

namespace IoPay\Core\Model\Payment;

use IoPay\Core\Model\Api;
use IoPay\Core\Model\Authentication;

class Pix extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE        = 'iopay_pix';
    protected $_code         = self::METHOD_CODE;

    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_isInitializeNeeded = true;
    protected $_cart;
    protected $_helper;
    protected $_infoBlockType = 'IoPay\Core\Block\Payment\Info\Pix';

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

        $pixArray = array(
            "amount"        => round($order->getGrandTotal(), 2) * 100,
            "currency"      => "BRL",
            "description"   => "Pedido # {$order->getIncrementId()} na loja {$this->_helper->getStoreName()}",
            "statement_descriptor"   => $this->_helper->getStoreName(),
            "io_seller_id" => $this->_helper->getIopaySellerId(),
            "payment_type" => "pix",
            "reference_id" => $order->getIncrementId()
        );

        $this->_helper->logs('--- Calling APi Pix ----');
        $this->_helper->logs($pixArray);

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
        $api->setData($pixArray);
        $api->connect();

        $response = $api->getResponse();

        if (isset($response['success']['id'])) {
            $payment_type    = $response['success']['payment_type'];
            $payment_method  = $response['success']['payment_method'];
            $id              = $payment_method['id'];
            $pix_link        = $payment_method['pix_link'];
            $qrcode          = $payment_method['qr_code']['emv'];
            $qrcode_url      = $response['success']['pix_qrcode_url'];
            $expiration_date = $payment_method['expiration_date'];

            try {
                $payment
                    ->setAdditionalInformation("iopayCustomer", $customerIopayId)
                    ->setAdditionalInformation("iopayPixLink", $pix_link)
                    ->setAdditionalInformation("iopayPixQrcodeUrl", $qrcode_url)
                    ->setAdditionalInformation("iopayPixQrcode", $qrcode)
                    ->setAdditionalInformation("iopayPaymentId", $id)
                    ->setAdditionalInformation("iopayPaymentType", $payment_type)
                    ->setAdditionalInformation("iopayExpirationDate", $expiration_date);

            } catch (Exception $e) {
                $this->_helper->logs('IOPay Pix Error Payment Save: ' . $e->getMessage());
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
                throw new \Magento\Framework\Exception\LocalizedException(__("IoPay Pix - Erro desconhecido: {$err}"));
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
