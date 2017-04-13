<?php
namespace Plugin\Oauth;
 
class Weixin
{
    static private $app_id = '';
    static private $app_secret = '';
    static private $redirect_uri = '';

    static public function request()
    {
        $param = array(
                'appid'         => $this->app_id,
                'redirect_uri'  => self::$redirect_uri,
                'response_type' => 'code',
                'scope'         => 'snsapi_login'
        );
        return 'https://open.weixin.qq.com/connect/qrconnect?'.http_build_query($param).'#wechat_redirect';
    }
    
    static public function callback($ret)
    {
        if ($ret['code']) {
            $param = array(
                    'appid'      => $this->app_id,
                    'secret'     => $this->app_secret,
                    'code'       => $ret['code'],
                    'grant_type' => 'authorization_code'
            );
            $token_data = \Util\Http::request_json('https://api.weixin.qq.com/sns/oauth2/access_token?'.http_build_query($param));
            if ($token_data['access_token'] && $token_data['unionid']) {
                $user_data = \Util\Http::request_json(
                    'https://api.weixin.qq.com/sns/userinfo?access_token='.$token_data['access_token'].'&openid='.$token_data['openid']
                );
                if ($user_data['openid']) {
                    return array(
                        'access_token'  => $token_data['access_token'],
                        'unionid'       => $token_data['unionid'],
                        'openid'        => $token_data['openid'],
                        'username'      => $user_data['nickname'],
                        'avatar'        => $user_data['headimgurl'],
                        'info'          => $user_info
                    ); 
                }
            }
        }
        return false;
    }
}