<?php
namespace framework\driver\rpc\client;

use framework\util\Arr;
use framework\util\Str;
use framework\core\http\Client;

class Http
{
    protected $config;
    
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    public function make($method, $ns, $filters, $params)
    {
        if ($params) {
            list($ns, $body) = $this->parseParams($ns, $params);
        }
        $path = implode('/', $ns);
        if (isset($this->config['url_style'])) {
            $path = $this->convertUrlStyle($path);
        }
        $url = $this->config['endpoint'].'/'.$path;
        if (isset($this->config['url_suffix'])) {
            $url .= $this->config['url_suffix'];
        }
        if ($filters) {
            $url .= (strpos($url, '?') ? '&' : '?').$this->setFilter($filters);
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
    
    public function response($client)
    {
        $response = $client->response;
        if ($response->status >= 200 && $response->status < 300) {
            $result = $response->body;
            if (isset($this->config['response_decode'])) {
                $result = $this->config['response_decode']($result);
            }
            if (isset($this->config['response_result_field'])) {
                if (($result = Arr::field($result, $this->config['response_result_field'])) !== null) {
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
            $error_code = Arr::field($result, $this->config['error_code_field']);
        }
        if (isset($this->config['error_message_field'])) {
            $error_message = Arr::field($result, $this->config['error_message_field']);
        }
        return error(isset($error_code) ? "[$error_code]".($error_message ?? '')  : $client->error, 2);
    }
    
    protected function setFilter($filters)
    {
        foreach ($filters as $filter) {
            $count = count($filter);
            if ($count === 1) {
                $arr = array_merge($arr, $filter[0]);
            } elseif ($count === 2) {
                $arr[$filter[0]] = $filter[1];
            }
        }
        return isset($arr) ? http_build_query($arr) : '';
    }
    
    protected function parseParams($ns, $params)
    {
        $body = is_array(end($params)) ? array_pop($params) : null;
        if ($params) {
            $ns = array_merge($ns, $params);
        }
        return [$ns, $body];
    }
    
    protected function convertUrlStyle($path)
    {
        switch ($this->config['url_style']) {
            case 'snake_to_spinal':
                return strtr($path, '_', '-');
            case 'camel_to_spinal':
                return Str::toSnake($path, '-');
            case 'snake_to_camel':
                return Str::toCamel($path);
            case 'camel_to_snake':
                return Str::toSnake($path);
        }
        throw new Exception("Illegal url style: $this->config['url_style']");
    }
}