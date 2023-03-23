<?php
namespace framework\core\http;

use framework\core\Logger;

class Client
{
    const EOL = "\r\n";
    // cURL句柄
    private $ch;
    // 调试设置
    private $debug = \app\env\APP_DEBUG;
    // 错误信息
    private $error;
    // 请求设置
    private $request;
    // 响应内容
    private $response;

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
    public static function batch(array $queries, callable $handler = null, $select_timeout = 0.1)
    {
        $mh = curl_multi_init();
        foreach ($queries as $i => $query) {
            $ch = $query->build();
            $indices[strval($ch)] = $i;
            curl_multi_add_handle($mh, $ch);
        }
        do{
            if (($status = curl_multi_exec($mh, $active)) != CURLM_CALL_MULTI_PERFORM) {
                if ($status != CURLM_OK) {
                    break;
                }
                while ($done = curl_multi_info_read($mh)) {
                    $ch = $done['handle'];
                    $index = $indices[strval($ch)];
                    $query = $queries[$index];
                    $query->setResponse(curl_multi_getcontent($ch));
                    if (isset($handler)) {
                        $return[$index] = $handler($query, $index);
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
     * 构造函数
     */
    public function __construct($method, $url)
    {
        $this->request = (object) ['url' => $url, 'method' => $method];
    }
	
    /*
     * 设置请求query
     */
    public function query($query)
    {
		$this->request->url .= strpos($this->request->url, '?') === false ? '?' : '&';
		$this->request->url .= is_array($query) ? http_build_query($query) : $query;
        return $this;
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
		return $this->body(json_encode($data, JSON_UNESCAPED_UNICODE), 'application/json; charset=UTF-8');
    }

    /*
     * 设置表单请求数据，数据类型默认为application/x-www-form-urlencoded格式否则为multipart/form-data
     */
    public function form(array $data, $multipart = false)
    {
        if ($multipart) {
			$this->request->body = $data;
			return $this;
        }
		return $this->body(http_build_query($data), 'application/x-www-form-urlencoded');
    }

    /*
     * 文件上传
     */
    public function file($name, $file, $filename = null, $mimetype = null)
    {
		if (isset($this->request->body) && !is_array($this->request->body)) {
			throw new \Exception("仅允许与multipart类型form方法合用");
		}
		$this->request->body[$name] = new \CURLFile($file, $mimetype, $filename);
        return $this;
    }
    
    /*
     * 变量内容上传（不能与file混用）
     */
    public function buffer($name, $content, $filename = null, $mimetype = null)
    {
        if (isset($this->request->boundary)) {
			$this->request->body = substr($this->request->body, 0, - strlen($this->request->boundary) - 6);
        } else {
            $this->request->boundary = uniqid();
            $this->request->headers['Content-Type'] = 'multipart/form-data; boundary='.$this->request->boundary;
			if (isset($this->request->body)) {
				if (!is_array($this->request->body)) {
					throw new \Exception("仅允许与multipart类型form方法合用");
				}
				$this->request->body = $this->setMultipartFormData($this->request->body).self::EOL;
			} else {
				$this->request->body = '';
			}
        }
        $this->request->body .= implode(self::EOL, [
            "--{$this->request->boundary}",
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
    public function auth($user, $pass = null)
    {
        $this->request->headers['Authorization'] = 'Basic '.base64_encode(isset($pass) ? "$user:$pass" : $user);
        return $this;
    }
	
    /*
     * 设置ssl
     */
    public function ssl($value = null)
    {
		if ($value) {
			$this->request->curlopts[CURLOPT_SSL_VERIFYPEER] = true;
			$this->request->curlopts[is_dir($value) ? CURLOPT_CAPATH : CURLOPT_CAINFO] = $value;
		} else {
			$this->request->curlopts[CURLOPT_SSL_VERIFYPEER] = false;
			$this->request->curlopts[CURLOPT_SSL_VERIFYHOST] = false;
		}
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
        $this->request->curlopts[CURLOPT_TIMEOUT] = $timeout;
        return $this;
    }
	
    /*
     * 设置请求连接超时时间
     */
    public function connectTimeout($timeout)
    {
		$this->request->curlopts[CURLOPT_CONNECTTIMEOUT] = $timeout;
        return $this;
    }
	
    /*
     * 设置重定向
     */
    public function allowRedirect($max = 1)
    {
        $this->request->curlopts[CURLOPT_FOLLOWLOCATION] = (bool) $max;
		if ($max > 1) {
			$this->request->curlopts[CURLOPT_MAXREDIRS] = (int) $max;
		}
        return $this;
    }

    /*
     * 设置是否获取并解析请求响应的headers数据
     */
    public function returnHeader($bool = true)
    {
		$this->request->return_header = $bool;
        return $this;
    }
    
    /*
     * 设置debug模式
     */
    public function debug($debug = true)
    {
        $this->debug = $debug;
        return $this;
    }
	
    /*
     * 获取请求响应
     */
    public function response()
    {
		if (!$this->response) {
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
    public function saveAs($file)
    {
        if ($this->response) {
            throw new \Exception("不能使用已完成的请求实例");
        }
        if ($fp = fopen($file, 'w+')) {
            $this->request->curlopts[CURLOPT_FILE] = $fp;
            $this->setResponse($this->exec());
            $return = $this->response->code == 200 && $this->response->body === true;
            if ($return) {
                fclose($fp);
            } else {
                rewind($fp);
                //$this->response->body = stream_get_contents($fp);
                fclose($fp);
				if (file_exists($file)) {
					unlink($file);
				}
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
		if (!$this->response) {
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
		$request = $this->request;
        $ch = curl_init($request->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (isset($request->method)){
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->method);
        }
        if (isset($request->body)){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request->body);
        }
        if (isset($request->headers)) {
			foreach ($request->headers as $name => $value) {
				$headers[] = "$name: $value";
			}
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if (isset($request->cookies)) {
			curl_setopt($ch, CURLOPT_COOKIE, http_build_query($request->cookies, '', ';'));
        }
        if ($this->debug) {
            $request->return_header = true;
            $request->curlopts[CURLINFO_HEADER_OUT] = true;
        }
        if (!empty($request->return_header)) {
			$request->curlopts[CURLOPT_HEADER] = true;
        }
        if (isset($request->curlopts)) {
            ksort($request->curlopts);
            curl_setopt_array($ch, $request->curlopts);
        }
        return $this->ch = $ch;
    }
    
    /*
     * 处理请求响应
     */
    protected function setResponse($body)
    {
        $code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        if (!($code >= 200 && $code < 300)) {
            $this->setError($code);
        }
        if (!empty($this->request->return_header)) {
        	$headers = $this->getResponseHeadersFromResult($body);
        }
        $this->response = new class ($code, $body, $headers ?? null) {
			public $code;
			public $body;
			public $headers;
			private $data;
            public function __construct($code, $body, $headers) {
				$this->code		= $code;
				$this->body 	= $body;
                $this->headers	= $headers;
            }
			// 获取响应头
            public function header($name, $default = null) {
				return $this->headers[$name] ?? $default;
            }
			// 解码响应内容
            public function decode(callable $decoder = null) {
				return $decoder ? $decoder($this->body) : json_decode($this->body, true);
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
            $message = $code ?? 'Unknown Status';
        } else {
            $code  	 = curl_errno($this->ch);
            $message = curl_error($this->ch);
        }
        $this->error = new class ($code, $message, $this->request) {
            private $request;
			public $code;
			public $message;
            public function __construct($code, $message, $request) {
                $this->code    = $code;
                $this->message = $message;
                $this->request = $request;
            }
            public function __toString() {
                return ($this->code ? "[$this->code]$this->message" : 'Unknown HTTP Error')
                       .": {$this->request->method} {$this->request->url}";
            }
        };
    }
	
    /*
     * 设置multipart类型数据
     */
    protected function setMultipartFormData($data, $parent = null)
    {
		$parts = [];
        foreach ($data as $k => $v) {
			if ($parent) {
				$k = $parent."[$k]";
			}
			if (is_array($v)) {
				$parts = array_merge($parts, $this->setMultipartFormData($v, $k));
			} else {
	            $parts[] = "--{$this->request->boundary}";
	            $parts[] = "Content-Disposition: form-data; name=\"$k\"";
	            $parts[] = '';
	            $parts[] = $v;
			}
        }
		return implode(self::EOL, $parts);
    }
    
    /*
     * 获取响应头
     */
    protected function getResponseHeadersFromResult(&$body)
    {
        if (is_string($body) && ($size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE))) {
			$body = substr($body, $size);
	        foreach (explode(self::EOL, substr($body, 0, $size)) as $line) {
	            $kv = explode(':', $line, 2);
	            if(isset($kv[1])) {
	                $k = trim($kv[0]);
	                $v = trim($kv[1]);
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
	        return $headers ?? null;
        }
    }
	
    /*
     * 日志
     */
    protected static function log($log)
    {
        Logger::channel($this->debug)->debug($log);
    }
    
    /*
     * 析构方法，关闭curl句柄
     */
    public function __destruct()
    {
        if (is_resource($this->ch)) curl_close($this->ch);
    }
}
