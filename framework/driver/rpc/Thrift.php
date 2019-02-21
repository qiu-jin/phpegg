<?php
namespace framework\driver\rpc;

use framework\core\Loader;

/* 
 * composer require apache/thrift
 * https://github.com/apache/thrift
 */
use Thrift\Transport\TSocket;
use Thrift\Transport\TBufferedTransport;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Protocol\TMultiplexedProtocol;

class Thrift extends Rpc
{
    // 服务实例
    protected $services;
    // 传输编码
    protected $protocol;
    // 传输层实例
    protected $transport;
    // 是否多服务类型
    protected $tmultiplexed = false;
    // service类名前缀
    protected $service_prefix;
    // 是否自动绑定参数
    protected $auto_bind_params = false;
    // 方法参数数据
    protected $service_method_params;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $socket = new TSocket($config['host'], $config['port']);
        if (isset($config['send_timeout'])) {
            $socket->setRecvTimeout($config['send_timeout']);
        }
        if (isset($config['recv_timeout'])) {
            $socket->setRecvTimeout($config['recv_timeout']);
        }
        $this->transport = new TBufferedTransport($socket, 1024, 1024);
        $this->protocol  = new TBinaryProtocol($this->transport);
        $this->transport->open();
        foreach ($config['service_load_rules'] as $type => $rules) {
            Loader::add($type, $rules);
        }
        if (isset($config['tmultiplexed'])) {
            $this->tmultiplexed = $config['tmultiplexed'];
        }
        if (isset($config['service_prefix'])) {
            $this->service_prefix = $config['service_prefix'];
        }
        if (isset($config['auto_bind_param'])) {
            $this->auto_bind_param = $config['auto_bind_param'];
        }
    }
    
    /*
     * query实例
     */
    public function query($name = null)
    {
        return new query\Thrift($this, $name);
    }
    
    /*
     * 调用
     */
    public function call($ns, $method, $params)
    {
        if (isset($this->service_prefix)) {
            array_unshift($ns, $this->service_prefix);
        }
        if (!$ns) {
            throw new \Exception('service is empty');
        }
        $class = implode('\\', $ns);
        if (!isset($this->services[$class])) {
            if ($this->tmultiplexed) {
                $name = substr(strrchr($class, '\\'), 1);
                $this->services[$class] = new $class(new TMultiplexedProtocol($this->protocol, $name));
            } else {
                $this->services[$class] = new $class($this->protocol);
            }
        }
        if ($this->auto_bind_params) {
            $this->bindParams($class, $method, $params);
        }
        return $this->services[$class]->$method(...$params);
    }
    
    /* 
     * 参数绑定
     */
    protected function bindParams($class, $method, &$params)
    {
        if (isset($this->service_method_params[$class][$method])) {
            if (empty($this->service_method_params[$class][$method])) {
                return;
            }
            foreach ($this->service_method_params[$class][$method] as $i => $name) {
               $params[$i] = new $name($params[$i]);
            }
        } else {
            $this->service_method_params[$class][$method] = [];
            foreach ((new \ReflectionMethod($class, $method))->getParameters() as $i => $parameter) {
                if ((string) $parameter->getType() === 'object') {
                    $name = $parameter->getName();
                    $params[$i] = new $name($params[$i]);
                    $this->service_method_params[$class][$method][$i] = $name;
                }
            }
        }
    }
    
    /* 
     * 析构函数
     */
    public function __destruct()
    {
        $this->transport && $this->transport->close();
    }
}
