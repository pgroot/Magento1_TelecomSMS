<?php
/**
 * SMS Model
 *
 * @category Telecom
 * @package Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_Model_Config
{

	/**
	 * Determine whether unicode is allowed or not.
	 *
	 * @param int $storeId
	 */
	public function isUnicodeAllowed($storeId = null)
	{
		return (bool) Mage::getStoreConfig("smsnotify/general/unicode", $storeId);
	}

	/**
	 * Determine whether number is allowed or not.
	 *
	 * For possible events look to Telecom_SMSNotifier_Helper_Data
	 * constants.
	 *
	 * @param string $event
	 * @param int $storeId
	 * @return bool
	 */
	public function isEventAllowed($event, $storeId = null)
	{
		return (bool) Mage::getStoreConfig("smsnotify/$event/enabled", $storeId);
	}

        /**
         * Determin whether there is allowed to send SMS for given shipping method
         * and event.
         *
         * @param type $event
         * @param type $shippingMethodCode
         * @param type $storeId
         */
        public function isShippingMethodAllowedForEvent($event, $shippingMethodCode, $storeId = null)
        {
            $disallow = (bool) Mage::getStoreConfig("smsnotify/$event/dissallow_for_shipping_methods", $storeId);
            $disallowedMethods = Mage::getStoreConfig("smsnotify/$event/disallowed_shipping_methods", $storeId);

            if (!$disallow)
                return true;

            $disallowedMethodsArray = explode(',', $disallowedMethods);

            foreach ($disallowedMethodsArray as $disallowMethod)
            {
                if (strpos($shippingMethodCode, $disallowMethod.'_') === 0)
                    return false;
            }

            return true;
        }


	/**
	 * Get message's text for an event.
	 *
	 * For possible events look to Telecom_SMSNotifier_Helper_Data
	 * constants.
	 *
	 * @param string $event
	 * @param int $storeId
	 * @return string
	 */
	public function getTextForEvent($event, $storeId = null)
	{
		return (string) Mage::getStoreConfig("smsnotify/$event/sms_text", $storeId);
	}


	/**
	 * Method determines whether there is allowed to notify the customer.
	 *
	 * @param string $event
	 * @param int $storeId
	 * @return bool
	 */
	public function getNotifyCustomerForEvent($event, $storeId = null)
	{
		return (bool) Mage::getStoreConfig("smsnotify/$event/to_customer", $storeId);
	}


	/**
	 * Get administrator's number for an event.
	 *
	 * For possible events look to Telecom_SMSNotifier_Helper_Data
	 * constants.
	 *
	 * Returned number is sanitizied (related to dial prefix).
	 *
	 * @param string $event
	 * @param int $storeId
	 * @return string
	 */
	public function getAdminNumberForEvent($event, $storeId = null)
	{
		$index = (int) Mage::getStoreConfig("smsnotify/$event/to_admin", $storeId);

		return $this->getAdminNumberByIndex($index, $storeId);
	}


	/**
	 * Get administrator's phone number by index.
	 *
	 * 1 - means primary phone number
	 * 2 - means secondary phone number
	 *
	 * on other values method returns false.
	 *
	 * Returned number is sanitizied (related to dial prefix).
	 *
	 * @param int $index
	 * @param int $storeId
	 * @return string|false
	 */
	public function getAdminNumberByIndex($index, $storeId = null)
	{
		$number = $this->getPureAdminNumberByIndex($index, $storeId);

		return (strlen($number) > 0) ? $this->sanitizeNumber($number) : false;
	}

	/**
	 * Get administrator's phone number by index.
	 *
	 * 1 - means primary phone number
	 * 2 - means secondary phone number
	 *
	 * on other values method returns false.
	 *
	 * Returned number IS NOT sanitizied.
	 *
	 * @param int $index
	 * @param int $storeId
	 * @return string|false
	 */
	public function getPureAdminNumberByIndex($index, $storeId = null)
	{
		if ($index == 1)
			return Mage::getStoreConfig("smsnotify/general/primary_admin", $storeId);
		else if ($index == 2)
			return Mage::getStoreConfig("smsnotify/general/secondary_admin", $storeId);
		else
			return false;
	}


	/**
	 * Method determines whether there is allowed to send the messages
	 * to country specified by $countryCode.
	 *
	 * $countryCode is two-char code of country (US,UK,DE etc.)
	 *
	 * If $countryCode is not specified method returns true
	 *
	 * @param string $countryCode
	 * @param int $storeId
	 * @return bool
	 */
	public function isCountryAllowed($countryCode, $storeId = null)
	{
		if (!$countryCode)
			return true;

		$local     = Mage::getStoreConfig("smsnotify/general/local_country", $storeId);
		$type	   = Mage::getStoreConfig("smsnotify/country_filter/type", $storeId);
		$countries = Mage::getStoreConfig("smsnotify/country_filter/specificcountry", $storeId);

		switch ($type)
		{
			// we can send only to local country
			case 'local':

				$iso3 = Mage::getModel('directory/country')->load($countryCode)->getIso3Code();

				$part = explode(',', $local);

				if (!isset($part[0]) || !$part[0])
				{
					Mage::log(__CLASS__.":".__METHOD__.": Invalid local country code: '$local'.", Zend_Log::WARN);
					return false;
				}

				return ($part[0] == $iso3);

			// we can send message everywhere
			case 'everywhere':
				return true;

			// we can send message to specified country
			case 'specific':
				return in_array($countryCode, explode(',', $countries));

			// invalid country filter
			default:
				Mage::log(__CLASS__.":".__METHOD__.": Invalid country filter type: '$countryCode'.", Zend_Log::WARN);
				return false;
		}
	}


	/**
	 * Sanitize number.
	 *
	 * Add dial prefix of local country if needed (if local country
	 * is not specified there will be used country from general settings).
	 *
	 * Whitespaces in $number will be automaticaly removed.
	 *
	 * @param string $number
	 * @return string
	 */
	public function sanitizeNumber($number, $storeId = null)
	{
		$length   = Mage::getStoreConfig("smsnotify/general/min_length_with_prefix", $storeId);
		$local	  = Mage::getStoreConfig("smsnotify/general/local_country", $storeId);
		$trimzero = Mage::getStoreConfig("smsnotify/general/trim_zero", $storeId);

		$prefix = $this->getDialPrefix($local);

		$number = str_replace(array(" ", "\t"), array("", ""), $number);
		$number = ltrim($number, ($trimzero ? "+0" : "+"));

		if (strlen($number) <= $length)
			$number = $prefix.$number;

		return $number;
	}


	/**
	 * Determine whether number is allowed or not.
	 *
	 * @param string $number
	 * @param int $storeId
	 * @return bool
	 */
	public function isNumberAllowed($number, $storeId = null)
	{
		$exclude = Mage::getStoreConfig("smsnotify/filter/exclude", $storeId);
		$include = Mage::getStoreConfig("smsnotify/filter/include", $storeId);

		$isExcluded = $this->_matchNumberFilters($number, $exclude);
		$isIncluded = $this->_matchNumberFilters($number, $include);

		return (!$isExcluded || $isIncluded);
	}


	/**
	 * Determine whether there is allowed log not sended messages.
	 *
	 * @param int $storeId
	 * @return bool
	 */
	public function isAllowedLogNotSended($storeId = null)
	{
		return in_array((int)Mage::getStoreConfig("smsnotify/general/log"), array(0, 1));
	}


	/**
	 * Determine whether there is allowed log sended messages.
	 *
	 * @param int $storeId
	 * @return bool
	 */
	public function isAllowedLogSended($storeId = null)
	{
		return in_array((int)Mage::getStoreConfig("smsnotify/general/log"), array(0, 2));
	}


	/**
	 * Extract dial prefix from $localCode.
	 *
	 * $localCode has format CODE,DIAL_PREFIX.
	 *
	 * @param string $localCode
	 * @return string
	 */
	public function getDialPrefix($localCode)
	{
		$parts = explode(',', $localCode);

		return (count($parts)==2) ? trim($parts[1]) : '';
	}


	/**
	 * Get used addresses.
	 *
	 * @param int $storeId
	 * @return string billing|shipping
	 */
	public function getUsedAddress($storeId = null)
	{
		return Mage::getStoreConfig('smsnotify/general/used_addresses', $storeId);
	}


	/**
	 * Filters is a string:
	 *
	 * CODE,DIAL_PREFIX,FILTER;CODE,DIAL_PREFIX,FILTER ...
	 *
	 * FILTER is a string:
	 * 09?99?8*
	 *
	 * @param string $number
	 * @param array $filters
	 * @return bool
	 */
	protected function _matchNumberFilters($number, $filters)
	{
		$maxLength = Telecom_SMSNotifier_Model_Sms::MAX_LENGTH_NUMBER;

		$filters = explode(';', $filters);

		foreach ($filters as $filter)
		{
			if ($filter == '')
				continue;

			list($country, $dialprefix, $filter) = explode(',', $filter);

			$parts = explode('-', $filter);

			// range
			if (count($parts) == 2)
			{
				$from = str_replace(array('*', '?'), array('', '0'), $parts[0]);
				$to   = str_replace(array('*', '?'), array('', '9'), $parts[1]);

				$from = $dialprefix.$from;
				$to	  = $dialprefix.$to;

				$from = str_pad($from, $maxLength, '0', STR_PAD_RIGHT);
				$to   = str_pad($to, $maxLength, '9', STR_PAD_RIGHT);

				$number = str_pad($number, $maxLength, '0', STR_PAD_RIGHT);

				if (strcmp($from, $number) <= 0 && strcmp($number, $to) <= 0)
					return true;
			}
			// pattern
			else
			{
				$regexp = '/^'.$dialprefix.str_replace(array('*', '?'), array('', '[0-9]'), $parts[0]).'/';

				if (preg_match($regexp, $number))
					return true;
			}

		}

		return false;
	}


}