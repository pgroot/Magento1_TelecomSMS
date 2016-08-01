<?php
/**
 * Gateway.
 *
 * @category Telecom
 * @package Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_Model_System_Config_Source_Log
{


    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
    	return array(
    		array('value' => '0', 'label' => Mage::helper('smsnotify')->__('All')),
    		array('value' => '1', 'label' => Mage::helper('smsnotify')->__('Only not send')),
    		array('value' => '2', 'label' => Mage::helper('smsnotify')->__('Only send')),
    		array('value' => '3', 'label' => Mage::helper('smsnotify')->__('Nothing'))
    	);
    }


}