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
        if (self::$init) {
            return;
        }
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
    public static function filter($name, callable $value)
    {
        self::$view->filters[$name] = $value;
    }
    
    /*
     * 渲染页面
     */
    public static function render($tpl, $vars = [])
    {
        ob_start();
        (static function($__file, $__vars) {
            extract($__vars, EXTR_SKIP);
            require $__file;
        })(self::file($tpl), self::$view->vars ? $vars + self::$view->vars : $vars);
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
            $path = "$dir/$tpl";
        }
        $phpfile = "$path.php";        
        if (isset(self::$config['template'])) {
            self::complie(self::getTemplateFile($path, $is_relative_path), $phpfile);
        }
        return $phpfile;
    }
    
    public static function block($tpl)
    {
        $path    = self::$config['dir'].$tpl;
        $prefix  = self::$config['template']['block_view_prefix'] ?? '__';
        $phpfile = dirname($path)."/$prefix".basename($path).'.php';
        if (isset(self::$config['template'])) {
            self::complie(self::getTemplateFile($path), $phpfile);
        }
        return $phpfile;
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
        if (is_file($layout = self::getTemplateFile($path, $tpl[0] !== '/'))) {
            if (filemtime($file) < filemtime($layout)) {
                if (self::complie(self::getTemplateFile(substr($file, 0, -4)), $file, file_get_contents($layout))) {
                    if (extension_loaded('opcache')) {
                        opcache_compile_file($file);
                    }
                }
            }
            return $file;
        } 
        throw new \Exception("Template file not found: $layoutfile");
    }

    /*
     * 错误页面，404 500页面等
     */
    public static function error($code, $message = null)
    {   
        if (isset(self::$config['error'][$code])) {
            return self::render(self::$config['error'][$code], compact('code', 'message'));
        } elseif ($code == 404) {
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
                    if (!isset($params[$i])) break;
                    $vars[$v] = $params[$i];
                }
            }
            return Response::view($tpl, $vars);
        }
        throw new \Exception('Call to undefined method '.__CLASS__."::$method");
    }

    public static function getTemplateFile($path, $is_relative_path = false)
    {
        $ext = self::$config['template']['ext'] ?? '.htm';
        if (empty(self::$config['template']['dir'])) {
            return $path.$ext;
        } else {
            if ($is_relative_path) {
                return self::$config['template']['dir'].$tpl.$ext;
            } else {
                return self::$config['template']['dir'].substr($path, strlen(self::$config['dir']) + 1).$ext;
            }
        }
    }
    
    public static function complie($tplfile, $phpfile, $layout = null)
    {
        if (!is_file($tplfile)) {
            throw new \Exception("Template file not found: $tplfile");
        }
        if (!is_file($phpfile) || filemtime($phpfile) < filemtime($tplfile)) {
            $dir = dirname($phpfile);
            if ($layout || is_dir($dir) || mkdir($dir, 0777, true)) {
                if ($content = file_get_contents($tplfile)) {
                    $engine = self::$config['template']['engine'] ?? Template::class;
                    if (file_put_contents($phpfile, $engine::complie($content, $layout))) {
                        return true;
                    }
                    throw new \Exception("View file write fail: $phpfile");
                }
                throw new \Exception("Template file read write fail: $tplfile");
            }
            throw new \Exception("View dir create fail: $dir");
        }
    }
    
    public static function free()
    {
        self::$view = null;
    }
}
View::init();
