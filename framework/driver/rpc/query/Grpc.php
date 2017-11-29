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
        if (!empty($this->options['auto_bind_param'])) {
            if (isset($this->options['request_scheme_format'])) {
                $request_class = strtr($this->options['request_scheme_format'], [
                    '{service}' => $service,
                    '{method}'  => ucfirst($method)
                ]);
            } else {
                $request_class = (string) (new \ReflectionMethod($class, $method))->getParameters()[0]->getType();
            }
            $params = $this->rpc->arrayToRequest($request_class, $params);
        }
        $class = $service.'Client';
        $client = new $class($this->options['endpoint'], [
            'credentials' => \Grpc\ChannelCredentials::createInsecure()
        ]);
        list($response, $status) = $client->$method($params)->wait();
        if ($status->code === 0) {
            if (empty($this->options['response_to_array'])) {
                return $response;
            }
            return $this->rpc->responseToArray($response);
        }
        error("[$status->code]$status->details");
    }
}