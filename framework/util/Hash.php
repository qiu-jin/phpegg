<?php
namespace framework\util;

class Hash
{   
    /*
     * 随机数hex
     */
    public static function random(int $length = 16, $raw = false)
    {
        $bytes = random_bytes($length);
        return $raw ? $bytes : bin2hex($bytes);
    }
    
    /*
     * 密码hash
     */
    public static function password($password, array $options = [])
    {
        return password_hash($password, $options['algo'] ?? PASSWORD_DEFAULT, $options);
    }
    
    /*
     * pbkdf2算法密码hash
     */
    public static function pbkdf2($password, array $options = [])
    {
        return hash_pbkdf2(
            $options['algo'] ?? 'md5',
            $password,
            $options['salt'] ?? self::random(),
            $options['count'] ?? 1000,
            $options['length'] ?? 0,
            !empty('raw')
        );
    }
    
    /*
     * 验证密码hash
     */
    public static function check($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /*
     * 验证两个hash是否相同
     */
    public static function equals($str1, $str2)
    {
        return hash_equals($str1, $str2);
    }
}