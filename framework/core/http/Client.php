<?php
namespace framework\core\http;

use framework\util\Xml;
use framework\extend\debug\HttpClient as HttpClientDebug;

define('CURLINFO_STATUS', CURLINFO_HTTP_CODE);

//Curl
class Client
{
    private $url;
    private $body;
    private $debug;
    private $result;
    private $method;
    private $headers = [];
    private $boundary;
    private $curlopt = [CURLOPT_TIMEOUT => 30];
    private $curlinfo = ['status'];
    
    const EOL = "\r\n";
    
    public function __construct($method, $url)
    {
        $this->url = $url;
        $this->debug = APP_DEBUG;
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
            $this->body[substr($name, 0, -2)][] = $this->curlFile($content, $filename, $mimetype);
        } else {
            $this->body[$name] = $this->curlFile($content, $filename, $mimetype);
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
            if (is_array($this->body)) {
                foreach ($this->body as $pk => $pv) {
                    $body[] = "--$this->boundary";
                    $body[] = "Content-Disposition: form-data; name=\"$pk\"";
                    $body[] = '';
                    $body[] = $pv;
                }
                $body[] = '';
                $this->body = implode(self::EOL, $body);
            } else {
                $this->body = null;
            }
        } else {
            $this->body = substr($this->body, 0, -strlen("--$this->boundary--".self::EOL));
        }
        $this->body .= $this->multipartFile($name, $content, $filename, $mimetype);
        return $this;
    }
    
    /*
     * 发送一个流，只支持put方法，在put大文件时使用节约内存
     */
    public function stream($fp)
    {
        $this->curlopt[CURLOPT_PUT] = 1;
        $this->curlopt[CURLOPT_INFILE] = $fp;
        $this->curlopt[CURLOPT_INFILESIZE] = fstat($fp)['size'];
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
        $this->curlopt[CURLOPT_TIMEOUT] = (int) $timeout;
        return $this;
    }
    
    /*
     * 设置底层curl参数
     */
    public function curlopt($name, $value)
    {
        $this->curlopt[$name] = $value;
        return $this;
    }
    
    /*
     * 设置底层curl参数
     */
    public function curlinfo($name)
    {
        $this->curlinfo[] = $name;
        return $this;
    }

    /*
     * 设置是否获取并解析请求响应的headers数据
     */
    public function returnHeaders($bool = true)
    {
        $this->curlopt[CURLOPT_HEADER] = (bool) $bool;
        return $this;
    }
    
    /*
     * 
     */
    public function debug(bool $bool)
    {
        $this->debug = $bool;
        return $this;
    }
    
    /*
     * 获取请求结果
     */
    public function result($name = null)
    {
        if (!$this->result) {
            $this->result = self::send();
        }
        if ($name === null) {
            return $this->result;
        }
        return $this->result[$name] ?? null;
    }
    
    /*
     * 获取请求结果魔术方法
     */
    public function __get($name)
    {
        switch ($name) {
            case 'json':
                return jsondecode($this->result('body'));
            case 'xml':
                return Xml::decode($this->result('body'));
            default:
                return $this->result($name);
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
                $this->curlopt[CURLOPT_FILE] = $fp;
                $this->result = self::send();
                fclose($fp);
                return $this->result['status'] === 200 && $this->result['body'] === true;
            }
            $this->result = false;
        }
        return false;
    }
    
    public function send()
    {
        if ($this->debug) {
            $this->curlopt[CURLOPT_HEADER] = true;
            $this->curlopt[CURLINFO_HEADER_OUT] = true;
            $this->curlinfo[] = 'header_out';
        }
        $ch = curl_exec($this->build());
        if ($curlinfo) {
            foreach (array_unique($curlinfo) as $name) {
                $return[$name] = curl_getinfo($ch, constant('CURLINFO_'.strtoupper($name)));
            }
        }
        if (!empty($curlopt[CURLOPT_HEADER])) {
            if ($result) {
                //忽略 HTTP/1.1 100 continue
                if (substr($result, 9, 3) === '100') {
                    list(, $header, $result) = explode(self::EOL.self::EOL, $result, 3);
                } else {
                    list($header, $result) = explode(self::EOL.self::EOL, $result, 2);
                }
                $return['headers'] = self::parseHeaders($header);
            } else {
                $return['headers'] = null;
            }
        }
        if (isset($return)) {
            if ($result === false) {
                $return['error'] = curl_errno($ch).': '.curl_error($ch);
            }
            $return['body'] = $result;
            if ($debug) {
                HttpClientDebug::write($body, $return);
            }
            return $return;
        }
        return $result;
    }
    
    /*
     * build
     */
    public function build()
    {   
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        if (stripos($url, 'https://') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($this->method));
        if ($this->method === 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, 1);
        }
        if ($this->body){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
        }
        if ($this->headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }
        if ($this->curlopt){
            curl_setopt_array($ch, $this->curlopt);
        }
        return $ch;
    }
    
    /*
     * 解析headers
     */
    public static function parseHeaders($str)
    {
        $headers = [];
        $arr = explode(self::EOL, $str);
        foreach ($arr as $v) {
            $line = explode(":", $v, 2);
            if(count($line) === 2) {
                $headers[trim($line[0])] = trim($line[1]);
            }
        }
        return $headers;
    }
    
    /*
     * 设置curl文件上传
     */
    protected function curlFile($filepath, $filename, $mimetype)
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
    protected function multipartFile($name, $content, $filename, $mimetype)
    {
        if (empty($filename)) {
            $filename = $name;
        }
        if (empty($mimetype)) {
            $mimetype = 'application/octet-stream';
        }
        return implode(self::EOL, [
            "--$this->boundary",
            "Content-Disposition: form-data; name=\"$name\"; filename=\"$filename\"",
            "Content-Type: $mimetype",
            "Content-Transfer-Encoding: binary",
            '',
            (string) $content,
            "--$this->boundary--",
            ''
        ]);
    }
}
