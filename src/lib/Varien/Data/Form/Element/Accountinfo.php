<?php
/**
 *
 *
 * @category   Varien
 * @package    Varien_Data
 */
class Varien_Data_Form_Element_Accountinfo extends Varien_Data_Form_Element_Abstract
{
    public function __construct($attributes=array())
    {
        parent::__construct($attributes);
    }

    public function getHtml()
    {
    	$helper  = Mage::helper('smsnotify');
        $service = Mage::getSingleton('smsnotify/service');

        $statusError = $service->testCredentials();

        if (!$statusError)
        {
        	$info 	  = $service->getCreditInfo();
            $access_token = $info['access_token'];
            $created_at = $info['created_at'];
            $expires_in = $info['expires_in'];
            $expired_at = empty($created_at) ? '': date('Y-m-d H:i:s',strtotime($created_at) + $expires_in);

            $html  = "";
            $html .= "<tr><td></td><td colspan=\"1\"><span class=\"smscredit\">Token:&nbsp;<b>$access_token</b></span></td></tr>";
            $html .= "<tr><td></td><td colspan=\"1\"><span class=\"smscredit\">created at:&nbsp;<b>$created_at</b></span></td></tr>";
            $html .= "<tr><td></td><td colspan=\"1\"><span class=\"smscredit\">expired at:&nbsp;<b>$expired_at</b></span></td></tr>";
            $html .= "<tr><td></td><td colspan=\"1\">";
        }
        else
        {
        	$statusError = $helper->__('Connection failed.').' '.$statusError;

            $html = "<tr><td></td><td colspan=\"1\"><div class=\"error\">$statusError</div></td></tr>";
        }

        return $html;
    }

    /**
     * Convert price in format XXX,XXX.XX to Magento standard format.
     *
     * @param string $price
     * @return string
     */
    protected function _formatPrice($price)
    {
    	$price = str_replace(',', '', $price);
    	$price = (float) $price;

    	return Mage::helper('core')->formatPrice($price, false);
    }

    /**
     * Convert number of hours in format XXX,XXX.XX to human readable format.
     *
     * @param string $hours
     * @return string
     */
    protected function _formatTime($hours)
    {
    	if (!$hours)
    		return "";

    	$hours = str_replace(',', '', $hours);
    	$hours = (int) $hours;

    	$days  = floor($hours / 24);
    	$hours = $hours % 24;

    	return ($days > 0) ? sprintf("%sd %sh", $days, $hours) : sprintf("%sh", $hours);
    }

    /**
     * Get Javascript code for wokring this controls.
     *
     * @return string
     */
    public function getJavascript($url, $username, $apikey)
    {
    	$html = "";

		$html .= "<script type=\"text/javascript\">";
		$html .= "//<![CDATA[";

		$html .= "
			document.observe('dom:loaded', function(event) {

				var f = document.createElement(\"form\");
				f.setAttribute('method','post');
				f.setAttribute('action','$url');
				f.setAttribute('target', '_blank');
				f.setAttribute('style','display:none');

				var i = document.createElement('input');
				i.setAttribute('type','hidden');
				i.setAttribute('name','credit');
				i.setAttribute('id','creditValues');

				var u = document.createElement('input');
				u.setAttribute('type','hidden');
				u.setAttribute('name','username');
				u.setAttribute('value','$username');

				var a = document.createElement('input');
				a.setAttribute('type','hidden');
				a.setAttribute('name','api_key');
				a.setAttribute('value','$apikey');

				f.appendChild(i);
				f.appendChild(u);
				f.appendChild(a);

				document.getElementsByTagName('body')[0].appendChild(f);

				$('smspurchasebutton').observe('click', function(event) {
					$('creditValues').value = $('smscreditamount').value;
					f.submit();
				});
			});
		";

		$html .= "//]]>";
		$html .= "</script>";

    	return $html;
    }

}
