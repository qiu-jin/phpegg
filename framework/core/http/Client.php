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
    
    public function __get($name)
    {
        if (!isset($this->result)) {
            if ($name === 'headers') {
                $this->return_headers = true;
            }
            $this->result = $this->send($this->method, $this->url, $this->body, $this->headers, $this->curlopt, true, $this->return_headers);
        }
        if (isset($this->result[$name])) {
            return $this->result[$name];
        } elseif ($name === 'json') {
            if (!empty($this->result['body'])) {
                return json_decode($this->result['body'], true);
            }
        }
        return null;
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
    
    public function form(array $data, $files = null, $is_buffer = false)
    {
        if ($files) {
            if ($is_buffer) {
                $boundary = uniqid();
                $data = self::multipart($data, $files, $boundary);
                $this->headers[] = 'Content-Type: multipart/form-data; boundary='.$boundary;
            } else {
                foreach ($files as $name => $file) {
                    if (is_array($file)) {
                        foreach ($file as $f) {
                            $data[$name][] = new \CURLFile(realpath($f));
                        }
                    } else {
                        $data[$name] = new \CURLFile(realpath($file));
                    }
                }
            }
        } else {
            $data = http_build_query($data);
            $this->headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        $this->body = $data;
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
        if (!isset($options['timeout'])) {
            curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);
        }
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
    
    private static function parseHeaders($header)
    {
        $header_arr = array();
        $arr = explode("\r\n", $header);
        foreach ($arr as $v) {
            $line = explode(":", $v, 2);
            if(count($line) === 2) $header_arr[$line[0]] = trim($line[1]);
        }
        return $header_arr;
    }
    
    private static function multipart($post, $files, $boundary)
    {
        $data = '';
        if ($post) {
            foreach ($post as $pk => $pv) {
                $data .= "--$boundary\r\ncontent-disposition: form-data; name=\"$pk\"\r\n\r\n$pv\r\n";
            }
        }
        if ($files) {
            foreach ($files as $name => $file) {
                $fname = $name;
                $ftype = 'application/octet-stream';
                $fcode = 'binary';
                $content = $file;
                $data .= "--$boundary\r\ncontent-disposition: form-data; name=\"$name\"; filename=\"$fname\"\r\n";
                $data .= "Content-Type: $ftype\r\nContent-Transfer-Encoding: $fcode\r\n\r\n".(string) $content."\r\n";
                $data .= "--$boundary--\r\n";
            }
        }
        return $data;
    }
}
