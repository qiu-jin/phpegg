<?php
namespace framework\driver\rpc\client;

use framework\util\Arr;
use framework\util\Str;
use framework\core\http\Client;

class Http
{
	// 配置项
    protected $config;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    /*
     * 生成client实例
     */
    public function make($method, $path, $filters, $data, $headers)
    {
		$url = $this->config['endpoint'];
		if ($path) {
			$url .= '/'.$path;
		}
        if ($filters) {
            $url .= (strpos($url, '?') === false ? '?' : '&').http_build_query($filters);
		}
        $client = new Client($method, $url);
        if (isset($this->config['http_headers'])) {
            $client->headers($this->config['http_headers']);
        }
        if (isset($headers)) {
            $client->headers($headers);
        }
        if (isset($this->config['http_curlopts'])) {
            $client->curlopts($this->config['http_curlopts']);
        }
        if ($data) {
			if (is_array($data)) {
				$client->form($data);
			} else {
				$client->body($data);
			}
        }
        $response = $client->response();
        if ($response->code >= 200 && $response->code < 300) {
            $result = $response->body;
            if (isset($this->config['response_decode'])) {
                $result = $this->config['response_decode']($result);
            }
            if (isset($this->config['response_result_field'])) {
                if (($result = Arr::get($result, $this->config['response_result_field'])) !== null) {
                    return $result;
                }
            } else {
                return $result;
            }
        }
        if (!empty($this->config['response_ignore_error'])) {
            return false;
        }
        if (isset($this->config['error_code_field'])) {
            $error_code = Arr::get($result, $this->config['error_code_field']);
        }
        if (isset($this->config['error_message_field'])) {
            $error_message = Arr::get($result, $this->config['error_message_field']);
        }
        return error(isset($error_code) ? "[$error_code]".($error_message ?? '')  : $client->error, 2);
    }
}