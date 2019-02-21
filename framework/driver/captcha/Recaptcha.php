<?php
namespace framework\driver\captcha;

use framework\core\http\Client;
use framework\core\http\Request;

/*
 * <script src='$this->script' async defer></script><div class='g-recaptcha' data-sitekey='$this->acckey'></div>
 */
class Recaptcha
{
    // 访问key
    protected $acckey;
    // 加密key
    protected $seckey;
    // 脚本地址
    protected $script = 'https://www.google.com/recaptcha/api.js';
	// 服务端点
    protected static $endpoint = 'https://www.google.com/recaptcha/api/siteverify';
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
    }
    
    /*
     * 获取脚本地址
     */
    public function script()
    {
        return $this->script;
    }
    
    /*
     * 获取访问key
     */
    public function sitekey()
    {
        return $this->acckey;
    }

    /*
     * 验证
     */
    public function verify($value = null)
    {
        $client = Client::post(self::$endpoint)->form([
            'secret'   => $this->seckey,
            'response' => $value ?? Request::post('g-recaptcha-response'),
            'remoteip' => Request::ip(true)
        ]);
        $result = $client->response()->json();
        if (isset($result['success']) && $result['success'] === true) {
            return true;
        }
        return error($result['error-codes'] ?? $client->error);
    }
}
