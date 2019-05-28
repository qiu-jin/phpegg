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
    public function make($method, $ns, $filters, $params)
    {
        if ($params) {
            list($ns, $body) = $this->parseParams($ns, $params);
        }
        $path = implode('/', $ns);
        if (isset($this->config['convert_path_style'])) {
            $path = $this->convertPathStyle($path);
        }
        $url = $this->config['endpoint'].'/'.$path;
        if (isset($this->config['url_suffix'])) {
            $url .= $this->config['url_suffix'];
        }
        if ($filters) {
            $url .= '?'.$this->setFilter($filters);
        }
        $client = new Client($method, $url);
        if (isset($this->config['http_headers'])) {
            $client->headers($this->config['http_headers']);
        }
        if (isset($this->config['http_curlopts'])) {
            $client->curlopts($this->config['http_curlopts']);
        }
        if (isset($body)) {
            if (isset($this->config['requset_encode'])) {
                $body = $this->config['requset_encode']($body);
            }
            $client->body($body);
        }
        return $client;
    }
    
    /*
     * 响应处理
     */
    public function response($client)
    {
        $response = $client->response();
        if ($response->status >= 200 && $response->status < 300) {
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
    
    /*
     * 设置filter
     */
    protected function setFilter($filters)
    {
		$arr = [];
        foreach ($filters as $filter) {
            $count = count($filter);
            if ($count == 1) {
                $arr = $filter[0] + $arr;
            } elseif ($count >= 2) {
                $arr[$filter[0]] = $filter[1];
            }
        }
        return http_build_query($arr);
    }
    
    /*
     * 解析参数
     */
    protected function parseParams($ns, $params)
    {
        $body = is_array(end($params)) ? array_pop($params) : null;
        if ($params) {
            $ns = array_merge($ns, $params);
        }
        return [$ns, $body];
    }
    
    /*
     * 转换url风格
     */
    protected function convertPathStyle($path)
    {
        switch (strtolower($this->config['convert_path_style'])) {
            case 'snake_to_spinal':
                return strtr($path, '_', '-');
            case 'camel_to_spinal':
                return Str::snakeCase($path, '-');
            case 'snake_to_camel':
                return Str::camelCase($path);
            case 'camel_to_snake':
                return Str::snakeCase($path);
        }
        throw new Exception("Illegal path style: $this->config['convert_path_style']");
    }
}