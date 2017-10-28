<?php
namespace framework\driver\rpc;

use framework\util\Str;
use framework\core\http\Client;

class Http
{
    const ALLOW_CLIENT_METHODS = [
        'filter', 'body', 'json', 'form', 'file', 'buffer', 'stream', 
        'header', 'headers', 'timeout', 'curlopt', 'curlinfo', 'debug'
    ];
    
    protected $config = [
        'ext' => null,
        'url_style' => null,
        'requset_encode' => 'body',
        'response_decode' => 'body',
        'method_alias' => [
            'ns'  => 'ns',
            'call'  => 'call',
            'query' => 'query',
            'batch' => 'batch',
            'setfilter' => 'setfilter',
            'setParams' => 'setParams',
            'makeClient'=> 'makeClient'
        ],
    ];

    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
    }
    
    public function __get($name)
    {
        return $this->ns($name);
    }
    
    public function __call($method, $params)
    {
        if (isset($this->config['method_alias'][$method])) {
            return $this->{$this->config['method_alias'][$method]}(...$params);
        }
        return $this->ns()->$method(...$params);
    }
    
    protected function ns($name)
    {
        return new query\Http($this, $this->getOptions(), $name);
    }
    
    protected function batch()
    {
        return new query\HttpBatch($this, $this->getOptions());
    }
    
    protected function setfilter($params)
    {
        $num = func_num_args();
        if ($num === 1) {
            return $params;
        } elseif ($num === 2) {
            return [$params[0], $params[1]];
        }
    }
    
    protected function setParams(&$ns, $params)
    {
        $return = is_array(end($params)) ? array_pop($params) : null;
        if ($params) {
            $ns = array_merge($ns, $params);
        }
        return $return;
    }
    
    protected function makeClient($method, $path, $filters, $client_methods)
    {
        if (isset($this->config['url_style'])) {
            $path = $this->convertUrlStyle($path);
        }
        $url = $this->config['host'].'/'.$path.$this->config['ext'];
        if ($filters) {
            $url .= '?'.http_build_query($filters);
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
        return $client;
    }
    
    protected function getOptions()
    {
        return [
            'ns_method' => array_search('ns', $this->config['method_alias'], true),
            'call_method' => array_search('call', $this->config['method_alias'], true),
            'filter_method' => array_search('filter', $this->config['method_alias'], true),
            'requset_encode' => $this->config['requset_encode'],
            'response_decode' => $this->config['response_decode'],
        ];
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