<?php
namespace framework\core\app;

class Job
{
    protected static function push($name, $message)
    {
        Container::driver($name)->producer()->push(function ($message) {
    }
    
    protected static function consume(($name))
    {
        Container::driver($name)->consumer()->consume(function ($message) {
            try {
                $return = (new $message[0][0])->$message[0][1](...$message[1]);
                if (isset($message[2])) {
                    $message[2][0]($return, $message[2][1]);
                }
            } catch (\Throwable $e) {
                //$this->setError($e);
            }
        });
    }
}
