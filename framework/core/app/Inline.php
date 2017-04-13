<?php
namespace Framework\Core\App;

use Framework\App;
use Framework\Core\View;
use Framework\Core\Router;
use Framework\Core\Config;
use Framework\Core\Http\Request;
use Framework\Core\Http\Response;

class Inline
{
    private $config;
    private $dispatch;
    
    public function __construct($config)
    {
        $dispatch = $this->dispatch();
        if ($dispatch) {
            $this->dispatch = $dispatch;
        } else {
            $this->error(404);
        }
    }

    public function run(callable $return_handler)
    {
        if (App::runing()) return;
        $params = [];
        $_return = require($this->dispatch['file']);
        if ($_return === 1) {
            $_return = null;
        }
        if (isset($return) && isset($_return)) {
            $_return = array_merge((array) $return,(array) $_return);
        }
        if (isset($return_handler)) {
            $return_handler($_return);
        }
        $_return && $this->response($_return);
    }
    
    public function error($code, $message = null)
    {
        Response::send("$code\r\n".print_r($message, true));
    }
    
    public function params()
    {
        
    }
    
    public function response($return = null)
    {
        switch ($this->config['view']) {
            case 0:
                Response::json($return);
            case 1:
                if (Config::has('view')) {
                    Response::view(implode('/',Request::dispatch('call')), $return);
                } else {
                    Response::json($return);
                }
            case 2:
                Response::view(implode('/',Request::dispatch('call')), $return);
        }
    }
    
    public function dispatch()
    {
        $path = trim(Request::path(), '/');
        if ($path) {
            if (preg_match('/^(\w+)(\/\w+)*$/', $path)) {
                $file = APP_DIR.'controller/'.$path.'.php';
                if (file_exists($file)) {
                    return ['file' => $file];
                }
            }
        } elseif (isset($this->config['index'])) {
            return ['file' => APP_DIR.'/controller/'.$index.'.php']; 
        }
        return false;
    }
}
