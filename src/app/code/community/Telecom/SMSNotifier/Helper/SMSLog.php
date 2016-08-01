<?php
/**
 * Standard helper.
 *
 * @category Telecom
 * @package Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_Helper_SMSLog extends Mage_Core_Helper_Abstract
{


	/**
	 * Log sended SMS to file var/log/smsnotify.log.
	 * Log must be enabled other method does nothing.
	 *
	 * Method return $this for keep the influence interface.
	 *
	 * @param Telecom_SMSNotifier_Model_Sms $sms
	 * @return Telecom_SMSNotifier_Helper_SMSLog $this
	 */
	public function logSendedSMS(Telecom_SMSNotifier_Model_Sms $sms)
	{
		$text = sprintf("SENT (%s)", $this->_smsToString($sms));

		Mage::log($text, Zend_Log::DEBUG, 'smsnotify.log');

		return $this;
	}


	/**
	 * Log sended SMS to file var/log/smsnotify.log.
	 * Log must be enabled other method does nothing.
	 *
	 * Method return $this for keep the influence interface.
	 *
	 * @param Telecom_SMSNotifier_Model_Sms $sms
	 * @param string $errorMessage
	 * @return Telecom_SMSNotifier_Helper_SMSLog
	 */
	public function logNotSendedSMS(Telecom_SMSNotifier_Model_Sms $sms, $errorMessage = '')
	{
		$text = sprintf("NOT SENT (error: %s) (%s)", $errorMessage, $this->_smsToString($sms));

		Mage::log($text, Zend_Log::DEBUG, 'smsnotify.log');

		return $this;
	}


	/**
	 * Convert $sms to string.
	 *
	 * @param Telecom_SMSNotifier_Model_Sms $sms
	 * @return string
	 */
	protected function _smsToString(Telecom_SMSNotifier_Model_Sms $sms)
	{
		return sprintf("Type: %s; Number: %s; Text: %s",
					$sms->isCustomerSMS() ? 'customer' : 'administrator',
					$sms->getNumber(),
					$sms->getText());
	}

}