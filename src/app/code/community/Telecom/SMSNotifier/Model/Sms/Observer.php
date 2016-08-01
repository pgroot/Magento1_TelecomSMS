<?php
/**
 * SMS Observer
 *
 * @category Telecom
 * @package Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_Model_Sms_Observer
{


	/**
	 * Event is invoked just before sending a message.
	 *
	 * @param Varien_Event_Observer $observer
	 * @return Telecom_SMSNotifier_Model_SmsObserver
	 */
	public function beforeSending(Varien_Event_Observer $observer)
	{
		return $this;
	}


	/**
	 * Event is invoked just after sending a message.
	 *
	 * @param Varien_Event_Observer $observer
	 * @return Telecom_SMSNotifier_Model_SmsObserver
	 */
	public function afterSending(Varien_Event_Observer $observer)
	{
		$event = $observer->getEvent();
		$sms   = $event->getData('sms');

		if ($this->_isThereAnySaleObject($sms))
		{
			$this->_addCommentToSaleObject($sms);
		}

		if ($this->_getConfig()->isAllowedLogSended($sms->getStoreId()))
		{
			$this->_getLogger()->logSendedSMS($sms);
		}

		return $this;
	}


	/**
	 * Event is invoked when sending a message is broken.
	 *
	 * @param Varien_Event_Observer $observer
	 * @return Telecom_SMSNotifier_Model_SmsObserver
	 */
	public function onError(Varien_Event_Observer $observer)
	{
		$event = $observer->getEvent();
		$sms   = $event->getData('sms');

		if ($this->_isThereAnySaleObject($sms))
		{
			$this->_addCommentToSaleObject($sms);
		}

		if ($this->_getConfig()->isAllowedLogNotSended($sms->getStoreId()))
		{
			$this->_getLogger()->logNotSendedSMS($sms, $sms->getCustomData('error_message'));
		}

		return $this;
	}


	/**
	 * Determine whether SMS holds any sale object.
	 *
	 * @param Telecom_SMSNotify_Model_Sms $sms
	 * @return bool
	 */
	protected function _isThereAnySaleObject($sms)
	{
		$possibleKeys = array(
			Telecom_SMSNotifier_Helper_Data::EVENT_NEW_ORDER,
			Telecom_SMSNotifier_Helper_Data::EVENT_NEW_INVOICE,
			Telecom_SMSNotifier_Helper_Data::EVENT_NEW_SHIPMENT
		);

		$data = $sms->getCustomData();

		$keys = array_keys($data);

		$intersect = array_intersect($possibleKeys, $keys);

		return !empty($intersect);
	}


	/**
	 * Add comment about send or not send SMS.
	 *
	 * @param Telecom_SMSNotify_Model_Sms $sms
	 * @return Telecom_SMSNotifier_Model_Sms_Observer
	 */
	protected function _addCommentToSaleObject($sms)
	{
		// get helper
		$helper = Mage::helper('smsnotify');

		// get comment text
		$error = $sms->getCustomData('error_message');

		$person  = $sms->isCustomerSMS() ?
			$helper->__('customer') :
			$helper->__('administrator');

		$message = $error ?
			$helper->__('Sending SMS to %s fails (Reason: %s)', $person, $error) :
			$helper->__('%s was notified by SMS (Number: %s; Text: %s)', ucfirst($person), $sms->getNumber(), $sms->getText());

		$comment = array();

		$comment[] = 'STATUS_SMS';
		$comment[] = $sms->isCustomerSMS() ? $helper->__('Customer') : $helper->__('Administrator');
		$comment[] = $error ? 0 : 1;
		$comment[] = $message;

		$comment = implode(';', $comment);

		// add comment
		$customData = $sms->getCustomData();

		$orderKey	 = Telecom_SMSNotifier_Helper_Data::EVENT_NEW_ORDER;
		$invoiceKey	 = Telecom_SMSNotifier_Helper_Data::EVENT_NEW_INVOICE;
		$shipmentKey = Telecom_SMSNotifier_Helper_Data::EVENT_NEW_SHIPMENT;

		if (isset($customData[$orderKey]))
		{
			$order = $customData[$orderKey];

			$order->addStatusHistoryComment($comment);
			$order->save();
		}

		if (isset($customData[$invoiceKey]))
		{
			$invoice = $customData[$invoiceKey];

			$invoice->addComment($comment);
			$invoice->save();
		}

		if (isset($customData[$shipmentKey]))
		{
			$shipment = $customData[$shipmentKey];

			$shipment->addComment($comment);
			$shipment->save();
		}

		return $this;
	}


	/**
	 * Get SMS logger.
	 *
	 * @return Telecom_SMSNotifier_Helper_SmsLog
	 */
	protected function _getLogger()
	{
		return Mage::helper('smsnotify/sMSLog');
	}


	/**
	 * Get standard config.
	 *
	 * @return Telecom_SMSNotify_Model_Config
	 */
	protected function _getConfig()
	{
		return Mage::getSingleton('smsnotify/config');
	}

}