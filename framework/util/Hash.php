<?php
namespace Framework\Util;

class Hash
{
    public static function __callstatic($name, $params)
    {
        if ( in_array($algo, hash_algos())) {
            return hash($name, $params[0]);
        }
        return false;
    }
    
    public static function fast($str, $salt, $length = 0, $algo = 'sha256', $count = 3)
    {
        $hash = hash_hmac($algo, $str, $salt);
        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $hash = hash_hmac($algo, $hash, $salt);
            }
        }
        if (!$length) return $hash;
        $len =  strlen($hash);
        if ($len > $length) {
            return substr($hash, 0, $length);
        } elseif ($len < $length) {
            
        } else {
            return $hash;
        }
    }
    
    public static function slow($str, $salt, $length = 32, $algo = 'sha256', $count = 10000)
    {
        if ($algo === 'sha256' || in_array($algo, hash_algos())) {
            if (function_exists("hash_pbkdf2")) {
                return hash_pbkdf2($algo, $str, $salt, $count, $length*2);
            } else {
                $block_count = ceil($length / strlen(hash($algo, "", true)));
                $output = "";
                for($i = 1; $i <= $block_count; $i++) {
                    $last = $salt . pack("N", $i);
                    $last = $xorsum = hash_hmac($algo, $last, $str, true);
                    for ($j = 1; $j < $count; $j++) {
                        $xorsum ^= ($last = hash_hmac($algo, $last, $str, true));
                    }
                    $output .= $xorsum;
                }
                return bin2hex(substr($output, 0, $length));
            }
        }
        return false;
    }
    
    public static function salt($length = 0)
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length, MCRYPT_DEV_URANDOM));
        } elseif ((function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        } else {
            return false;
        }
    }

    public static function equals($a, $b)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($a, $b);
        } else {
            $diff = strlen($a) ^ strlen($b);
            for($i = 0; $i < strlen($a) && $i < strlen($b); $i++){
                $diff |= ord($a[$i]) ^ ord($b[$i]);
            }
            return $diff === 0;
        }
    }
    
    public static function password($password)
    {
        if (function_exists('password_hash')) {
            return password_hash($password, ['cost' => 10]);
        } else {
            return crypt($password, self::salt());
        }
    }

    public static function verify($str, $hash)
    {
        if (function_exists('password_verify')) {
            return password_verify($str, $hash);
        } else {
            list($algo, $count, $salt, $hash) = explode('$', $hash);
            return self::equals(crypt($str, $salt), $hash);
        }
    }
}