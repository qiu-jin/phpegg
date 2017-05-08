<?php
namespace framework\core\http;

//Curl
class Client
{
    private $url;
    private $body;
    private $result;
    private $method;
    private $headers = [];
    private $curlopt = [];
    private $boundary;
    private $file_is_buffer;
    private $return_headers = false;
    private static $timeout = 3;
    
    private function __construct($method, $url)
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
    
    public static function __callStatic($name, $params)
    {
        if (in_array($name, ['put', 'delete', 'options', 'head', 'patch'], true)) {
            return new self(strtoupper($name), $params[0]);
        }
        throw new \Exception('Not support HTTP method: '.$name);
    }
    
    public function getStatus()
    {
        isset($this->result) || $this->getResult(false);
        return $this->result['status'];
    }
    
    public function getHeader($name = null)
    {
        isset($this->result) || $this->getResult(false);
        if ($name) {
            return isset($this->result['headers'][$name]) ? $this->result['headers'][$name] : null;
        }
        return $this->result['headers'];
    }
    
    public function getBody()
    {
        isset($this->result) || $this->getResult(false);
        return $this->result['body'];
    }
    
    public function getJson()
    {
        isset($this->result) || $this->getResult(false);
        return isset($this->result['body']) ? jsondecode($this->result['body']) : null;
    }
    
    public function getError()
    {
        isset($this->result) || $this->getResult(false);
        return isset($this->result['error']) ? $this->result['error'] : null;
    }
    
    public function getResult($return = true)
    {
        if ($this->file_is_buffer) {
            $this->body .= "--$this->boundary--\r\n";
        }
        $result = self::send($this->method, $this->url, $this->body, $this->headers, $this->curlopt, true, $this->return_headers);
        if ($return) {
            $this->result = [];
            return $result;
        } else {
            $this->result = $result;
        }
    }
    
    public function save($path)
    {
        $curlopt = $this->curlopt;
        $fp = fopen($path, 'w+');
        if ($fp) {
            $curlopt['file'] = $fp;
            $this->result = self::send($this->method, $this->url, null, $this->headers, $curlopt, true);
            return $this->result['status'] === 200 && $this->result['body'] === true;
        }
        return false;
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
    
    public function stream($path)
    {
        $fp = fopen($path, 'r');
        if ($fp) {
            $this->curlopt['PUT'] = 1;
            $this->curlopt['INFILE'] = $fp;
            return $this;
        }
        return false;
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
        $this->curlopt['timeout'] = $timeout;
        return $this;
    }
    
    public function curlopt($name, $value)
    {
        $this->curlopt[$name] = $value;
        return $this;
    }
    
    public function return_headers($bool = true)
    {
        $this->return_headers = (bool) $bool;
        return $this;
    }
    
    public static function send($method, $url, $body = null, array $headers = null, array $curlopt = null, $return_status = false, $return_headers = false)
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
        isset($curlopt['timeout']) || curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);
        if ($return_headers) {
            $return = [];
            curl_setopt($ch, CURLOPT_HEADER, 1);
            $res = curl_exec($ch);
            if ($res) {
                $pairs = explode("\r\n\r\n", $res, 2);
                if (isset($pairs[0])) {
                    $return['headers'] = self::parseHeaders($pairs[0]);
                }
                if (isset($pairs[1])) {
                    $return['body'] = $pairs[1];
                }
            } else {
                $return = ['headers' => null, 'body' => null];
            }
            if ($res === false) {
                $return['error'] = [curl_errno($ch), curl_error($ch)];
            }
            if ($return_status) {
                $return['status'] = curl_getinfo($ch,CURLINFO_HTTP_CODE);
            }
            return $return;
        } else {
            $res = curl_exec($ch);
            if ($return_status) {
                $return = ['status' => curl_getinfo($ch,CURLINFO_HTTP_CODE), 'body' => $res];
                if ($res === false) {
                    $return['error'] = [curl_errno($ch), curl_error($ch)];
                }
                return $return;
            } else {
                return $res;
            }
        }
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
        $header_arr = array();
        $arr = explode("\r\n", $header);
        foreach ($arr as $v) {
            $line = explode(":", $v, 2);
            if(count($line) === 2) $header_arr[$line[0]] = trim($line[1]);
        }
        return $header_arr;
    }
}
