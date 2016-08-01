<?php
/**
 * Form text element
 *
 * @category   Varien
 * @package    Varien_Data
 */
class Varien_Data_Form_Element_SelectDynamic extends Varien_Data_Form_Element_Select
{

	public function getAfterElementHtml()
	{
		$html = parent::getAfterElementHtml();

		$html .= '<input type="hidden" id="'.$this->getHtmlId().'-hidden" value="'.$this->getValue().'" />';

		return $html;
	}

}