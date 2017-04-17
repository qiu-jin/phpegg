<?php
namespace framework\util;

class Zip
{
    private $zip;
    
    private function __construct($file)
    {
        $zip = new \ZipArchive;
        if (is_file($file)) {
            $ret = $zip->open($file);
        } else {
            $dir = dirname($file);
            if (!is_dir($dir)) {
                if (!mkdir($to)) {
                    throw new \Exception('Illegal zip file');
                }
            }
            $ret = $zip->open($file, \ZipArchive::CREATE);
        }
        if ($ret === true) {
            $this->zip = $zip;
        } else {
            throw new \Exception('Illegal zip file');
        }
    }
    
	public static function open($file)
    {
        return new self($file);
    }
    
	public function get($name)
    {
        return $this->zip->getFromName($name);
    }
    
	public function has($name)
    {
        return $this->zip->getNameIndex($name) !== false;
    }
    
	public function put($value, $name, $is_buffer = false)
    {
        return isset($is_buffer) ? $this->zip->addFromString($name, $value) : $this->zip->addFile($value, $name);
    }
    
	public function save($name, $to)
    {
        $data = $this->zip->getFromName($name);
        if ($data) {
            return (bool) file_put_contents($to, $data);
        }
        return false;
    }
    
	public function stat($name)
    {
        return $this->zip->statName($name);
    }
    
	public function delete($name)
    {
        return $this->zip->deleteName($name);
    }
    
	public function extract($to)
    {
        if (!is_dir($to)) {
            if (!mkdir($to)) {
                return false;
            }
        }
        return $this->zip->extractTo($to);
    }
    
	public function password($pw)
    {
        return $this->zip->setPassword($pw);
    }
    
	public function __destruct()
    {
        return $this->zip->close();
    }
}