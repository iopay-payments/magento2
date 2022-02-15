<?php

namespace IoPay\Core\Block\Payment\Info;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Block\Info;

class Pix extends Info
{

    protected $_helper;

    const TEMPLATE = 'IoPay_Core::info/pix.phtml';

    public function _construct()
    {
        $this->setTemplate(self::TEMPLATE);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('IoPay\Core\Helper\Data');
    }

    public function getMethod()
    {
        return $this->getInfo()->getMethod();
    }

    /**
     * @return string|null
     * @throws LocalizedException
     */
    public function getPixQrCodeUrl()
    {
        $info   = $this->getInfo();
        $method = $info->getMethod();

        if (strpos($method, "iopay_pix") === false) {
            return null;
        }

        return $this->getInfo()->getAdditionalInformation('iopayPixQrcodeUrl');
    }

    public function getPixQrCode() {
        return $this->getInfo()->getAdditionalInformation('iopayPixQrcode');
    }

    public function getTitle()
    {
        return $this->getInfo()->getAdditionalInformation('method_title');
    }

    public function getExpirationDate() {
        return $this->_helper->convertDateHour($this->getInfo()->getAdditionalInformation('iopayExpirationDate'));
    }
}
