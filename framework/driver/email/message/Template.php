<?php
namespace framework\driver\email\message;

use framework\core\View;

class Template
{
    public static function view($tpl, array $vars = null)
    {
        return View::render($tpl, $vars);
    }
    
    public static function simple($tpl, array $vars = null)
    {
        if ($vars) {
            foreach ($vars as $k => $v) {
                $replace['{'.$k.'}'] = $v;
            }
            $content = strtr($content, $replace);
        }
    }
    
    public static function makedown($tpl, array $vars = null)
    {
        
    }
}