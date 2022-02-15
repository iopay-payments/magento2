<?php

namespace IoPay\Core\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class CreditCardDataAssignObserver extends AbstractDataAssignObserver
{

    /**
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $additionalData = new DataObject($additionalData);
        $paymentMethod = $this->readMethodArgument($observer);

        $payment = $observer->getPaymentModel();
        if (!$payment instanceof InfoInterface) {
            $payment = $paymentMethod->getInfoInstance();
        }

        if (!$payment instanceof InfoInterface) {
            throw new LocalizedException(__('Payment model does not provided.'));
        }
    }
}
