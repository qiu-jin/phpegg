<?php
namespace framework\driver\storage;

use framework\core\Model;
use framework\core\http\Client;

abstract class Storage
{
    abstract public function get($from);
    
    abstract public function put($from, $to);
    
    abstract public function stat($from);
    
    abstract public function copy($from, $to);
    
    abstract public function move($from, $to);

    abstract public function delete($from);
    
    public function fetch($from, $to)
    {
        if (strpos($from, '://')) {
            list($scheme, $uri) = explode('://', $from, 2);
            $scheme = strtolower($scheme);
            if ($scheme === 'http' || $scheme === 'https') {
                $data = Client::send('GET', $from);
            } else {
                $data = Model::connect('storage', $scheme)->get($uri);
            }
            return $data ? $this->put($data, $to, true) : false;
        }
        return false;
    }
    
    protected function path($path)
    {
        $path = trim($path);
        return $path{0} !== '/' ? '/'.$path : $path;
    }
}
