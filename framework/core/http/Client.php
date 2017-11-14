<?php
namespace framework\core\http;

class Client
{
    const EOL = "\r\n";
    
    const HTTP_STATUS = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];
    // cURL句柄
    private $ch;
    // 启用调试
    private $debug;
    // 错误信息
    private $error;
    // 请求设置
    private $request;
    // 响应内容
    private $response;
    
    
    public function __construct($method, $url)
    {
        $this->debug = APP_DEBUG;
        $this->request = (object) compact('url', 'method');
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
     * 批量请求
     */
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
                    $query->response(curl_multi_getcontent($ch));
                    if (isset($handle)) {
                        $return[$index] = $handle($query, $index);
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

    /*
     * 设置请求的body内容
     */
    public function body($body, $type = null)
    {
        $this->request->body = $body;
        if ($type) {
             $this->request->headers[] = 'Content-Type: '.$type;
        }
        return $this;
    }
    
    /*
     * 设置请求的body内容为数组被json_encode后的字符串
     */
    public function json(array $data)
    {
        $this->request->body = jsonencode($data);
        $this->request->headers[] = 'Content-Type: application/json; charset=UTF-8';
        return $this;
    }

    /*
     * 设置表单数据，数据默认为multipart/form-data格式否则为application/x-www-form-urlencoded
     */
    public function form(array $data, $x_www_form_urlencoded = false)
    {
        if ($x_www_form_urlencoded) {
            $this->request->body = http_build_query($data);
            $this->request->headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } else {
            $this->request->body = $data;
        }
        return $this;
    }

    /*
     * 本地文件上传请求，只支持post方法，通常在form方法后调用
     */
    public function file($name, $content, $filename = null, $mimetype = null)
    {
        if (substr($name, -2) === '[]') {
            $this->request->body[substr($name, 0, -2)][] = $this->curlFile($content, $filename, $mimetype);
        } else {
            $this->request->body[$name] = $this->curlFile($content, $filename, $mimetype);
        }
        return $this;
    }
    
    /*
     * 变量内容上传，与file方法相似
     */
    public function buffer($name, $content, $filename = null, $mimetype = null)
    {
        if (empty($this->request->boundary)) {
            $this->request->boundary = uniqid();
            $this->request->headers[] = 'Content-Type: multipart/form-data; boundary='.$this->request->boundary;
            if (is_array($this->requestt->body)) {
                foreach ($this->requestt->body as $pk => $pv) {
                    $body[] = "--$this->request['boundary']";
                    $body[] = "Content-Disposition: form-data; name=\"$pk\"";
                    $body[] = '';
                    $body[] = $pv;
                }
                $body[] = '';
                $this->request->body = implode(self::EOL, $body);
            } else {
                $this->request->body = null;
            }
        } else {
            $this->request->body = substr($this->body, 0, -strlen("--{$this->request->boundary}--".self::EOL));
        }
        $this->request->body .= $this->multipartFile($name, $content, $filename, $mimetype);
        return $this;
    }
    
    /*
     * 发送一个流，只支持put方法，在put大文件时使用节约内存
     */
    public function stream($fp)
    {
        $this->request->curlopts[CURLOPT_PUT] = 1;
        $this->request->curlopts[CURLOPT_INFILE] = $fp;
        $this->request->curlopts[CURLOPT_INFILESIZE] = fstat($fp)['size'];
        return $this;
    }

    /*
     * 设置单个header
     */
    public function header($name, $value)
    {
        $this->request->headers[] = $name.': '.$value;
        return $this;
    }
    
    /*
     * 设置多个header
     */
    public function headers(array $headers)
    {
        $this->request->headers = array_merge($this->request->headers ?? [], $headers);
        return $this;
    }
    
    /*
     * 设置请求超时时间
     */
    public function timeout($timeout)
    {
        $this->request->curlopts[CURLOPT_TIMEOUT] = (int) $timeout;
        return $this;
    }
    
    /*
     * 设置底层curl参数
     */
    public function curlopt($name, $value)
    {
        $this->request->curlopts[$name] = $value;
        return $this;
    }
    
    /*
     * 设置底层curl参数
     */
    public function curlopts(array $values)
    {
        $this->request->curlopts = array_merge($this->request->curlopts ?? [], $values);
        return $this;
    }

    /*
     * 设置是否获取并解析请求响应的headers数据
     */
    public function returnHeaders($bool = true)
    {
        $this->request->curlopts[CURLOPT_HEADER] = (bool) $bool;
        return $this;
    }
    
    /*
     * 
     */
    public function debug($bool = true)
    {
        $this->debug = (bool) $bool;
        return $this;
    }
    
    public function __get($name)
    {
        switch ($name) {
            case 'request':
                return $this->request;
            case 'response':
                isset($this->response) || $this->send();
                return $this->response;
            case 'error':
                return $this->error;
        }
    }
    
    /*
     * 将请求的获得的body数据直接写入到本地文件，在body内容过大时可节约内存
     */
    public function save($path)
    {
        if (isset($this->response)) {
            return false;
        }
        if ($fp = fopen($path, 'w+')) {
            $this->request['curlopts'][CURLOPT_FILE] = $fp;
            $this->send();
            fclose($fp);
            return $this->getStatus() === 200 && $this->response['body'] === true;
        }
        return $this->response = false;
    }
    
    public function getCurlInfo($name)
    {
        isset($this->response) || $this->send();
        return curl_getinfo($this->ch, $name);
    }
    
    protected function send()
    {
        if ($this->debug) {
            $this->request->curlopts[CURLOPT_HEADER] = true;
            $this->request->curlopts[CURLINFO_HEADER_OUT] = true;
        }
        $this->response(curl_exec($this->build()));
    }
    
    protected function build()
    {
        $ch = curl_init($this->request->url);
        if (stripos($this->request->url, 'https://') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($this->request->method));
        if ($this->request->method === 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, 1);
        }
        if (isset($this->request->body)){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request->body);
        }
        if (isset($this->request->headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->request->headers);
        }
        if (isset($this->request->curlopts)) {
            ksort($this->request->curlopts);
            curl_setopt_array($ch, $this->request->curlopts);
        }
        return $this->ch = $ch;
    }
    
    protected function error()
    {
        if ($this->response->status) {
            $code = $this->response->status;
            $message = self::HTTP_STATUS[$code];
        } else {
            $code = curl_errno($this->ch);
            $message = curl_error($this->ch);
        }
        $this->error = new class ($code, $message, $this->request) {
            private $request;
            public function __construct($code, $message, $request) {
                $this->code = $code;
                $this->message = $message;
                $this->request = $request;
            }
            public function __toString() {
                return ($this->code ? "[$this->code]$this->message" : 'unknown http error').": {$this->request->method} {$this->request->url}";
            }
        };
    }
    
    protected function response($content)
    {
        $this->response = new class () {
            public function json() {
                return $this->body ? jsondecode($this->body) : false;
            }
        };
        if (empty($this->request->curlopts[CURLOPT_HEADER])) {
            $this->response->body = $content;
        } else {
            $this->responseWithHeaders($content);
        }
        $this->response->status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        if (!($this->response->status >= 200 && $this->response->status < 300)) {
            $this->error();
        }
    }
    
    protected function responseWithHeaders($content)
    {
        // 跳过HTTP/1.1 100 continue
        if (substr($str, 9, 3) === '100') {
            list(, $header, $this->response->body) = explode(self::EOL.self::EOL, $content, 3);
        } else {
            list($header, $this->response->body) = explode(self::EOL.self::EOL, $content, 2);
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
        $this->response->headers = $headers;
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
            "--$this->request['boundary']--",
            ''
        ]);
    }
    
    public function __destruct()
    {
        isset($this->ch) && curl_close($this->ch);
    }
}
