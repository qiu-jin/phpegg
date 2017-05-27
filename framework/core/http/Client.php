<?php
namespace framework\core\http;

use framework\util\Xml;

//Curl
class Client
{
    private $url;
    private $body;
    private $result;
    private $method;
    private $headers = [];
    private $boundary;
    private $file_is_buffer;
    private $return_headers = false;
    private $curlopt = ['TIMEOUT' => 30];
    
    public function __construct($method, $url)
    {
        $this->url = $url;
        $this->method = $method;
    }
        
    public static function get($url)
    {
        return new self('GET', $url);
    }
    
    public static function post($url)
    {
        return new self('POST', $url);
    }
    
    public function __get($name)
    {
        switch ($name) {
            case 'status':
                return $this->getResult('status');
            case 'headers':
                return $this->getResult('headers');
            case 'body':
                return $this->getResult('body');
            case 'error':
                return $this->getResult('error');
            case 'json':
                return jsondecode($this->getResult('body'));
            case 'xml':
                return Xml::decode($this->getResult('body'));
        }
    }
    
    public function getResult($name = null)
    {
        if (!$this->result) {
            if ($this->file_is_buffer) {
                $this->body .= "--$this->boundary--\r\n";
            }
            $this->result = self::send($this->method, $this->url, $this->body, $this->headers, $this->curlopt, true);
        }
        if ($name === null) {
            return $this->result;
        }
        return isset($this->result[$name]) ? $this->result[$name] : null;
    }

    public function body($body, $type = null)
    {
        $this->body = $body;
        if ($type) {
            $this->headers[] = 'Content-Type: '.$type;
        }
        return $this;
    }
    
    public function json(array $data)
    {
        $this->body = json_encode($data);
        $this->headers[] = 'Content-Type: application/json; charset=UTF-8';
        return $this;
    }
    
    public function form(array $data, $file_is_buffer = null)
    {
        if (isset($file_is_buffer)) {
            if ($file_is_buffer) {
                $this->body = '';
                $this->boundary = uniqid();
                $this->headers[] = 'Content-Type: multipart/form-data; boundary='.$this->boundary;
                if ($data) {
                    foreach ($data as $pk => $pv) {
                        $this->body .= "--$this->boundary\r\nContent-Disposition: form-data; name=\"$pk\"\r\n\r\n$pv\r\n";
                    }
                }
            } else {
                $this->body = $data;
            }
            $this->file_is_buffer = $file_is_buffer;
        } else {
            $this->body = http_build_query($data);
            $this->headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        return $this;
    }

    public function file($name, $content, $filename = null, $mimetype = null)
    {
        if (isset($this->file_is_buffer)) {
            if ($this->file_is_buffer) {
                $this->body .= self::multipartFile($this->boundary, $name, $content, $filename, $mimetype);
            } else {
                if (substr($name, -2) === '[]') {
                    $this->body[substr($name, 0, -2)][] = self::curlFile($content, $filename, $mimetype);
                } else {
                    $this->body[$name] = self::curlFile($content, $filename, $mimetype);
                }
            }
            return $this;
        }
        throw new \Exception('Must call after form method');
    }
    
    public function stream($fp)
    {
        $this->curlopt['PUT'] = 1;
        $this->curlopt['INFILE'] = $fp;
        $this->curlopt['INFILESIZE'] = fstat($fp)['size'];
        return $this;
    }

    public function header($name, $value)
    {
        $this->headers[] = $name.': '.$value;
        return $this;
    }
    
    public function headers(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }
    
    public function timeout($timeout)
    {
        $this->curlopt['TIMEOUT'] = (int) $timeout;
        return $this;
    }
    
    public function curlopt($name, $value)
    {
        $this->curlopt[strtoupper($name)] = $value;
        return $this;
    }
    
    public function return_headers($bool = true)
    {
        $this->curlopt['HEADER'] = (bool) $bool;
        return $this;
    }
    
    public function save($path)
    {
        $fp = fopen($path, 'w+');
        if ($fp) {
            $this->curlopt['FILE'] = $fp;
            $this->result = self::send($this->method, $this->url, null, $this->headers, $this->curlopt, true);
            fclose($fp);
            return $this->result['status'] === 200 && $this->result['body'] === true;
        }
        $this->result = [];
        return false;
    }
    
    public static function send($method, $url, $body = null, array $headers = null, array $curlopt = null, $return_status = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (stripos($url, 'https://') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        if ($method === 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, 1);
        }
        if ($body){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($curlopt){
            foreach ($curlopt as $optk => $optv) {
                $const = constant('CURLOPT_'.strtoupper($optk));
                if ($const) {
                    curl_setopt($ch, $const, $optv);
                }
            }
        }
        $result = curl_exec($ch);
        if ($return_status) {
            $return['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }
        if (!empty($curlopt['HEADER'])) {
            if ($result) {
                $pairs = explode("\r\n\r\n", $result, 2);
                $result = isset($pairs[1]) ? $pairs[1] : null;
                $return['headers'] = isset($pairs[0]) ? self::parseHeaders($pairs[0]) : null;
            } else {
                $return['headers'] = null;
            }
        }
        if (isset($return)) {
            if ($result === false) {
                $return['error'] = curl_errno($ch).': '.curl_error($ch);
            }
            $return['body'] = $result;
            return $return;
        }
        return $result;
    }
    
    protected static function curlFile($filepath, $filename, $mimetype)
    {
        $file = new \CURLFile(realpath($filepath));
        if (isset($mimetype)) {
            $file->setMimeType($mimetype);
        }
        if (isset($filename)) {
            $file->setPostFilename($filename);
        }
        return $file;
    }
    
    protected static function multipartFile($boundary, $name, $content, $filename, $mimetype)
    {
        $file = '';
        if (empty($filename)) {
            $filename = $name;
        }
        if (empty($mimetype)) {
            $mimetype = 'application/octet-stream';
        }
        $file .= "--$boundary\r\nContent-Disposition: form-data; name=\"$name\"; filename=\"$filename\"\r\n";
        $file .= "Content-Type: $mimetype\r\nContent-Transfer-Encoding: binary\r\n\r\n".(string) $content."\r\n";
        return $file;
    }
    
    protected static function parseHeaders($header)
    {
        $header_arr = [];
        $arr = explode("\r\n", $header);
        foreach ($arr as $v) {
            $line = explode(":", $v, 2);
            if(count($line) === 2) {
                $header_arr[trim($line[0])] = trim($line[1]);
            }
        }
        return $header_arr;
    }
}
