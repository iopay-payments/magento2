<?php

namespace IoPay\Core\Block\Payment\Info;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Block\Info;

class Boleto extends Info
{

    protected $_helper;

    const TEMPLATE = 'IoPay_Core::info/boleto.phtml';

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

    public function getTitle()
    {
        return $this->getInfo()->getAdditionalInformation('method_title');
    }

    public function getBoletoUrl() {
        return $this->getInfo()->getAdditionalInformation('iopayBoletoUrl');
    }

    public function getExpirationDate() {
        return $this->_helper->convertDateHour($this->getInfo()->getAdditionalInformation('iopayExpirationDate'));
    }
}
