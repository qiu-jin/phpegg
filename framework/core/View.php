<?php
namespace framework\core;

use framework\core\http\Response;
use framework\extend\view\Error as ViewError;

class View
{    
    private static $init;
    private static $view;
    private static $config;
    
    /*
     * 初始化
     */
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        self::$view = new \stdClass();
        self::$config = Config::get('view');
        if (empty(self::$config['dir'])) {
            self::$config['dir'] = APP_DIR.'view/';
        }
        Event::on('exit', __CLASS__.'::free');
    }
    
    /*
     * 设置变量
     */
    public static function var($name, $value)
    {
        self::$view->vars[$name] = $value;
    }
    
    /*
     * 设置函数
     */
    public static function func($name, callable $value)
    {
        self::$view->func[$name] = $value;
    }
    
    public static function output($tpl, $vars = null)
    {
        Response::view($tpl, $vars);
    }
    
    /*
     * 渲染页面
     */
    public static function render($tpl, $vars = [])
    {
        if (isset(self::$view->vars)) {
            $vars = array_merge($view->vars, $vars);
        }
        ob_start();
        __include_view_file(self::file($tpl), $vars);
        return ob_get_clean();
    }
    
    /*
     * 返回视图文件路径
     */
    public static function file($tpl, $dir = null)
    {
        if ($dir === null || $tpl[0] === '/') {
            $is_relative_path = false;
            $path = self::$config['dir'].$tpl;
        } else {
            $is_relative_path = true;
            $path = $dir.'/'.$tpl;
        }
        $phpfile = $path.'.php';        
        if (!isset(self::$config['template'])) {
            return $phpfile;
        }
        $tplfile = self::getTemplateFile($path, $is_relative_path);
        if (is_file($tplfile)) {
            if (!is_file($phpfile) || filemtime($phpfile) < filemtime($tplfile)) {
                $dir = dirname($phpfile);
                if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                    throw new Exception("View dir create fail: $dir");
                }
                if (!file_put_contents($phpfile, Template::complie(file_get_contents($tplfile)))) {
                    throw new Exception("View file write fail: $phpfile");
                }
            }
            return $phpfile;
        }
        throw new Exception("Not found template: $tplfile");
    }

    public static function layout($tpl, $file)
    {
        if (!isset(self::$config['template'])) {
            return;
        }
        if ($tpl[0] === '/') {
            $path = self::$config['dir'].$tpl;
        } else {
            $path = dirname($file).'/'.$tpl;
        }
        $layoutfile = self::getTemplateFile($path, $tpl[0] !== '/');
        if (!is_file($layoutfile)) {
            throw new Exception("Not found template: $layoutfile");
        }
        if (filemtime($file) < filemtime($layoutfile)) {
            $tplfile = self::getTemplateFile(substr($file, 0, -4));
            $content = Template::complie(file_get_contents($tplfile), file_get_contents($layoutfile));
            if (file_put_contents($file, $content)) {
                if (extension_loaded('opcache')) {
                    opcache_compile_file($file);
                }
                include $file;
                return true;
            }
            throw new Exception("View file write fail: $file");
        }
    }
    
    private static function layoutFile($tpl)
    {
        $path = self::$config['dir'].$tpl;
        $prefix = self::$config['template']['layout_prefix'] ?? '_layout_';
        $phpfile = dirname($path)."/$prefix".basename($path).'.php';
        if (!isset(self::$config['template'])) {
            return $phpfile;
        }
        $tplfile = self::getTemplateFile($path);
        if (is_file($tplfile)) {
            if (!is_file($phpfile) || filemtime($phpfile) < filemtime($tplfile)) {
                $content = Template::complie($tplfile);
                file_put_contents($layoutfile, $content);
            }
            return $phpfile;
        }
        throw new Exception("Not found template: $tplfile");
    }
    
    public static function exists($tpl)
    {
        $path = self::$config['dir'].$tpl;
        if (isset(self::$config['template'])) {
            return is_file(self::getTemplateFile($path, true));
        }
        return is_php_file("$path.php");
    }

    /*
     * 错误页面，404 500页面等
     */
    public static function error($code, $message = null)
    {   
        if (isset(self::$config['error'][$code])) {
            ob_start();
            __include_view_file(self::file(self::$config['error'][$code]), compact('code', 'message'));
            return ob_get_clean();
        } elseif ($code === 404) {
            return ViewError::render404($message);
        } else {
            return ViewError::renderError($message);
        }
    }
    
    /*
     * 视图魔术方法
     */
    public static function __callStatic($method, $params = [])
    {
        if (isset(self::$config['methods'][$method])) {
            $vars = self::$config['methods'][$method];
            $tpl = array_pop($vars);
            if ($vars) {
                foreach (array_keys($vars) as $i => $v) {
                    if (!isset($params[$i]))  break;
                    $vars[$v] = $params[$i];
                }
            }
            Response::view($tpl, $vars);
        }
        throw new Exception('Call to undefined method '.__CLASS__.'::'.$method);
    }
    
    private static function getTemplateFile($path, $is_relative_path = false)
    {
        $ext = self::$config['template']['ext'] ?? '.htm';
        if (empty(self::$config['template']['dir'])) {
            return $path.$ext;
        } else {
            if ($is_relative_path) {
                return self::$config['template']['dir'].$tpl.$ext;
            } else {
                return self::$config['template']['dir'].substr($path, strlen(self::$config['dir'])+1).$ext;
            }
        }
    }

    public static function free()
    {
        self::$view = null;
        self::$config = null;
    }
}
View::init();

function __include_view_file($__view_file, $__view_vars)
{
    if ($__view_vars) {
        extract($__view_vars, EXTR_SKIP);
    }
    include $__view_file;
}
