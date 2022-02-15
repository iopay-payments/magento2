<?php

namespace IoPay\Core\Model\Config\Source\Antifraude;

class Plan
    implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return available checkout types
     * @return array
     */
    public function toOptionArray()
    {
        $arr = [
            ["value" => "with_anti_fraud", 'label' => __("with_anti_fraud")],
            ["value" => "with_anti_fraud_insurance", 'label' => __("with_anti_fraud_insurance")],
            ["value" => "without_antifraud", 'label' => __("without_antifraud")],
        ];

        return $arr;
    }
}
