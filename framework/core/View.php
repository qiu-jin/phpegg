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
    private static $template;
    
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
    
    public static function var($name, $value)
    {
        self::$view->vars[$name] = $value;
    }
    
    public static function func($name, callable $value)
    {
        self::$view->func[$name] = $value;
    }
    
    public static function output($tpl, $vars = null)
    {
        Response::view($tpl, $vars);
    }
    
    public static function render($tpl, $vars = null)
    {
        $phpfile = self::path(trim($tpl));
        if ($phpfile) {
            if (isset(self::$view->vars)) {
                extract(self::$view->vars, EXTR_SKIP);
                self::$view->vars = null;
            }
            if ($vars) {
                extract($vars, EXTR_SKIP);
                unset($vars);
            }
            ob_start();
            include $phpfile;
            return ob_get_clean();
        } else {
            return self::error('404', 'Not found template: '.$tpl);
        }
    }
    
    public static function error($code, $message = null)
    {   
        if (isset(self::$config['error'][$code])) {
            $phpfile = self::path(self::$config['error'][$code]);
            if ($phpfile) {
                ob_start();
                include $phpfile;
                return ob_get_clean();
            }
        }
        return $code === 404 ? ViewError::render404($message) : ViewError::renderError($message);
    }
    
    public static function __callStatic($name, $params = [])
    {
        if (isset($this->config['methods'][$method])) {
            $vars = $this->config['methods'][$name];
            $tpl = array_pop($vars);
            if ($vars) {
                foreach (array_keys($vars) as $i => $v) {
                    if (!isset($params[$i]))  break;
                    $vars[$v] = $params[$i];
                }
            }
            Response::view($tpl, $vars);
        }
        throw new \Exception('Illegal View method: '.$method);
    }
    
    private static function path($tpl, $dir = null)
    {
        $path = $tpl{0} === '/' ? self::$config['dir'].$tpl : $dir.'/'.$tpl;
        $phpfile = $path.'.php';
        if (isset(self::$config['template'])) {
            if (empty(self::$config['template']['dir'])) {
                $tplfile = $path.'.htm';
            } else {
                $dir = dirname($phpfile);
                if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                    return false;
                }
                $tplfile = str_replace(self::$config['template']['dir'], self::$config['dir'], $path).'.htm';
            }
            if (file_exists($phpfile)) {
                if (file_exists($tplfile)) {
                    if (filemtime($phpfile) > filemtime($tplfile)) {
                        return $phpfile;
                    } else {
                        if (file_put_contents($phpfile, self::template()->complie(file_get_contents($tplfile)))) {
                            return $phpfile;
                        }
                    }
                }
            } elseif (file_exists($tplfile)) {
                if (file_put_contents($phpfile, self::template()->complie(file_get_contents($tplfile)))) {
                    return $phpfile;
                }
            }
        } elseif (file_exists($phpfile)) {
            return $phpfile;
        }
        return false;
    }

    private static function layout($tpl, $file)
    {
        if (empty(self::$config['template'])) {
            return;
        }
        $path = $tpl{0} === '/' ? $tpl : dirname($file).'/'.$tpl;
        if (isset(self::$config['template']['dir'])) {
            $tplfile = str_replace(self::$config['dir'], self::$config['template']['dir'], $path).'.htm';
        } else {
            $tplfile = $path.'.htm';
        }
        $phpfile = $path.'.php';
        if (file_exists($tplfile) && (!file_exists($file) || filemtime($tplfile) > filemtime($file))) {
            $php_content = file_get_contents($file);
            $complie_content = "<?php View::layout('$tpl', __FILE__); ?>".PHP_EOL;
            $complie_content .= self::template()->complie(file_get_contents($tplfile), $php_content);
            if (file_put_contents($file, $complie_content)) {
                if (!empty(self::$config['template']['layout'])) {
                    $dir = dirname($file).'/.layout/';
                    if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                        return false;
                    }
                    file_put_contents($dir.basename($file), $content);
                }
                if (extension_loaded('opcache')) {
                    opcache_compile_file($file);
                }
                include $file;
                return true;
            } 
        }
        return false;
    }
    
    private static function template()
    {
        if (isset(self::$template)) {
            return self::$template;
        }
        return self::$template = new Template(self::$config['template']);
    }
    
    public static function free()
    {
        self::$view = null;
        self::$config = null;
        self::$template = null;
    }
}
View::init();
