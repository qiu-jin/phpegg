<?php
namespace framework\core\http;

class Client
{
    const EOL = "\r\n";
    
    private $ch;
    private $url;
    private $body;
    private $debug;
    private $result;
    private $method;
    private $headers = [];
    private $boundary;
    private $curlopt = [CURLOPT_TIMEOUT => 30];
    
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
     * 获取请求结果魔术方法
     */
    public function __get($name)
    {
        return $this->{'get'.ucfirst($name)}();
    }
    
    public function getStatus()
    {
        return $this->getInfo('HTTP_CODE');
    }
    
    public function getBody()
    {
        isset($this->result) || $this->send();
        return $this->result['body'] ?? null;
    }
    
    public function getJson()
    {
        return jsondecode($this->getBody('body'));
    }
    
    public function getHeader($name = null, $default = null)
    {
        isset($this->result) || $this->send();
        if ($name === null) {
            return $this->result['headers'] ?? null;
        }
        return $this->result['headers'][$name] ?? $default;
    }
    
    public function getInfo($name)
    {
        isset($this->result) || $this->send();
        return curl_getinfo($this->ch, constant('CURLINFO_'.strtoupper($name)));
    }
    
    public function getError()
    {   
        isset($this->result) || $this->send();
        return [curl_errno($ch), curl_error($ch)];
    }
    
    public function getErrorInfo()
    {   
        $error = $this->getError();
        if ($error[0] === 0) {
            return "Unknown HTTP Error $this->url";
        }
        return "HTTP Error [$error[0]]$error[1]  $this->url";  
    }
    
    /*
     * 将请求的获得的body数据直接写入到本地文件，在body内容过大时可节约内存
     */
    public function save($path)
    {
        if (isset($this->result)) {
            return false;
        }
        if ($fp = fopen($path, 'w+')) {
            $this->curlopt[CURLOPT_FILE] = $fp;
            $this->send();
            fclose($fp);
            return $this->getStatus() === 200 && $this->result['body'] === true;
        }
        return $this->result = false;
    }
    
    public static function multi($queries, callable $handle = null, $select_timeout = 0.1)
    {
        $mh = curl_multi_init();
        foreach ($queries as $i => $query) {
            $ch = $query->build();
            $indices[strval($ch)] = $i;
            curl_multi_add_handle($mh, $ch);
        }
        do{
            if (($status = curl_multi_exec($mh, $active)) !== CURLM_CALL_MULTI_PERFORM) {
                if ($status !== CURLM_OK) {
                    break;
                }
                while ($done = curl_multi_info_read($mh)) {
                    $ch = $done['handle'];
                    $index = $indices[strval($ch)];
                    $query = $queries[$index];
                    $result = curl_multi_getcontent($ch);
                    if (isset($query->curlopt[CURLOPT_HEADER])) {
                        list($result, $headers) = $query->parseWithHeaders($result);
                        $query->result['headers'] = $headers;
                    }
                    $query->result['body'] = $result;
                    if (isset($handle)) {
                        $return[$index] = $handle($query);
                    } else {
                        $return[$index] = $query;
                    }
                    curl_multi_remove_handle($mh, $ch);
                    if ($active > 0) {
                        curl_multi_select($mh, $select_timeout);
                    }
                }
            }
        } while ($active > 0);
        curl_multi_close($mh);
        return $return ?? null;
    }
    
    protected function send()
    {
        if ($this->debug) {
            $this->curlopt[CURLOPT_HEADER] = true;
            $this->curlopt[CURLINFO_HEADER_OUT] = true;
        }
        $result = curl_exec($this->build());
        if (isset($this->curlopt[CURLOPT_HEADER])) {
            list($headers, $result) = self::parseWithHeaders($result);
            $this->result['headers'] = $headers;
        }
        $this->result['body'] = $result;
        if ($this->debug) {
            //\framework\extend\debug\HttpClient::write($this->body, $this);
        }
    }
    
    protected function build()
    {   
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        if (stripos($this->url, 'https://') === 0) {
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
        return $this->ch = $ch;
    }
    
    /*
     * 解析headers
     */
    protected static function parseWithHeaders($str)
    {
        // 跳过HTTP/1.1 100 continue
        if (substr($str, 9, 3) === '100') {
            list(, $header, $body) = explode(self::EOL.self::EOL, $str, 3);
        } else {
            list($header, $body) = explode(self::EOL.self::EOL, $str, 2);
        }
        $arr = explode(self::EOL, $header);
        foreach ($arr as $v) {
            $line = explode(":", $v, 2);
            if(count($line) === 2) {
                $k = trim($line[0]);
                $v = trim($line[1]);
                if (isset($headers[$k])) {
                    if (count($headers[$k]) === 1) {
                        $headers[$k] = [$headers[$k], $v];
                    } else {
                        $headers[$k][] = $v;
                    }
                } else {
                    $headers[$k] = $v;
                }
            }
        }
        return [$headers ?? null, $body];
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
    
    public function __destruct()
    {
        isset($this->ch) && curl_close($this->ch);
    }
}
