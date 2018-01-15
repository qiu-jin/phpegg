<?php
namespace Framework\Util;

class Hash
{
    public static function __callstatic($name, $params)
    {
        if (in_array($algo, hash_algos())) {
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
            return hash_pbkdf2($algo, $str, $salt, $count, $length*2);
        }
        return false;
    }
    
    public static function salt($length = 10, $raw = false)
    {
        $salt = random_bytes($length, MCRYPT_DEV_URANDOM);
        return $raw ? $salt : bin2hex($salt);
    }

    public static function equals($a, $b)
    {
        return hash_equals($a, $b);
    }
    
    public static function password($password)
    {
        return password_hash($password, ['cost' => 10]);
    }

    public static function verify($str, $hash)
    {
        return password_verify($str, $hash);
    }
}