<?php
namespace framework\core\app;

use framework\App;
use framework\util\Arr;
use framework\core\Job;
use framework\core\Hook;
use framework\core\Loader;
use framework\core\Logger;
use framework\core\http\Request;
use framework\core\http\Response;

class Jsonrpc extends App
{
    protected $id;
    protected $return;
    protected $config = [
        // 控制器公共路径
        'controller_path' => 'controller',
        /* 参数模式
         * 0 无参数
         * 1 循序参数
         * 2 键值参数
         */
        'param_mode' => 1,
        // 启用批调用
        'enable_batch' => 0,
        // 最大批调用数
        'max_batch_num' => 999,
        /* 通知调用模式
         * null, false, 不使用通知调用
         * ture，使用Hook close实现
         * string，使用异步队列任务实现，string为队列任务名
         */
        'notification_mode' => null,
        // 通知回调方法
        'notification_callback' => null,
        // Request 解码
        'request_encode' => 'jsonencode',
        // Response 编码
        'response_decode' => 'jsondecode',
        // Response header content type
        'response_content_type' => null
    ];
    protected $is_batch_call = false;
    protected $controller_instances = [];
    
    const VERSION = '2.0';
    
    protected function dispatch()
    {
        $data = ($this->config['request_decode'])(Request::body());
        if (!$data) {
            $this->abort(-32700, 'Parse error');
        }
        //批调度
        if ($this->config['enable_batch'] && !Arr::isAssoc($data)) {
            if (count($data) > $this->config['max_batch_num']) {
                $this->abort(-32001, 'Max batch num');
            }
            $this->is_batch_call = true;
            foreach ($data as $item) {
                $dispatch[] = $this->getDispatch($item);
            }
        } else {
            $dispatch[] = $this->getDispatch($data);
        }
        return $dispatch;
    }
    
    protected function handle()
    {
        foreach ($this->dispatch as $dispatch) {
            $id = $dispatch['id'] ?? null;
            if (isset($dispatch['error'])) {
                $return[] = [
                    'id'        => $id,
                    'jsonrpc'   => self::VERSION,
                    'error'     => $dispatch['error']
                ];
            } else {
                // 通知调度
                if (!isset($dispatch['id']) && $this->config['notification_mode']) {
                    $this->addJob($dispatch);
                    $return[] = null;
                } else {
                    try {
                        $return[] = [
                            'id'        => $id,
                            'jsonrpc'   => self::VERSION,
                            'result'    => $this->call($dispatch)
                        ];
                    } catch (\Throwable $e) {
                        $return[] = [
                            'id'        => $id,
                            'jsonrpc'   => self::VERSION,
                            'error'     => ['code' => $e->getCode() ?? -32000, 'message' => $e->getMessage()]
                        ];
                        $this->writeExceptionLog($e);
                    }
                }
            }
        }
        return $this->is_batch_call ? $return : $return[0];
    }
    
    protected function call($dispatch)
    {
        $action = $dispatch['action'];
        $params = $dispatch['params'];
        $controller = $dispatch['controller'];
        switch ($this->config['param_mode']) {
            case 1:
                return $controller->{$action}(...$params);
            case 2:
                $parameters = [];
                $method = new \ReflectionMethod($controller, $action);
                if ($method->getnumberofparameters() > 0) {
                    foreach ($method->getParameters() as $param) {
                        if (isset($params[$param->name])) {
                            $parameters[] = $params[$param->name];
                        } elseif($param->isDefaultValueAvailable()) {
                            $parameters[] = $param->getdefaultvalue();
                        } else {
                            throw new \Exception('Invalid params', -32602);
                        }
                    }
                }
                return $method->invokeArgs($controller, $parameters);
            default:
                Request::set('post', $params);
                return $controller->{$action}();
        }
    }
    
    protected function getDispatch($item)
    {
        $id = $data['id'] ?? null;
        if (isset($item['method'])) {
            $method = explode('.', $item['method']);
            if (count($method) > 1) {
                $action = array_pop($method);
                if ($action[0] !== '_' ) {
                    $class = $this->getClass($method);
                    if (isset($this->controller_instances[$class])) {
                        $controller = $this->controller_instances[$class];
                    } else {
                        if (Loader::importPrefixClass($class)) {
                            $controller = new $class();
                            $this->controller_instances[$class] = $controller;
                        } else {
                            $this->controller_instances[$class] = false;
                        }
                    }
                    if (!empty($controller) && is_callable($controller, $action)) {
                        return [
                            'id'            => $id,
                            'controller'    => $controller,
                            'action'        => $action,
                            'params'        => $data['params'] ?? []
                        ];
                    }
                }
            }
            return [
                'id'    => $id,
                'error' => ['code' => -32601, 'message' => 'Method not found']
            ];
        }
        return [
            'id'    => $id,
            'error' => ['code' => -32600, 'message' => 'Invalid Request']
        ];
    }
    
    protected function error($code = null, $message = null)
    {
        if ($this->is_batch_call) {
            $return_count = count($this->return);
            $dispatch_count = count($this->dispatch);
            if ($dispatch_count > $return_count) {
                $this->return[] = [
                    'id'        => $this->dispatch[$return_count]['id'] ?? null,
                    'jsonrpc'   => self::VERSION,
                    'error'     => [
                        'code'      => $code ?? -32000,
                        'message'   => $message ?? 'Server error'
                    ]
                ];
                if ($dispatch_count > $return_count + 1) {
                    for ($i = $return_count + 1; $i < $dispatch_count; $i++) {
                        $this->return[] = [
                            'id'        => $this->dispatch[$return_count+$i]['id'] ?? null,
                            'jsonrpc'   => self::VERSION,
                            'error'     => ['code'=> -32000, 'message' => 'Server error']
                        ];
                    }
                }
            }
            $this->response($this->return);
        } else {
            $this->response([
                'id'        => $this->dispatch['id'] ?? null,
                'jsonrpc'   => self::VERSION,
                'error'     => compact('code', 'message')
            ]);
        }
    }
    
    protected function response($return = null)
    {
        $this->return = null;
        Response::send(($this->config['response_decode'])($return), $this->config['response_content_type'], false);
    }
    
    protected function getClass($method)
    {
        return 'app\\'.$this->config['controller_path'].'\\'.implode('\\', $method);
    }
    
    protected function addJob($dispatch)
    {
        Hook::add('close', function () {
            try {
                $return = $this->call($dispatch);
            } catch (\Throwable $e) {
                $this->writeExceptionLog($e);
            }
            if (isset($dispatch['callback']) && isset($this->config['notification_callback'])) {
                ($this->config['notification_callback'])($dispatch['callback'], $return);
            }
        });
    }
    
    protected function addQueueJob($dispatch)
    {
        /*
        Job::add($this->config['notification_mode'], function () {
            $return = $this->call($dispatch);
            if (isset($dispatch['callback']) && isset($this->config['notification_callback'])) {
                ($this->config['notification_callback'])($dispatch['callback'], $return);
            }
        });
        */
    }
    
    protected function writeExceptionLog($e)
    {
        Logger::write(Logger::ERROR, 'Uncaught '.get_class($e).': '.$e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }

}