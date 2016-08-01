<?php
/**
 * Country filter.
 *
 * @category Telecom
 * @package Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_Model_System_Config_Source_CountryFilter
{


    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
    	return array(
    		array('value' => 'local', 		'label' => Mage::helper('smsnotify')->__('Only local country')),
    		array('value' => 'everywhere',	'label' => Mage::helper('smsnotify')->__('Everywhere')),
    		array('value' => 'specific',	'label' => Mage::helper('smsnotify')->__('Only specific countries'))
    	);
    }


}