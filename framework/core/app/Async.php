<?php
namespace framework\core\app;

use framework\core\Getter;

/*
 * pecl install swoole
 */
class Async extends Cli
{   
    protected $config = [
        'server_ip' => '127.0.0.1',
        'server_port' => 9501,
    ];

    protected $server;
    
    protected function dispatch()
    {
        $this->server = new \swoole_http_server($this->config['server_ip'], $this->config['server_port']);
        $this->server->on("start", function ($server) {
            
        });
        $this->server->on("request", function ($request, $response) {

        });
        $this->server->start();
    }
    
    protected function call()
    {

    }
    
    protected function error($code = null, $message = null)
    {

    }
    
    protected function response($return = null)
    {

    }
}
