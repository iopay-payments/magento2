<?php

namespace IoPay\Core\Model;

class Authentication
{
    protected $_helper;

    public function __construct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('IoPay\Core\Helper\Data');
    }

    /**
     * Method to get token from API
     * @return access_token
     */
    public function getToken() {
        try {

            $email      = $this->_helper->getIopayEmail();
            $secret     = $this->_helper->getIopaySecret();
            $sellerId   = $this->_helper->getIopaySellerId();

            $auth = array(
                "email"         => $email,
                "secret"        => $secret,
                "io_seller_id"  => $sellerId
            );

            $headers = array(
                "cache-control: no-cache",
                "content-type: application/json",
            );

            $api = new Api();
            $api->setHeader($headers);
            $api->setUri("/auth/login?email={$email}&secret={$secret}&io_seller_id={$sellerId}");
            $api->setData($auth);
            $api->connect();

            $response = $api->getResponse();

            if (isset($response['error'])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('IoPay: Erro ao recuperar access_token: '.json_encode($response['error'])));
                return false;
            } else {
                if (isset($response['access_token'])) {
                    return $response['access_token'];
                }
            }
        } catch (Exception $e) {
            $this->_helper->logs("--- getToken Error ---");
            $this->_helper->logs($e->getMessage());
            return false;
        }
    }

    /**
     * Method to get token from card
     * @return access_token
     */
    public function getCardToken() {
        try {

            $email      = $this->_helper->getIopayEmail();
            $secret     = $this->_helper->getIopaySecret();
            $sellerId   = $this->_helper->getIopaySellerId();

            $auth = array(
                "email"         => $email,
                "secret"        => $secret,
                "io_seller_id"  => $sellerId
            );

            $headers = array(
                "cache-control: no-cache",
                "content-type: application/json",
            );

            $api = new Api();
            $api->setHeader($headers);
            $api->setUri("/v1/card/authentication");
            $api->setData($auth);
            $api->connect();

            $response = $api->getResponse();

            if (isset($response['error'])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('IoPay: Erro ao recuperar card access_token: '.json_encode($response['error'])));
                return false;
            } else {
                if (isset($response['access_token'])) {
                    return $response['access_token'];
                }
            }
        } catch (Exception $e) {
            $this->log("--- getToken Error ---");
            $this->log($e->getMessage());
            return false;
        }
    }
}
