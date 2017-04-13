<?php
namespace Framework\Core;

use Framework\Extend\View\Template;
use Framework\Extend\View\Error as ViewError;

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
    }
    
    public static function assign($name, $value)
    {
        self::$view->vars[$name] = $value;
    }
    
    public static function func($name, $value)
    {
        self::$view->func[$name] = $value;
    }
    
    public static function render($tpl, array $vars = [])
    {
        $phpfile = self::_include(trim($tpl));
        if ($phpfile) {
            if (isset(self::$view->vars)) {
                extract(self::$view->vars, EXTR_SKIP);
                self::$view->vars = null;
            }
            if ($vars) {
                extract($vars, EXTR_SKIP);
                unset($vars);
            }
            include $phpfile;
            self::clear();
        } else {
            self::error('404', 'Not found template: '.$tpl);
        }
    }
    
    public static function error($name, array $message = [])
    {   
        if (isset(self::$config['error'][$name])) {
            $phpfile = self::_include(self::$config['error'][$name]);
            if ($phpfile) {
                include $phpfile;
                return self::clear();
            }
        }
        self::clear();
        echo $code === '404' ? ViewError::print404($message) : ViewError::printError($message);
    }
    
    private static function _import($tpl, $dir = null)
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
            if (isset(self::$config['template']['dir'])) {
                $htmfile = str_replace(self::$config['template']['dir'], self::$config['dir'], $path, 1).'.htm';
                $dir = dirname($phpfile);
                if (!is_dir($dir)) mkdir($dir, 0777, true);
            } else {
                $htmfile = $path.'.htm';
            }
            if (file_exists($phpfile)) {
                if (!file_exists($htmfile) || filemtime($phpfile) > filemtime($htmfile)) {
                    return $phpfile;
                } else {
                    file_put_contents($phpfile, self::_template_handler()->complie(file_get_contents($htmfile)));
                    return $phpfile;
                }
            } elseif (file_exists($htmfile)) {
                file_put_contents($phpfile, self::_template_handler()->complie(file_get_contents($htmfile)));
                return $phpfile;
            }
        } elseif (file_exists($phpfile)) {
            return $phpfile;
        }
        return false;
    }
    
    private function _inline($tpl, $dir = null){}

    private function _layout($tpl, $phpfile, $str = null)
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
    
    private static function _template_handler()
    {
        return isset(self::$template_handler) ? self::$template_handler : self::$template_handler = new Template(self::$config['template']);
    }
    
    private static function clear()
    {
        self::$view = null;
        self::$config = null;
        self::$template_handler = null;
    }
}
View::init();
