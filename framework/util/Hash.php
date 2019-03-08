<?php
namespace framework\util;

defined('app\env\DEFAULT_HASH_ALGO') || define('app\env\DEFAULT_HASH_ALGO', 'md5');

class Hash
{
    /*
     * hmac
     */
    public static function make($data, $algo = \app\env\DEFAULT_HASH_ALGO, $raw = false)
    {
        return hash($algo, $data, $raw);
    }
    
    /*
     * hmac
     */
    public static function hmac($data, $salt, $algo = \app\env\DEFAULT_HASH_ALGO, $raw = false)
    {
        return hash_hmac($algo, $data, $salt, $raw);
    }

    /*
     * pbkdf2算法hash
     */
    public static function pbkdf2($data, $salt, array $options = null)
    {
        return hash_pbkdf2(
            $options['algo'] ?? \app\env\DEFAULT_HASH_ALGO,
            $data, $salt,
            $options['iterations'] ?? 1000,
            $options['length'] ?? 0,
            $options['raw_output'] ?? false
        );
    }
    
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
     * 验证两个hash是否相同
     */
    public static function equals($str1, $str2)
    {
        return hash_equals($str1, $str2);
    }
	
    /*
     * 验证密码hash
     */
    public static function verify($password, $hash)
    {
        return password_verify($password, $hash);
    }
    
    /*
     * 是否需要重新密码hash
     */
    public static function needsRehash($hash, $algo, array $options = [])
    {
        return password_needs_rehash($hash, $algo, $options);
    }
    
    /*
     * 获取密码hash信息
     */
    public static function getInfo($hash)
    {
        return password_get_info($hash);
    }
}