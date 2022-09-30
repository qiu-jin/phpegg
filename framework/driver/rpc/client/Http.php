<?php
namespace framework\driver\rpc\client;

use framework\util\Arr;
use framework\util\Str;
use framework\core\http\Client;

class Http
{
    // 配置项
    protected $config/* = [
        // 服务端点
        'endpoint'
        // 请求公共headers
        'http_headers'
        // 请求公共curlopts
        'http_curlopts'
        // 请求内容编码
        'requset_encode'
        // 响应内容解码
        'response_decode'
        // 响应结果字段
        'response_result_field'
        // 抛出响应错误异常
        'throw_response_error'
        // 错误码定义字段
        'response_error_code_field'
        // 错误信息定义字段
        'response_error_message_field'
    ]*/;
	// 错误码
	protected $error_code;
	// 错误信息
	protected $error_message;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    /*
     * 请求
     */
    public function send($method, $path, $filters, $body, $headers)
    {
		$config = $this->config;
		if (isset($config['request_handler'])) {
			$request = (object) compact('config', 'method', 'path', 'filters', 'body', 'headers');
			$config['request_handler']($request);
			extract((array) $request);
		}
		$url = $config['endpoint'];
		if ($path) {
			$url = Str::lastPad($url, '/').$path;
		}
        if ($filters) {
            $url .= (strpos($url, '?') === false ? '?' : '&').http_build_query($filters);
		}
        $client = new Client($method, $url);
        if (isset($config['http_headers'])) {
            $client->headers($config['http_headers']);
        }
        if (isset($headers)) {
            $client->headers($headers);
        }
        if (isset($config['http_curlopts'])) {
            $client->curlopts($config['http_curlopts']);
        }
        if ($body) {
			if (is_string($body)) {
				$client->body($body);
			} else {
				if (isset($config['request_encode'])) {
					$client->body($config['request_encode']($body));
				} else {
					$client->form($body);
				}
			}
        }
        $response = $client->response();
		if (isset($config['response_handler'])) {
			$result = $config['response_handler']($response);
			if ($result !== null) {
				return $result;
			}
		}
        if ($response->code >= 200 && $response->code < 300) {
            $result = $response->body;
            if (isset($config['response_decode'])) {
                $result = $config['response_decode']($result);
            }
            if (empty($config['response_result_field'])) {
				return $result;
            }
			$result = Arr::get($result, $config['response_result_field']);
            if (isset($result)) {
                return $result;
            }
        }
		$this->error_code = $response->code;
		$this->error_message = '';
		if (!$this->error_code) {
			$this->error_message = $client->error->message;
		} else {
			if (isset($config['response_error_code_field'])) {
				$this->error_code = Arr::get($result, $config['response_error_code_field']);
			}
			if (isset($config['response_error_message_field'])) {
				$this->error_message = Arr::get($result, $config['response_error_message_field']);
			}
		}
        if (empty($config['throw_response_error'])) {
            return false;
        }
		if ($config['throw_response_error'] !== true) {
			throw new \Exception("[$this->error_code]".$this->error_message);
		}
		$class = $config['throw_response_error'];
		throw new $class("[$this->error_code]".$this->error_message);
	}
}