<?php
namespace framework\driver\rpc\query;

use framework\core\Loader;

/* 
 * https://grpc.io/docs/quickstart/php.html
 */
class Grpc
{
    protected $ns;
    protected $rpc;
    protected $options;
    protected $clients;
    protected $request_classes;
    
    public function __construct($rpc, $ns, $options)
    {
        $this->ns = $ns;
        $this->rpc = $rpc;
        $this->options = $options;
    }
    
    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        if (!$this->ns) {
            throw new \Exception('Service is empty');
        }
        $service  = implode('\\', $this->ns);
        $class = $service.'Client';
        if (!isset($this->clients[$class])) {
            $this->clients[$class] = new $class($this->options['endpoint'], [
                'credentials' => \Grpc\ChannelCredentials::createInsecure()
            ]);
        }
        if ($this->auto_bind_param) {
            if ($this->request_scheme_format) {
                $request_class = strtr($this->request_scheme_format, [
                    '{service}' => $service,
                    '{method}'  => ucfirst($method)
                ]);
            } else {
                $request_class = $this->getRequestClass($class, $method);
            }
            $params = $this->bindParams($request_class, $params);
        }
        list($reply, $status) = $this->client->$method($params)->wait();
        if ($status->code === 0) {
            return $reply;
        }
        error("[$status->code]$status->details");
    }
    
    protected function getRequestClass($class, $method)
    {
        if (!isset($this->request_classes[$class][$method])) {
            $this->request_classes[$class][$method] = (string) (new \ReflectionMethod($class, $method))->getParameters()[0]->getType();
        }
        return $this->request_classes[$class][$method];
    }
}