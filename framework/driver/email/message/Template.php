<?php
namespace framework\driver\email\message;

use framework\core\View;

class Template
{
    public static function view($template, array $vars = null)
    {
        return View::render($template, $vars);
    }
    
    public static function simple($template, array $vars = null)
    {
        if ($vars) {
            foreach ($vars as $k => $v) {
                $replace['{'.$k.'}'] = $v;
            }
            $content = strtr($content, $replace);
        }
    }
    
    public static function makedown()
    {
        
    }
}