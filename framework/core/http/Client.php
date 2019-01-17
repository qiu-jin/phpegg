<?php
namespace framework\core\http;

use framework\util\Arr;

class Client
{
    const EOL = "\r\n";
    // cURL句柄
    private $ch;
    // 错误信息
    private $error;
    // 请求设置
    private $request;
    // 响应内容
    private $response;
    
    public function __construct($method, $url)
    {
        $this->request = (object) compact('url', 'method');
		$this->request->debug = APP_DEBUG;
    }

    /*
     * GET实例
     */
    public static function get($url)
    {
        return new self('GET', $url);
    }
    
    /*
     * POST实例
     */
    public static function post($url)
    {
        return new self('POST', $url);
    }
    
    /*
     * 多进程批量请求
     */
    public static function multi(array $queries, callable $handle = null, $select_timeout = 0.1)
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
                    $query->setResponse(curl_multi_getcontent($ch));
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
     * 设置请求body
     */
    public function body(string $body, $type = null)
    {
        $this->request->body = $body;
        if ($type) {
            $this->request->headers['Content-Type'] = $type;
        }
        return $this;
    }
    
    /*
     * 设置请求body为数组被json_encode后的字符串
     */
    public function json(array $data)
    {
        $this->request->body = jsonencode($data);
        $this->request->headers['Content-Type'] = 'application/json; charset=UTF-8';
        return $this;
    }

    /*
     * 设置表单请求数据，数据类型默认为application/x-www-form-urlencoded格式否则为multipart/form-data
     */
    public function form(array $data, $multipart = false)
    {
        if ($multipart) {
			$this->request->body = $data;
        } else {
            $this->request->body = http_build_query($data);
            $this->request->headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }
        return $this;
    }

    /*
     * 文件上传
     */
    public function file($name, $content, $filename = null, $mimetype = null)
    {
		$this->request->body[$name] = new \CURLFile(realpath($file), $mimetype, $filename);
        return $this;
    }
    
    /*
     * 变量内容上传（不能与file混用）
     */
    public function buffer($name, $content, $filename = null, $mimetype = null)
    {
        if (isset($this->request->boundary)) {
			$this->request->body = substr($this->request->body, 0, -19);
        } else {
            $this->request->boundary = uniqid();
            $this->request->headers['Content-Type'] = 'multipart/form-data; boundary='.$this->request->boundary;
            if (isset($this->request->body) && is_array($this->request->body)) {
                foreach ($this->request->body as $k => $v) {
                    $body[] = '--'.$this->request->boundary;
                    $body[] = "Content-Disposition: form-data; name=\"$k\"";
                    $body[] = '';
                    $body[] = $v;
                }
                $body[] = '';
                $this->request->body = implode(self::EOL, $body);
            } else {
                $this->request->body = null;
            }
        }
        $this->request->body .= implode(self::EOL, [
            '--'.$this->request->boundary,
            'Content-Disposition: form-data; name="'.$name.'"; filename="'.($filename ?? $name).'"',
            'Content-Type: '.($mimetype ?? 'application/octet-stream'),
            'Content-Transfer-Encoding: binary',
            '',
            $content,
            "--{$this->request->boundary}--",
            ''
        ]);
        return $this;
    }
    
    /*
     * 发送一个流，只支持PUT方法，在PUT大文件时使用节约内存
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
        $this->request->headers[$name] = $value;
        return $this;
    }
    
    /*
     * 设置多个header
     */
    public function headers(array $values)
    {
		$this->request->headers = isset($this->request->headers) ? $values + $this->request->headers : $values;
        return $this;
    }
	
    /*
     * 认证
     */
    public function auth($user, $pass)
    {
        $this->request->headers['Authorization'] = 'Basic '.base64_encode("$user:$pass");
        return $this;
    }
	
    /*
     * 设置单个cookie
     */
    public function cookie($name, $value)
    {
        $this->request->cookies[$name] = $value;
        return $this;
    }
    
    /*
     * 设置多个cookie
     */
    public function cookies(array $values)
    {
		$this->request->cookies = isset($this->request->cookies) ? $values + $this->request->cookies : $values;
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
		$this->request->curlopts = isset($this->request->curlopts) ? $values + $this->request->curlopts : $values;
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
     * 设置请求超时时间
     */
    public function allowRedirects($bool = true, int $max = 3)
    {
        $this->request->curlopts[CURLOPT_FOLLOWLOCATION] = $bool;
		if ($bool && $max > 0) {
			$this->request->curlopts[CURLOPT_MAXREDIRS] = $max;
		}
        return $this;
    }

    /*
     * 设置是否获取并解析请求响应的headers数据
     */
    public function returnHeaders($bool = true)
    {
		$this->request->return_headers = $bool;
        return $this;
    }
    
    /*
     * 设置debug模式
     */
    public function debug($bool = true)
    {
        $this->request->debug = $bool;
        return $this;
    }
	
    /*
     * 获取请求响应
     */
    public function response()
    {
		if (!isset($this->response)) {
			$this->setResponse($this->exec());
		}
		return $this->response;
    }
    
    /*
     * 魔术方法，获取request response error信息
     */
    public function __get($name)
    {
        switch ($name) {
            case 'request':
                return $this->request;
            case 'response':
                return $this->response();
            case 'error':
                return $this->error;
        }
		throw new \Exception("Undefined property: $$name");
    }
    
    /*
     * 将请求的获得的body数据直接写入到本地文件，在body内容过大时可节约内存
     */
    public function save($file)
    {
        if (isset($this->response)) {
            throw new \Exception("已完成的请求实例");
        }
        if ($fp = fopen($file, 'w+')) {
            $this->request->curlopts[CURLOPT_FILE] = $fp;
            $this->setResponse($this->exec());
            $return = $this->response->status === 200 && $this->response->body === true;
            if ($return) {
                fclose($fp);
            } else {
                rewind($fp);
                $this->response->body = stream_get_contents($fp);
                fclose($fp);
                unlink($file);
            }
            return $return;
        }
        return $this->response = false;
    }
    
    /*
     * 获取Curl信息
     */
    public function getCurlInfo($name)
    {
		if (!isset($this->response)) {
			$this->setResponse($this->exec());
		}
        return curl_getinfo($this->ch, $name);
    }
	
    /*
     * 执行请求
     */
    protected function exec()
    {
		return curl_exec($this->build());
    }
    
    /*
     * 构建请求数据
     */
    protected function build()
    {
        $ch = curl_init($this->request->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (isset($this->request->method)){
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->request->method);
        }
        if (isset($this->request->body)){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request->body);
        }
        if (isset($this->request->headers)) {
			foreach ($this->request->headers as $name => $value) {
				$headers[] = "$name: $value";
			}
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if (isset($this->request->cookies)) {
			curl_setopt($ch, CURLOPT_COOKIE, http_build_query($this->request->cookies, '', ';'));
        }
        if (!empty($this->request->debug)) {
            $this->request->return_headers = true;
            $this->request->curlopts[CURLINFO_HEADER_OUT] = true;
        }
        if (!empty($this->request->return_headers)) {
			$this->request->curlopts[CURLOPT_HEADER] = true;
        }
        if (isset($this->request->curlopts)) {
            ksort($this->request->curlopts);
            curl_setopt_array($ch, $this->request->curlopts);
        }
        return $this->ch = $ch;
    }
    
    /*
     * 处理请求响应
     */
    protected function setResponse($body)
    {
        $status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        if (!($status >= 200 && $status < 300)) {
            $this->setError($status);
        }
        if (!empty($this->request->return_headers)) {
        	$headers = $this->getResponseHeadersFromResult($body);
        }
        $this->response = new class ($status, $body, $headers ?? null) {
            public function __construct($status, $body, $headers) {
                $this->status	= $status;
                $this->body 	= $body;
                $this->headers	= $headers;
            }
            public function json($name = null, $default = null) {
				$data = jsondecode($this->body);
				return $name === null ? $data : Arr::get($data, $name, $default);
            }
            public function __toString() {
                return $this->body;
            }
        };
    }
	
    /*
     * 处理错误信息
     */
    protected function setError($code)
    {
        if ($code) {
            $message = Status::CODE[$code] ?? 'unknown status';
        } else {
            $code  	 = curl_errno($this->ch);
            $message = curl_error($this->ch);
        }
        $this->error = new class ($code, $message, $this->request) {
            private $request;
            public function __construct($code, $message, $request) {
                $this->code    = $code;
                $this->message = $message;
                $this->request = $request;
            }
            public function __toString() {
                return ($this->code ? "[$this->code]$this->message" : 'unknown http error')
                       .": {$this->request->method} {$this->request->url}";
            }
        };
    }
    
    /*
     * 获取响应头
     */
    protected function getResponseHeadersFromResult(&$body)
    {
        if (is_string($body) && ($size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE))) {
	        foreach (explode(self::EOL, substr($body, 0, $size)) as $v) {
	            $l = explode(":", $v, 2);
	            if(isset($l[1])) {
	                $k = trim($l[0]);
	                $v = trim($l[1]);
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
			$body = substr($body, $size);
	        return $headers ?? null;
        }
    }
    
    /*
     * 析构方法，关闭curl句柄
     */
    public function __destruct()
    {
        empty($this->ch) || curl_close($this->ch);
    }
}
