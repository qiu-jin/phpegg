<?php
namespace framework\util;

class Zip extends \ZipArchive
{
    public function __construct($file = null)
    {
        if (is_file($file)) {
            $ret = $this->open($file);
        } else {
            if (!is_dir($dir = dirname($file)) && !mkdir($dir)) {
                throw new \Exception('Create zip file failed');
            }
            $ret = $this->open($file, \ZipArchive::CREATE);
        }
        if ($ret !== true) {
            throw new \Exception('Illegal zip file');
        }
    }
    
	public function num()
    {
        return $this->numFiles;
    }
    
	public function hasName($name)
    {
        return $this->getNameIndex($name) !== false;
    }

    public function getNames() 
    {
        if (($num = $this->numFiles) > 0) {
            for ($i = 0; $i < $num; $i++) {
                $names[] = $this->getNameIndex($i);
            }
        }
        return $names ?? [];
    }
    
    public function uploadTo($to)
    {
        if ($filename = $this->filename) {
            $this->close();
            return File::upload($filename, $to);
        }
        return false;
    }
    
	public function __destruct()
    {
        empty($this->filename) || $this->close();
    }
}