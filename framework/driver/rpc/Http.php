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
        'client_method_alias' => null
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
        return new query\Http($this, $name, $this->config['client_method_alias']);
    }
    
    public function __send($method, $ns, $params, $client_methods)
    {
        return $this->send(isset($params) ? 'POST' : 'GET', implode('/', $ns)."/$method", $params, $client_methods);
    }

    protected function send($method, $path, $params, $client_methods)
    {
        if (isset($this->config['url_style'])) {
            $path = $this->{$this->config['url_style']}($path);
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
                foreach ($values as $value) {
                    $client->{$name}(...$value);
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
            if ($filters === 2) {
                $filters[$filter[0]] = $filter[1];
            } elseif ($count === 1 && is_array($filter)) {
                $filters += $filter;
            }
        }
        return $filters;
    }
        
    protected function snakeCamel($str)
    {
        return Str::toCamel($str);
    }
    
    protected function camelSnake($str)
    {
        return Str::toSnake($str);
    }
    
    protected function spinalSnake($str)
    {
        return strtr('_', '-', $str);
    }
    
    protected function spinalCamel($str)
    {
        return Str::toCamel($str, '-');
    }
}