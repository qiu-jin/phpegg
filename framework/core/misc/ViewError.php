<?php
namespace framework\core\misc;

use framework\core\Logger;

class ViewError
{   
    public static function render404($message)
    {
        $html = '<h1 style="text-align: center">🙁 404 Page Not Found 🙁</h1>';
        if ($message) {
            $html .= '<p style="text-align: center">'.$message.'</p>';
        }
        return $html;
    }
    
    public static function renderError($message)
    {
        $loglevel = [
            Logger::EMERGENCY  => ['icon'=>'❌', 'class' => 'error',   'title' => 'error'],
            Logger::ALERT      => ['icon'=>'❌', 'class' => 'error',   'title' => 'error'],
            Logger::CRITICAL   => ['icon'=>'❌', 'class' => 'error',   'title' => 'error'],
            Logger::ERROR      => ['icon'=>'❌', 'class' => 'error',   'title' => 'error'],
            Logger::WARNING    => ['icon'=>'⚠️', 'class' => 'warning', 'title' => 'warning'],
            Logger::NOTICE     => ['icon'=>'⚠️', 'class' => 'warning', 'title' => 'warning'],
            Logger::INFO       => ['icon'=>'❕', 'class' => 'info',    'title' => 'info'],
            Logger::DEBUG      => ['icon'=>'❕', 'class' => 'info',    'title' => 'info']
        ];
        $html = '<h1 style="text-align: center">🙁 500 Internal Server Error 🙁</h1>';
        if($message) {
            $html .= '<style type="text/css">.table {background: #AAAAAA}tr{ background-color: #EEEEEE;}.error{ background-color: #FFCCCC;}.warning{ background-color: #FFFFCC;}.info{ background-color: #EEEEEE;}</style>';
            $html .= '<table table cellpadding="5" cellspacing="1" width="100%" class="table">';
            foreach ($message as $line){
                $level = $loglevel[$line['level']];
                $txt   = $line['message'].' in '.($line['context']['file'] ?? '').' on '.($line['context']['line'] ?? '');
                $html .= '<tr class="'.$level['class'].'"><td title="'.$level['title'].'">'.$level['icon'].' '.$txt.'</td></tr>';
            }
            $html .= '</table>';
        }
        return $html;
    }
}
