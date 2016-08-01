<?php
/**
 * Form text element
 *
 * @category   Varien
 * @package    Varien_Data
 */
class Varien_Data_Form_Element_Smstextarea extends Varien_Data_Form_Element_Textarea
{
    public function __construct($attributes=array())
    {
        parent::__construct($attributes);
        $this->setType('textarea');
        $this->setExtType('textarea');
        $this->setRows(2);
        $this->setCols(15);
    }


    /**
     *
     * @return string
     */
    public function getHtmlId()
    {
    	$form = $this->getForm();

    	if ($form)
    		return $form->getHtmlIdPrefix() . $this->getData('html_id') . $form->getHtmlIdSuffix();
    	else
    		return $this->getData('html_id');
    }


    /**
     *
     * @return string
     */
    public function getName()
    {
    	$form = $this->getForm();

    	if ($form && $suffix = $this->getForm()->getFieldNameSuffix())
    		$name = $this->getForm()->addSuffixToName($name, $suffix);
    	else
    		$name = $this->getData('name');

    	return $name;
    }

    public function getHtmlAttributes()
    {
        return array('title', 'class', 'style', 'onclick', 'onchange', 'rows', 'cols', 'readonly', 'disabled', 'onkeyup', 'tabindex');
    }

    public function getElementHtml()
    {
    	$helper = Mage::helper('smsnotify');

    	$conurl = Mage::helper("adminhtml")->getUrl('adminhtml/system_config/edit/section/smsnotify');

    	$allowedUnicode = (int) Mage::getSingleton('smsnotify/config')->isUnicodeAllowed();

    	$id		  = $this->getHtmlId();
    	$remain   = $this->getHtmlId().'-remain';
    	$chars    = $this->getHtmlId().'-chars';
    	$messages = $this->getHtmlId().'-messages';

        $this->addClass('textarea');
        $html = '';

        //$html .= '<span class="'.$id.'-static-value">'.$helper->__('Remain <b><span class="%s"></span></b> characters to new message.', $remain).'</span>';
        //$html .= '<span class="'.$id.'-dynamic-value">'.$helper->__('Remain about <b><span class="%s"></span></b> characters to new message.', $remain).'</span>';
        //$html .= '<br />';
        $html .= '<p class="button-bar">';
        $html .= '<span>'.$helper->__('Variables:').'</span>';
        if ($id == 'sms_text')
        {
        	$html .= '<a href="javascript:void(0)" class="'.$id.'-variable-button" title="'.$helper->__('Append customer firstname to message').'" type="button"><span class="text">'.Telecom_SMSNotifier_Model_Sms_Template::CUSTOMER_FIRSTNAME.'</span>'.$helper->__('Customer Firstname').'</a>';
        	$html .= '<a href="javascript:void(0)" class="'.$id.'-variable-button" title="'.$helper->__('Append customer lastname to message').'" type="button"><span class="text">'.Telecom_SMSNotifier_Model_Sms_Template::CUSTOMER_LASTNAME.'</span>'.$helper->__('Customer Lastname').'</a>';
        	$html .= '<a href="javascript:void(0)" class="'.$id.'-variable-button" title="'.$helper->__('Append customer email to message').'" type="button"><span class="text">'.Telecom_SMSNotifier_Model_Sms_Template::CUSTOMER_EMAIL.'</span>'.$helper->__('Customer Email').'</a>';
        }
        else
        {
        	$html .= '<a href="javascript:void(0)" class="'.$id.'-variable-button" title="'.$helper->__('Append customer firstname to message').'" type="button"><span class="text">'.Telecom_SMSNotifier_Model_Sms_Template::SALE_FIRSTNAME.'</span>'.$helper->__('Customer Firstname').'</a>';
        	$html .= '<a href="javascript:void(0)" class="'.$id.'-variable-button" title="'.$helper->__('Append customer lastname to message').'" type="button"><span class="text">'.Telecom_SMSNotifier_Model_Sms_Template::SALE_LASTNAME.'</span>'.$helper->__('Customer Lastname').'</a>';
        	$html .= '<a href="javascript:void(0)" class="'.$id.'-variable-button" title="'.$helper->__('Append customer email to message').'" type="button"><span class="text">'.Telecom_SMSNotifier_Model_Sms_Template::SALE_EMAIL.'</span>'.$helper->__('Customer Email').'</a>';
        	$html .= '<a href="javascript:void(0)" class="'.$id.'-variable-button amount" title="'.$helper->__('Append total amount to message').'" type="button"><span class="text">'.Telecom_SMSNotifier_Model_Sms_Template::SALE_AMOUNT.'</span>'.$helper->__('Amount').'</a>';
        	$html .= '<a href="javascript:void(0)" class="'.$id.'-variable-button invoice" title="'.$helper->__('Append invoice increment id to message').'" type="button"><span class="text">'.Telecom_SMSNotifier_Model_Sms_Template::SALE_INVOICE_NR.'</span>'.$helper->__('Invoice Nr.').'</a>';
        	$html .= '<a href="javascript:void(0)" class="'.$id.'-variable-button shipment" title="'.$helper->__('Append shipment increment id to message').'" type="button"><span class="text">'.Telecom_SMSNotifier_Model_Sms_Template::SALE_SHIPMENT_NR.'</span>'.$helper->__('Shipment Nr.').'</a>';
        	$html .= '<a href="javascript:void(0)" class="'.$id.'-variable-button order" title="'.$helper->__('Append order increment id to message').'" type="button"><span class="text">'.Telecom_SMSNotifier_Model_Sms_Template::SALE_ORDER_NR.'</span>'.$helper->__('Order Nr.').'</a>';
        }
       	//$html .= '<button class="'.$id.'-variable-button" title="'.$helper->__('Append customer firstname to message').'" type="button"><span class="text">{{firstname}}</span>'.$helper->__('Customer Firstname').'</button>';
        //$html .= '<button class="'.$id.'-variable-button" title="'.$helper->__('Append customer lastname to message').'" type="button"><span class="text">{{lastname}}</span>'.$helper->__('Customer Lastname').'</button>';
        //$html .= '<button class="'.$id.'-variable-button" title="'.$helper->__('Append customer email to message').'" type="button"><span class="text">{{email}}</span>'.$helper->__('Customer Email').'</button>';
        $html .= '</p>';
        $html .= '<textarea id="'.$id.'" name="'.$this->getName().'" '.$this->serialize($this->getHtmlAttributes()).' >';
        $html .= $this->getEscapedValue();
        $html .= "</textarea>";
        //$html .= '<span class="'.$id.'-static-value">'.$helper->__('You have written <b><span class="%s"></span></b> character(s).', $chars).'</span> ';
        //$html .= '<span class="'.$id.'-dynamic-value">'.$helper->__('You have written about <b><span class="%s"></span></b> character(s).', $chars).'</span> ';
        //$html .= '<span class="'.$id.'-static-value">'.$helper->__('Text will be sended as <b><span class="%s"></span></b> message(s).', $messages).'</span>';
        //$html .= '<span class="'.$id.'-dynamic-value">'.$helper->__('Text will be sended perhaps as <b><span class="%s"></span></b> message(s).', $messages).'</span>';
        //$html .= '<br class="'.$id.'-dynamic-value" /><span class="'.$id.'-dynamic-value">'.$helper->__('Keep in mind that if you use variables then the values on counters above are only estimates. Really length of message depends on values of those variables. The texts which are longer than <b>160</b> chars for standard message or <b>70</b> chars for Unicode message will be splitted to more messages.').'</span>';
        $html .= '<br />';
        //$html .= '<span class="'.$id.'-ascii"><i>'.$helper->__('Note some special chars (ex.: ^, {, }, \, [, ], ~, |, , newline char) may cost 2 chars.').'</i></span><br class="'.$id.'-ascii" />';
        //$html .= '<span class="'.$id.'-noascii-nounicode"><b>'.$helper->__('You are using Unicode chars, but you did not allow this. All Unicode characters will be replaced with ASCII or removed if replacement is not possible. You can allow Unicode messages <a href="%s">here</a>.', $conurl).'</b></span>';
        $html .= '<span class="'.$id.'-noascii-unicode"><b>'.$helper->__('Unicode message.').'</b></span>';
        $html .= $this->getAfterElementHtml();

		$html .= "
			<script type=\"text/javascript\">
			//<![CDATA[

				function ${id}_smstextarea_reloading() {

					var regex = new RegExp('{{[^}]*}}');

					var recompute = function(event) {

						var allowedUnicode = ${allowedUnicode};

						var count = 0;
						var value = $('$id').value;
						var ch2   = '^{}[]~|';

						var hide = function(el) { el.hide(); };
						var show = function(el) { el.show(); };

						if (regex.test(value)) {
							$$('.${id}-static-value').each(hide);
							$$('.${id}-dynamic-value').each(show);
						} else {
							$$('.${id}-static-value').each(show);
							$$('.${id}-dynamic-value').each(hide);
						}

						var onlyAscii = /^[\\x00-\\x7F]*$/.test(value);

						for (var i=0; i<value.length; i++) {
							var ch  = value.charAt(i);
							var chc = value.charCodeAt(i);

							if (allowedUnicode && !onlyAscii)
							{
								count++;
							}
							else
							{
								if (chc == 10 || chc == 92 || ch2.indexOf(ch) > -1)
									count = count+2;
								else
									count++;
							}
    					}

    					var maxchars = (allowedUnicode && !onlyAscii) ? 70 : 160;


    					if (onlyAscii)
    					{
    						$$('.${id}-ascii').each(show);
    						$$('.${id}-noascii-nounicode').each(hide);
    						$$('.${id}-noascii-unicode').each(hide);
    					}
    					else
    					{
    						if (allowedUnicode)
    						{
    							$$('.${id}-ascii').each(hide);
								$$('.${id}-noascii-nounicode').each(hide);
    							$$('.${id}-noascii-unicode').each(show);
    						}
    						else
    						{
    							$$('.${id}-ascii').each(show);
    							$$('.${id}-noascii-nounicode').each(show);
    							$$('.${id}-noascii-unicode').each(hide);
    						}
    					}

    					$$('.$chars').each(function(el) { el.innerHTML = count; });
    					$$('.$messages').each(function(el) { el.innerHTML = Math.floor((count-1) / maxchars + 1) });
						$$('.$remain').each(function(el) { el.innerHTML = maxchars - (count % maxchars); });
					};

					$('$id').observe('change', recompute);
					$('$id').observe('keyup', recompute);
					recompute(null);

					$$('.${id}-variable-button').each(function(button) {
						button.observe('click', function(event) {
							var val = button.select('span.text').first().innerHTML;
							$('$id').value = $('$id').value + val;
							recompute(event);
						});
					});

					$$('.${id}-variable-button').last().addClassName('last');
				}

				document.observe('dom:loaded', function(event) {
					${id}_smstextarea_reloading();
				});
			//]]>
			</script>
		";

        return $html;
    }

}