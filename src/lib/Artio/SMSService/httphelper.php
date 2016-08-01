<?php
/**
 * Allows simple HTTP requests calls without any external dependencies like cURL.
 * However, OpenSSL must be still installed for HTTPS requests.
 */
class AHttpHelper
{
    private $errors = array();

    private static $instance = null;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    { }

    /**
     * Returns a singleton instance of AHttpHelper class
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new AHttpHelper();
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
     * Sends HTTP request
     *
     * @param string $url Target URL
     * @param mixed $data Data for the POST request - associative array or query string with URL encoded values
     * @param string $method Method to use - 'POST' or 'GET'
     * @param array $headers Additional headers to send with the request (associative array)
     * @return mixed Response object on success or false on error. Response object has the following properties:
     *                 code - HTTP response code
     *                 header - header part of the HTTP response
     *                 content - data part of the HTTP response
     */
    public function sendRequest($url, $data = '', $method = 'POST', $headers = array())
    {
        $method = strtoupper($method);

        // Convert data array to query string
        if (is_array($data)) {
            // format --> test1=a&test2=b
            $_data = array();
            foreach ($data as $key => $val) {
                $_data[] = $key . '=' . urlencode($val);
            }
            $data = implode('&', $_data);
        }
        else if (!is_string($data)) {
            $this->setError('Unsupported data - only associative array or string is supported');
            return false;
        }

        // Check data
        if (($method == 'GET') && !empty($data)) {
            $this->setError('No data allowed for GET requests');
            return false;
        }

        // Content type
        if (!empty($data)) {
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            }
        }

        // Parse the given URL
        $url = parse_url($url);
        if (!isset($url['scheme'])) {
            $this->setError('No protocol in the URL');
            return false;
        }

        // Extract host and path
        if (!isset($url['host'])) {
            $this->setError('No host in the URL');
            return false;
        }

        $host = $url['host'];
        $path = isset($url['path']) ? $url['path'] : '/';

        // Prepare host and port to connect to
        $connhost = $host;
        $port = 80;

        // Workaround for some PHP versions, where fsockopen can't connect to
        // 'localhost' string on Windows servers
        if ($connhost == 'localhost') {
            $connhost = gethostbyname('localhost');
        }

        // Handle scheme
        if ($url['scheme'] == 'https') {
            $connhost = 'ssl://'.$connhost;
            $port = 443;
        }
        else if ($url['scheme'] != 'http') {
            $this->setError('Unsupported protocol: '.$url['scheme']);
            return false;
        }

        // Open a socket connection
        $errno = null;
        $errstr = null;
        $fp = @fsockopen($connhost, $port, $errno, $errstr, 5);
        if (!is_resource($fp) || ($fp === false)) {
            $this->setError('Could not connect to host: '.$connhost.':'.$port);
            return false;
        }

        // Handle query string from URL
        $query = '';
        if (isset($url['query'])) {
            $query = '?'.$url['query'];
        }

        // Prepare the request
        $req = "{$method} {$path}{$query} HTTP/1.1\r\n";
        $req .= "Host: {$host}\r\n";
        if ($method == 'POST') {
            $req .= "Content-Length: ".strlen($data)."\r\n";
        }
        if (is_array($headers)) {
            foreach ($headers as $key => $val) {
                $header = $key.': '.trim($val)."\r\n";
                $req .= $header;
            }
        }
        $req .= "Connection: close\r\n\r\n";
        if (!empty($data)) {
            $req .= $data;
        }

        // Check the fputs, sometimes fsockopen doesn't fail, but fputs doesn't work
        if (!@fputs($fp, $req)) {
            @fclose($fp);
            $this->setError('Could not send data to host: '.$connhost.':'.$port);
            return false;
        }

        $result = '';
        while(!feof($fp)) {
            // receive the results of the request
            $result .= fgets($fp, 1024);
        }

        // close the socket connection:
        fclose($fp);

        // split the result header from the content
        $result = explode("\r\n\r\n", $result, 2);

        $header = isset($result[0]) ? $result[0] : '';
        $content = isset($result[1]) ? $result[1] : '';

        // Build response object
        $response = new stdClass();
        $response->header = $header;
        $response->content = $content;

        // Get the response code from header
        $headerLines = explode("\n", $response->header);
        $header1 = explode(' ', trim($headerLines[0]));
        $code = intval($header1[1]);
        $response->code = $code;

        // Handle chunked transfer if needed
        if (strpos(strtolower($response->header), 'transfer-encoding: chunked') !== false) {
            $parsed = '';
            $left = $response->content;

            while (true) {
                $pos = strpos($left, "\r\n");
                if ($pos === false) {
                    return $response;
                }

                $chunksize = substr($left, 0, $pos);
                $pos += strlen("\r\n");
                $left = substr($left, $pos);

                $pos = strpos($chunksize, ';');
                if ($pos !== false) {
                    $chunksize = substr($chunksize, 0, $pos);
                }
                $chunksize = hexdec($chunksize);

                if ($chunksize == 0) {
                    break;
                }

                $parsed .= substr($left, 0, $chunksize);
                $left = substr($left, $chunksize + strlen("\r\n"));
            }

            $response->content = $parsed;
        }

        // Return the response object
        return $response;
    }
}
