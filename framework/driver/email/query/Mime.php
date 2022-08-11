<?php
namespace framework\driver\email\query;

use framework\util\File;

class Mime
{
	// 换行符
    const EOL = "\r\n";
    
    /*
     * 构建邮件
     */
    public static function make($options)
    {
        $data = ["MIME-Version: 1.0", "Date: ".date("D, j M Y G:i:s O")];
        if (isset($options['from'])) {
            $data[] = 'From: '.self::makeAddr($options['from']);
        }
        foreach ($options['to'] as $to) {
            $data[] = "To: ".self::makeAddr($to);
            $addrs[] = $to[0];
        }
        if (isset($options['cc'])) {
            foreach ($options['cc'] as $cc) {
                $data[] = "CC: ".self::makeAddr($cc);
                $addrs[] = $cc[0];
            }
        }
        if (isset($options['bcc'])) {
            foreach ($options['bcc'] as $bcc) {
                $data[] = "BCC: ".self::makeAddr($bcc);
                $addrs[] = $bcc[0];
            }
        }
        if (isset($options['replyto'])) {
            $data[] = 'Reply-To: '.self::makeAddr($options['replyto']);
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
            $data[] = self::makeAttachments($options['attach'], $boundary);
            $data[] = '';
        } else {
            $data[] = "Content-Type: $type; charset=utf-8";
            $data[] = "Content-Transfer-Encoding: $encoding";
            $data[] = '';
            $data[] = self::encodeContent($options['content'], $encoding);
        }
        return [$addrs, implode(self::EOL, $data)];
    }
    
    /*
     * 构建地址
     */
    public static function makeAddr($addr)
    {
        return empty($addr[1]) ? "<$addr[0]>" : self::encodeHeader($addr[1])."<$addr[0]>";
    }

    /*
     * 编码头
     */
    public static function encodeHeader($str)
    {
        return '=?utf-8?B?'.base64_encode($str).'?=';
    }
    
    /*
     * 编码内容
     */
    public static function encodeContent($str, $encoding = 'quoted-printable')
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
        throw new \Exception("无效邮件编码: $encoding");
    }
	
    /*
     * 构建附件
     */
    public static function makeAttachments($attachs, $boundary)
    {
        foreach ($attachs as $attach) {
            if ($attach[2]) {
                $content = $attach[0];
                $name = $attach[1] ?? 'attach';
            } else {
                $content = file_get_contents($attach[0]);
                $name = $attach[1] ?? basename($attach[0]);
            }
			$encode_name = self::encodeHeader($name);
            $mime = $attach[3] ?? File::mime($attach[0], $attach[2]);
            $data[] = "--$boundary";
            $data[] = "Content-Type: $mime; name=$encode_name";
            $data[] = "Content-Transfer-Encoding: base64";
			if (isset($attach[4])) {
				$data[] = "Content-Id: <$name>";
				$data[] = "Content-Disposition: inline; filename=$encode_name";
			} else {
				$data[] = "Content-Disposition: attachment; filename=$encode_name";
			}
            $data[] = '';
            $data[] = self::encodeContent($content);
            $data[] = '';
        }
        return implode(self::EOL, $data);
    }
}
