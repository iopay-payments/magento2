<?php

namespace IoPay\Core\Model\Payment;

use IoPay\Core\Model\Api;
use IoPay\Core\Model\Authentication;

class Creditcard extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE        = 'iopay_creditcard';
    protected $_code         = self::METHOD_CODE;

    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_isInitializeNeeded = true;
    protected $_cart;
    protected $_helper;
    protected $_infoBlockType = 'IoPay\Core\Block\Payment\Info\Creditcard';

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

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        if (!($data instanceof \Magento\Framework\DataObject)) {
            $data = new \Magento\Framework\DataObject($data);
        }

        $infoForm = $data->getData();

        if(isset($infoForm['additional_data'])){
            $infoForm = $infoForm['additional_data'];
        }

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation($infoForm);
    }

    public function initialize($paymentAction, $stateObject)
    {
        $payment    = $this->getInfoInstance();
        $order      = $payment->getOrder();
        $cpf        = $order->getBillingAddress()->getVatId();

        $customerIopayId = $this->_helper->createComprador($order);
        $this->_helper->logs('--- Comprador ----');
        $this->_helper->logs($customerIopayId);

        $this->_helper->logs('--- initialize credit card ----');
        $this->_helper->logs($this->getInfoInstance()->getAdditionalInformation());

        $validateForm       = true;
        $validateMessage    = "";
        if ($this->getInfoInstance()->getAdditionalInformation('credit_card_holder') == "") {
            $validateForm = false;
            $validateMessage .= "- informe o nome que está no cartão \n";
        }
        if ($this->getInfoInstance()->getAdditionalInformation('credit_card_number') == "") {
            $validateForm = false;
            $validateMessage .= "- informe o número que está no cartão \n";
        }
        if ($this->getInfoInstance()->getAdditionalInformation('cc_exp_month') == "") {
            $validateForm = false;
            $validateMessage .= "- informe o mês de vencimento \n";
        }
        if ($this->getInfoInstance()->getAdditionalInformation('cc_exp_year') == "") {
            $validateForm = false;
            $validateMessage .= "- informe o ano de vencimento \n";
        }
        if ($this->getInfoInstance()->getAdditionalInformation('cc_cid') == "") {
            $validateForm = false;
            $validateMessage .= "- informe o código verificador do cartão \n";
        }
        if ($this->getInfoInstance()->getAdditionalInformation('credit_card_document') == "") {
            $validateForm = false;
            $validateMessage .= "- informe o documento do titular \n";
        }
        if ($this->getInfoInstance()->getAdditionalInformation('cc_installments') == "") {
            $validateForm = false;
            $validateMessage .= "- informe o número de parcelas \n";
        }
        if ($this->getInfoInstance()->getAdditionalInformation('credit_card_token') == "") {
            $validateForm = false;
            $validateMessage .= "- credit_card_token inválido \n";
        }

        if (!$validateForm) {
            throw new \Magento\Framework\Exception\LocalizedException(__($validateMessage));
        }

        $creditCard     = $this->getInfoInstance()->getAdditionalInformation('credit_card_number');
        $installments   = (int) $this->getInfoInstance()->getAdditionalInformation('cc_installments');
        $cpf            = $this->getInfoInstance()->getAdditionalInformation('credit_card_document');
        $cardToken      = $this->getInfoInstance()->getAdditionalInformation('credit_card_token');

        if (!$cardToken || $cardToken == null) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Cartão não criptografado, verifique os dados informados e tente novamente...'));
        }

        $ccArray = array(
            "amount"        => round($order->getGrandTotal(), 2) * 100,
            "currency"      => "BRL",
            "description"   => "Pedido {$order->getIncrementId()}",
            "token"         => $cardToken,
            "capture"       => 1,
            "statement_descriptor"   => $this->_helper->getStoreName(),
            "installment_plan" => array(
                "number_installments" => $installments
            ),
            "io_seller_id"  => $this->_helper->getIopaySellerId(),
            "payment_type"  => "credit",
            "reference_id"  => (string)$order->getIncrementId(),
            "products"      => $this->_helper->getShoppingCart($order)
        );

        /* Check antifraud options */
        $antifraud_plan = $this->_helper->getIopayAntifraudePlan();
        $sessionId      = $this->_helper->getCustomerSession();

        if ($antifraud_plan == 'with_anti_fraud' || $antifraud_plan == 'with_anti_fraud_insurance') {
            $ccArray['antifraud_sessid'] = $sessionId;
            $ccArray['shipping'] = array(
                "taxpayer_id"       => preg_replace("/[^0-9]/", "", $cpf),
                "firstname"         => $order->getCustomerFirstname(),
                "lastname"          => $order->getCustomerLastname(),
                "address_1"         => $this->_helper->getAddressData($order)['line1'],
                "address_2"         => $this->_helper->getAddressData($order)['line2'],
                "address_3"         => $this->_helper->getAddressData($order)['line3'],
                "postal_code"       => $this->_helper->getAddressData($order)['postal_code'],
                "city"              => $this->_helper->getAddressData($order)['city'],
                "state"             => $this->_helper->getAddressData($order)['state'],
                "client_type"       => "pf",
                "phone_number"      => $this->_helper->getTelephone($order)
            );
        }

        $this->_helper->logs('--- Calling APi Credit Card ----');
        $this->_helper->logs($ccArray);

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
        $api->setData($ccArray);
        $api->connect();

        $response = $api->getResponse();

        if (isset($response['success']['id'])) {
            $payment_type   = $response['success']['payment_type'];
            $payment_method = $response['success']['payment_method'];
            $id             = $payment_method['id'];
            $cardFormatted  =  substr($creditCard, 0, 4).'********'.substr($creditCard, -4);

            try {
                $payment
                    ->setAdditionalInformation("iopayCreditCardId", $id)
                    ->setAdditionalInformation("iopayCreditCardNumber", $cardFormatted)
                    ->setAdditionalInformation("iopayResponse", json_encode($response['success']));

            } catch (Exception $e) {
                $this->_helper->logs('IOPay Creditcard Error Payment Save: ' . $e->getMessage());
                throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }

        } else {
            if (isset($response['error'])) {
                if (isset($response['error'][0])) {
                    $err = null;
                    foreach ($response['error'] as $group => $errors) {
                        foreach ($errors as $k => $v) {
                            $err .= "- Error [{$group}]: ".$v;
                        }
                    }
                }

                if (isset($response['error']['message_display'])) {
                    $err = $response['error']['message_display'];
                } else if (isset($response['error']['message'])) {
                    $err = $response['error']['message'];
                }

                throw new \Magento\Framework\Exception\LocalizedException(__($err));
            } else {
                $err = json_encode($response);
                throw new \Magento\Framework\Exception\LocalizedException(__("IoPay Cartão - Erro desconhecido: {$err}"));
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
