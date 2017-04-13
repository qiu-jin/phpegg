<?php
namespace Framework\Core\App;

class Cli
{
    private $pid;
    
    
    public function __construct($config)
    {
        if (PHP_SAPI === 'cli' || defined('STDIN')) {
            $path = getopt('m:c:a:');
            $dispatch = self::_dispatch($path, $module);
            $this->pid = getmypid();
            if ($dispatch) {
                $class = 'controller\\'.$dispatch['module'].'\\'.implode('\\', $dispatch['controller']);
                $_instance = new $class();
                if (is_subclass_of($_instance, 'Core\Controller\Cli')) {
                    $_instance->_run($dispatch);
                }
            }
        }
        $this->error();
    }
    
    public function run($dispatch)
    {
        $this->m = $dispatch['module'];
        $this->c = $dispatch['controller'];
        $this->a = $dispatch['action'];
        //$this->params = file_get_contents('php://stdin');
        try {
            $method = new \ReflectionMethod($this, $this->a);
        } catch (\Exception $e) {
            self::error('404');
        }
        $conf = config('controller');
        if (!($conf['mode']) || !in_array($conf['mode'], array('crontab'))) {
            
        }
        $result = call_user_func_array(array($this, $this->a), $this->params);
        if ($this->result) {
            foreach ($this->result as $k => $v) {
                $result[$k] = $v;
            }
        }
        print_r($result);
        if ($result) file_put_contents('php://stdou', json_encode($result));
    }
    
    public function error($code = null, $message = null)
    {
        file_put_contents('php://stderr', "$code\t".json_encode($message));
        abort();
    }
    
    private static function _dispatch($path, $module)
    {
        if (empty($path['c']) || empty($path['a'])) return false;
        $path['c'] = explode('/', $path['c']);
        if (is_array($module)) {
            if (isset($path['m']) && in_array($path['m'], $module)) {
                if (method_exists('controller\\'.$path['m'].'\\'.implode('\\', $path['c']), $path['a'])) {
                    return array(
                        'module'    => $path['m'],
                        'controller'=> $path['c'],
                        'action'    => $path['a']
                    );
                }
            }
        } else {
            if (!isset($path['m']) || $path['m'] === $module) {
                if (method_exists('controller\\'.$module.'\\'.implode('\\', $path['c']), $path['a'])) {
                    return array(
                        'module'    => $module,
                        'controller'=> $path['c'],
                        'action'    => $path['a']
                    );
                }
            }
        }
        return false;
    }
}
