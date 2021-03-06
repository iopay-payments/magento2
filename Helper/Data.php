<?php
namespace IoPay\Core\Helper;

use IoPay\Core\Model\Api;
use IoPay\Core\Model\Authentication;
use \Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    protected $_sessionManager;
    protected $_pricingHelper;

    public function __construct
    (
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Session\SessionManager $sessionManager,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper
    ) {
        $this->_sessionManager = $sessionManager;
        $this->_pricingHelper = $pricingHelper;
        parent::__construct($context);
    }

    public function getCustomerSession()
    {
        return $this->_sessionManager->getSessionId();
    }

    public function getIopayEnvironment() {
        return $this->scopeConfig->getValue('payment/iopay_preferences/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getIopayDebug() {
        return $this->scopeConfig->getValue('payment/iopay_preferences/debug', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getIopayAutoInvoice() {
        return $this->scopeConfig->getValue('payment/iopay_preferences/auto_invoice', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getIopayLogoCheckout() {
        return $this->scopeConfig->getValue('payment/iopay_preferences/logo_checkout', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getIopayLogo() {
        return "https://iopay.com.br/assets/img/logo/iopay-dark.png";
    }

    /**
     * Return configuration from admin settings on Payment Methods
     * @return mixed
     */
    public function getIopayEmail() {
        return $this->scopeConfig->getValue('payment/iopay_preferences/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Return configuration from admin settings on Payment Methods
     * @return mixed
     */
    public function getIopaySellerId() {
        return $this->scopeConfig->getValue('payment/iopay_preferences/seller_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Return configuration from admin settings on Payment Methods
     * @return mixed
     */
    public function getIopaySecret() {
        return $this->scopeConfig->getValue('payment/iopay_preferences/secret', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getIopayPixInstructions() {
        return $this->scopeConfig->getValue('payment/iopay_pix/instructions', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getIopayBoletoInstructions() {
        return $this->scopeConfig->getValue('payment/iopay_boleto/instructions', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getIopayCreditCardInstallments() {
        return $this->scopeConfig->getValue('payment/iopay_creditcard/installments', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getIopayCcInstallmentsWithFee() {
        return $this->scopeConfig->getValue('payment/iopay_creditcard/installments_with_fee', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getIopayCcInstallmentsFee() {
        return $this->scopeConfig->getValue('payment/iopay_creditcard/installments_fee', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Return installments options to populate dropdown in checkout field
     * @return array|mixed|null
     */
    public function getIopayCcInstallments($order) {
        $grandTotal         = $order->getGrandTotal();
        $installOpt         = $this->getIopayCreditCardInstallments();
        $installWithFee     = $this->getIopayCcInstallmentsWithFee();
        $installFee         = $this->getIopayCcInstallmentsFee();
        $installments       = null;

        for ($i=1; $i <= $installOpt; $i++) {

            $installmentAmount  = $grandTotal/$i; //valor da parcela
            $labelFee           = "sem juros";
            $orderTotal         = $grandTotal;

            if ($installWithFee) {
                if ($i >= $installWithFee) { //juros nas parcelas acima de
                    $fee                = ($grandTotal / 100) * $installFee;
                    $installmentAmount  = $installmentAmount + $fee; //parcela + juro
                    $orderTotal         = $i * $installmentAmount;
                    $labelFee           = "com juros de {$installFee}%";
                }
            }

            $orderTotal         = $this->_pricingHelper->currency($orderTotal,true,false);
            $installmentAmount  = $this->_pricingHelper->currency($installmentAmount,true,false);

            $label              = "{$i}x de {$installmentAmount} {$labelFee} (Total {$orderTotal})";
            $installments[$i]   = $label;
        }

        return $installments;
    }

    /**
     * Return installments options to populate dropdown in checkout field
     * @return array|mixed|null
     */
    public function getOrderTotalWithInstallments($order, $installment) {
        $grandTotal         = $order->getGrandTotal();
        $installOpt         = $this->getIopayCreditCardInstallments();
        $installWithFee     = $this->getIopayCcInstallmentsWithFee();
        $installFee         = $this->getIopayCcInstallmentsFee();

        for ($i=1; $i <= $installOpt; $i++) {

            $installmentAmount  = $grandTotal/$i; //valor da parcela
            $orderTotal         = $grandTotal;

            if ($installWithFee) {
                if ($i >= $installWithFee) { //juros nas parcelas acima de
                    $fee                = ($grandTotal / 100) * $installFee;
                    $installmentAmount  = $installmentAmount + $fee; //parcela + juro
                    $orderTotal         = $i * $installmentAmount;
                }
            }

            if ($i == $installment) {
                return $orderTotal;
            }
        }
    }

    public function getIopayAntifraudePlan() {
        return $this->scopeConfig->getValue('payment/iopay_creditcard/antifraud_plan', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getIopayAntifraudeKey() {
        return $this->scopeConfig->getValue('payment/iopay_creditcard/antifraud_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Return session id from user browser
     * @return mixed
     */
    public function getSessionId() {
        $session = Mage::getSingleton('core/session');
        return $session->getEncryptedSessionId();
    }

    public function getShoppingCart($order)
    {
        $result = array();
        foreach ($order->getAllVisibleItems() as $item) {
            $result[] = array(
                "name"      => $item->getName(),
                "code"      => $item->getSku(),
                "quantity"  => intval($item->getQtyOrdered()),
                "amount"    => $this->_formatNumber($item->getBasePrice())
            );
        }

        return $result;
    }

    public function _formatNumber($number)
    {
        return (float) round(sprintf('%0.2f', $number), 2) * 100;
    }

    public function getStoreName()
    {
        return $this->scopeConfig->getValue(
            'general/store_information/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function logs($message) {

        if (!$this->getIoPayDebug()) {
            return;
        }

        try {
            //Check magento version
            $objectManager      = \Magento\Framework\App\ObjectManager::getInstance();
            $productMetadata    = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
            $version            = $productMetadata->getVersion();

            if ($version == '2.4.3') {
                $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/iopay.log');
                $logger = new \Zend_Log();
            } else {
                $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/iopay.log');
                $logger = new \Zend\Log\Logger();
            }

            if (is_array($message)) {
                $message = print_r($message, true);
            }

            $logger->addWriter($writer);
            $logger->info($message);

        } catch (Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

    public function getTelephone($order) {
        try {
            $billing            = $order->getBillingAddress()->getData();
            $customerPhone      = $billing['telephone'];
            return $customerPhone;
        } catch (Exception $e) {
            $this->logs("--- telephone Error ---");
            $this->logs($e->getMessage());
            return 0000000000000;
        }
    }

    public function createComprador($order) {
        try {
            $this->logs("--- IOPay Criando Comprador ---");

            $auth = new Authentication();
            $token = $auth->getToken();

            $payment            = $order->getPayment();
            $billing            = $order->getBillingAddress()->getData();
            $customerFirstName  = $order->getCustomerFirstname();
            $customerLastName   = $order->getCustomerLastname();
            $customerEmail      = $order->getCustomerEmail();
            $customerPhone      = $billing['telephone'];
            $customerGender     = $order->getCustomerGender();
            $address            = $this->getAddressData($order);
            $cpf                = $order->getBillingAddress()->getVatId();

            $comprador = array(
                'first_name'     => $customerFirstName,
                'last_name'     => $customerLastName,
                'email'         => $customerEmail,
                'taxpayer_id'   => $cpf,
                'phone_number'  => $customerPhone,
                'gender'        => ($customerGender == 1 ? 'male' : 'female'),
                'address'       => $address
            );

            $this->logs('--- comprador ---');
            $this->logs($comprador);

            $headers = array(
                "Authorization: Bearer {$token}",
                "cache-control: no-cache",
                "content-type: application/json",
            );

            $api = new Api();
            $api->setHeader($headers);
            $api->setUri("/v1/customer/new");
            $api->setData($comprador);
            $api->connect();

            $response = $api->getResponse();

            if (isset($response['error'])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Comprador: Erro ao criar comprador'));
                return false;
            } else {
                if (isset($response['success']['id'])) {
                    return $response['success']['id'];
                }
            }

            return false;

        } catch (Exception $e) {
            $this->logs("--- createComprador Error ---");
            $this->logs($e->getMessage());
            return false;
        }
    }

    public function getCreditCardToken() {
        $auth       = new Authentication();
        $cardToken  = $auth->getCardToken();
        return $cardToken;
    }

    public function getAddressData($order)
    {
        $address        = $order->getBillingAddress();
        $addressData    = array();

        if ($address) {
            $addressData = array(
                "line1"         => $this->_getAddressStreet($address),
                "line2"         => $this->_getAddressStreetNumber($address),
                "line3"         => ($this->_getAddressComplement($address) ? $this->_getAddressComplement($address) : "-"),
                "neighborhood"  => $this->_getAddressNeighborhood($address),
                "city"          => $address->getCity(),
                "state"         => (strlen($address->getRegion()) > 2 ? $this->getUf($address->getRegion()) : $address->getRegion()),
                "postal_code"   => $this->_getAddressPostalCode($address)
            );
        }

        return $addressData;
    }

    /**
     * Retrieves address street
     *
     * @param $address
     * @return string
     */
    protected function _getAddressStreet($address)
    {
        return $address->getStreetLine(1);
    }

    /**
     * Retrieves address street number
     *
     * @param $address
     * @return string
     */
    protected function _getAddressStreetNumber($address)
    {
        return ($address->getStreetLine(2)) ? $address->getStreetLine(2) : 'SN';
    }

    /**
     * Retrieves address complement
     *
     * @param $address
     * @return string
     */
    protected function _getAddressComplement($address)
    {
        return $address->getStreetLine(3);
    }

    /**
     * Retrieves address neighborhood
     *
     * @param $address
     * @return string
     */
    protected function _getAddressNeighborhood($address)
    {
        return $address->getStreetLine(4);
    }

    /**
     * Retrieves address postal code
     *
     * @param $address
     * @return string
     */
    protected function _getAddressPostalCode($address)
    {
        return preg_replace('/[^0-9]/', '', $address->getPostcode());
    }

    public function getUf($estado) {
        $estadosBrasileiros = array(
            'AC'=>'Acre',
            'AL'=>'Alagoas',
            'AP'=>'Amap??',
            'AM'=>'Amazonas',
            'BA'=>'Bahia',
            'CE'=>'Cear??',
            'DF'=>'Distrito Federal',
            'ES'=>'Esp??rito Santo',
            'GO'=>'Goi??s',
            'MA'=>'Maranh??o',
            'MT'=>'Mato Grosso',
            'MS'=>'Mato Grosso do Sul',
            'MG'=>'Minas Gerais',
            'PA'=>'Par??',
            'PB'=>'Para??ba',
            'PR'=>'Paran??',
            'PE'=>'Pernambuco',
            'PI'=>'Piau??',
            'RJ'=>'Rio de Janeiro',
            'RN'=>'Rio Grande do Norte',
            'RS'=>'Rio Grande do Sul',
            'RO'=>'Rond??nia',
            'RR'=>'Roraima',
            'SC'=>'Santa Catarina',
            'SP'=>'S??o Paulo',
            'SE'=>'Sergipe',
            'TO'=>'Tocantins'
        );
        $uf = array_search($estado, $estadosBrasileiros);
        return $uf;
    }

    public function convertDateHour($date) {
        return date("d/m/Y H:m:s", strtotime($date));
    }

    public function getImageUrl($imageModulePath)
    {
        /** @var \Magento\Framework\App\ObjectManager $om */
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Framework\View\Asset\Repository */
        $viewRepository = $objectManager->get('\Magento\Framework\View\Asset\Repository');
        return $viewRepository->getUrl($imageModulePath);
    }

    public function getCardBrand($number) {
        $brand = null;
        $brands = array(
            'visa'       => '/^4\d{12}(\d{3})?$/',
            'mastercard' => '/^(5[1-5]\d{4}|677189)\d{10}$/',
            'diners'     => '/^3(0[0-5]|[68]\d)\d{11}$/',
            'discover'   => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
            'elo'        => '/^((((636368)|(438935)|(504175)|(451416)|(636297))\d{0,10})|((5067)|(4576)|(4011))\d{0,12})$/',
            'amex'       => '/^3[47]\d{13}$/',
            'jcb'        => '/^(?:2131|1800|35\d{3})\d{11}$/',
            'aura'       => '/^(5078\d{2})(\d{2})(\d{11})$/',
            'hipercard'  => '/^(606282\d{10}(\d{3})?)|(3841\d{15})$/',
            'maestro'    => '/^(?:5[0678]\d\d|6304|6390|67\d\d)\d{8,15}$/',
        );

        foreach ($brands as $_brand => $regex) {
            if (preg_match($regex, $number) ) {
                $brand = $_brand;
                break;
            }
        }

        return $brand;
    }

    public function getBrandImage($brand) {
        switch ($brand) {
            case 'visa':
                $brandImg = $this->getImageUrl('IoPay_Core::images/iopay/flags/visa.svg');
                break;
            case 'mastercard':
                $brandImg = $this->getImageUrl('IoPay_Core::images/iopay/flags/master-card.svg');
                break;
            case 'diners':
                $brandImg = $this->getImageUrl('IoPay_Core::images/iopay/flags/diners.svg');
                break;
            case 'discover':
                $brandImg = $this->getImageUrl('IoPay_Core::images/iopay/flags/discover-network.svg');
                break;
            case 'elo':
                $brandImg = $this->getImageUrl('IoPay_Core::images/iopay/flags/elo-card.svg');
                break;
            case 'amex':
                $brandImg = $this->getImageUrl('IoPay_Core::images/iopay/flags/american-express.svg');
                break;
            case 'jcb':
                $brandImg = $this->getImageUrl('IoPay_Core::images/iopay/flags/jcb.svg');
                break;
            case 'aura':
                $brandImg = $this->getImageUrl('IoPay_Core::images/iopay/flags/aura.svg');
                break;
            case 'hipercard':
                $brandImg = $this->getImageUrl('IoPay_Core::images/iopay/flags/hipercard.svg');
                break;
            case 'maestro':
                $brandImg = $this->getImageUrl('IoPay_Core::images/iopay/flags/maestro.svg');
                break;
            default:
                $brandImg = null;
                break;
        }

        return $brandImg;
    }
}
