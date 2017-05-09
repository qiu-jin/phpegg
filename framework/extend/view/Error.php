<?php
namespace framework\extend\view;

use framework\core\Logger;

class Error
{   
    public static function dump($vars)
    {
        
    }
    
    private static function render404($message)
    {
        $html = '<h1 style="text-align: center">🙁 404 Page Not Found 🙁</h1>';
        if ($message) {
            $html .= '<p style="text-align: center">'.$message.'</p>';
        }
        return $html;
    }
    
    private static function renderError($message)
    {
        $loglevel = [
            Logger::EMERGENCY  => ['icon'=>'❌', 'class' => 'error', 'txt' => 'error'],
            Logger::ALERT      => ['icon'=>'❌', 'class' => 'error', 'txt' => 'error'],
            Logger::CRITICAL   => ['icon'=>'❌', 'class' => 'error', 'txt' => 'error'],
            Logger::ERROR      => ['icon'=>'❌', 'class' => 'error', 'txt' => 'error'],
            Logger::WARNING    => ['icon'=>'⚠️', 'class' => 'warning', 'txt' => 'warning'],
            Logger::NOTICE     => ['icon'=>'⚠️', 'class' => 'warning', 'txt' => 'warning'],
            Logger::INFO       => ['icon'=>'❕', 'class' => 'info', 'txt' => 'info'],
            Logger::DEBUG      => ['icon'=>'❕', 'class' => 'info', 'txt' => 'info']
        ];
        $html = '<h1 style="text-align: center">🙁 500 Internal Server Error 🙁</h1>';
        if($message) {
            $html .= '<style type="text/css">.table {background: #AAAAAA}tr{ background-color: #EEEEEE;}.error{ background-color: #FFCCCC;}.warning{ background-color: #FFFFCC;}.info{ background-color: #EEEEEE;}</style>';
            $html .= '<table table cellpadding="5" cellspacing="1" width="100%" class="table">';
            foreach ($message as $level => $logs){
                foreach ($logs as $log){
                    $html .= '<tr class="'.$loglevel[$level]['class'].'"><td title="'.$loglevel[$level]['txt'].'">'.$loglevel[$level]['icon'].' '.$log.'</td></tr>';
                }
            }
            $html .= '</table>';
        }
        return $html;
    }
}
