<?php
/**
 * Send form.
 *
 * @category    Telecom
 * @package     Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_Block_Adminhtml_Send_Form extends Mage_Adminhtml_Block_Widget_Form
{


	/**
	 * Prepare form.
	 */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
        	'name'   => 'send_form',
        	'id'     => 'edit_form',
        	'action' => $this->getData('action'), 'method' => 'post')
        );

        $fieldset = $form->addFieldset('sendsms_fieldset', array(
        	'legend' => Mage::helper('smsnotify')->__('Telephone Number List'),
        	'class'  => 'fieldset'
        ));

        $fieldset->addType('customer_numbers', 'Telecom_SMSNotifier_Block_Adminhtml_Data_Form_Element_CustomerNumbers');
        $fieldset->addField('customer_numbers', 'customer_numbers', array(
        	'name'  => 'customer_numbers',
        	'label' => Mage::helper('smsnotify')->__('Telephone Number List')
        ));

        $fieldset->addField('admin', 'selectex', array(
        	'name'	  => 'admin',
        	'label'	  => Mage::helper('smsnotify')->__('Send to administrator'),
        	'options' => Mage::getModel('smsnotify/system_config_source_sendToAdmin')->toOptions()
        ));


        $fieldset2 = $form->addFieldset('textsms_fieldset', array(
        	'legend' => Mage::helper('smsnotify')->__('SMS Content'),
        	'class'  => 'fieldset-wide'
        ));

        $fieldset2->addType('smstextarea', 'Varien_Data_Form_Element_Smstextarea');

        $fieldset2->addField('sms_template_id', 'text', array(
            'name'		=> 'sms_template_id',
            'label'		=> Mage::helper('smsnotify')->__('Message Template ID'),
            'required'	=> true
        ));

        $fieldset2->addField('sms_text', 'smstextarea', array(
        	'name'		=> 'sms_text',
        	'label'		=> Mage::helper('smsnotify')->__('Message Param'),
        	'required'	=> true
        ));





        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }


}
