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
    private static $serviceUrl = 'http://www.Telecom.net/index.php?option=com_Telecomsms&controller=api';

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
     * Returns current credit info - available credit and estimated credit exhaustion in hours
     *
     * @param string $username SMS Service API username
     * @param string $apiKey SMS Service API key
     * @return mixed Associative array with credit info on success or false on error.
     *               Array contains following keys: credit, exhaustion
     */
    public function getCreditInfo($username, $apiKey)
    {
        if (empty($username) || empty($apiKey)) {
            $this->setError('Username or API key not set.');
            return false;
        }

        // Prepare request
        $data = array(
            'task' => 'get_credit_info',
            'username' => $username,
            'api_key' => $apiKey
        );

        // Send request
        $http = AHttpHelper::getInstance();
        $out = $http->sendRequest(self::$serviceUrl, $data);
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
        if (!$data->success) {
            $this->setError($data->err.': '.$data->msg);
            return false;
        }

        // Return the credit
        $result = array(
            'credit' => $data->credit,
            'exhaustion' => $data->exhaustion
        );

        return $result;
    }

    /**
     * Sends an SMS message from user's account
     *
     * @param string $username SMS Service API username
     * @param string $apiKey SMS Service API key
     * @param string $to Recipient phone number in international format, max 16 characters, no spaces and no leading plus or zeroes
     * @param string $text Text of the message (will be split to several messages if longer than 160 characters,
     *                     some characters are counted as two: ^, {, }, \, [, ], ~, |, €, newline char)
     * @param string $allowUnicode Whether Unicode SMS is allowed. If enabled, SMS with special characters will be sent as Unicode,
     *                             limit is 70 characters. If disabled, Unicode characters will be replaced with ASCII or removed
     *                             if replacement is not possible. If enabled and SMS doesn't contain any special characters,
     *                             SMS will be sent normally (160 chars limit).
     * @return bool True on success or false on error
     */
    public function sendMessage($username, $apiKey, $to, $text, $allowUnicode = false)
    {
        if (empty($username) || empty($apiKey) || empty($to) || empty($text)) {
            $this->setError('Some parameters not set.');
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
            'task' => 'send_sms',
            'username' => $username,
            'api_key' => $apiKey,
            'to' => $to,
            'text' => $text,
            'allowUnicode' => ($allowUnicode ? '1' : '0')
        );

        // Send request
        $http = AHttpHelper::getInstance();
        $out = $http->sendRequest(self::$serviceUrl, $data);
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
        if (!$data->success) {
            $this->setError($data->err.': '.$data->msg);
            return false;
        }

        // Message sent successfully
        return true;
    }

    /**
     * Sends an SMS message from user's account to his own phone number (set in SMS Services)
     *
     * @param string $username SMS Service API username
     * @param string $apiKey SMS Service API key
     * @param string $text Text of the message (will be split to several messages if longer than 160 characters,
     *                     some characters are counted as two: ^, {, }, \, [, ], ~, |, €, newline char)
     * @return bool True on success or false on error
     */
    public function sendOwnMessage($username, $apiKey, $text)
    {
        if (empty($username) || empty($apiKey) || empty($text)) {
            $this->setError('Some parameters not set.');
            return false;
        }

        // Prepare request
        $data = array(
            'task' => 'send_own_sms',
            'username' => $username,
            'api_key' => $apiKey,
            'text' => $text
        );

        // Send request
        $http = AHttpHelper::getInstance();
        $out = $http->sendRequest(self::$serviceUrl, $data);
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
        if (!$data->success) {
            $this->setError($data->err.': '.$data->msg);
            return false;
        }

        // Message sent successfully
        return true;
    }

    /**
     * Loads and returns the array of available credit amounts for purchase and the purchase URL
     *
     * @return array Associative array of [creditValues] => Credit amounts available for purchase ([value] => text)
     *                                    [link] => Purchase URL
     */
    public function getCreditPurchaseInfo()
    {
        // Prepare request
        $data = array(
            'task' => 'get_credit_purchase_info'
        );

        // Send request
        $http = AHttpHelper::getInstance();
        $out = $http->sendRequest(self::$serviceUrl, $data);
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
        if (!$data->success) {
            $this->setError($data->err.': '.$data->msg);
            return false;
        }

        // Return credit values
        $result = array(
            'creditValues' => $data->creditValues,
            'link' => $data->link
        );

        return $result;
    }
}