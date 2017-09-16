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
    protected $allow_client_methods = [
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
        return new query\Http($this, $name, $this->config['ns_method'] ?? 'ns', $client_methods);
    }
    
    public function call($ns, $method, $params, $client_methods)
    {
        return $this->send(isset($params) ? 'POST' : 'GET', implode('/', $ns)."/$method", $params, $client_methods);
    }

    protected function send($method, $path, $params, $client_methods)
    {
        if (isset($this->config['url_style'])) {
            switch ($this->config['url_style']) {
                case 'snake_spinal':
                    $path = strtr('_', '-', $path);
                    break;
                case 'camel_spinal':
                    $path = Str::toSnake($path, '-');
                    break;
                case 'snake_to_camel':
                    $path = Str::toCamel($path);
                    break;
                case 'camel_to_snake':
                    $path = Str::toSnake($path);
                    break;
            }
        }
        $url = $this->config['host'].'/'.$path.$this->config['ext'];
        $data = null;
        $count = count($params);
        if ($count === 1) {
            if (is_array($params[0])) {
                $data = $params[0];
            } else {
                $url .= '/'.$params[0];
            }
        } elseif ($count > 1) {
            $url .= '/'.$params[0];
            $data = $params[1];
        }
        if (isset($client_methods['filter'])) {
            $url .= (strpos('?', $url) ? '&' : '?').http_build_query($this->filter($client_methods['filter']));
            unset($client_methods['filter']);
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
                if (in_array($name, self::$allow_client_methods)) {
                    foreach ($values as $value) {
                        $client->{$name}(...$value);
                    }
                }
            }
        }
        if ($data) {
            $client->{$this->config['requset_encode']}($data);
        }
        $status = $client->status;
        if ($status >= 200 && $status < 300) {
            return $client->{$this->config['response_decode']};
        }
        return $status ? error($status) : error(var_export($client->error, true));
    }
    
    protected function filter($vaules)
    {
        $filters = [];
        foreach ($vaules as $filter) {
            $count = count($filter);
            if ($count === 2) {
                $filters[$filter[0]] = $filter[1];
            } elseif ($count === 1 && is_array($filter)) {
                $filters += $filter;
            }
        }
        return $filters;
    }
}