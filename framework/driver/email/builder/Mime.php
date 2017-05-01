<?php
namespace framework\driver\email\builder;

class Mime
{
    public static function build($option)
    {
        $mime = '';
        $addrs = []; 
        $mime = "MIME-Version: 1.0\r\n";
        $mime .= "Date: ".date("D, j M Y G:i:s O")."\r\n";
        if (isset($option['from'])) {
            $mime .= 'From: '.self::buildAddr($option['from'])."\r\n";
        }
        foreach ($option['to'] as $to) {
            $mime .= "To: ".self::buildAddr($to)."\r\n";
            $addrs[] = $to[0];
        }
        if (isset($option['cc'])) {
            foreach ($option['cc'] as $cc) {
                $mime .= "CC: ".self::buildAddr($cc)."\r\n";
                $addrs[] = $cc[0];
            }
        }
        if (isset($option['bcc'])) {
            foreach ($option['bcc'] as $bcc) {
                $mime .= "BCC: ".self::buildAddr($bcc)."\r\n";
                $addrs[] = $bcc[0];
            }
        }
        if (isset($option['replyto'])) {
            $mime .= 'Reply-To: '.self::buildAddr($option['replyto'])."\r\n";
        }
        if (isset($option['subject'])) {
            $mime .= "Subject: ".self::buildUtf8Header($option['subject'])."\r\n";
        }
        $type = $option['ishtml'] ? 'text/html' : 'text/plain';
        if (isset($option['attach'])) {
            $boundary = uniqid();
            $mime .= "Content-Type:multipart/mixed;boundary=\"$boundary\"\r\n\r\n";
            $mime .= "--$boundary\r\nContent-Type: $type; charset=utf-8\r\nContent-Transfer-Encoding: base64";
            $mime .= "\r\n\r\n".base64_encode($option['content'])."\r\n\r\n";
            $mime .= self::buildAttachments($option['attach'], $boundary, $option['attach_is_buffer']);
        } else {
            $mime .= "Content-Type: $type; charset=utf-8\r\n\r\n".$option['content'];
        }
        return [$addrs, $mime];
    }
    
    public static function buildAddr($addr)
    {
        return empty($addr[1]) ? "<$addr[0]>" : self::buildUtf8Header($addr[1])."<$addr[0]>";
    }
    
    public static function buildUtf8Header($str)
    {
        return '=?utf-8?B?'.base64_encode($str).'?=';
    }
    
    public static function buildAttachments($attachs, $boundary, $is_buffer)
    {
        $mime = '';
        foreach ($attachs as $attach) {
            if ($is_buffer) {
                $content = $attach[0];
                $filename = isset($attach[1]) ? self::buildUtf8Header($attach[1]) : 'nonename';
            } else {
                $content = file_get_contents($attach[0]);
                $filename = self::buildUtf8Header(isset($attach[1]) ? $attach[1] : basename($attach[0]));
            }
            if (isset($attach[2])) {
                $mimetype = $attach[2];
            } else {
                $finfo = finfo_open(FILEINFO_MIME); 
                $mimetype = $is_buffer ? finfo_buffer($finfo, $attach[0]) : finfo_file($finfo, $attach[0]);
                finfo_close($finfo);
            }
            $mime .= "--$boundary\r\nContent-Type: $mimetype; name=$filename\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; name=$filename";
            $mime .= "\r\n\r\n".base64_encode($content)."\r\n\r\n";
        }
        return $mime."--$boundary--\r\n";
    }
}
