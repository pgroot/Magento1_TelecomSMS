<?php
/*<BUILD_TAG>*/

// no direct access
require_once 'httphelper.php';

/**
 * Allows simple access to Telecom SMS Services API
 */
class ASmsService
{
    /**
     * Telecom SMS Service API URL
     */
    private static $accessTokenUrl = 'https://oauth.api.189.cn/emp/oauth2/v3/access_token';
    private static $templateSMSUrl = 'http://api.189.cn/v2/emp/templateSms/sendSms';

    private $errors = array();

    private static $instance = null;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    { }

    /**
     * Returns a singleton instance of ASmsService class
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new ASmsService();
        }

        return self::$instance;
    }

    /**
     * Adds error to the list of errors
     */
    protected function setError($error)
    {
        $this->errors[] = $error;
    }

    /**
     * Returns the last error message
     */
    public function getError()
    {
        return end($this->errors);
    }

    /**
     * Returns all the errors concatenated with the $newline string
     */
    public function getErrors($newline = "\n")
    {
        return implode($newline, $this->errors);
    }

    /**
     * 获取access token
     *
     * @param string $apiKey SMS Service APP id
     * @param string $secretKey SMS Service Secret Key
     * @return mixed Associative array with credit info on success or false on error.
     *               Array contains following keys: access_token, expires_in
     */
    public function getCreditInfo($apiKey,$secretKey)
    {
        if (empty($apiKey) || empty($secretKey)) {
            $this->setError('Username or API key not set.');
            return false;
        }

        // Prepare request
        $data = array(
            'grant_type' => 'client_credentials',
            'app_id' => $apiKey,
            'app_secret' => $secretKey
        );

        // Send request
        $http = AHttpHelper::getInstance();
        $out = $http->sendRequest(self::$accessTokenUrl, $data);
        if (!$out) {
            $this->setError('Could not send the HTTP request');
            return false;
        }

        // Parse the data
        $data = json_decode($out->content);
        if (!$data) {
            $this->setError('Wrong response from server');
            return false;
        }

        // Check response
        if ($data->res_code != 0) {
            $this->setError($data->err.': '.$data->res_message);
            return false;
        }

        // Return the credit
        $result = array(
            'access_token' => $data->access_token,
            'expires_in' => $data->expires_in
        );

        return $result;
    }

    /**
     * Sends an SMS message from user's account
     *
     * @param string $apikey Service APP id
     * @param string $accessToken  see:http://open.189.cn/index.php?m=content&c=index&a=lists&catid=62
     * @param string $to
     * @param string $template_id
     * @param string $template_param see:http://open.189.cn/index.php?m=api&c=index&a=show&id=858#9
     * @param bool $allowUnicode
     * @return bool
     */
    public function sendMessage($apikey, $accessToken, $to, $template_id,$template_param, $allowUnicode = false)
    {
        if (empty($apikey) || empty($accessToken) || empty($to)) {
            $this->setError('Some parameters not set. api id ,access token or send to number');
            return false;
        }

        // Fix the phone number
        $to = ltrim($to, '+0');
        $to = str_replace(' ', '', $to);

        // Validate the phone number
        if ((strlen($to) > 16)) {
            $this->setError('Incorrect recipient phone number format.');
            return false;
        }

        // Prepare request
        $data = array(
            'app_id' => $apikey,
            'access_token' => $accessToken,
            'acceptor_tel' => $to,
            'template_id' => $template_id,
            'template_param' => $template_param,
            'timestamp' => Mage::getModel('core/date')->date('Y-m-d H:i:s')
        );

        // Send request
        $http = AHttpHelper::getInstance();
        $out = $http->sendRequest(self::$templateSMSUrl, $data);
        if (!$out) {
            $this->setError('Could not send the HTTP request');
            return false;
        }

        // Parse the data
        $data = json_decode($out->content);
        if (!$data) {
            $this->setError('Wrong response from server'.$data->res_message);
            return false;
        }

        // Check response
        if (!$data->idertifier) {
            $this->setError($data->res_code.': '.$data->res_message.',template_id:'.$template_param);
            return false;
        }

        // Message sent successfully
        return true;
    }

    /**
     *
     *
     * @param $apikey
     * @param $accessToken
     * @param $to
     * @param $template_id
     * @param $template_param
     * @return bool
     */
    public function sendOwnMessage($apikey, $accessToken, $to, $template_id,$template_param)
    {
        return $this->sendMessage($apikey, $accessToken, $to, $template_id,$template_param);
    }
}