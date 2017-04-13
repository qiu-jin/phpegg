<?php
namespace Plugin\Oauth;

class Weibo
{
    static private $app_id = '';
    static private $app_secret = '';
    static private $redirect_uri = '';
    
    public function __construct($config)
    {
        
    }
    
    static public function request()
    {
        $param = array(
                'client_id'     => self::$app_id,
                'redirect_uri'  => self::$redirect_uri,
                'response_type' => 'code',
        );
        return 'https://api.weibo.com/oauth2/authorize?'.http_build_query($param);
    }

    static public function callback($ret)
    {
        if ($ret['code']) {
            $param = array(
                    'client_id'     => self::$app_id,
                    'client_secret' => self::$app_secret,
                    'code'          => $ret['code'],
                    'redirect_uri'  => self::$redirect_uri
            );
            $token_data = \Util\Http::request_json('https://api.weibo.com/oauth2/access_token', http_build_query($param));
            if ($token_data['access_token'] && $token_data['uid']) {
                $user_data = \Util\Http::request_json(
                    'https://api.weibo.com/2/users/show.json?access_token='.$token_data['access_token'].'&uid='.$token_data['uid']
                );
                if ($user_data['id']) {
                    return array(
                        'access_token'  => $token_data['access_token'],
                        'openid'        => $token_data['uid'],
                        'username'      => $user_data['screen_name'],
                        'avatar'        => $user_data['avatar_large']
                    );
                }
            }
        }
        return false;
    }
}
