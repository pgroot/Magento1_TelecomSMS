<?php
/**
 * Include extern library
 */
require_once Mage::getBaseDir() . DS . 'lib' . DS . 'Telecom' . DS . 'SMSService' . DS . 'smsservice.php';

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
        if (!$sms || !($sms instanceof Telecom_SMSNotifier_Model_SMS)) {
            Mage::log(__CLASS__ . ":" . __METHOD__ . ": SMS is not set or is not instance of Telecom_SMSNotifier_Model_SMS.", Zend_Log::WARN);
            return false;
        }

        $apikey = $this->getApikey();
        $secretKey = $this->getSecretKey();

        if (!$apikey) {
            $sms->addCustomData('error_message', $this->_helper()->__('API Key is not set. Check it in the configuration, please.'));
            Mage::dispatchEvent('smsnotifier_error', array('sms' => $sms));
            return false;
        }
        if (!$secretKey) {
            $sms->addCustomData('error_message', $this->_helper()->__('Secret Key is not set. Check it in the configuration, please.'));
            Mage::dispatchEvent('smsnotifier_error', array('sms' => $sms));
            return false;
        }

        if (!$sms->getNumber()) {
            $sms->addCustomData('error_message', $this->_helper()->__('Number is not set.'));
            Mage::dispatchEvent('smsnotifier_error', array('sms' => $sms));
            return false;
        }

        if (!preg_match('/^[0-9]{1,16}$/', $sms->getNumber())) {
            $sms->addCustomData('error_message', $this->_helper()->__("Number '%s' is not valid.", $sms->getNumber()));
            Mage::dispatchEvent('smsnotifier_error', array('sms' => $sms));
            return false;
        }

        if (!$this->_getConfig()->isNumberAllowed($sms->getNumber(), $sms->getStoreId())) {
            $sms->addCustomData('error_message', $this->_helper()->__("It is forbidden to send SMS to number '%s'. If you think that it is bad then check your number filters definitions in the configuration of SMSNotifyForMagento, please.", $sms->getNumber()));
            Mage::dispatchEvent('smsnotifier_error', array('sms' => $sms));
            return false;
        }

        if (!$this->_getConfig()->isCountryAllowed($sms->getCountry(), $sms->getStoreId())) {
            $sms->addCustomData('error_message', $this->_helper()->__("It is forbidden to send SMS to country '%s'. If you think that it is bad then check your country filter definitions in the configuration of SMSNotifyForMagento, please.", $sms->getCountry()));
            Mage::dispatchEvent('smsnotifier_error', array('sms' => $sms));
            return false;
        }

        $allowUnicode = $this->_getConfig()->isUnicodeAllowed($sms->getStoreId());

        Mage::dispatchEvent('smsnotifier_before_sending', array('sms' => $sms));

        $storeId = $sms->getStoreId();
        $cache = Mage::getSingleton('core/cache');
        $key = 'telecom_access_token_' . $storeId;
        $access_token = $cache->load($key);
        if (!$access_token) {
            $service = ASmsService::getInstance();
            $result = $service->getCreditInfo($apikey, $secretKey);
            if ($result === false) {
                $sms->addCustomData('error_message', $this->_helper()->__('fetch access token error.'));
                Mage::dispatchEvent('smsnotifier_error', array('sms' => $sms));
                return false;
            }
            $access_token = $result['access_token'];
            $cache->save($access_token, $key, array(), 3600 * 2);
        }

        $service = ASmsService::getInstance();
        $result = $service->sendMessage($apikey, $access_token, $sms->getNumber(), $sms->getTemplateId(), $sms->getText(), $allowUnicode);

        if ($result) {
            $sms->addCustomData('error_message', null);
            Mage::dispatchEvent('smsnotifier_after_sending', array('sms' => $sms));
            return true;
        } else {
            $sms->addCustomData('error_message', $service->getError());
            Mage::dispatchEvent('smsnotifier_error', array('sms' => $sms));
            return false;
        }
    }


    /**
     * Determine whether APP id and APP Secret Key is valid or not.
     *
     * @return string
     */
    public function testCredentials()
    {
        $apikey = $this->getApikey();
        $secretKey = $this->getSecretKey();
        if (!$apikey)
            return $this->_helper()->__('API Key is not set.');
        if (!$secretKey) {
            return $this->_helper()->__('Secret Key is not set.');
        }

        $result = $this->getCreditInfo();

        if (!$result)
            return $this->_helper()->__('You have not configured API username or API key properly.');
        else
            return "";
    }


    /**
     * Returns current access_token info
     *
     * @return mixed Associative array with credit info on success or false on error.
     *               Array contains following keys: access_token, expires_id,created_at
     */
    public function getCreditInfo()
    {
        $apikey = $this->getApikey();
        $secretKey = $this->getSecretKey();
        $storeId = Mage::app()->getStore()->getId();

        $cache = Mage::getSingleton('core/cache');
        $key = 'telecom_access_token_result_' . $storeId;
        $result = json_decode($cache->load($key), true);
        if (empty($result)) {
            $service = ASmsService::getInstance();
            $result = $service->getCreditInfo($apikey, $secretKey);
            if ($result !== false && is_array($result)) {
                $result['created_at'] = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                $cache->save(json_encode($result), $key, array(), intval($result['expires_in']) - 600);
            }
        }
        return $result;
    }

    /**
     * @return mixed
     */
    public function getSecretKey()
    {

        return Mage::getStoreConfig('smsnotify/credentials/apisecretkey');
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