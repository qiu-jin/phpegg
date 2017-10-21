<?php
namespace framework\core;

use framework\core\http\Response;
use framework\extend\view\Error as ViewError;

class View
{    
    private static $init;
    private static $view;
    private static $config;
    private static $template;
    
    /*
     * 类加载时调用此初始方法
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
        Hook::add('exit', __CLASS__.'::free');
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
        if (self::$view->vars) {
            $vars = array_merge($view->vars, $vars);
        }
        ob_start();
        __include_view(self::file($tpl), $vars);
        return ob_get_clean();
    }
    
    /*
     * 返回视图文件路径
     */
    public static function file($tpl, $dir = null)
    {
        $path = $tpl[0] === '/' ? self::$config['dir'].$tpl : $dir.'/'.$tpl;
        $phpfile = $path.'.php';        
        if (!isset(self::$config['template'])) {
            return $phpfile;
        }
        $tplfile = self::getTemplateFile($path);
        if (is_file($tplfile)) {
            if (is_file($phpfile) && filemtime($phpfile) >= filemtime($tplfile)) {
                return $phpfile;
            }
            return self::complie($tplfile, $phpfile);
        }
        throw new Exception("Not found template: $tplfile");
    }
    
    public static function exists($tpl)
    {
        $path = self::$config['dir'].$tpl;
        return isset(self::$config['template']) ? is_file(self::getTemplateFile($path)) : is_php_file("$path.php");
    }

    public static function layout($tpl, $file)
    {
        if (!isset(self::$config['template'])) {
            return;
        }
        $path = $tpl[0] === '/' ? $tpl : dirname($file).'/'.$tpl;
        $phpfile = $path.'.php';
        $tplfile = self::getTemplateFile($path);
        if (file_exists($tplfile) && (!file_exists($file) || filemtime($tplfile) > filemtime($file))) {
            $php_content = file_get_contents($file);
            $complie_content = "<?php View::layout('$tpl', __FILE__); ?>".PHP_EOL;
            $complie_content .= self::getTemplateHandler()->complie(file_get_contents($tplfile), $php_content);
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
    
    /*
     * 错误页面，404 500页面等
     */
    public static function error($code, $message = null)
    {   
        if (isset(self::$config['error'][$code])) {
            ob_start();
            __include_view(self::file(self::$config['error'][$code]), compact('code', 'message'));
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
    
    private static function complie($tplfile, $phpfile)
    {
        $dir = dirname($phpfile);
        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            throw new Exception("Not create view dir: $dir");
        }
        if (!file_put_contents($phpfile, self::getTemplateHandler()->complie(file_get_contents($tplfile)))) {
            throw new Exception("View file write failure: $phpfile");
        }
        return $phpfile;
    }
    
    private static function getTemplateFile($path)
    {
        $ext = self::$config['template']['ext'] ?? '.htm';
        if (empty(self::$config['template']['dir'])) {
            return $path.$ext;
        } else {
            return self::$config['template']['dir'].substr($path, strlen(self::$config['dir'])+1).$ext;
        }
    }
    
    private static function getTemplateHandler()
    {
        return self::$template ?? self::$template = new Template(self::$config['template']);
    }
    
    public static function free()
    {
        self::$view = null;
        self::$config = null;
        self::$template = null;
    }
}
View::init();

function __include_view($__view_file, $__view_vars)
{
    extract($__view_vars, EXTR_SKIP);
    include $__view_file;
}
