<?php
namespace framework\driver\email\message;

use framework\util\File;

class Mime
{
    const EOL = "\r\n";
    
    public static function build($options, &$addrs = null)
    {
        $data = ["MIME-Version: 1.0", "Date: ".date("D, j M Y G:i:s O")];
        if (isset($options['from'])) {
            $data[] = 'From: '.self::buildAddr($options['from']);
        }
        foreach ($options['to'] as $to) {
            $data[] = "To: ".self::buildAddr($to);
            $addrs[] = $to[0];
        }
        if (isset($options['cc'])) {
            foreach ($options['cc'] as $cc) {
                $data[] = "CC: ".self::buildAddr($cc);
                $addrs[] = $cc[0];
            }
        }
        if (isset($options['bcc'])) {
            foreach ($options['bcc'] as $bcc) {
                $data[] = "BCC: ".self::buildAddr($bcc);
                $addrs[] = $bcc[0];
            }
        }
        if (isset($options['replyto'])) {
            $data[] = 'Reply-To: '.self::buildAddr($options['replyto']);
        }
        if (isset($options['subject'])) {
            $data[] = "Subject: ".self::encodeHeader($options['subject']);
        }
        $encoding = $options['encoding'] ?? 'quoted-printable';
        $type = empty($options['ishtml']) ? 'text/plain' : 'text/html';
        if (isset($options['attach'])) {
            $boundary = uniqid();
            $data[] = "Content-Type:multipart/mixed;boundary=\"$boundary\"";
            $data[] = '';
            $data[] = "--$boundary";
            $data[] = "Content-Type: $type; charset=utf-8";
            $data[] = "Content-Transfer-Encoding: $encoding";
            $data[] = '';
            $data[] = self::encodeContent($options['content'], $encoding);
            $data[] = '';
            $data[] = self::buildAttachments($options['attach'], $boundary, $options['attach_is_buffer']);
            $data[] = '';
        } else {
            $data[] = "Content-Type: $type; charset=utf-8";
            $data[] = "Content-Transfer-Encoding: $encoding";
            $data[] = '';
            $data[] = self::encodeContent($options['content'], $encoding);
        }
        return implode(self::EOL, $data);
    }
    
    public static function buildAddr($addr)
    {
        return empty($addr[1]) ? "<$addr[0]>" : self::encodeHeader($addr[1])."<$addr[0]>";
    }

    public static function encodeHeader($str)
    {
        return '=?utf-8?B?'.base64_encode($str).'?=';
    }
    
    public static function encodeContent($str, $encoding = 'base64')
    {
        switch ($encoding)
        {
            case 'base64':
                return chunk_split(base64_encode($str), 76, self::EOL);
            case 'quoted-printable':
                return quoted_printable_encode($str);
            case '7bit':
            case '8bit':
                $str = str_replace("\r\n", "\n", $str);
                $str = str_replace("\r", "\n", $str);
                $str = str_replace("\n", self::EOL, $str);
                if (substr($str, - strlen(self::EOL)) != self::EOL) {
                    $str = $str.self::EOL;
                }
                return $str;
            case 'binary':
                return $str;
        }
        throw new \Exception("Encoding invalid: $encoding");
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
            $type = $attach[2] ?? File::mime($attach[0], $is_buffer);
            $data[] = "--$boundary";
            $data[] = "Content-Type: $type; name=$filename";
            $data[] = "Content-Transfer-Encoding: base64";
            $data[] = "Content-Disposition: attachment; name=$filename";
            $data[] = '';
            $data[] = self::encodeContent($content);
            $data[] = '';
        }
        return implode(self::EOL, $data);
    }
}
