<?php
/**
 * Form text element
 *
 * @category   Varien
 * @package    Varien_Data
 */
class Varien_Data_Form_Element_Numberfilters extends Varien_Data_Form_Element_Abstract
{

	/**
	 *
	 */
    public function __construct($attributes=array())
    {
        parent::__construct($attributes);
        $this->setType('text');
        $this->setExtType('textfield');
    }


    /**
     * Get element HTML
     * @return string
     */
    public function getElementHtml()
    {
    	$h = Mage::helper('smsnotify');

		$name  = $this->getName();
		$id	   = $this->getHtmlId();
		$value = $this->getValue();

      	$html = "
      		<div id=\"$id\">
                <div>
                    <select class=\"country-codes\" id=\"$id-filterTable-country\" onchange=\"$id.filterTable.changeCountry()\"></select>
                    <div class=\"numberfilters-table-wrapper\">
                    <table class=\"border\" cellpadding=\"0\" cellspacing=\"0\">
                        <tbody id=\"$id-filterTable\">
                            <tr id=\"$id-filterTable-template\" style=\"display:none\">
                                <td class=\"first\"><span class=\"prefix\"></span></td>
                                <td><span class=\"value\" onclick=\"$id.openDialog($id.filterTable, this)\">*</span>
                                <td class=\"last\"><button type=\"button\" class=\"delete\" onclick=\"$id.filterTable.removeFilter(this);\"><span></span></button>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                    <button class=\"add-button\" type=\"button\" onclick=\"$id.openDialog($id.filterTable);\">".$h->__('Add filter')."</button>
                    <input type=\"hidden\" id=\"$id-filterTable-field\" name=\"$name\" value=\"$value\" />
                </div>
            </div>
            <div class=\"numberfilters-dialog-wrapper\">
            <div id=\"$id-dialog\" class=\"numberfilters-dialog\" style=\"display:none\">
	        	<div class=\"entry-edit-head\">
	        		<a href=\"javascript:void(0)\" onclick=\"$id.closeDialog()\" class=\"close-button\"></a>
	        		<a href=\"javascript:void(0)\">".$h->__('Build filter')."</a>
	        	</div>
	            <div class=\"numberfilters-dialog-inner box\">
	            	<ul>
	            		<li>
	                		<input type=\"radio\" class=\"radio\" name=\"$id-type\" id=\"$id-dialog-type-2\" onclick=\"$id.dialog.setType(true)\" checked=\"checked\" value=\"2\" />".$h->__('Range')."
	                		<input type=\"radio\" class=\"radio radio-right\" name=\"$id-type\" id=\"$id-dialog-type-1\" onclick=\"$id.dialog.setType(false)\" value=\"1\" />".$h->__('Pattern')."
	                	</li>
	                	<li>
	                    	<div id=\"$id-dialog-digits-1\">
	                        	<select class=\"digits\" onchange=\"$id.dialog.selectDigit(this)\"></select>
	                    	</div>
	                    	<div id=\"$id-dialog-digits-2\">
	                       		<select class=\"digits\" onchange=\"$id.dialog.selectDigit(this)\"></select>
	                    	</div>
	                    </li>
	                    <li>
	                    	<button class=\"right\" type=\"button\" onclick=\"$id.saveDialog()\">".$h->__('Close')."</button>
	                    	<button type=\"button\" onclick=\"$id.saveDialog()\">".$h->__('Ok')."</button>
	                    </li>
	                  </ul>
	                </div>
	             </div>
         	</div>
         	</div>
	      	<script type=\"text/javascript\">
	        	if (typeof(NumberFilters) != 'undefined')
	        	{
	            	var $id = new NumberFilters('$id');
	            	new Draggable('$id-dialog');
	        	}
	    	</script>";

        return $html;
    }

}