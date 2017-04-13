<?php
namespace framework\driver\storage;

class Local extends Storage
{
    private $dir;
    
    public function __construct($config)
    {
        $this->dir = $config['dir'];
    }
    
    public function get($from)
    {
        return file_get_contents($this->dir.$from);
    }
    
    public function put($from, $to, $type='file')
    {
        $this->mkdir($to);
        if ($type === 'file') {
            return copy($this->dir.$to, $from);
        } else {
            return (bool) file_put_contents($this->dir.$to, $from);
        }
    }

    public function stat($to)
    {
        $fp = fopen($this->dir.$to, 'r');
        $fstat = fstat($fp);
        fclose($fp);
        return array('size' => $fstat['size'], 'mtime' => $fstat['mtime'], 'ctime' => $fstat['ctime']);
    }
    
    public function copy($from, $to)
    {
        $this->mkdir($to);
        return copy($this->dir.$from, $this->dir.$to);
    }
    
    public function move($from, $to)
    {
        $this->mkdir($to);
        return rename($this->dir.$from, $this->dir.$to);
    }
    
    public function delete($to)
    {
        return unlink($this->dir.$to);
    }
    
    private function mkdir($path) {
        $dir = dirname($this->dir.$path);
        if(!is_dir($dir)) mkdir($dir, 0755);
    }
}
