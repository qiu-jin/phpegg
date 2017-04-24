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
        if (isset($option['attachment'])) {
            $mime .= "Content-Type: text/html; charset=utf-8\r\n\r\n".$option['content'];
            $mime .= self::buildAttachment($option['attachment']);
        } else {
            $mime .= "Content-Type: text/html; charset=utf-8\r\n\r\n".$option['content'];
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
    
    public static function buildAttach(array $attchs)
    {
        foreach ($attchs as $attch) {
            
        }
    }
}