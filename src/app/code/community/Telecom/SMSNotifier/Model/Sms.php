<?php
/**
 * SMS Model
 *
 * @category Telecom
 * @package Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_Model_Sms extends Varien_Object
{

	/**
	 * Max number length.
	 *
	 * @var int
	 */
	const MAX_LENGTH_NUMBER = 16;


	/**
	 * This flag means: SMS is send to an administrator.
	 *
	 * @var int
	 */
	const TYPE_ADMIN = 1;


	/**
	 * This flag means: SMS is send to a customer.
	 * @var int
	 */
	const TYPE_CUSTOMER = 2;


	/**
	 * Type of SMS.
	 * @see Telecom_SMSNotifier_Model_Sms::TYPE_ADMIN
	 * @see Telecom_SMSNotifier_Model_Sms::TYPE_CUSTOMER
	 */
	protected $_type = self::TYPE_CUSTOMER;


	/**
	 * Store id.
	 *
	 * @var int
	 */
	protected $_store_id = 0;


	/**
	 * Phone number where SMS will be sent.
	 *
	 * @var string
	 */
	protected $_number = '';


	/**
	 * ISO2 code of country.
	 * This field is optional not all SMS has to filled.
	 *
	 * @var string
	 */
	protected $_country = '';


	/**
	 * Fullmeaning text of SMS, thus without any replacement {{ ... }}.
	 *
	 * @var string
	 */
	protected $_text = '';


	/**
	 * A custom data. Here can be stored a related customer or a related order
	 * or whatever you want.
	 *
	 * @var Varien_Object
	 */
	protected $_customData = null;


	/**
	 * Set type. You can use TYPE_ADMIN or TYPE_CUSTOMER as $type.
	 * If you use other value method does nothing and log warning.
	 *
	 * Method returns $this for keep the influence interface.
	 *
	 * @param int $type TYPE_ADMIN | TYPE_CUSTOMER
	 */
	public function setType($type)
	{
		if ($type != self::TYPE_ADMIN && $type != self::TYPE_CUSTOMER)
		{
			Mage::log(__CLASS__.":".__METHOD__.": $type is not allowed for type SMS.", Zend_Log::WARN);
		}
		else
		{
			$this->_type = $type;
		}

		return $this;
	}


	/**
	 * Determine whether SMS is send to a customer or not.
	 *
	 * @return bool TRUE when sms is send to a customer, otherwise false.
	 */
	public function isCustomerSMS()
	{
		return ($this->getType() == self::TYPE_CUSTOMER);
	}


	/**
	 * Determine whether SMS is send to an administrator or not.
	 *
	 * @return bool TRUE when sms is send to an administrator, otherwise false.
	 */
	public function isAdminSMS()
	{
		return ($this->getType() == self::TYPE_ADMIN);
	}


	/**
	 * Get current type of SMS.
	 *
	 * @return int
	 */
	public function getType()
	{
		return $this->_type;
	}


	/**
	 * Set number where SMS will be sent.
	 *
	 * Number should be max 16 chars length and should
	 * contains only digits [0-9].
	 *
	 * @param string $number
	 * @return Telecom_SMSNotifier_Model_Sms
	 */
	public function setNumber($number)
	{
		$this->_number = $number;

		return $this;
	}


	/**
	 * Set number where SMS will be sent.
	 *
	 * @return string
	 */
	public function getNumber()
	{
		return $this->_number;
	}


	/**
	 * Set country where SMS will be sent.
	 *
	 * Country code should be ISO2
	 *
	 * @param string $country
	 * @return Telecom_SMSNotifier_Model_Sms
	 */
	public function setCountry($country)
	{
		$this->_country = $country;

		return $this;
	}


	/**
	 * Get country where SMS will be sent.
	 *
	 * @return string
	 */
	public function getCountry()
	{
		return $this->_country;
	}


	/**
	 * Set text of SMS.
	 *
	 * @param string $text
	 * @return Telecom_SMSNotifier_Model_Sms
	 */
	public function setText($text)
	{
		$this->_text = $text;

		return $this;
	}


	/**
	 * Get text of SMS.
	 *
	 * @return string
	 */
	public function getText()
	{
		return $this->_text;
	}


	/**
	 * Set store id.
	 *
	 * @param string $storeid
	 * @return Telecom_SMSNotifier_Model_Sms
	 */
	public function setStoreId($storeid)
	{
		$this->_store_id = $storeid;

		return $this;
	}


	/**
	 * Get store id.
	 *
	 * @return string
	 */
	public function getStoreId()
	{
		return $this->_store_id;
	}


	/**
	 * Add $data to customData as $key.
	 *
	 * If there is a data as $key, then data will
	 * be overwritten.
	 *
	 * Method returns $this for kepp the influence interface.
	 *
	 * @param string $key
	 * @param mixed $data
	 * @return Telecom_SMSNotifier_Model_Sms
	 */
	public function addCustomData($key, $data)
	{
		if (is_null($this->_customData))
			$this->_customData = new Varien_Object();

		$this->_customData->setData($key, $data);

		return $this;
	}


	/**
	 * Get custom data.
	 *
	 * @return Varien_Object|null
	 */
	public function getCustomData($key = null)
	{
		if ($key)
			$data = $this->_customData->getData($key);
		else
			$data = $this->_customData->getData();

		return $data;
	}


}