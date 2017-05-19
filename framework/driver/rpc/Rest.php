<?php
namespace framework\driver\rpc;

use framework\util\Xml;
use framework\core\Error;
use framework\core\http\Client;

class Rest
{
    protected $url;
    protected $option;
    protected $headers;

    public function __construct($config)
    {
        $this->server = $config['server'];
    }
    
    public function __get($name)
    {
        return new query\Names($this, $class);
    }
    
    public function __call($method, $params = [])
    {
        return $this->call(null, $method, $params);
    }
    
    public function call($ns, $method, $params)
    {
        if (in_array($method, ['get', 'put', 'post', 'delete'])) {
            $client = new Client($method, $this->url($ns, $query));
            if ($this->headers) {
                $client->headers($this->headers);
            }
            $result = $client->getResult();
            if ($result['status'] >= 200 && $result['status'] < 300) {
                return $result['body'] ? $this->$decode($result['body']) : true
            }
            return $this->setError($result);
        }
        throw new \Exception("no method: $method");
    }
    
    protected function url($ns, $query = null)
    {
        $url = $this->server;
        if ($ns) {
            $url .= '/'.implode('/', $ns);
        }
        if ($query) {
            $url .= '?'.http_build_query($query);
        }
        return $url;
    }
    
    protected function setError($result)
    {
        if ($result['body']) {
            $data = Xml::decode($result['body']);
            if ($data) {
                return (bool) Error::set($data['Code'].': '.$data['Message'], Error::ERROR, 3);
            }
        }
        $error = isset($result['error']) ? 'Curl error '.$result['error'][0].': '.$result['error'][1] : 'unknown error';
        return (bool) Error::set($error, Error::ERROR, 3);
    }
}