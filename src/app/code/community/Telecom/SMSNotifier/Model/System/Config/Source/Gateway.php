<?php
/**
 * Gateway.
 *
 * @category Telecom
 * @package Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_Model_System_Config_Source_Gateway
{


    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
    	return array(
    		array('value' => 'Telecom', 'label' => 'Telecom (http://open.189.cn/)')
    	);
    }


}