<?php
/**
 * SMS Template
 *
 * @method setCustomer(Mage_Customer_Model_Customer $customer)
 * @method Mage_Customer_Model_Customer getCustomer()
 * @method setOrder(Mage_Sales_Model_Order $customer)
 * @method Mage_Sales_Model_Order getOrder()
 * @method setInvoice(Mage_Sales_Model_Order_Invoice $customer)
 * @method Mage_Sales_Model_Order_Invoice getInvoice()
 * @method setShipment(Mage_Sales_Model_Order_Shipment $customer)
 * @method Mage_Sales_Model_Order_Shipment getShipment()
 *
 * @category Telecom
 * @package Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_Model_Sms_Template extends Varien_Object
{


	const CUSTOMER_FIRSTNAME = '{{c_firstname}}';
	const CUSTOMER_LASTNAME  = '{{c_lastname}}';
	const CUSTOMER_EMAIL     = '{{c_email}}';


	const SALE_FIRSTNAME	 = '{{firstname}}';
	const SALE_LASTNAME	     = '{{lastname}}';
	const SALE_EMAIL	     = '{{email}}';
	const SALE_AMOUNT		 = '{{amount}}';
	const SALE_ORDER_NR	  	 = '{{order}}';
	const SALE_INVOICE_NR 	 = '{{invoice}}';
	const SALE_SHIPMENT_NR   = '{{shipment}}';


	/**
	 * Process message text. Method replaces the special marks {{ ... }}
	 * by the meaningful words (customer firstname, order number ...).
	 *
	 * If $text is not a string or is undefined method returns '{}'.
	 *
	 * If $type is not Telecom_SMSNotifier_Model_Sms::TYPE_CUSTOMER or
	 * Telecom_SMSNotifier_Model_Sms::TYPE_ADMIN then argument will set
	 * to Telecom_SMSNotifier_Model_Sms::TYPE_CUSTOMER.
	 *
	 * For more description about replaces see
	 * @see Telecom_SMSNotifier_Model_Sms_Template::_processCustomerMarks()
	 * @see Telecom_SMSNotifier_Model_Sms_Template::_processSaleMarks()
	 *
	 * @param string $text
	 * @param int $type Telecom_SMSNotifier_Model_Sms::TYPE_CUSTOMER | Telecom_SMSNotifier_Model_Sms::TYPE_ADMIN
	 * @return string
	 */
	public function process($text, $type = Telecom_SMSNotifier_Model_Sms::TYPE_CUSTOMER)
	{
		if (!$text || !is_string($text))
			return '{}';

		if (!in_array($type, array(Telecom_SMSNotifier_Model_Sms::TYPE_CUSTOMER, Telecom_SMSNotifier_Model_Sms::TYPE_ADMIN)))
			$type = Telecom_SMSNotifier_Model_Sms::TYPE_CUSTOMER;

		$data1 = $this->_processCustomerMarks($text, $type);
		$data2 = $this->_processSaleMarks($text);

        $data = array_merge($data1,$data2);

        if(empty($data)) return '{}';

		return json_encode($data);
	}


	/**
	 * Method processes customer marks.
	 *
	 * @param string $text
	 * @param int $type Telecom_SMSNotifier_Model_Sms::TYPE_CUSTOMER | Telecom_SMSNotifier_Model_Sms::TYPE_ADMIN
	 * @return string
	 */
	protected function _processCustomerMarks($text, $type = Telecom_SMSNotifier_Model_Sms::TYPE_CUSTOMER)
	{
		$firstname = '';
		$lastname  = '';
		$email     = '';

		if ($type == Telecom_SMSNotifier_Model_Sms::TYPE_CUSTOMER)
		{
			if ($this->hasCustomer())
			{
				$firstname = $this->getCustomer()->getFirstname();
				$lastname  = $this->getCustomer()->getLastname();
				$email	   = $this->getCustomer()->getEmail();
			}
		}
		else
		{
			$firstname = 'admin';
			$lastname  = 'admin';
		}

		preg_match_all("/{{(.*?)}}/",$text,$matches);
        if(empty($matches[1])) return array();

        $map = array();
        foreach($matches[1] as $key) {
            if(isset($$key)) $map[$key] = $$key;
        }
		return $map;
	}


	/**
	 * Method processes sale marks.
	 *
	 * @param string $text
	 * @return string
	 */
	protected function _processSaleMarks($text)
	{
		$mainObject = null;

		if ($this->hasShipment())
			$mainObject = $this->getShipment();
		elseif ($this->hasInvoice())
			$mainObject = $this->getInvoice();
		elseif ($this->hasOrder())
			$mainObject = $this->getOrder();

		$firstname = '';
		$lastname  = '';
		$email     = '';
		$amount	   = '';
		$order     = '';
		$invoice   = '';
		$shipment  = '';

		if ($mainObject)
		{
			$storeId = $mainObject->getStoreId();

			$address = $this->_getConfig()->getUsedAddress($storeId);

			if ($address == 'billing')
			{
				$primary   = $mainObject->getBillingAddress();
				$secondary = $mainObject->getShippingAddress();
			}
			else
			{
				$primary   = $mainObject->getShippingAddress();
				$secondary = $mainObject->getBillingAddress();
			}

			$firstname = $primary->getFirstname();
			$lastname  = $primary->getLastname();
			$email	   = $primary->getEmail();

			if (!$firstname)
				$firstname = $secondary->getFirstname();
			if (!$lastname)
				$lastname = $secondary->getLastname();
			if (!$email)
				$email = $secondary->getEmail();
		}

		if ($this->hasOrder())
		{
			$order = $this->getOrder()->getIncrementId();

			$amount = $this->_formatPrice($this->getOrder());
		}

		if ($this->hasInvoice())
		{
			$order   = $this->getInvoice()->getOrder()->getIncrementId();
			$invoice = $this->getInvoice()->getIncrementId();

			$amount = $this->_formatPrice($this->getInvoice());
		}

		if ($this->hasShipment())
		{
			$order    = $this->getShipment()->getOrder()->getIncrementId();
			$shipment = $this->getShipment()->getIncrementId();
		}

        preg_match_all("/{{(.*?)}}/",$text,$matches);
        if(empty($matches[1])) return array();

        $map = array();
        foreach($matches[1] as $key) {
            if(isset($$key)) $map[$key] = $$key;
        }
        return $map;
	}


	/**
	 *
	 * @param Mage_Core_Model_Abstract $object
	 * @return string
	 */
	protected function _formatPrice($object)
	{
		return $this->_currencyByStore(
					$object->getGrandTotal(),
					$object->getStoreId());
	}


	/**
	 * Convert and format price value for specified store
	 *
	 * @param   float $value
	 * @param   int|Mage_Core_Model_Store $store
	 * @param   bool $format
	 * @param   bool $includeContainer
	 * @return  mixed
	 */
	protected function _currencyByStore($value, $store = null)
	{
		try {
			if (!($store instanceof Mage_Core_Model_Store)) {
				$store = Mage::app()->getStore($store);
			}

			$value = $store->convertPrice($value, $format = true, $includeContainer = false);
		}
		catch (Exception $e){
			$value = $e->getMessage();
		}

		return $value;
	}


	protected function _makeKey($mark) {
	    $mark = str_replace("{{","",$mark);
        $mark = str_replace("}}","",$mark);
        return $mark;
    }


	/**
	 * @return Telecom_SMSNotifier_Model_Config
	 */
	protected function _getConfig()
	{
		return Mage::getSingleton('smsnotify/config');
	}

}