<?php
/**
 * Gateway.
 *
 * @category Telecom
 * @package Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_Model_System_Config_Source_Addresses
{


    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
    	return array(
    		array('value' => 'billing', 'label' => Mage::helper('smsnotify')->__('Billing Address')),
    		array('value' => 'shipping', 'label' => Mage::helper('smsnotify')->__('Shipping Address'))
    	);
    }


}