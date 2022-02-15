<?php
namespace IoPay\Core\Model\Config\Source;

class Installments
    implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return available checkout types
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();
        for($i=1;$i<=12;$i++) {
            $options[$i] = array(
                'value' => $i,
                'label' => $i.'x'
            );
        }

        return $options;
    }
}
