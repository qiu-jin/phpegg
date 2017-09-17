<?php
namespace framework\driver\rpc;

use framework\util\Str;
use framework\core\http\Client;

class Http
{
    protected $config = [
        'ext' => null,
        'url_style' => null,
        'requset_encode' => 'body',
        'response_decode' => 'body',
    ];
    const ALLOW_CLIENT_METHODS = [
        'filter', 'body', 'json', 'form', 'file', 'buffer', 'stream', 'header', 'headers', 'timeout', 'curlopt', 'curlinfo', 'debug'
    ];

    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
    }
    
    public function __get($name)
    {
        return $this->query($name);
    }

    public function query($name, $client_methods = null)
    {
        $ns_method = $this->config['ns_method'] ?? 'ns';
        $filter_method = $this->config['filter_method'] ?? 'filter';
        return new query\Http($this, $name, $ns_method, $filter_method,$client_methods);
    }
    
    public function call($method, $path, $filters, $body, $client_methods)
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
        if ($body) {
            $client->{$this->config['requset_encode']}($body);
        }
        $status = $client->status;
        if ($status >= 200 && $status < 300) {
            return $client->{$this->config['response_decode']};
        }
        return $status ? error($status) : error(var_export($client->error, true));
    }
    
    protected function convertUrlStyle()
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