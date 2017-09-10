<?php
namespace framework\driver\email\message;

class Mime
{
    const EOL = "\r\n";
    
    public static function build($option)
    {
        $mime = '';
        $addrs = [];
        $mime = ["MIME-Version: 1.0", "Date: ".date("D, j M Y G:i:s O")];
        if (isset($option['from'])) {
            $mime[] = 'From: '.self::buildAddr($option['from']);
        }
        foreach ($option['to'] as $to) {
            $mime[] = "To: ".self::buildAddr($to);
            $addrs[] = $to[0];
        }
        if (isset($option['cc'])) {
            foreach ($option['cc'] as $cc) {
                $mime[] = "CC: ".self::buildAddr($cc);
                $addrs[] = $cc[0];
            }
        }
        if (isset($option['bcc'])) {
            foreach ($option['bcc'] as $bcc) {
                $mime[] = "BCC: ".self::buildAddr($bcc);
                $addrs[] = $bcc[0];
            }
        }
        if (isset($option['replyto'])) {
            $mime[] = 'Reply-To: '.self::buildAddr($option['replyto']);
        }
        if (isset($option['subject'])) {
            $mime[] = "Subject: ".self::encodeHeader($option['subject']);
        }
        $type = empty($option['ishtml']) ? 'text/plain' : 'text/html';
        if (isset($option['attach'])) {
            $boundary = uniqid();
            $mime[] = "Content-Type:multipart/mixed;boundary=\"$boundary\"";
            $mime[] = '';
            $mime[] = "--$boundary";
            $mime[] = "Content-Type: $type; charset=utf-8";
            $mime[] = "Content-Transfer-Encoding: base64";
            $mime[] = '';
            $mime[] = base64_encode($option['content']);
            $mime[] = '';
            $mime[] = self::buildAttachments($option['attach'], $boundary, $option['attach_is_buffer']);
            $mime[] = '';
        } else {
            $mime[] = "Content-Type: $type; charset=utf-8";
            $mime[] = "Content-Transfer-Encoding: 8bit";
            $mime[] = '';
            $mime[] = $option['content'];
        }
        return [$addrs, implode(self::EOL, $mime)];
    }
    
    public static function buildAddr($addr)
    {
        return empty($addr[1]) ? "<$addr[0]>" : self::encodeHeader($addr[1])."<$addr[0]>";
    }
    
    public static function encodeHeader($str)
    {
        return '=?utf-8?B?'.base64_encode($str).'?=';
    }
    
    public static function buildAttachments($attachs, $boundary, $is_buffer)
    {
        foreach ($attachs as $attach) {
            if ($is_buffer) {
                $content = $attach[0];
                $filename = isset($attach[1]) ? self::encodeHeader($attach[1]) : 'nonename';
            } else {
                $content = file_get_contents($attach[0]);
                $filename = self::encodeHeader(isset($attach[1]) ? $attach[1] : basename($attach[0]));
            }
            if (isset($attach[2])) {
                $mimetype = $attach[2];
            } else {
                $finfo = finfo_open(FILEINFO_MIME); 
                $mimetype = $is_buffer ? finfo_buffer($finfo, $attach[0]) : finfo_file($finfo, $attach[0]);
                finfo_close($finfo);
            }
            $mime[] = "--$boundary";
            $mime[] = "Content-Type: $mimetype; name=$filename";
            $mime[] = "Content-Transfer-Encoding: base64";
            $mime[] = "Content-Disposition: attachment; name=$filename";
            $mime[] = '';
            $mime[] = base64_encode($content);
            $mime[] = '';
        }
        return implode(self::EOL, $mime);
    }
}
