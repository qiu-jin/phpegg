<?php
namespace framework\core;

use framework\util\Arr;
use framework\core\http\Response;
use framework\core\exception\ViewException;
use framework\extend\view\Error as ViewError;

class View
{    
    private static $init;
    private static $view;
    private static $config = [
        'dir' => APP_DIR.'view/'
    ];
    private static $template = [
        'ext'               => '.html',
        'engine'            => Template::class,
        'force_complie'     => false,
        'block_view_prefix' => '__',
    ];
    
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
        if ($config = Config::get('view')) {
            $template = Arr::pull($config, 'template');
            if ($template === false) {
                self::$template = false;
            } elseif (is_array($template)) {
                self::$template = $template + self::$template;
            }
            self::$config = $config + self::$config;
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
    
    public static function vars(array $values)
    {
        self::$view->vars = isset(self::$view->vars) ? $values + self::$view->vars : $values;
    }
    
    /*
     * 设置函数
     */
    public static function filter($name, callable $value)
    {
        self::$view->filters[$name] = $value;
    }
    
    public static function filters(array $values)
    {
        self::$view->filters = isset(self::$view->filters) ? $values + self::$view->filters : $values;
    }
    
    /*
     * 渲染页面
     */
    public static function render($tpl, $vars = null)
    {
        $vars && self::vars($vars);
        ob_start();
        (static function($__file, $__vars) {
            if ($__vars) {
                extract($__vars, EXTR_SKIP);
            }
            require $__file;
        })(self::path($tpl), self::$view->vars ?? null);
        return ob_get_clean();
    }
    
    /*
     * 返回视图文件路径
     */
    public static function path($tpl)
    {
        $phpfile = self::$config['dir']."$tpl.php";
        if (self::$template) {
            if (!is_file($tplfile = self::getTemplate($tpl))) {
                throw new ViewException("Template file not found: $tplfile");
            } 
            if (self::$template['force_complie'] || !is_file($phpfile) || filemtime($phpfile) < filemtime($tplfile)) {
                self::writeView($phpfile, (self::$template['engine'])::complie(self::readTemplate($tplfile)));
            }
        }
        return $phpfile;
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
                    if (!isset($params[$i])) {
                        break;
                    }
                    $vars[$v] = $params[$i];
                }
            }
            return Response::view($tpl, $vars);
        }
        throw new \BadMethodCallException('Call to undefined method '.__CLASS__."::$method");
    }
    
    public static function getTemplate($tpl)
    {
        if (empty(self::$template['dir'])) {
            return self::$config['dir'].$tpl.self::$template['ext'];
        } else {
            return self::$template['dir'].$tpl.self::$template['ext'];
        }
    }
    
    public static function readTemplate($tpl)
    {
        if ($content = file_get_contents(self::getTemplate($tpl))) {
            return $content;
        }
        throw new ViewException("Template file read fail: $file");
    }
    
    public static function isExpired(...$tpls)
    {
        if (self::$template && $tpls) {
            foreach ($tpls as $tpl) {
                if (is_file($tplfile = self::getTemplate($tpl))) {
                    throw new ViewException("Template file not found: $tplfile");
                }
                $phpfile = self::$config['dir']."$tpl.php";
                if (!is_file($phpfile) || filemtime($phpfile) < filemtime($tplfile)) {
                    return true;
                }
            }
        }
    }

    private static function writeView($file, $content)
    {
        if (is_dir($dir = dirname($file)) || mkdir($dir, 0777, true)) {
            if (file_put_contents($file, $content)) {
                return true;
            }
            throw new ViewException("View file write fail: $file");
        }
        throw new ViewException("View dir create fail: $dir");
    }
    
    public static function free()
    {
        self::$view = null;
    }
}
View::init();
