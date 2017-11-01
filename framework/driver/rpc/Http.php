<?php
namespace framework\driver\rpc;

use framework\util\Str;
use framework\core\http\Client;

class Http
{
    const ALLOW_CLIENT_METHODS = [
        'body', 'json', 'form', 'file', 'buffer', 'stream', 'header', 'headers', 'timeout', 'curlopt', 'debug'
    ];
    
    protected $config = [
        'ext' => null,
        'url_style' => null,
        'requset_encode' => null,
        'response_decode' => null,
    ];

    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
    }
    
    public function __get($name)
    {
        return $this->query($name);
    }
    
    public function __call($method, $params)
    {
        return $this->query()->$method(...$params);
    }
    
    public function query($name = null, $options = null)
    {
        return new query\Http($this, $name, $options);
    }
    
    public function batch($common_ns = null, $common_client_methods = null, $options = null)
    {
        return new query\HttpBatch($this, $common_ns, $common_client_methods, $options);
    }
    
    public function requsetHandle($method, $ns, $filters, $params, $client_methods)
    {
        if ($params) {
            $body = $this->setParams($ns, $params);
        }
        $path = implode('/', $ns);
        if (isset($this->config['url_style'])) {
            $path = $this->convertUrlStyle($path);
        }
        $url = $this->config['host'].'/'.$path.$this->config['ext'];
        if ($filters) {
            $url .= (strpos($url, '?') ? '&' : '?').$this->setfilter($filters);
        }
        $client = new Client($method, $url);
        if (isset($this->config['headers'])) {
            $client->headers($this->config['headers']);
        }
        if (isset($this->config['curlopt'])) {
            $client->curlopt($this->config['curlopt']);
        }
        if ($client_methods) {
            foreach ($client_methods as $name=> $values) {
                if (in_array($name, self::ALLOW_CLIENT_METHODS)) {
                    foreach ($values as $value) {
                        $client->{$name}(...$value);
                    }
                }
            }
        }
        if (isset($body)) {
            if (isset($this->config['requset_encode'])) {
                $body = $this->config['requset_encode']($body);
            }
            $client->body($body);
        }
        return $client;
    }
    
    public function responseHandle($client)
    {
        $status = $client->getStatus();
        if ($status >= 200 && $status < 300) {
            $body = $client->getBody();
            return isset($this->config['response_decode']) ? $this->config['response_decode']($body) : $body;
        }
        return error($status ?: $client->getErrorInfo());
    }
    
    protected function setfilter($filters)
    {
        $arr = [];
        foreach ($filters as $filter) {
            $count = count($filter);
            if ($count === 1) {
                $arr = array_merge($arr, $filter[0]);
            } elseif ($count === 2) {
                $arr[$filter[0]] = $filter[1];
            }
        }
        return http_build_query($arr);
    }
    
    protected function setParams(&$ns, $params)
    {
        $return = is_array(end($params)) ? array_pop($params) : null;
        if ($params) {
            $ns = array_merge($ns, $params);
        }
        return $return;
    }
    
    protected function convertUrlStyle($path)
    {
        switch ($this->config['url_style']) {
            case 'snake_to_spinal':
                return strtr('_', '-', $path);
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