<?php
/**
 * Standard helper.
 *
 * @category Telecom
 * @package Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_Helper_Data extends Mage_Core_Helper_Abstract
{

	/**
	 * Code for event new order.
	 * Code is related to config value, do not change it, please!
	 * @var string
	 */
	const EVENT_NEW_ORDER = 'new_order';

	/**
	 * Code for event new invoice.
	 * Code is related to config value, do not change it, please!
	 * @var string
	 */
	const EVENT_NEW_INVOICE = 'new_invoice';

	/**
	 * Code for event new shipment.
	 * Code is related to config value, do not change it, please!
	 * @var string
	 */
	const EVENT_NEW_SHIPMENT = 'new_shipment';


	/**
	 * All SMS order/invoice/shipment statuses should start
	 * this mark.
	 *
	 * @var string
	 */
	const SMS_MARK = 'STATUS_SMS';


	/**
	 * Determine whether there is allowed to send message
	 * for this event.
	 *
	 * @param string $type new_order|new_invoice|new_shipment
	 * @param Mage_Core_Model_Abstract $object
	 * @return bool
	 */
	public function isEventAllowed($type, $object)
	{
            return $this->_getConfig()->isEventAllowed($type, $object->getStoreId());
	}

        /**
         * Determine whethere there is allowed to send message
         * for this event with bussines object with a shipping method.
         *
         * @param type $type
         * @param type $object
         */
        public function isShippingMethodAllowed($type, $object)
        {
            $shippingCode = $this->getShippingMethodCode($type, $object);

            return $this->_getConfig()->isShippingMethodAllowedForEvent($type, $shippingCode, $object->getStoreId());
        }

	/**
	 * Get customer's number.
	 *
	 * @param string $type new_order|new_invoice|new_shipment
	 * @param Mage_Core_Model_Abstract $object
	 */
	public function getCustomerNumber($type, $object)
	{
		// no, we do not want to notify custoemr
		if (!$this->_getConfig()->getNotifyCustomerForEvent($type, $object->getStoreId()))
			return "";

		$address = $this->_getConfig()->getUsedAddress($object->getStoreId());

		$phone = '';

		if ($address == 'billing')
		{
                    $primary   = $object->getBillingAddress();
                    $secondary = $object->getShippingAddress();
		}
		else
		{
                    $primary   = $object->getShippingAddress();
                    $secondary = $object->getBillingAddress();
		}

		$phone = $primary->getTelephone();

		if (!$phone)
                    $phone = $secondary->getTelephone();

		// add dial prefix if necessary
		if ($phone)
                    $phone = $this->_getConfig()->sanitizeNumber($phone, $object->getStoreId());

		return $phone;
	}


	/**
	 * Get country code.
	 *
	 * @param string $type new_order|new_invoice|new_shipment
	 * @param Mage_Core_Model_Abstract $object
	 */
	public function getCountryCode($type, $object)
	{
		$address = $this->_getConfig()->getUsedAddress($object->getStoreId());

		$country = '';

		if ($address == 'billing')
		{
			$primary   = $object->getBillingAddress();
			$secondary = $object->getShippingAddress();
		}
		else
		{
			$primary   = $object->getShippingAddress();
			$secondary = $object->getBillingAddress();
		}

		$country = $primary->getCountry();

		if (!$country)
			$country = $secondary->getCountry();

		return $country;
	}

        /**
         * Get shipping code (first part) from $object.
         *
         * @param string $type new_order|new_invoice|new_shipment
         * @param Mage_Core_Model_Abstract $object
         * @return string
         */
        public function getShippingMethodCode($type, $object)
        {
            switch ($type)
            {
                case self::EVENT_NEW_INVOICE:
                case self::EVENT_NEW_SHIPMENT:
                    $order = $object->getOrder();
                    break;

                case self::EVENT_NEW_ORDER:
                default:
                    $order = $object;
                    break;
            }

            return $order->getShippingMethod();
        }


	/**
	 *
	 * @param string $type new_order|new_invoice|new_shipment
	 * @param Mage_Core_Model_Abstract $object
	 */
	public function getAdminNumber($type, $object)
	{
		return $this->_getConfig()->getAdminNumberForEvent($type, $object->getStoreId());
	}


	/**
	 *
	 * @param string $type new_order|new_invoice|new_shipment
	 * @param Mage_Core_Model_Abstract $object
	 */
	public function getText($type, $object)
	{
		return $this->_getConfig()->getTextForEvent($type, $object->getStoreId());
	}


    /**
     * get sms template id
     * @param $type
     * @param $object
     * @return mixed
     */
	public function getTemplateId($type,$object) {
        return $this->_getConfig()->getTemplateIdForEvent($type, $object->getStoreId());
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

}