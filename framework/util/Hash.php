<?php
namespace framework\util;

class Hash
{   
    public static function random(int $length = 16, $raw = false)
    {
        $bytes = random_bytes($length);
        return $raw ? $bytes : bin2hex($bytes);
    }
    
    public static function password($password, $algo = PASSWORD_DEFAULT, array $options = [])
    {
        return password_hash($password, $algo, $options);
    }
    
    public static function pbkdf2($data, $salt, $raw = false, $algo = 'md5', int $count = 1000, int $length = 0)
    {
        return hash_pbkdf2($algo, $data, $salt, $count, $length, $raw);
    }
    
    public static function check($password, $hash)
    {
        return password_verify($password, $hash);
    }

    public static function equals($str1, $str2)
    {
        return hash_equals($str1, $str2);
    }
}