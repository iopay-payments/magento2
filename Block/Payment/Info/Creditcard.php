<?php

namespace IoPay\Core\Block\Payment\Info;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Block\Info;

class Creditcard extends Info
{

    protected $_helper;

    const TEMPLATE = 'IoPay_Core::info/creditcard.phtml';

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

    public function getCreditCardId() {
        return $this->getInfo()->getAdditionalInformation('iopayCreditCardId');
    }

    public function getCreditCard() {
        return $this->getInfo()->getAdditionalInformation('iopayCreditCardNumber');
    }
}
