<?php
namespace framework\core;

use framework\core\http\Response;
use framework\extend\view\Template;
use framework\extend\view\Error as ViewError;

class View
{    
    private static $init;
    private static $view;
    private static $config;
    private static $template_handler;
    
    //run this method in last line when load class
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        self::$view = new \stdClass();
        self::$config = Config::get('view');
        if (empty(self::$config['dir'])) {
            self::$config['dir'] = APP_DIR.'view/';
        }
        Hook::add('exit', __CLASS__.'::free');
    }
    
    public static function __callStatic($method, $params = [])
    {
        if (isset($this->config['methods'][$method])) {
            $vars = null;
            $method_array = $this->config['methods'][$method];
            $tpl = array_pop($method_array);
            if ($method_array) {
                $i = 0;
                foreach ($method_array as $k => $v) {
                    if (is_int($k)) {
                        if (isset($params[$i])) {
                            $vars[$v] = $params[$i];
                        } else {
                            throw new \Exception('Illegal view method: '.$method);
                        }
                    } else {
                        $vars[$k] = isset($params[$i]) ? $params[$i] : $v;
                    }
                    $i++;
                }
            }
            /*
            $count = count($this->config['methods'][$method]);
            if ($count) {
                $vars = array_combine($this->config['methods'][$method], array_pad($params, $count, null));
            }
            */
            Response::view($tpl, $vars);
        }
        throw new \Exception('Illegal view method: '.$method);
    }
    
    public static function var($name, $value)
    {
        self::$view->vars[$name] = $value;
    }
    
    public static function func($name, $value)
    {
        self::$view->func[$name] = $value;
    }
    
    public static function display($tpl, array $vars = null)
    {
        Response::view($tpl, $vars);
    }
    
    public static function render($tpl, array $vars = null)
    {
        $phpfile = self::import(trim($tpl));
        if ($phpfile) {
            if ($vars) {
                extract($vars, EXTR_SKIP);
                unset($vars);
            }
            if (isset(self::$view->vars)) {
                extract(self::$view->vars, EXTR_SKIP);
                self::$view->vars = null;
            }
            ob_start();
            include $phpfile;
            return ob_get_clean();
        } else {
            return self::error('404', 'Not found template: '.$tpl);
        }
    }
    
    public static function error($code, array $message = [])
    {   
        if (isset(self::$config['error'][$name])) {
            $phpfile = self::import(self::$config['error'][$name]);
            if ($phpfile) {
                ob_start();
                include $phpfile;
                return ob_get_clean();
            }
        }
        return $code === '404' ? ViewError::render404($message) : ViewError::renderError($message);
    }
    
    private static function import($tpl, $dir = null)
    {
        if ($tpl{0} === '/') {
            $path = self::$config['dir'].$tpl;
        } elseif ($dir) {
            $path = $dir.'/'.$tpl;
        } else {
            return false;
        }
        $phpfile = $path.'.php';
        if (isset(self::$config['template'])) {
            if (empty(self::$config['template']['dir'])) {
                $htmfile = $path.'.htm';
            } else {
                $dir = dirname($phpfile);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                $htmfile = str_replace(self::$config['template']['dir'], self::$config['dir'], $path, 1).'.htm';
            }
            if (file_exists($phpfile)) {
                if (file_exists($htmfile)) {
                    if (filemtime($phpfile) > filemtime($htmfile)) {
                        return $phpfile;
                    } else {
                        if (file_put_contents($phpfile, self::template_handler()->complie(file_get_contents($htmfile)))) {
                            return $phpfile;
                        }
                    }
                }
            } elseif (file_exists($htmfile)) {
                if (file_put_contents($phpfile, self::template_handler()->complie(file_get_contents($htmfile)))) {
                    return $phpfile;
                }
            }
        } elseif (file_exists($phpfile)) {
            return $phpfile;
        }
        return false;
    }
    
    private function inline($tpl, $dir = null)
    {
        
    }

    private function layout($tpl, $phpfile, $str = null)
    {
        if (!isset($this->template_engine)) return;
        if ($this->textend_num > 2) {
            error('Repeat execution _extend');
        }
        $this->textend_num++;
        $path = ($tpl{0} === '/' ? $tpl : dirname($phpfile).'/'.$tpl);
        $htmfile = isset($this->tpl_dir) ? str_replace($this->view_dir, $this->tpl_dir, $path, 1).'.htm' : $path.'.htm';
        if (is_null($str)) {
            if (file_exists($htmfile) && filemtime($htmfile) > filemtime($phpfile)) {
                $htmfile = substr($phpfile, 0, -3).'htm';
                if (isset($this->tpl_dir)) {
                    $htmfile = str_replace($this->view_dir, $this->tpl_dir, $htmfile, 1);
                }
                if (file_put_contents($phpfile, $this->_template_handler()->complie(file_get_contents($htmfile)))) {
                    if (function_exists('opcache_invalidate')) {
                        opcache_invalidate($phpfile);
                    }
                    include($phpfile);
                    return true;
                } 
            }
        } else {
            $code = '<?php if($this->_extend("'.$tpl.'", __FILE__)) return; ?>'.PHP_EOL;
            $code .= $this->_template_handler()->complie(file_get_contents($htmfile), base64_decode($str));
            if (file_put_contents($phpfile, $code)) {
                if (function_exists('opcache_invalidate')) opcache_invalidate($phpfile);
                include($phpfile);
            }
        }
    }
    
    private static function template_handler()
    {
        if (isset(self::$template_handler)) {
            return self::$template_handler;
        }
        return self::$template_handler = new Template(self::$config['template']);
    }
    
    public static function free()
    {
        self::$view = null;
        self::$config = null;
        self::$template_handler = null;
    }
}
View::init();
