<?php
namespace framework\util;

class Hash
{
    public static function salt(int $length = 8, $raw = false)
    {
        $salt = random_bytes($length);
        return $raw ? $salt : bin2hex($salt);
    }
    
    public static function hmac($data, $salt, $raw = false, $algo = 'md5', int $count = 3)
    {
        do {
            $data = hash_hmac($algo, $data, $salt, true);
        } while ($count-- > 0);
        return $raw ? $data : bin2hex($data);
    }
    
    public static function pbkdf2($data, $salt, $raw = false, $algo = 'md5', int $count = 1000, int $length = 0)
    {
        return hash_pbkdf2($algo, $data, $salt, $count, $length, $raw);
    }

    public static function equals($data1, $data2)
    {
        return hash_equals($data1, $data2);
    }
    
    public static function password($password, $algo = PASSWORD_DEFAULT, array $options = [])
    {
        return password_hash($password, $algo, $options);
    }

    public static function verify($password, $hash)
    {
        return password_verify($password, $hash);
    }
}