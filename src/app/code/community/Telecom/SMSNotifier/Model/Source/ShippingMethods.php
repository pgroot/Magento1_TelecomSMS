<?php
/**
 * SMS Model
 *
 * @category Telecom
 * @package Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_Model_Source_ShippingMethods
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options =  array();

        foreach (Mage::app()->getStore()->getConfig('carriers') as $code => $carrier)
        {
            if (isset($carrier['title']))
            {
                $options[] = array(
                    'value' => $code,
                    'label' => $carrier['title']
                );
            }
        }
        return $options;
    }

}