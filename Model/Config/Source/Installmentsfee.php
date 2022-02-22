<?php
namespace IoPay\Core\Model\Config\Source;

class Installmentsfee
    implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return available checkout types
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();

        $options[0] = array(
            'value' => 0,
            'label' => 'sem juros'
        );

        for($i=1;$i<=12;$i++) {
            $options[$i] = array(
                'value' => $i,
                'label' => $i.'x'
            );
        }

        return $options;
    }
}
