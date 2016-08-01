<?php
/**
 * Include extern library
 */
require_once Mage::getBaseDir().DS.'lib'.DS.'Telecom'.DS.'SMSService'.DS.'smsservice.php';

/**
 * SMS Model
 *
 * @category Telecom
 * @package Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_Model_Service
{


	/**
	 * Send $sms.
	 *
	 * If $sms is not set or not instance of Telecom_SMSNotifier_Model_SMS then
	 * method logs WARNING and returns false.
	 *
	 * If there is not set username, apikey, or number is not set or is invalid
	 * method generates the event 'smsnotifier_error' and returns false.
	 *
	 * If all is right method generates the event 'smsnotifier_before_sending'
	 * and tries to send SMS.
	 *
	 * If SMS was sent method generates the event 'smsnotifier_after_sending'
	 * and returns true, otherwise generates 'smsnotifier_error' and return false.
	 *
	 * @param Telecom_SMSNotifier_Model_SMS $sms
	 * @return bool
	 */
	public function send($sms)
	{
		if (!$sms || !($sms instanceof Telecom_SMSNotifier_Model_SMS))
		{
			Mage::log(__CLASS__.":".__METHOD__.": SMS is not set or is not instance of Telecom_SMSNotifier_Model_SMS.", Zend_Log::WARN);
			return false;
		}

		$username = $this->getUsername();
		$apikey   = $this->getApikey();

		if (!$username)
		{
			$sms->addCustomData('error_message', $this->_helper()->__('API Username is not set. Check it in the configuration, please.'));
			Mage::dispatchEvent('smsnotifier_error', array('sms' => $sms));
			return false;
		}

		if (!$apikey)
		{
			$sms->addCustomData('error_message', $this->_helper()->__('API Key is not set. Check it in the configuration, please.'));
			Mage::dispatchEvent('smsnotifier_error', array('sms' => $sms));
			return false;
		}

		if (!$sms->getNumber())
		{
			$sms->addCustomData('error_message', $this->_helper()->__('Number is not set.'));
			Mage::dispatchEvent('smsnotifier_error', array('sms' => $sms));
			return false;
		}

		if (!preg_match('/^[0-9]{1,16}$/', $sms->getNumber()))
		{
			$sms->addCustomData('error_message', $this->_helper()->__("Number '%s' is not valid.", $sms->getNumber()));
			Mage::dispatchEvent('smsnotifier_error', array('sms' => $sms));
			return false;
		}

		if (!$this->_getConfig()->isNumberAllowed($sms->getNumber(), $sms->getStoreId()))
		{
			$sms->addCustomData('error_message', $this->_helper()->__("It is forbidden to send SMS to number '%s'. If you think that it is bad then check your number filters definitions in the configuration of SMSNotifyForMagento, please.", $sms->getNumber()));
			Mage::dispatchEvent('smsnotifier_error', array('sms' => $sms));
			return false;
		}

		if (!$this->_getConfig()->isCountryAllowed($sms->getCountry(), $sms->getStoreId()))
		{
			$sms->addCustomData('error_message', $this->_helper()->__("It is forbidden to send SMS to country '%s'. If you think that it is bad then check your country filter definitions in the configuration of SMSNotifyForMagento, please.", $sms->getCountry()));
			Mage::dispatchEvent('smsnotifier_error', array('sms' => $sms));
			return false;
		}

		$allowUnicode = $this->_getConfig()->isUnicodeAllowed($sms->getStoreId());

		Mage::dispatchEvent('smsnotifier_before_sending', array('sms' => $sms));

		$service = ASmsService::getInstance();
		$result = $service->sendMessage($username, $apikey, $sms->getNumber(), $sms->getText(), $allowUnicode);

		if ($result)
		{
			$sms->addCustomData('error_message', null);
			Mage::dispatchEvent('smsnotifier_after_sending', array('sms' => $sms));
			return true;
		}
		else
		{
			$sms->addCustomData('error_message', $service->getError());
			Mage::dispatchEvent('smsnotifier_error', array('sms' => $sms));
			return false;
		}
	}


	/**
	 * Determine whether Username and API key is valid or not.
	 *
	 * @return string
	 */
	public function testCredentials()
	{
		$username = $this->getUsername();
		$apikey   = $this->getApikey();

		if (!$username)
			return $this->_helper()->__('API Username is not set.');

		if (!$apikey)
			return $this->_helper()->__('API Key is not set.');

		$service = ASmsService::getInstance();
		$result = $service->getCreditInfo($username, $apikey);

		if (!$result)
			return $this->_helper()->__('You have not configured API username or API key properly.');
		else
			return "";
	}


	/**
     * Returns current credit info - available credit and estimated credit exhaustion in hours
     *
     * @return mixed Associative array with credit info on success or false on error.
     *               Array contains following keys: credit, exhaustion
     */
	public function getCreditInfo()
	{
		$username = $this->getUsername();
		$apikey   = $this->getApikey();

		$service = ASmsService::getInstance();
		$result = $service->getCreditInfo($username, $apikey);

		return $result;
	}


	/**
	 * Loads and returns the array of available credit amounts for purchase and the purchase URL
	 *
	 * @return array Associative array of [creditValues] => Credit amounts available for purchase ([value] => text)
	 *                                    [link] => Purchase URL
	 */
	public function getCreditPurchaseInfo()
	{
		$username = $this->getUsername();
		$apikey   = $this->getApikey();

		$service = ASmsService::getInstance();
		$result = $service->getCreditPurchaseInfo($username, $apikey);

		return $result;
	}


	/**
	 * @return string
	 */
	public function getUsername()
	{
		return Mage::getStoreConfig('smsnotify/credentials/username');
	}


	/**
	 * @return string
	 */
	public function getApikey()
	{
		return Mage::getStoreConfig('smsnotify/credentials/apikey');
	}


	/**
	 * Get standard config.
	 *
	 * @return Telecom_SMSNotifier_Model_Config
	 */
	protected function _getConfig()
	{
		return Mage::getSingleton('smsnotify/config');
	}


	/**
	 * Get standard helper.
	 *
	 * @return Telecom_SMSNotifier_Helper_Data
	 */
	protected function _helper()
	{
		return Mage::helper('smsnotify');
	}


}