<?php
namespace framework\driver\storage;

use Framework\Core\Http\Client;

class Oss extends Storage
{
    private $bucket;
    private $endpoint;
    private $keyid;
    private $keysecret;
    
    public function __construct($config)
    {
        $this->bucket = $config['bucket'];
        $this->endpoint = $config['endpoint'];
        $this->keyid = $config['keyid'];
        $this->keysecret = $config['keysecret'];
    }
    
    public function get($from, $to = null)
    {
        $res = Client::send('GET', $this->url($from), null, $this->buildHeader('GET', $from), null, true);
        if ($res && $res['status'] == 200) {
            if ($to) {
                return file_put_contents($to, $res['body']);
            }
            return $res['body'];
        }
        return false;
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        $to = $this->path($to);
        $type = $this->mime($from, $is_buffer);
        if (!$is_buffer) {
            $from = file_get_contents($from);
        }
        $date = $this->date();
        $ossr = '/'.$this->bucket.'/'.trim($to, '/');
        $fmd5 = base64_encode(md5($from, true));
        $res = Client::send('PUT', $this->url($to), $from, [
            'Date: '.$date,
            'Content-Length: '.strlen($from),
            'Content-Md5: '.$fmd5,
            'Content-Type: '.$type,
            'Authorization: OSS '.$this->sign("PUT\n$fmd5\n$type\n$date\n$ossr")
        ], ['timeout' => 30], true);
        return $res && $res['status'] == 200;
    }
    
    public function append($from, $to, $pos = 0, $is_buffer = false)
    {

    }

    public function stat($from)
    {
        $from = $this->path($from);
        $client = Client::head($this->url($from))->return_headers()->headers($this->buildHeader('HEAD', $from));
        if ($client->status == 200) {
            $headers = $client->headers;
            if ($headers) {
                return [
                    'size' => $headers['Content-Length'],
                    'mtime' => strtotime($headers['Last-Modified']),
                    'type' => $headers['Content-Type']
                ];
            }
        }
        return false;
    }

    public function move($from, $to)
    {
        if ($this->copy($from, $to)) {
            return (bool) $this->delete($from);
        }
        return false;
    }
    
    public function copy($from, $to)
    {
        $to = $this->path($to);
        $from = $this->path($from);
        $res = Client::send('PUT', $this->url($to), null, $this->buildHeader('PUT', $to, 'x-oss-copy-source:/'.$this->bucket.$from), null, true);
        return $res && $res['status'] == 200;
    }

    public function delete($from)
    {
        $from = $this->path($from);
        $res = Client::send('DELETE', $this->url($from), null, $this->buildHeader('DELETE', $from), null, true);
        return $res && $res['status'] == 204;
    }
    
    private function url($path)
    {
        return 'http://'.$this->bucket.'.'.$this->endpoint.$path;
    }

    private function sign($str)
    {
        $digest = hash_hmac('sha1', $str, $this->keysecret, true);
        return $this->keyid.':'.base64_encode($digest);
    }
    
    private function mime($file, $is_buffer = false)
    {
        $finfo = finfo_open(FILEINFO_MIME); 
        if ($is_buffer) {
            $mime = finfo_buffer($finfo, $file);
        } else {
            $mime = finfo_file($finfo, $file);
        }
        finfo_close($finfo);
        return $mime;
    }

    private function date()
    { 
        return gmdate('D, d M Y H:i:s').' GMT';
    }
    
    private function buildHeader($method, $path, $ossh = null)
    {
        $date = $this->date();
        $ossr = '/'.$this->bucket.$path;
        $sign = $this->sign("$method\n\n\n$date\n".($ossh ? "$ossh\n" : '').$ossr);
        $headers = ['Date: '.$date, 'Authorization: OSS '.$sign];
        if ($ossh) {
            $headers[] = $ossh;
        }
        return $headers;
    }
}