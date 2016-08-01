<?php
/**
 * Send form.
 *
 * @category    Telecom
 * @package     Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_Block_Adminhtml_Send extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
        $this->_objectId   = 'page_id';
        $this->_blockGroup = 'smsnotify';
        $this->_controller = 'adminhtml';

        parent::__construct();

        $this->_removeButton('reset');
        $this->_removeButton('save');

        $this->_addButton('save', array(
        		'label'     => Mage::helper('smsnotify')->__('Send SMS'),
        		'onclick'   => 'editForm.submit();',
        		'class'     => 'save',
        ), 1);
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeaderText() {
    	return Mage::helper('smsnotify')->__('SMS Notifier - Send SMS');
    }

}
