<?php

namespace IoPay\Core\Model;

class Api
{
    /**
     * Environment variables
     * @var string
     */
    protected $api_url;
    protected $header;
    protected $uri;
    protected $data;
    protected $response;
    protected $responseCode;
    protected $_helper;

    public function __construct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('IoPay\Core\Helper\Data');

        $this->api_url = "https://api.iopay.com.br/api";

        if ($this->_helper->getIopayEnvironment() == 'sandbox') {
            $this->api_url = "https://sandbox.api.iopay.com.br/api";
        }

        $this->header = array(
            "cache-control: no-cache",
            "content-type: application/json",
        );
    }

    /**
     * Set headers for send to API
     * @param $header
     */
    public function setHeader($header) {
        $this->header = $header;
    }

    /**
     * Set URI on path
     * @param $uri
     */
    public function setUri($uri) {
        $this->uri = $uri;
    }

    /**
     * Set data for send to API
     * @param $data
     */
    public function setData($data) {
        $this->data = json_encode($data);
    }

    /**
     * Return API response
     * @return mixed
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Return API response code
     * @return mixed
     */
    public function getResponseCode() {
        return $this->responseCode;
    }

    /**
     * Return a combination from URL + URI
     * @return false|string
     * @throws Mage_Core_Exception
     */
    public function getCompleteUrl() {
        if (!$this->uri || !$this->api_url) {
            throw new \Magento\Framework\Exception\LocalizedException('Parâmetros inválidos na conexão da API');
            return false;
        }

        return $this->api_url.$this->uri;
    }

    /**
     * Method to connect and send data to API
     * @return false|mixed|null
     */
    public function connect($post = true) {
        try {

            if (!$this->uri || !$this->data) {
                throw new \Magento\Framework\Exception\LocalizedException('Parâmetros inválidos na conexão da API');
                return false;
            }

            $url = $this->api_url.$this->uri;

            $this->_helper->logs('---- API Connect ---');
            $this->_helper->logs($url);
            $this->_helper->logs($this->data);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            if ($post) {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);

            if (curl_error($ch)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    sprintf('Falha ao tentar enviar parametros ao IoPay: %s (%s)', curl_error($ch), curl_errno($ch))
                );
            }

            $response       = curl_exec($ch);
            $http_status    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close ($ch);

            $this->responseCode = $http_status;
            $this->response     = json_decode($response, true);

            $this->_helper->logs('---- API Response ---');
            $this->_helper->logs(print_r($this->response, true));

            return $this->response;

        } catch (Exception $e) {
            $this->_helper->logs($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException($e->getMessage());
            return null;
        }
    }

}
