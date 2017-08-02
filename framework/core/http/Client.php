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
    private $return_headers = false;
    private $curlopt = ['TIMEOUT' => 30];
    
    public function __construct($method, $url)
    {
        $this->url = $url;
        $this->method = $method;
    }

    /*
     * 返回GET实例
     */
    public static function get($url)
    {
        return new self('GET', $url);
    }
    
    /*
     * 返回POST实例
     */
    public static function post($url)
    {
        return new self('POST', $url);
    }

    /*
     * 设置请求的body内容
     */
    public function body($body, $type = null)
    {
        $this->body = $body;
        if ($type) {
            $this->headers[] = 'Content-Type: '.$type;
        }
        return $this;
    }
    
    /*
     * 设置请求的body内容为数组被json_encode后的字符串
     */
    public function json(array $data)
    {
        $this->body = jsonencode($data);
        $this->headers[] = 'Content-Type: application/json; charset=UTF-8';
        return $this;
    }

    /*
     * 设置表单数据，数据默认为multipart/form-data格式否则为application/x-www-form-urlencoded
     */
    public function form(array $data, $x_www_form_urlencoded = false)
    {
        if ($x_www_form_urlencoded) {
            $this->body = http_build_query($data);
            $this->headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } else {
            $this->body = $data;
        }
        return $this;
    }

    /*
     * 本地文件上传请求，只支持post方法，通常在form方法后调用
     */
    public function file($name, $content, $filename = null, $mimetype = null)
    {
        if (substr($name, -2) === '[]') {
            $this->body[substr($name, 0, -2)][] = self::curlFile($content, $filename, $mimetype);
        } else {
            $this->body[$name] = self::curlFile($content, $filename, $mimetype);
        }
        return $this;
    }
    
    /*
     * 变量内容上传，与file方法相似
     */
    public function buffer($name, $content, $filename = null, $mimetype = null)
    {
        if (!$this->boundary) {
            $this->boundary = uniqid();
            $this->headers[] = 'Content-Type: multipart/form-data; boundary='.$this->boundary;
        }
        if (is_array($this->body)) {
            foreach ($this->body as $pk => $pv) {
                $body .= "--$this->boundary\r\nContent-Disposition: form-data; name=\"$pk\"\r\n\r\n$pv\r\n";
            }
            $this->body = $body;
        }
        $this->body .= self::multipartFile($this->boundary, $name, $content, $filename, $mimetype);
        return $this;
    }
    
    /*
     * 发送一个流，只支持put方法，在put大文件时使用节约内存
     */
    public function stream($fp)
    {
        $this->curlopt['PUT'] = 1;
        $this->curlopt['INFILE'] = $fp;
        $this->curlopt['INFILESIZE'] = fstat($fp)['size'];
        return $this;
    }

    /*
     * 设置单个header
     */
    public function header($name, $value)
    {
        $this->headers[] = $name.': '.$value;
        return $this;
    }
    
    /*
     * 设置多个header
     */
    public function headers(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }
    
    /*
     * 设置请求超时时间
     */
    public function timeout($timeout)
    {
        $this->curlopt['TIMEOUT'] = (int) $timeout;
        return $this;
    }
    
    /*
     * 设置底层curl参数
     */
    public function curlopt($name, $value)
    {
        $this->curlopt[strtoupper($name)] = $value;
        return $this;
    }
    
    /*
     * 设置是否获取并解析请求响应的headers数据
     */
    public function returnHeaders($bool = true)
    {
        $this->curlopt['HEADER'] = (bool) $bool;
        return $this;
    }
    
    /*
     * 获取请求结果
     */
    public function result($name = null)
    {
        if (!$this->result) {
            if ($this->boundary) {
                $this->body .= "--$this->boundary--\r\n";
            }
            $this->result = self::send($this->method, $this->url, $this->body, $this->headers, $this->curlopt, true);
        }
        if ($name === null) {
            return $this->result;
        }
        return isset($this->result[$name]) ? $this->result[$name] : null;
    }
    
    /*
     * 获取请求结果魔术方法
     */
    public function __get($name)
    {
        if (in_array($name, ['status', 'headers', 'body', 'error'])) {
            return $this->result($name);
        } elseif ($name === 'json') {
            return jsondecode($this->result('body'));
        } elseif ($name === 'xml') {
            return Xml::decode($this->result('body'));
        }
    }
    
    /*
     * 将请求的获得的body数据直接写入到本地文件，在body内容过大时可节约内存
     */
    public function save($path)
    {
        if (!isset($this->result)) {
            $fp = fopen($path, 'w+');
            if ($fp) {
                $this->curlopt['FILE'] = $fp;
                $this->result = $this->result();
                fclose($fp);
                return $this->result['status'] === 200 && $this->result['body'] === true;
            }
            $this->result = false;
        }
        return false;
    }
    
    /*
     * 底层curl方法封装
     */
    public static function send($method, $url, $body = null, array $headers = null, array $curlopt = null, $return_status = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (stripos($url, 'https://') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
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
    
    /*
     * 设置curl文件上传
     */
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
    
    /*
     * 设置multipart协议上传
     */
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
    
    /*
     * 解析headers
     */
    protected static function parseHeaders($str)
    {
        $headers = [];
        $arr = explode("\r\n", $str);
        foreach ($arr as $v) {
            $line = explode(":", $v, 2);
            if(count($line) === 2) {
                $headers[trim($line[0])] = trim($line[1]);
            }
        }
        return $headers;
    }
}
