<?php
/**
 * Controller
 *
 * @category    Telecom
 * @package     Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_AdminhtmlController extends Mage_Adminhtml_Controller_Action
{


   	/**
	 * Dispaly form for sending a message.
	 *
	 * If credentials are not configured properly then user
	 * will be redirect to configuration page.
   	 */
	public function indexAction()
	{
		if ($error = $this->_getService()->testCredentials())
		{
			$this->_getSession()->addError($error);
			$this->_redirect('adminhtml/system_config/edit/section/smsnotify');
			return;
		}

		$this->loadLayout();
		$this->renderLayout();
	}


	/**
	 * Send SMS action.
	 *
	 * If $smsText is not defined then action terminates with warning message.
	 * If credentials (API username, API key) is not configured properly action
	 * terminates with warning message.
	 *
	 * If all is right then action tries to send SMS to all customer numbers
	 * and to administrator number when is required.
	 *
	 * There will be displayed the information messages to each SMS.
	 */
	public function saveAction()
	{
		$customerNumbers = $this->getRequest()->getParam('customer_numbers');
		$text    		 = $this->getRequest()->getParam('sms_text');
		$admin			 = $this->getRequest()->getParam('admin');
		$inputNumber	 = $this->getRequest()->getParam('q');

		$helper  = Mage::helper('smsnotify');
		$config  = $this->_getConfig();
		$storeId = Mage::app()->getStore()->getId();
		$service = $this->_getService();

		// check sms text, try to send message without text has no sense
		if (!$text)
		{
			$this->_getSession()->addError($helper->__('Message text is not defined.'));
			$this->_redirectReferer();
			return;
		}

		// check credentials
		if ($error = $service->testCredentials())
		{
			$this->_getSession()->addError($error);
			$this->_redirectReferer();
			return;
		}

		// send SMS to admin
		$adminNumber = $this->_getConfig()->getAdminNumberByIndex($admin);
		if ($adminNumber)
		{
			$adminSms = Mage::getModel('smsnotify/sms');
			$adminSms->setType(Telecom_SMSNotifier_Model_Sms::TYPE_ADMIN);
			$adminSms->setStoreId($storeId);
			$adminSms->setNumber($adminNumber);
			$adminSms->setText($text);

			if ($service->send($adminSms))
			{
				$this->_getSession()->addSuccess($helper->__("SMS to '%s' was sent.", $adminNumber));
			}
			else
			{
				$message = $adminSms->getCustomData('error_message');

				if ($message)
					$this->_getSession()->addError($helper->__("SMS to '%s' could not be sent. Reason: '%s'", $adminNumber, $message));
				else
					$this->_getSession()->addError($helper->__("SMS to '%s' could not be sent. Reason is unknown. For more information see log, please.", $adminNumber));
			}
		}

		// add input number to list if possible
		if ($inputNumber)
			$customerNumbers .= ";".$config->sanitizeNumber($inputNumber);

		// convert text representation customer_numbers to arrays
		$customerNumbers = $this->_parseCustomerNumbers($customerNumbers);
		$customerIds	 = $this->_getCustomerIds($customerNumbers);

		// load customer
		$customers = Mage::getModel('customer/customer')
						->getCollection()
						->addAttributeToSelect('firstname')
						->addAttributeToSelect('lastname')
						->addAttributeToSelect('email')
						->addFieldToFilter('entity_id', array('in' => $customerIds))
						->load();

		$alreadySended = array();

		// send SMS to customers
		foreach ($customerNumbers as $customerNumber)
		{
			$telephone  = $customerNumber['telephone'];
			$customerId = $customerNumber['customer_id'];

			if (!$telephone || in_array($telephone, $alreadySended))
				continue;

			$customer = $customers->getItemById($customerId);

			$smsTemplate = Mage::getModel('smsnotify/sms_template');
			if ($customer)
				$smsTemplate->setCustomer($customer);

			$messageText = $smsTemplate->process($text);

			$customerSms = Mage::getModel('smsnotify/sms');
			$customerSms->setType(Telecom_SMSNotifier_Model_Sms::TYPE_CUSTOMER);
			$customerSms->setStoreId($storeId);
			$customerSms->setNumber($telephone);
			$customerSms->setText($messageText);

			$alreadySended[] = $telephone;

			if ($service->send($customerSms))
			{
				$this->_getSession()->addSuccess($helper->__("SMS to '%s' was sent.", $telephone));
			}
			else
			{
				$message = $customerSms->getCustomData('error_message');

				if ($message)
					$this->_getSession()->addError($helper->__("SMS to '%s' could not be sent. Reason: '%s'", $telephone, $message));
				else
					$this->_getSession()->addError($helper->__("SMS to '%s' could not be sent. Reason is unknown. For more information see log, please.", $telephone));
			}
		}

		$this->_redirectReferer();
	}


	/**
	 * Parse the text representation of customer numbers to array
	 *
	 * 123133,2;12312313,3;4533553 => array(
	 * 	array(
	 * 		'telephone'  => 123133
	 * 		'customer_id => 2
	 *  ),
	 *  array(
	 * 		'telephone'  => 12312313
	 * 		'customer_id => 3
	 *  ),
	 *  array(
	 * 		'telephone'  => 4533553
	 * 		'customer_id => 0
	 *  ),
	 *
	 * @param string $numbers
	 * @return array
	 */
	protected function _parseCustomerNumbers($numbers)
	{
		$result = array();

		$numbers = explode(';', $numbers);

		foreach ($numbers as $number)
		{
			$item = array();

			$parts = explode(',', $number);

			$item['telephone']		= (isset($parts[0])) ? $parts[0] : '';
			$item['customer_id']	= (isset($parts[1])) ? (int) $parts[1] : 0;

			$result[] = $item;
		}

		return $result;
	}


	/**
	 * Extract column 'customer_id' from an array
	 * made by _parseCustomerNumbers
	 *
	 * @param array $items
	 * @return array
	 */
	protected function _getCustomerIds($items)
	{
		$result = array();

		foreach ($items as $item)
			if ($item['customer_id'])
				$result[] = (int) $item['customer_id'];

		$result = array_unique($result);

		return $result;
	}


	/**
	 * Get numbers by the name or the phone number.
	 *
	 * Method searchs the customer's by firstname, lastname
	 * or telephone.
	 */
	public function getnumbersAction()
	{
		$query = $this->getRequest()->getParam('q');

		$result = array();

		if ($query)
		{
			$custTable = Mage::getModel('core/resource')->getTableName('customer/entity');

			$customerAddressCollection = Mage::getModel('customer/address')
				->getCollection()
				->joinTable('customer/entity', 'entity_id=parent_id', array('email'), null, 'left')
				->joinAttribute('firstname', 'customer_address/firstname', 'entity_id', null, 'left')
				->joinAttribute('lastname', 'customer_address/lastname', 'entity_id', null, 'left')
				->joinAttribute('telephone', 'customer_address/telephone', 'entity_id', null, 'left')
				->joinAttribute('country', 'customer_address/country_id', 'entity_id', null, 'left')
				->joinAttribute('c_firstname', 'customer/firstname', 'parent_id', null, 'left')
				->joinAttribute('c_lastname', 'customer/lastname', 'parent_id', null, 'left')
				->addAttributeToSort('lastname')
				->setPageSize(20); // we want to get only first 20

			$version = Mage::getVersionInfo();

			if ($version['minor'] < 6) // magento 1.6.0.0 and higher use another alias table
			{
				$customerAddressCollection->getSelect()
					->where("`_table_firstname`.`value` LIKE ?", "%$query%")
					->orWhere("`_table_lastname`.`value` LIKE ?", "%$query%")
					->orWhere("`_table_c_firstname`.`value` LIKE ?", "%$query%")
					->orWhere("`_table_c_lastname`.`value` LIKE ?", "%$query%")
					->orWhere("`email` LIKE ?", "%$query%");
			}
			else
			{
				$customerAddressCollection->getSelect()
					->where("`at_firstname`.`value` LIKE ?", "%$query%")
					->orWhere("`at_lastname`.`value` LIKE ?", "%$query%")
					->orWhere("`at_c_firstname`.`value` LIKE ?", "%$query%")
					->orWhere("`at_c_lastname`.`value` LIKE ?", "%$query%")
					->orWhere("`email` LIKE ?", "%$query%");
			}

			foreach ($customerAddressCollection as $address)
				$result[] = $this->_buildLiElement(
						$address->getLastname(),
						$address->getFirstname(),
						$address->getTelephone(),
						$address->getCountry(),
						$address->getCustomerId());

		}

		// if user enters number with spaces we trim spaces
		if (preg_match('/^[0-9\t ]+$/', $query))
			$query = str_replace(array(' ', '\t'), array('', ''), $query);

		// if user enters number then we have to suggest it
		if (preg_match('/^[0-9]+$/', $query))
			array_unshift($result, $this->_buildLiElement("", "", $query, ""));

		$isEmpty = empty($result);

		// autcompleter requires at least 1 child
		array_unshift($result, $this->_buildEmptyLiElement($isEmpty));

		$html = '<ul>'.implode(' ', $result).'</ul>';

		$this->getResponse()->setHeader('Content-type', 'text/html');
		$this->getResponse()->setBody($html);
	}


	/**
	 * @return string
	 */
	protected function _buildEmptyLiElement($visible)
	{
		if ($visible)
			return '<li class="not-allowed">'.Mage::helper('smsnotify')->__('No data found').'</li>';
		else
			return '<li style="display:none"></li>';
	}


	/**
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $number
	 * @return string
	 */
	protected function _buildLiElement($firstname, $lastname, $number, $country, $customerId = null)
	{
		if (!$number)
			return '';

		$config = $this->_getConfig();

		$number  = $config->sanitizeNumber($number);

		$allowed = true;
		$title   = Mage::helper('smsnotify')->__('Add number to list');

		if (strlen($number)>16)
		{
			$allowed = false;
			$title   = Mage::helper('smsnotify')->__('Telephone number is too long. Telephone number may be maximally 16 digits length.');
		}
		elseif (!$config->isNumberAllowed($number))
		{
			$allowed = false;
			$title   = Mage::helper('smsnotify')->__("It is forbidden to send SMS to number '%s'. If you think that it is bad then check your number filters definitions in the configuration of SMS Notifier, please.", $number);
		}
		elseif (!$config->isCountryAllowed($country))
		{
			$allowed = false;
			$title   = Mage::helper('smsnotify')->__("It is forbidden to send SMS to country '%s'. If you think that it is bad then check your country filter definitions in the configuration of SMS Notifier, please.", $country);
		}

		$class = $allowed ? '' : 'not-allowed';

		$label = '';

		if ($firstname && $lastname)
			$label = $firstname." ".$lastname.", ".$number;
		else if ($firstname && !$lastname)
			$label = $firstname.", ".$number;
		else if (!$firstname && $lastname)
			$label = $lastname.", ".$number;
		else
			$label = $number;

		$id = $customerId ? 'id="customer-'.$customerId.'"' : '';

		return sprintf('<li class="%s" title="%s" %s >%s</li>', $class, $title, $id, $label);
	}


	/**
	 * Send SMS to order's customer.
	 */
	public function smscommentAction()
	{
		$this->_initOrder();

		$text = $this->getRequest()->getParam('sms_comment');

		if ($text)
		{
			try
			{
				$event = Telecom_SMSNotifier_Helper_Data::EVENT_NEW_ORDER;
				$type  = Telecom_SMSNotifier_Model_Sms::TYPE_CUSTOMER;

				$helper = Mage::helper('smsnotify');
				$order  = Mage::registry('sales_order');

				$sms = Mage::getModel('smsnotify/sms');
				$sms->setStoreId($order->getStoreId());
				$sms->setType($type);
				$sms->setCountry($helper->getCountryCode($event, $order));
				$sms->setNumber($helper->getCustomerNumber($event, $order));
				$sms->setText($text);
				$sms->addCustomData($event, $order);

				$this->_getService()->send($sms);
			}
			catch (Exception $e)
			{
				Mage::logException($e);
				Mage::log(__CLASS__.":".__METHOD__.": SMS is not send!.", Zend_Log::ERR);
			}

		}

		$this->getLayout()->getUpdate()->addHandle('adminhtml_sales_order_addcomment');

		$this->loadLayout('empty');
		$this->renderLayout();
	}


	/**
	 * Initialize order model instance
	 *
	 * @return Mage_Sales_Model_Order || false
	 */
	protected function _initOrder()
	{
		$id = $this->getRequest()->getParam('order_id');
		$order = Mage::getModel('sales/order')->load($id);

		if (!$order->getId()) {
			$this->_getSession()->addError($this->__('This order no longer exists.'));
			$this->_redirect('*/*/');
			$this->setFlag('', self::FLAG_NO_DISPATCH, true);
			return false;
		}
		Mage::register('sales_order', $order);
		Mage::register('current_order', $order);
		return $order;
	}


	/**
	 * Get standard configuration model.
	 *
	 * @return Telecom_SMSNotifier_Helper_Model_Config
	 */
	protected function _getConfig()
	{
		return Mage::getSingleton('smsnotify/config');
	}


	/**
	 * Get standard service.
	 *
	 * @return Telecom_SMSNotifyHelper_Model_Service
	 */
	protected function _getService()
	{
		return Mage::getSingleton('smsnotify/service');
	}


	/**
	 * Get backend session.
	 *
	 * @return Mage_Adminhtml_Model_Session
	 */
	protected function _getSession()
	{
		return Mage::getSingleton('adminhtml/session');
	}


}