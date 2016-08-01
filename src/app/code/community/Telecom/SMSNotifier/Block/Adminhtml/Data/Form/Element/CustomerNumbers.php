<?php
/**
 * Send form.
 *
 * @category    Telecom
 * @package     Telecom_SMSNotifier
 */
class Telecom_SMSNotifier_Block_Adminhtml_Data_Form_Element_CustomerNumbers extends Varien_Data_Form_Element_Multiselect
{

    public function getElementHtml()
    {
    	$config = Mage::getSingleton('smsnotify/config');

    	$helper = Mage::helper('smsnotify');
    	$url    = Mage::helper("adminhtml")->getUrl('*/*/getnumbers');
    	$conurl = Mage::helper("adminhtml")->getUrl('adminhtml/system_config/edit/section/smsnotify');

    	$length = Mage::getStoreConfig("smsnotify/general/min_length_with_prefix");
		$prefix	= $config->getDialPrefix(Mage::getStoreConfig("smsnotify/general/local_country"));

	    $html  = "
	    	<input type=\"text\" id=\"search\" name=\"q\" value=\"\" class=\"input-text\" maxlength=\"128\" autocomplete=\"off\" /><br />
			<p class=\"note\"><span>"
				.$helper->__("Enter phone number or customer's name.")."<br />"
				.$helper->__("There will be added dial prefix '%s' to numbers which are %s digits length or shorter.", $prefix, $length).' '
				.$helper->__('Change this <a href="%s">here</a>.', $conurl).
		    "</span></p>
	    	<div id=\"search_autocomplete\" class=\"search-autocomplete\" style=\"display:none;\"></div>
	    	<input type=\"hidden\" id=\"customer_numbers\" name=\"customer_numbers\" class=\"required-entry\" value=\"\" />
	    	<p id=\"table-numbers-empty-message\">
	    		<small>".$helper->__("Number list is empty. Enter phone number and click to suggest list, please.")."</small>
	    	</p>
	    	<table id=\"table-numbers\" class=\"empty-table\">
	    		<tbody id=\"table-numbers-tbody\">
	    			<tr id=\"table-numbers-template\" style=\"display:none\">
	    				<td><span class=\"phone\" style=\"display:none\"></span><span class=\"label\"></span></td>
	    				<td class=\"last\"><button type=\"button\" onclick=\"removeNumber(this);\" class=\"delete\" ><span></span></button></td>
	    			</tr>
	    		</tbody>
	    	</table>
	       	<script type=\"text/javascript\">
	        //<![CDATA[
	           	var searchForm = new Varien.searchForm('edit_form', 'search', '');
	           	function removeNumber(element) {
	           		var row = $(element).up('TR');
	           		row.remove();
	           		updateValues();
    			}
    			function updateValues() {
    				var vals = [];
    				$$('#table-numbers span.phone').each(function(span) {
    					var val = '';
    					if (span.up('TR').id != 'table-numbers-template')
    						val = span.innerHTML;
    					if (span.id)
    						val = val + ',' + span.id.replace('customer-', '');
    					if (val)
    						vals.push(val);
    				});
					if (vals.length == 0)
					{
						$('table-numbers').addClassName('empty-table');
						$('table-numbers-empty-message').removeClassName('hidden');
					}
					else
					{
						$('table-numbers').removeClassName('empty-table');
						$('table-numbers-empty-message').addClassName('hidden');
					}
					$('customer_numbers').value = vals.join(';');
					$('search').value = '';
    			}
	           	new Ajax.Autocompleter(
	           		searchForm.field,
	           		'search_autocomplete',
	           		'".$url."',
	           		{
	               		paramName: searchForm.field.name,
	               		method: 'get',
	               		minChars: 2,
	               		updateElement: function(element) {

	               			if(typeof String.prototype.trim !== 'function') {
  								String.prototype.trim = function() {
    								return this.replace(/^\s+|\s+$/g, '');
  								}
							}

	                		var parts  = element.innerHTML.split(',');

	                		var name   = (parts.length == 2) ? parts[0] : '';
	                		var number = (parts.length == 2) ? parts[1] : parts[0];

	                		name   = new String(name);
	                		number = new String(number);

	                		name   = name.trim();
	                		number = number.trim();

	                		var alreadyExist = $$('#table-numbers tr span.phone').any(function(span) {
	                			return (span.innerHTML == number);
	    					});
	    					var allowed = !element.hasClassName('not-allowed');

	    					if (!alreadyExist && allowed)
	    					{
	                			var newrow = $('table-numbers-template').cloneNode(true);
	                			newrow.id = '';
	                			newrow.style.display = '';

	                			newrow.select('span.phone').each(function(span) {
	                				span.innerHTML = number;
	                				span.id = element.id;
    							});
    							newrow.select('span.label').each(function(span) {
	                				span.innerHTML = element.innerHTML;
    							});

	                			$('table-numbers-tbody').appendChild(newrow);
	                		}

	                		updateValues();

	    				},
	                	onShow : function(element, update) {
	                    	if(!update.style.position || update.style.position=='absolute') {
	                       		update.style.position = 'absolute';
	                       		Position.clone(element, update, {
	                           		setHeight: false,
	                           		offsetTop: element.offsetHeight
	                       		});
	                    	}
	                    	Effect.Appear(update,{duration:0});
	                    	//if (update.select('ul li').length > 1)
	                    	//	update.removeClassName('empty-list');
	                    	//else
	                    	//	update.addClassName('empty-list');
	                	}

	            	}
	            );
	        //]]>
	        </script>";

       	return $html;
    }


}
